<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\Tenant\InventoryGroup;
use Database\Seeders\Tenant\DefaultCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Tests\TestCase;

class DefaultCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    private ?string $databaseFile = null;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('tenancy.database.central_connection', 'sqlite');
        config()->set('tenancy.database.template_tenant_connection', 'sqlite');
        config()->set('tenancy.bootstrappers', [DatabaseTenancyBootstrapper::class]);
    }

    protected function tearDown(): void
    {
        tenancy()->end();
        DB::purge('tenant');

        if ($this->databaseFile && is_file($this->databaseFile)) {
            unlink($this->databaseFile);
        }

        parent::tearDown();
    }

    public function test_salon_inventory_categories_are_seeded_idempotently(): void
    {
        $this->initializeTenantDatabase();

        $seeder = new DefaultCatalogSeeder;
        $seeder->run();
        $seeder->run();

        $this->assertSame(4, InventoryGroup::query()->count());
        $this->assertEqualsCanonicalizing(
            [
                'مستلزمات الصالون',
                'مستحضرات التجميل',
                'منتجات العناية بالبشرة',
                'منتجات العناية بالشعر',
            ],
            InventoryGroup::query()->orderBy('name')->pluck('name')->all(),
        );

        $this->assertSame(4, InventoryGroup::query()->whereNull('picture')->count());
    }

    public function test_reseeding_preserves_custom_inventory_categories(): void
    {
        $this->initializeTenantDatabase();
        InventoryGroup::query()->create([
            'name' => 'خدمات العناية الخاصة',
            'description' => 'تصنيف مخصص للفرع',
            'is_active' => true,
        ]);

        (new DefaultCatalogSeeder)->run();
        (new DefaultCatalogSeeder)->run();

        $this->assertSame(5, InventoryGroup::query()->count());
        $this->assertDatabaseHas('inventory_groups', ['name' => 'خدمات العناية الخاصة']);
    }

    private function initializeTenantDatabase(): void
    {
        $databaseName = 'default-catalog-test-'.Str::uuid().'.sqlite';
        $this->databaseFile = database_path($databaseName);
        touch($this->databaseFile);

        $tenant = Tenant::withoutEvents(fn (): Tenant => Tenant::create([
            'id' => (string) Str::ulid(),
            'is_active' => true,
        ]));
        $tenant->setInternal('db_name', $databaseName);
        $tenant->save();

        tenancy()->initialize($tenant);

        foreach ([
            '2026_06_11_120000_create_inventory_groups_table.php',
        ] as $migration) {
            (require database_path('migrations/tenant/'.$migration))->up();
        }
    }
}
