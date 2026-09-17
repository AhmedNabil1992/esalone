<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\Tenant\DevicesType;
use App\Models\Tenant\InventoryGroup;
use App\Models\Tenant\PlayType;
use Database\Seeders\Tenant\DefaultCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

    public function test_default_catalog_is_seeded_idempotently_with_tenant_images(): void
    {
        $this->initializeTenantDatabase();
        Storage::fake('public');

        $seeder = new DefaultCatalogSeeder;
        $seeder->run();
        $seeder->run();

        $this->assertSame(3, DevicesType::query()->count());
        $this->assertSame(7, PlayType::query()->count());
        $this->assertSame(4, InventoryGroup::query()->count());

        $playstation = DevicesType::query()->where('name', 'بلاي ستيشن')->firstOrFail();
        $this->assertSame(
            ['فردي', 'زوجي', 'نيتفلكس'],
            PlayType::query()->where('device_type_id', $playstation->id)->orderBy('id')->pluck('name')->all(),
        );

        $paths = DevicesType::query()->pluck('picture')
            ->merge(PlayType::query()->pluck('picture'))
            ->merge(InventoryGroup::query()->pluck('picture'));

        $this->assertCount(14, $paths);
        Storage::disk('public')->assertExists($paths->all());
    }

    public function test_all_default_assets_are_optimized_square_webp_images(): void
    {
        $assets = glob(resource_path('tenant-defaults/*/*.webp'));

        $this->assertIsArray($assets);
        $this->assertCount(14, $assets);

        foreach ($assets as $asset) {
            $size = getimagesize($asset);

            $this->assertIsArray($size);
            $this->assertSame(512, $size[0]);
            $this->assertSame(512, $size[1]);
            $this->assertSame(IMAGETYPE_WEBP, $size[2]);
        }
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
            '2026_06_11_074435_create_devices_types_table.php',
            '2026_06_11_084710_create_play_types_table.php',
            '2026_06_11_120000_create_inventory_groups_table.php',
            '2026_06_27_093451_add_fixed_times_to_devices_types_table.php',
        ] as $migration) {
            (require database_path('migrations/tenant/'.$migration))->up();
        }

    }
}
