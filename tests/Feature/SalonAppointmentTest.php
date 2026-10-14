<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Tenant\PostExpenseAction;
use App\Actions\Tenant\RecordAppointmentCommissionAction;
use App\Actions\Tenant\RecordStaffCommissionPayoutAction;
use App\Actions\Tenant\SaveAppointmentAction;
use App\Actions\Tenant\SaveSalonServiceAction;
use App\Filament\Pages\AppointmentOutcomesReport;
use App\Filament\Pages\ServicePerformanceReport;
use App\Filament\Pages\StaffProductivityReport;
use App\Models\Tenant;
use App\Models\Tenant\Appointment;
use App\Models\Tenant\Branch;
use App\Models\Tenant\CashBox;
use App\Models\Tenant\CatalogItem;
use App\Models\Tenant\ExpenseHistory;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\ProductComponent;
use App\Models\Tenant\SalonService;
use App\Models\Tenant\StaffCommission;
use App\Models\Tenant\StaffCommissionPayout;
use App\Models\Tenant\StaffMember;
use App\Models\Tenant\User;
use App\Scopes\BranchScope;
use App\Support\Tenant\TenantClock;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Tests\TestCase;
use WallaceMartinss\FilamentEvolution\Enums\StatusConnectionEnum;
use WallaceMartinss\FilamentEvolution\Services\EvolutionClient;

class SalonAppointmentTest extends TestCase
{
    use RefreshDatabase;

    private ?string $databaseFile = null;

    private ?Tenant $tenant = null;

    private Branch $branch;

    private User $user;

    private StaffMember $staffMember;

    private SalonService $haircut;

    private SalonService $beardTrim;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('tenancy.database.central_connection', 'sqlite');
        config()->set('tenancy.database.template_tenant_connection', 'sqlite');
        config()->set('tenancy.bootstrappers', [DatabaseTenancyBootstrapper::class]);

        BranchScope::resetCache();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->initializeTenantDatabase();
        $this->setUpTenantSchema();

        $this->branch = Branch::query()->create([
            'name' => 'الفرع الرئيسي',
            'code' => 'MAIN',
            'is_active' => true,
        ]);

        $this->user = User::query()->create([
            'name' => 'مدير الصالون',
            'email' => 'salon-manager@example.test',
            'password' => 'password',
        ]);
        $this->user->branches()->attach($this->branch);
        $this->actingAs($this->user);
        session(['active_branch_id' => $this->branch->getKey()]);

        $this->staffMember = StaffMember::query()->create([
            'branch_id' => $this->branch->getKey(),
            'name' => 'أحمد',
            'job_title' => 'حلاق',
            'is_active' => true,
        ]);

        $this->haircut = $this->createService('قص شعر', 45, 250);
        $this->beardTrim = $this->createService('تهذيب اللحية', 15, 100);
    }

    protected function tearDown(): void
    {
        tenancy()->end();
        DB::purge('tenant');
        BranchScope::resetCache();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if ($this->databaseFile && is_file($this->databaseFile)) {
            unlink($this->databaseFile);
        }

        parent::tearDown();
    }

    public function test_it_saves_selected_services_and_derives_the_appointment_duration_and_price(): void
    {
        $appointment = app(SaveAppointmentAction::class)->execute($this->appointmentData());

        $this->assertSame('10:00', $appointment->start_at->format('H:i'));
        $this->assertSame('11:00', $appointment->end_at->format('H:i'));
        $this->assertSame(60, $appointment->duration_minutes);
        $this->assertSame('350.00', $appointment->total_price);
        $this->assertEqualsCanonicalizing(
            ['قص شعر', 'تهذيب اللحية'],
            $appointment->items->pluck('service_name')->all(),
        );
    }

    public function test_it_rejects_overlapping_appointments_for_the_same_staff_member(): void
    {
        app(SaveAppointmentAction::class)->execute($this->appointmentData());

        try {
            app(SaveAppointmentAction::class)->execute($this->appointmentData(startAt: '2026-10-20 10:30:00'));
            $this->fail('An overlapping appointment should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('start_at', $exception->errors());
        }

        $this->assertSame(1, Appointment::query()->count());
    }

    public function test_it_rejects_appointments_outside_a_staff_members_weekly_schedule(): void
    {
        $startAt = now()->addDays(5)->setTime(10, 0);
        $this->staffMember->forceFill([
            'weekly_schedule' => [[
                'day_of_week' => $startAt->dayOfWeek,
                'opens_at' => '09:00',
                'closes_at' => '17:00',
            ]],
        ])->save();

        app(SaveAppointmentAction::class)->execute($this->appointmentData(
            startAt: $startAt->format('Y-m-d H:i:s'),
            serviceIds: [(int) $this->haircut->getKey()],
        ));

        try {
            app(SaveAppointmentAction::class)->execute($this->appointmentData(
                startAt: $startAt->copy()->setTime(18, 0)->format('Y-m-d H:i:s'),
                serviceIds: [(int) $this->haircut->getKey()],
            ));
            $this->fail('An appointment outside the staff schedule should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('start_at', $exception->errors());
        }
    }

    public function test_it_saves_service_material_usage_as_inventory_components(): void
    {
        $hairColor = CatalogItem::query()->create([
            'name' => 'صبغة شعر',
            'purchase_price' => 120,
            'sale_price' => 0,
            'is_product' => false,
            'is_component' => true,
            'is_active' => true,
        ]);
        $shampoo = CatalogItem::query()->create([
            'name' => 'شامبو',
            'purchase_price' => 80,
            'sale_price' => 0,
            'is_product' => false,
            'is_component' => true,
            'is_active' => true,
        ]);
        $serviceData = [
            'name' => 'صبغة كاملة',
            'category' => 'hair',
            'description' => 'صبغة مع غسل الشعر.',
            'duration_minutes' => 90,
            'price' => 600,
            'is_active' => true,
            'component_usages' => [[
                'component_id' => $hairColor->getKey(),
                'quantity' => 0.15,
                'notes' => 'حسب طول الشعر',
            ]],
        ];

        $service = app(SaveSalonServiceAction::class)->execute($serviceData);
        $component = ProductComponent::query()
            ->where('product_id', $service->catalog_item_id)
            ->sole();

        $this->assertTrue($service->catalogItem->is_product);
        $this->assertSame('0.150', $component->quantity);

        $serviceData['component_usages'] = [[
            'component_id' => $shampoo->getKey(),
            'quantity' => 0.05,
            'notes' => null,
        ]];
        $service = app(SaveSalonServiceAction::class)->execute($serviceData, $service);
        $component = ProductComponent::query()
            ->where('product_id', $service->catalog_item_id)
            ->sole();

        $this->assertSame(1, ProductComponent::query()->where('product_id', $service->catalog_item_id)->count());
        $this->assertSame((int) $shampoo->getKey(), (int) $component->component_id);
        $this->assertTrue($service->catalogItem->is_product);
    }

    public function test_it_creates_commission_only_for_a_fully_paid_appointment_invoice_and_keeps_the_rate_snapshot(): void
    {
        $this->staffMember->forceFill(['commission_rate' => 10])->save();
        $appointment = app(SaveAppointmentAction::class)->execute($this->appointmentData());
        $invoice = $this->createInvoice(finalAmount: 350, paidAmount: 300, remainAmount: 50);
        $action = app(RecordAppointmentCommissionAction::class);

        $this->assertNull($action->execute($appointment, $invoice));
        $this->assertSame(0, StaffCommission::query()->count());

        $invoice->forceFill([
            'final_amount' => 300,
            'discount_amount' => 50,
            'paid_amount' => 300,
            'remain_amount' => 0,
        ])->save();

        $commission = $action->execute($appointment, $invoice);

        $this->assertInstanceOf(StaffCommission::class, $commission);
        $this->assertSame('300.00', $commission->commission_base);
        $this->assertSame('10.00', $commission->commission_rate);
        $this->assertSame('30.00', $commission->amount);

        $this->staffMember->forceFill(['commission_rate' => 20])->save();

        $duplicate = $action->execute($appointment, $invoice);
        $this->assertSame($commission->getKey(), $duplicate?->getKey());
        $this->assertSame('10.00', $duplicate?->commission_rate);
        $this->assertSame(1, StaffCommission::query()->count());
    }

    public function test_it_records_partial_commission_payouts_and_prevents_overpayment(): void
    {
        $this->staffMember->forceFill(['commission_rate' => 10])->save();
        $appointment = app(SaveAppointmentAction::class)->execute($this->appointmentData());
        $invoice = $this->createInvoice(finalAmount: 350, paidAmount: 350, remainAmount: 0);
        $commission = app(RecordAppointmentCommissionAction::class)->execute($appointment, $invoice);
        $this->assertInstanceOf(StaffCommission::class, $commission);

        $this->createPayoutDependencies();
        Permission::create(['name' => 'Update:StaffMember', 'guard_name' => 'web']);
        $this->user->givePermissionTo('Update:StaffMember');

        $postExpenseAction = \Mockery::mock(PostExpenseAction::class);
        $postExpenseAction
            ->shouldReceive('execute')
            ->twice()
            ->andReturnUsing(function (array $payload): ExpenseHistory {
                return ExpenseHistory::query()->create([
                    'branch_id' => $this->branch->getKey(),
                    'shift_id' => 1,
                    'cash_box_id' => $payload['cash_box_id'],
                    'direction' => 'out',
                    'category' => 'expense',
                    'amount' => $payload['amount'],
                    'affects_profit' => true,
                    'description' => $payload['description'],
                    'occurred_at' => now(),
                ]);
            });
        $this->app->instance(PostExpenseAction::class, $postExpenseAction);

        $cashBox = CashBox::query()->create([
            'branch_id' => $this->branch->getKey(),
            'name' => 'الخزينة الرئيسية',
            'code' => '1',
            'is_active' => true,
            'current_balance' => 1000,
        ]);

        $action = app(RecordStaffCommissionPayoutAction::class);
        $firstPayout = $action->execute($commission, (int) $cashBox->getKey(), 12);

        $this->assertInstanceOf(StaffCommissionPayout::class, $firstPayout);
        $this->assertSame('12.00', $firstPayout->amount);
        $this->assertSame(23.0, $commission->fresh()->remaining_amount);

        try {
            $action->execute($commission, (int) $cashBox->getKey(), 24);
            $this->fail('A payout larger than the remaining commission should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('amount', $exception->errors());
        }

        $secondPayout = $action->execute($commission, (int) $cashBox->getKey(), 23);

        $this->assertSame('23.00', $secondPayout->amount);
        $this->assertSame(0.0, $commission->fresh()->remaining_amount);
        $this->assertSame(2, StaffCommissionPayout::query()->count());
    }

    public function test_it_sends_a_whatsapp_reminder_once_for_a_confirmed_appointment(): void
    {
        Schema::create('whatsapp_instances', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('status');
            $table->softDeletes();
            $table->timestamps();
        });
        DB::table('whatsapp_instances')->insert([
            'id' => (string) Str::uuid(),
            'name' => 'salon-main',
            'status' => StatusConnectionEnum::OPEN->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('system_configs')->insert([
            ['key' => 'sys_whatsapp_enabled', 'value' => '1', 'group' => 'system', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'sys_booking_alert_time', 'value' => '30', 'group' => 'system', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'sys_country_code', 'value' => '2', 'group' => 'system', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'sys_shop_name', 'value' => 'صالون الجمال', 'group' => 'system', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $startAt = app(TenantClock::class)->nowUtc()->addMinutes(10);
        $appointment = Appointment::query()->create([
            'branch_id' => $this->branch->getKey(),
            'staff_member_id' => $this->staffMember->getKey(),
            'contact_name' => 'عميلة الصالون',
            'contact_phone' => '01012345678',
            'start_at' => $startAt,
            'end_at' => $startAt->copy()->addMinutes(45),
            'duration_minutes' => 45,
            'total_price' => 250,
            'status' => 'confirmed',
        ]);
        $appointment->items()->create([
            'salon_service_id' => $this->haircut->getKey(),
            'service_name' => $this->haircut->name,
            'duration_minutes' => $this->haircut->duration_minutes,
            'unit_price' => $this->haircut->price,
        ]);
        $scheduledAppointment = Appointment::query()->create([
            'branch_id' => $this->branch->getKey(),
            'staff_member_id' => $this->staffMember->getKey(),
            'contact_name' => 'عميلة بموعد مجدول',
            'contact_phone' => '01012345678',
            'start_at' => $startAt,
            'end_at' => $startAt->copy()->addMinutes(45),
            'duration_minutes' => 45,
            'total_price' => 250,
            'status' => 'scheduled',
        ]);
        $unconfirmedAppointment = Appointment::query()->create([
            'branch_id' => $this->branch->getKey(),
            'staff_member_id' => $this->staffMember->getKey(),
            'contact_name' => 'طلب غير مؤكد',
            'contact_phone' => '01012345678',
            'start_at' => $startAt,
            'end_at' => $startAt->copy()->addMinutes(45),
            'duration_minutes' => 45,
            'total_price' => 250,
            'status' => 'requested',
        ]);

        $evolutionClient = \Mockery::mock(EvolutionClient::class);
        $evolutionClient
            ->shouldReceive('sendText')
            ->twice()
            ->with('salon-main', '201012345678', \Mockery::type('string'))
            ->andReturn([]);
        $this->app->instance(EvolutionClient::class, $evolutionClient);

        $this->artisan('tenants:send-appointment-reminders')->assertExitCode(0);
        $this->assertNotNull($appointment->fresh()->reminder_sent_at);
        $this->assertNotNull($scheduledAppointment->fresh()->reminder_sent_at);
        $this->assertNull($unconfirmedAppointment->fresh()->reminder_sent_at);
        $this->artisan('tenants:send-appointment-reminders')->assertExitCode(0);
    }

    public function test_service_performance_only_includes_completed_appointments_with_posted_invoices(): void
    {
        $issuedAt = app(TenantClock::class)->nowUtc();
        $invoice = Invoice::query()->create([
            'branch_id' => $this->branch->getKey(),
            'invoice_no' => 'SALON-REPORT-1',
            'invoice_type' => 'direct_sale',
            'status' => 'posted',
            'subtotal' => 250,
            'final_amount' => 250,
            'issued_at' => $issuedAt,
        ]);

        $completedAppointment = Appointment::query()->create([
            'branch_id' => $this->branch->getKey(),
            'staff_member_id' => $this->staffMember->getKey(),
            'invoice_id' => $invoice->getKey(),
            'start_at' => $issuedAt,
            'end_at' => $issuedAt->copy()->addMinutes(45),
            'duration_minutes' => 45,
            'total_price' => 250,
            'status' => 'completed',
        ]);
        $completedAppointment->items()->create([
            'salon_service_id' => $this->haircut->getKey(),
            'service_name' => $this->haircut->name,
            'duration_minutes' => 45,
            'unit_price' => 250,
        ]);

        $unpaidAppointment = Appointment::query()->create([
            'branch_id' => $this->branch->getKey(),
            'staff_member_id' => $this->staffMember->getKey(),
            'start_at' => $issuedAt,
            'end_at' => $issuedAt->copy()->addMinutes(15),
            'duration_minutes' => 15,
            'total_price' => 100,
            'status' => 'completed',
        ]);
        $unpaidAppointment->items()->create([
            'salon_service_id' => $this->beardTrim->getKey(),
            'service_name' => $this->beardTrim->name,
            'duration_minutes' => 15,
            'unit_price' => 100,
        ]);

        $cancelledInvoice = $this->createInvoice(100, 100, 0);
        $cancelledAppointment = Appointment::query()->create([
            'branch_id' => $this->branch->getKey(),
            'staff_member_id' => $this->staffMember->getKey(),
            'invoice_id' => $cancelledInvoice->getKey(),
            'start_at' => $issuedAt,
            'end_at' => $issuedAt->copy()->addMinutes(15),
            'duration_minutes' => 15,
            'total_price' => 100,
            'status' => 'cancelled',
        ]);
        $cancelledAppointment->items()->create([
            'salon_service_id' => $this->beardTrim->getKey(),
            'service_name' => $this->beardTrim->name,
            'duration_minutes' => 15,
            'unit_price' => 100,
        ]);

        $reportRows = app(ServicePerformanceReport::class)
            ->getServicePerformanceQuery($issuedAt->toDateString(), $issuedAt->toDateString())
            ->get();

        $this->assertCount(1, $reportRows);
        $this->assertSame($this->haircut->getKey(), $reportRows->first()->getKey());
        $this->assertSame(1, (int) $reportRows->first()->completed_services_count);
        $this->assertSame(250.0, (float) $reportRows->first()->recorded_service_value);
    }

    public function test_appointment_outcomes_report_separates_completion_and_posted_invoice_conversion(): void
    {
        $startAt = app(TenantClock::class)->nowLocal()->startOfDay()->addHours(10);
        $postedInvoice = $this->createInvoice(250, 250, 0);
        $draftInvoice = $this->createInvoice(100, 100, 0);
        $draftInvoice->forceFill(['status' => 'draft'])->save();

        $createAppointment = function (string $status, ?Invoice $invoice, int $hour) use ($startAt): Appointment {
            $appointmentStart = $startAt->copy()->setTime($hour, 0);

            return Appointment::query()->create([
                'branch_id' => $this->branch->getKey(),
                'staff_member_id' => $this->staffMember->getKey(),
                'invoice_id' => $invoice?->getKey(),
                'start_at' => $appointmentStart,
                'end_at' => $appointmentStart->copy()->addMinutes(45),
                'duration_minutes' => 45,
                'total_price' => 250,
                'status' => $status,
            ]);
        };

        $createAppointment('completed', $postedInvoice, 10);
        $createAppointment('completed', $draftInvoice, 11);
        $createAppointment('scheduled', null, 12);
        $createAppointment('cancelled', null, 13);
        $createAppointment('no_show', null, 14);

        $otherBranch = Branch::query()->create([
            'name' => 'فرع غير مسند',
            'code' => 'BR-REPORT',
            'is_active' => true,
        ]);
        $otherStaff = StaffMember::query()->create([
            'branch_id' => $otherBranch->getKey(),
            'name' => 'موظف فرع آخر',
            'is_active' => true,
        ]);
        Appointment::query()->create([
            'branch_id' => $otherBranch->getKey(),
            'staff_member_id' => $otherStaff->getKey(),
            'start_at' => $startAt->copy()->setTime(15, 0),
            'end_at' => $startAt->copy()->setTime(15, 45),
            'duration_minutes' => 45,
            'total_price' => 250,
            'status' => 'completed',
        ]);

        $report = app(AppointmentOutcomesReport::class);
        $report->appliedFrom = $startAt->toDateString();
        $report->appliedUntil = $startAt->toDateString();
        $results = $report->getReportData();

        $this->assertSame(5, $results['total']);
        $this->assertSame(2, $results['completed']);
        $this->assertSame(1, $results['collected']);
        $this->assertSame(1, $results['completed_without_invoice']);
        $this->assertSame(40.0, $results['completion_rate']);
        $this->assertSame(20.0, $results['booking_collection_rate']);
        $this->assertSame(50.0, $results['completion_collection_rate']);
        $this->assertEqualsCanonicalizing(
            ['completed', 'scheduled', 'cancelled', 'no_show'],
            $results['statuses']->pluck('status')->all(),
        );
    }

    public function test_staff_productivity_report_calculates_schedule_occupancy_and_handles_missing_schedules(): void
    {
        $startAt = app(TenantClock::class)->nowLocal()->startOfDay()->addHours(9);
        $this->staffMember->forceFill([
            'weekly_schedule' => [[
                'day_of_week' => $startAt->dayOfWeek,
                'opens_at' => '09:00',
                'closes_at' => '17:00',
            ]],
        ])->save();

        $collectedInvoice = $this->createInvoice(250, 250, 0);
        $makeAppointment = function (string $status, ?Invoice $invoice, int $minute, int $duration): Appointment {
            $appointmentStart = app(TenantClock::class)->nowLocal()->startOfDay()->addHours(9)->addMinutes($minute);

            return Appointment::query()->create([
                'branch_id' => $this->branch->getKey(),
                'staff_member_id' => $this->staffMember->getKey(),
                'invoice_id' => $invoice?->getKey(),
                'start_at' => $appointmentStart,
                'end_at' => $appointmentStart->copy()->addMinutes($duration),
                'duration_minutes' => $duration,
                'total_price' => 250,
                'status' => $status,
            ]);
        };

        $collectedAppointment = $makeAppointment('completed', $collectedInvoice, 0, 30);
        $makeAppointment('completed', null, 30, 15);
        $makeAppointment('confirmed', null, 45, 15);
        $makeAppointment('cancelled', null, 60, 60);
        $makeAppointment('no_show', null, 120, 60);
        $makeAppointment('requested', null, 180, 30);
        StaffCommission::query()->create([
            'branch_id' => $this->branch->getKey(),
            'staff_member_id' => $this->staffMember->getKey(),
            'appointment_id' => $collectedAppointment->getKey(),
            'invoice_id' => $collectedInvoice->getKey(),
            'commission_rate' => 10,
            'commission_base' => 250,
            'amount' => 25,
        ]);

        $unscheduledStaff = StaffMember::query()->create([
            'branch_id' => $this->branch->getKey(),
            'name' => 'موظف دون جدول',
            'is_active' => true,
        ]);

        $report = app(StaffProductivityReport::class);
        $report->appliedFrom = $startAt->toDateString();
        $report->appliedUntil = $startAt->toDateString();
        $rows = $report->getStaffProductivityRows()->keyBy('name');
        $staffRow = $rows->get($this->staffMember->name);
        $unscheduledRow = $rows->get($unscheduledStaff->name);

        $this->assertSame(6, $staffRow['appointments']);
        $this->assertSame(2, $staffRow['completed']);
        $this->assertSame(1, $staffRow['collected_appointments']);
        $this->assertSame(250.0, $staffRow['collected_revenue']);
        $this->assertSame(25.0, $staffRow['commission']);
        $this->assertSame(60, $staffRow['occupied_minutes']);
        $this->assertSame(480, $staffRow['available_minutes']);
        $this->assertSame(12.5, $staffRow['occupancy_rate']);
        $this->assertNull($unscheduledRow['available_minutes']);
        $this->assertNull($unscheduledRow['occupancy_rate']);
    }

    public function test_it_rejects_appointments_for_a_branch_the_user_is_not_assigned_to(): void
    {
        $otherBranch = Branch::query()->create([
            'name' => 'الفرع الثاني',
            'code' => 'BR-2',
            'is_active' => true,
        ]);
        $otherStaffMember = StaffMember::query()->create([
            'branch_id' => $otherBranch->getKey(),
            'name' => 'منى',
            'is_active' => true,
        ]);
        $otherService = $this->createService('صبغة', 90, 500, $otherBranch);

        $this->expectException(AuthorizationException::class);

        app(SaveAppointmentAction::class)->execute($this->appointmentData(
            branchId: (int) $otherBranch->getKey(),
            staffMemberId: (int) $otherStaffMember->getKey(),
            serviceIds: [(int) $otherService->getKey()],
        ));
    }

    public function test_it_rejects_changes_to_an_appointment_that_has_been_checked_out(): void
    {
        $appointment = app(SaveAppointmentAction::class)->execute($this->appointmentData());
        DB::table('invoices')->insert(['id' => 1]);
        $appointment->forceFill(['invoice_id' => 1])->save();

        $this->expectException(ValidationException::class);

        app(SaveAppointmentAction::class)->execute(
            $this->appointmentData(startAt: '2026-10-20 12:00:00'),
            $appointment,
        );
    }

    public function test_a_customer_can_request_and_cancel_a_tenant_scoped_public_appointment(): void
    {
        $this->get($this->tenantUrl('/bookings/create'))
            ->assertOk()
            ->assertSee('اطلب موعدك')
            ->assertSee('الوقت المفضل')
            ->assertSee('إرسال طلب الموعد');

        $response = $this->post($this->tenantUrl('/bookings'), [
            'branch_id' => $this->branch->getKey(),
            'staff_member_id' => $this->staffMember->getKey(),
            'service_ids' => [$this->haircut->getKey()],
            'start_at' => now()->addDays(2)->setTime(10, 0)->format('Y-m-d\TH:i'),
            'contact_name' => 'عميلة جديدة',
            'contact_phone' => '01012345678',
            'notes' => 'أفضل موعد صباحي.',
        ]);

        $response->assertRedirect();
        $confirmationUrl = (string) $response->headers->get('Location');
        $this->assertMatchesRegularExpression('~/bookings/confirmation/([a-f0-9]{64})$~', $confirmationUrl);
        preg_match('~/bookings/confirmation/([a-f0-9]{64})$~', $confirmationUrl, $matches);
        $token = $matches[1];

        $appointment = Appointment::query()->firstOrFail();
        $this->assertSame('requested', $appointment->status);
        $this->assertSame('عميلة جديدة', $appointment->contact_name);
        $this->assertSame('01012345678', $appointment->contact_phone);
        $this->assertNull($appointment->customer_id);
        $this->assertNotSame($token, $appointment->public_booking_token_hash);
        $this->assertSame('قص شعر', $appointment->items->sole()->service_name);

        $this->get($this->tenantUrl('/bookings/confirmation/'.$token))
            ->assertOk()
            ->assertSee('بانتظار تأكيد الصالون')
            ->assertSee('قص شعر');

        $this->post($this->tenantUrl('/bookings/confirmation/'.$token.'/cancel'))
            ->assertRedirect($this->tenantUrl('/bookings/confirmation/'.$token));

        $this->assertSame('cancelled', $appointment->fresh()->status);
    }

    public function test_public_booking_rejects_an_existing_staff_time_conflict(): void
    {
        $startAt = now()->addDays(3)->setTime(11, 0);
        app(SaveAppointmentAction::class)->execute($this->appointmentData(
            startAt: $startAt->format('Y-m-d H:i:s'),
            serviceIds: [(int) $this->haircut->getKey()],
        ));

        $this->from($this->tenantUrl('/bookings/create'))
            ->post($this->tenantUrl('/bookings'), [
                'branch_id' => $this->branch->getKey(),
                'staff_member_id' => $this->staffMember->getKey(),
                'service_ids' => [$this->haircut->getKey()],
                'start_at' => $startAt->copy()->timezone('Africa/Cairo')->format('Y-m-d\TH:i'),
                'contact_name' => 'عميل',
                'contact_phone' => '01012345678',
            ])
            ->assertRedirect($this->tenantUrl('/bookings/create'))
            ->assertSessionHasErrors('start_at');

        $this->assertSame(1, Appointment::query()->count());
    }

    /**
     * @return array<string, mixed>
     */
    private function appointmentData(
        string $startAt = '2026-10-20 10:00:00',
        ?int $branchId = null,
        ?int $staffMemberId = null,
        ?array $serviceIds = null,
    ): array {
        return [
            'branch_id' => $branchId ?? (int) $this->branch->getKey(),
            'staff_member_id' => $staffMemberId ?? (int) $this->staffMember->getKey(),
            'service_ids' => $serviceIds ?? [
                (int) $this->haircut->getKey(),
                (int) $this->beardTrim->getKey(),
            ],
            'start_at' => $startAt,
            'status' => 'scheduled',
        ];
    }

    private function createService(string $name, int $durationMinutes, float $price, ?Branch $branch = null): SalonService
    {
        return SalonService::query()->create([
            'branch_id' => $branch?->getKey() ?? $this->branch->getKey(),
            'name' => $name,
            'duration_minutes' => $durationMinutes,
            'price' => $price,
            'is_active' => true,
        ]);
    }

    private function initializeTenantDatabase(): void
    {
        $databaseName = 'salon-appointment-test-'.Str::uuid().'.sqlite';
        $this->databaseFile = database_path($databaseName);
        touch($this->databaseFile);

        $tenant = Tenant::withoutEvents(fn (): Tenant => Tenant::create([
            'id' => (string) Str::ulid(),
            'is_active' => true,
        ]));
        $tenant->setInternal('db_name', $databaseName);
        $tenant->save();
        $tenant->domains()->create(['domain' => 'salon-test.example.test']);
        $this->tenant = $tenant;

        tenancy()->initialize($tenant);
    }

    private function setUpTenantSchema(): void
    {
        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        Schema::create('branch_user', function (Blueprint $table): void {
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->timestamps();
        });

        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });

        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->nullable();
            $table->string('invoice_no')->nullable();
            $table->string('invoice_type')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('shift_id')->nullable();
            $table->foreignId('cash_box_id')->nullable();
            $table->foreignId('created_by_user_id')->nullable();
            $table->decimal('play_amount', 10, 2)->default(0);
            $table->decimal('order_amount', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('service_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('deposit_amount', 10, 2)->default(0);
            $table->decimal('final_amount', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('remain_amount', 10, 2)->default(0);
            $table->string('status')->nullable();
            $table->dateTime('issued_at')->nullable();
            $table->timestamps();
        });

        Schema::create('expense_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('shift_id')->nullable();
            $table->foreignId('cash_box_id')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('direction')->nullable();
            $table->string('category')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->boolean('affects_profit')->default(true);
            $table->text('description')->nullable();
            $table->dateTime('occurred_at')->nullable();
            $table->timestamps();
        });

        Schema::create('cash_boxes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->nullable();
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('current_balance', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('shifts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->nullable();
            $table->unsignedInteger('shift_no')->default(1);
            $table->string('status')->default('open');
            $table->boolean('is_active')->default(true);
            $table->dateTime('shift_to')->nullable();
            $table->decimal('expenses_amount', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('catalog_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inventory_group_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('purchase_price', 10, 2)->default(0);
            $table->decimal('sale_price', 10, 2)->default(0);
            $table->boolean('is_product')->default(false);
            $table->boolean('is_component')->default(false);
            $table->boolean('is_active')->default(true);
            $table->decimal('min_quantity', 10, 3)->default(0);
            $table->timestamps();
        });

        Schema::create('system_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->nullable();
            $table->timestamps();
        });

        foreach ([
            '2026_10_14_070647_create_salon_services_table.php',
            '2026_10_14_070649_create_staff_members_table.php',
            '2026_10_14_070651_create_appointments_table.php',
            '2026_10_14_070653_create_appointment_services_table.php',
            '2026_10_14_093958_add_public_booking_details_to_appointments_table.php',
            '2026_10_14_101450_create_staff_commissions_table_and_add_weekly_schedule_to_staff_members.php',
            '2026_10_14_103436_add_reminder_sent_at_to_appointments_table.php',
        ] as $migration) {
            (require database_path("migrations/tenant/{$migration}"))->up();
        }

        (require database_path('migrations/tenant/2026_06_11_120020_create_product_components_table.php'))->up();
    }

    private function createInvoice(float $finalAmount, float $paidAmount, float $remainAmount): Invoice
    {
        return Invoice::query()->create([
            'branch_id' => $this->branch->getKey(),
            'invoice_no' => Str::upper(Str::random(10)),
            'invoice_type' => 'direct_sale',
            'subtotal' => 350,
            'discount_amount' => 0,
            'final_amount' => $finalAmount,
            'paid_amount' => $paidAmount,
            'remain_amount' => $remainAmount,
            'status' => 'posted',
            'issued_at' => now(),
        ]);
    }

    private function createPayoutDependencies(): void
    {
        DB::table('shifts')->insert([
            'id' => 1,
            'branch_id' => $this->branch->getKey(),
            'shift_no' => 1,
            'status' => 'open',
            'is_active' => true,
            'expenses_amount' => 0,
        ]);
    }

    private function tenantUrl(string $path): string
    {
        return 'http://salon-test.example.test'.$path;
    }
}
