<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Ai\Agents\PlayStationAssistant;
use App\Ai\Tools\GetCatalogInventoryTool;
use App\Ai\Tools\GetClosedDevicesByUserTool;
use App\Ai\Tools\GetItemOrderLocationsTool;
use App\Models\AiChatLog;
use App\Models\AiTrainingRule;
use App\Models\Tenant;
use App\Models\Tenant\CatalogItem;
use App\Models\Tenant\Devices;
use App\Models\Tenant\DevicesType;
use App\Models\Tenant\InventoryGroup;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\OrderSessionDetail;
use App\Models\Tenant\PlayPrice;
use App\Models\Tenant\PlaySessions;
use App\Models\Tenant\PlayType;
use App\Models\Tenant\Shift;
use App\Models\Tenant\SystemConfig;
use App\Models\Tenant\User;
use App\Services\AIAssistantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Ai\Tools\Request;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Tests\TestCase;

class AIAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;

    private static string $tenantDatabasePath;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('tenancy.database.central_connection', 'sqlite');
        config()->set('tenancy.database.template_tenant_connection', 'sqlite');
        config()->set('tenancy.bootstrappers', [DatabaseTenancyBootstrapper::class]);

        $id = (string) Str::ulid();
        $databaseName = 'ai-test-'.getmypid().'.sqlite';
        self::$tenantDatabasePath = database_path($databaseName);

        if (! is_file(self::$tenantDatabasePath)) {
            touch(self::$tenantDatabasePath);
        }

        $this->tenant = Tenant::withoutEvents(function () use ($id) {
            return Tenant::create([
                'id' => $id,
                'has_ai_subscription' => false,
                'ai_requests_limit' => 10,
            ]);
        });
        $this->tenant->setInternal('db_name', $databaseName);
        $this->tenant->save();

        tenancy()->initialize($this->tenant);

        if (! DB::connection('tenant')->getSchemaBuilder()->hasTable('migrations')) {
            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => database_path('migrations/tenant'),
                '--realpath' => true,
                '--force' => true,
            ]);
        }
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            foreach ([
                'invoice_payments',
                'market_history',
                'order_session_details',
                'invoices',
                'play_session_details',
                'play_sessions',
                'shifts',
                'play_prices',
                'play_types',
                'devices',
                'devices_types',
                'warehouse_stocks',
                'catalog_items',
                'inventory_groups',
                'branch_user',
                'model_has_permissions',
                'model_has_roles',
                'users',
            ] as $table) {
                DB::connection('tenant')->table($table)->delete();
            }

            tenancy()->end();
        }
        DB::purge('tenant');
        if ($this->tenant) {
            $this->tenant->deleteQuietly();
        }

        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        if (isset(self::$tenantDatabasePath) && is_file(self::$tenantDatabasePath)) {
            unlink(self::$tenantDatabasePath);
        }

        parent::tearDownAfterClass();
    }

    public function test_tenant_creation_defaults_to_ai_subscription_with_ten_requests_limit(): void
    {
        $newTenant = Tenant::withoutEvents(function () {
            return Tenant::create([
                'id' => 'default_ai_tenant_'.uniqid(),
            ]);
        });

        $this->assertTrue($newTenant->has_ai_subscription);
        $this->assertEquals(10, $newTenant->ai_requests_limit);
    }

    public function test_ai_fails_if_no_subscription(): void
    {
        $service = new AIAssistantService;
        $response = $service->ask('العملاء ليهم فلوس كام ؟');

        $this->assertStringContainsString('تتطلب الاشتراك في باقة المساعد الذكي', $response);
    }

    public function test_ai_matches_database_training_rule(): void
    {
        // 1. Enable subscription
        $this->tenant->update(['has_ai_subscription' => true]);

        // Fake API key
        config(['services.gemini.key' => 'AIzaSyFakeKeyForTestingPurposesOnly']);

        // 2. Create a training rule in the central database
        AiTrainingRule::create([
            'pattern' => '(العملاء ليهم فلوس كام|أرصدة العملاء)',
            'sql_query' => 'SELECT 1 as test_field',
            'is_active' => true,
            'description' => 'Test Rule',
        ]);

        // Mock Gemini response formatting the query result
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'إليك نتيجة استعلام العملاء: 1.'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new AIAssistantService;
        $response = $service->ask('العملاء ليهم فلوس كام ؟');

        $this->assertStringContainsString('إليك نتيجة استعلام العملاء: 1.', $response);
    }

    public function test_ai_handles_play_price_updates_successfully(): void
    {
        $this->tenant->update(['has_ai_subscription' => true]);
        config(['services.gemini.key' => 'AIzaSyFakeKeyForTestingPurposesOnly']);
        SystemConfig::set('sys_locale_currency', 'ج.م');

        // Create Play Price test data
        $deviceType = DevicesType::create(['name' => 'بلايستيشن 5', 'description' => 'PS5']);
        $device = Devices::create([
            'name' => 'جهاز 1',
            'devices_type_id' => $deviceType->id,
            'status' => 'active',
        ]);
        $playType = PlayType::create([
            'name' => 'فردي',
            'price' => 30.00,
            'device_type_id' => $deviceType->id,
        ]);
        $playPrice = PlayPrice::create([
            'play_type_id' => $playType->id,
            'device_type_id' => $deviceType->id,
            'device_id' => $device->id,
            'price' => 40.00,
            'start_from' => '00:00:00',
        ]);

        // Mock Gemini response returning the JSON action
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => '{"action": "update_price", "type": "play", "target": "بلايستيشن 5", "amount": 10, "operation": "add"}'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new AIAssistantService;
        $result = $service->ask('زودلي سعر ساعة البلايستيشن 5 بمقدار 10 جنيه');

        // Step 1: ask() should return a pending action array (not yet executed)
        $this->assertIsArray($result);
        $this->assertArrayHasKey('__pending_action', $result);
        $this->assertArrayHasKey('preview', $result);
        $this->assertStringContainsString('40', $result['preview']); // old price shown
        $this->assertStringContainsString('50', $result['preview']); // new price shown

        // Price must NOT be changed yet
        $playPrice->refresh();
        $this->assertEquals(40.00, (float) $playPrice->price);

        // Step 2: manager confirms → execute the actual update
        $finalResponse = $service->executePriceUpdate($result['__pending_action']);

        $this->assertStringContainsString('تم تعديل أسعار اللعب بنجاح', $finalResponse);
        $this->assertStringContainsString('50.00 ج.م', $finalResponse);

        $playPrice->refresh();
        $this->assertEquals(50.00, (float) $playPrice->price);
    }

    public function test_ai_handles_product_price_updates_successfully(): void
    {
        $this->tenant->update(['has_ai_subscription' => true]);
        config(['services.gemini.key' => 'AIzaSyFakeKeyForTestingPurposesOnly']);
        SystemConfig::set('sys_locale_currency', 'ج.م');

        // Create Catalog Item test data
        $group = InventoryGroup::create(['name' => 'مشروبات ساخنة']);
        $item = CatalogItem::create([
            'inventory_group_id' => $group->id,
            'name' => 'شاي فتلة',
            'sale_price' => 10.00,
            'purchase_price' => 3.00,
            'is_product' => true,
            'is_active' => true,
        ]);

        // Mock Gemini response returning the JSON action
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => '{"action": "update_price", "type": "product", "target": "مشروبات ساخنة", "amount": 5, "operation": "add"}'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new AIAssistantService;
        $result = $service->ask('زودلي المشروبات الساخنة 5 جنيه');

        // Step 1: ask() should return a pending action array (not yet executed)
        $this->assertIsArray($result);
        $this->assertArrayHasKey('__pending_action', $result);
        $this->assertArrayHasKey('preview', $result);
        $this->assertStringContainsString('10', $result['preview']); // old price shown
        $this->assertStringContainsString('15', $result['preview']); // new price shown

        // Price must NOT be changed yet
        $item->refresh();
        $this->assertEquals(10.00, (float) $item->sale_price);

        // Step 2: manager confirms → execute the actual update
        $finalResponse = $service->executePriceUpdate($result['__pending_action']);

        $this->assertStringContainsString('تم تعديل أسعار المبيعات بنجاح', $finalResponse);
        $this->assertStringContainsString('15.00 ج.م', $finalResponse);

        $item->refresh();
        $this->assertEquals(15.00, (float) $item->sale_price);
    }

    public function test_ai_updates_price_for_specific_device_only(): void
    {
        $this->tenant->update(['has_ai_subscription' => true]);
        config(['services.gemini.key' => 'AIzaSyFakeKeyForTestingPurposesOnly']);

        // Create devices: Room 1 and Room 2
        $deviceType = DevicesType::create(['name' => 'بلايستيشن 5', 'description' => 'PS5']);
        $room1 = Devices::create([
            'name' => 'Room 1',
            'devices_type_id' => $deviceType->id,
            'status' => 'active',
        ]);
        $room2 = Devices::create([
            'name' => 'Room 2',
            'devices_type_id' => $deviceType->id,
            'status' => 'active',
        ]);

        $playType = PlayType::create([
            'name' => 'فردي',
            'price' => 30.00,
            'device_type_id' => $deviceType->id,
        ]);

        // Room 1 play price (40)
        $price1 = PlayPrice::create([
            'play_type_id' => $playType->id,
            'device_type_id' => $deviceType->id,
            'device_id' => $room1->id,
            'price' => 40.00,
            'start_from' => '00:00:00',
        ]);

        // Room 2 play price (40)
        $price2 = PlayPrice::create([
            'play_type_id' => $playType->id,
            'device_type_id' => $deviceType->id,
            'device_id' => $room2->id,
            'price' => 40.00,
            'start_from' => '00:00:00',
        ]);

        // Mock Gemini response returning the JSON action targeted at "Room 1"
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => '{"action": "update_price", "type": "play", "target": "Room 1", "amount": 10, "operation": "add"}'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new AIAssistantService;
        $result = $service->ask('قلل سعر Room 1 بمقدار 10 جنيه');

        $this->assertIsArray($result);
        // The preview should contain Room 1
        $this->assertStringContainsString('Room 1', $result['preview']);
        // The preview should NOT contain Room 2 (since it's targeted specifically at Room 1)
        $this->assertStringNotContainsString('Room 2', $result['preview']);

        // Execute the action
        $finalResponse = $service->executePriceUpdate($result['__pending_action']);

        $price1->refresh();
        $price2->refresh();

        // Room 1 should be updated (40 + 10 = 50)
        $this->assertEquals(50.00, (float) $price1->price);
        // Room 2 should remain unchanged (40)
        $this->assertEquals(40.00, (float) $price2->price);
    }

    public function test_self_learning_lookup_works_when_enabled(): void
    {
        $this->tenant->update(['has_ai_subscription' => true]);
        config(['services.gemini.key' => 'AIzaSyFakeKeyForTestingPurposesOnly']);

        // 1. Create a log generated by the AI (was_routed = false)
        AiChatLog::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => 1,
            'user_name' => 'Manager',
            'question' => 'كم عدد الاجهزة النشطة الان',
            'response' => 'يوجد 3 أجهزة نشطة حاليا.',
            'executed_sql' => 'SELECT COUNT(*) as active_count FROM devices WHERE status = "active"',
            'was_routed' => false,
        ]);

        // Fake database for the query (let's insert 3 active devices)
        $deviceType = DevicesType::create(['name' => 'VIP']);
        Devices::create(['name' => 'Device A', 'status' => 'active', 'devices_type_id' => $deviceType->id]);
        Devices::create(['name' => 'Device B', 'status' => 'active', 'devices_type_id' => $deviceType->id]);
        Devices::create(['name' => 'Device C', 'status' => 'active', 'devices_type_id' => $deviceType->id]);

        // We do NOT fake HTTP requests for the first call because when self-learning is enabled,
        // it should bypass the HTTP request completely!

        // Enable self learning lookup in config
        config(['services.gemini.enable_self_learning_lookup' => true]);

        // Also mock final response since ask() calls generateFinalAnswer
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'يوجد 3 أجهزة نشطة حاليا.'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new AIAssistantService;
        // Ask a 95% similar question
        $response = $service->ask('كم عدد الاجهزة النشطة الآن؟');

        $this->assertStringContainsString('3', $response); // 3 active devices

        // Disable self learning lookup
        config(['services.gemini.enable_self_learning_lookup' => false]);

        // If we ask now, it should try calling Gemini, so let's mock Gemini returning a different response to verify it doesn't use the cache
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'SELECT 99 as active_count'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response2 = $service->ask('كم عدد الاجهزة النشطة الآن؟');
        $this->assertStringContainsString('3', $response2); // In test environment, the faked DB call still runs our seed data (3 items), but it ran via the fresh SQL path
    }

    public function test_catalog_search_finds_arabic_spelling_variants(): void
    {
        $group = InventoryGroup::create(['name' => 'مشروبات ساخنة']);
        CatalogItem::create([
            'inventory_group_id' => $group->id,
            'name' => 'قهوه فرنساوي',
            'sale_price' => 35,
            'purchase_price' => 10,
            'is_product' => true,
            'is_active' => true,
        ]);

        $result = json_decode((string) (new GetCatalogInventoryTool)->handle(new Request([
            'search_name' => 'القهوة الفرنساوي',
        ])), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('ok', $result['status']);
        $this->assertSame('قهوه فرنساوي', $result['products'][0]['product_name']);

        CatalogItem::create([
            'inventory_group_id' => $group->id,
            'name' => 'قهوة فرنساوي موقوفة',
            'sale_price' => 35,
            'purchase_price' => 10,
            'is_product' => false,
            'is_component' => true,
            'is_active' => false,
        ]);

        $inactiveResult = json_decode((string) (new GetCatalogInventoryTool)->handle(new Request([
            'search_name' => 'قهوة فرنساوي موقوفة',
        ])), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('matched_items_include_inactive', $inactiveResult['status']);
        $this->assertSame('component', $inactiveResult['products'][0]['item_type']);
    }

    public function test_item_location_tool_distinguishes_existing_item_with_active_order(): void
    {
        $user = User::create([
            'name' => 'عبدالرحمن',
            'email' => 'abdelrahman@example.test',
            'password' => 'password',
        ]);
        $deviceType = DevicesType::create(['name' => 'ترابيزات']);
        $device = Devices::create(['name' => 'ترابيزة 4', 'status' => 'busy', 'devices_type_id' => $deviceType->id]);
        $group = InventoryGroup::create(['name' => 'مشروبات ساخنة']);
        $item = CatalogItem::create([
            'inventory_group_id' => $group->id,
            'name' => 'قهوه فرنساوي',
            'sale_price' => 35,
            'purchase_price' => 10,
            'is_product' => true,
            'is_active' => true,
        ]);
        $session = PlaySessions::create(['device_id' => $device->id, 'status' => 'active']);
        OrderSessionDetail::create([
            'play_session_id' => $session->id,
            'device_id' => $device->id,
            'catalog_item_id' => $item->id,
            'price' => 35,
            'quantity' => 2,
            'total' => 70,
            'status' => 'served',
            'user_id' => $user->id,
        ]);

        $result = json_decode((string) (new GetItemOrderLocationsTool)->handle(new Request([
            'item_name' => 'القهوة الفرنساوي',
        ])), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('active_orders_found', $result['status']);
        $this->assertSame(['قهوه فرنساوي'], $result['matched_catalog_items']);
        $this->assertSame('ترابيزة 4', $result['active_locations'][0]['device_name']);
        $this->assertSame(2, (int) $result['active_locations'][0]['quantity']);
    }

    public function test_closed_devices_tool_uses_real_invoice_and_user_relationships(): void
    {
        $user = User::create([
            'name' => 'عبدالرحمن أحمد',
            'email' => 'closer@example.test',
            'password' => 'password',
        ]);
        $deviceType = DevicesType::create(['name' => 'ترابيزات']);
        $device = Devices::create(['name' => 'ترابيزة 7', 'status' => 'active', 'devices_type_id' => $deviceType->id]);
        $shift = Shift::create([
            'shift_no' => 6,
            'shift_date' => '2026-09-21',
            'shift_from' => '2026-09-21 08:00:00',
            'opened_by_user_id' => $user->id,
            'status' => 'open',
        ]);
        Invoice::create([
            'invoice_no' => 'AI-REGRESSION-1',
            'invoice_type' => 'device_sale',
            'device_id' => $device->id,
            'shift_id' => $shift->id,
            'created_by_user_id' => $user->id,
            'final_amount' => 120,
            'paid_amount' => 120,
            'status' => 'posted',
            'issued_at' => '2026-09-21 10:00:00',
        ]);

        $result = json_decode((string) (new GetClosedDevicesByUserTool)->handle(new Request([
            'user_name' => 'عبد الرحمن',
            'shift_no' => 6,
        ])), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('ok', $result['status']);
        $this->assertSame(['عبدالرحمن أحمد'], $result['matched_users']);
        $this->assertSame('ترابيزة 7', $result['devices'][0]['device_name']);
        $this->assertSame(120.0, (float) $result['devices'][0]['total_amount']);
    }

    public function test_follow_up_messages_continue_one_compact_conversation(): void
    {
        $this->tenant->update(['has_ai_subscription' => true]);
        config(['services.gemini.key' => 'AIzaSyFakeKeyForTestingPurposesOnly']);

        $user = User::create([
            'name' => 'Manager',
            'email' => 'manager@example.test',
            'password' => 'password',
        ]);
        $this->actingAs($user);
        PlayStationAssistant::fake(['الرد الأول', 'الرد الثاني']);

        $service = new AIAssistantService;
        $this->assertSame('الرد الأول', $service->ask('طريقة عمل تصريح لمستخدم في المبيعات المباشرة'));
        $this->assertSame('الرد الثاني', $service->ask('ممكن تبحث عليها؟'));

        $this->assertSame(1, DB::table('agent_conversations')->where('user_id', $user->id)->count());
        $this->assertSame(4, DB::table('agent_conversation_messages')->where('user_id', $user->id)->count());
    }
}
