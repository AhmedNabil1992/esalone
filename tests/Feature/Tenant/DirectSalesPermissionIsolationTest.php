<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Filament\Resources\Tenant\Invoices\InvoiceResource;
use App\Filament\Resources\Tenant\Sales\Pages\DirectSales;
use App\Filament\Resources\Tenant\Sales\SalesResource;
use App\Models\Tenant\User;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DirectSalesPermissionIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSchema();
    }

    protected function tearDown(): void
    {
        $this->tearDownSchema();

        parent::tearDown();
    }

    private function setUpSchema(): void
    {
        $this->tearDownSchema();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('roles', function (Blueprint $table): void {
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
            $table->index(['model_id', 'model_type']);
        });

        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
        });

        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });
    }

    private function tearDownSchema(): void
    {
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('users');
    }

    #[Test]
    public function shield_generates_isolated_permissions_for_sales_and_invoice_resources(): void
    {
        $resources = FilamentShield::getResources();

        $this->assertArrayHasKey(SalesResource::class, $resources);
        $this->assertArrayHasKey(InvoiceResource::class, $resources);

        $salesPermissions = $resources[SalesResource::class]['permissions'];
        $invoicePermissions = $resources[InvoiceResource::class]['permissions'];

        // SalesResource must only have the 'create' permission
        $this->assertCount(1, $salesPermissions);
        $this->assertArrayHasKey('create', $salesPermissions);
        $this->assertSame('Create:DirectSale', $salesPermissions['create']['key']);

        // InvoiceResource has standard 6 permissions
        $this->assertCount(6, $invoicePermissions);
        $this->assertArrayHasKey('viewAny', $invoicePermissions);
        $this->assertSame('ViewAny:Invoice', $invoicePermissions['viewAny']['key']);

        // The permission keys must not overlap
        $salesKeys = collect($salesPermissions)->pluck('key')->all();
        $invoiceKeys = collect($invoicePermissions)->pluck('key')->all();

        $this->assertEmpty(array_intersect($salesKeys, $invoiceKeys));
    }

    #[Test]
    public function sales_resource_and_page_access_strictly_depends_on_create_direct_sale_permission(): void
    {
        $user = User::query()->create([
            'name' => 'Cashier User',
            'email' => 'cashier@test.com',
            'password' => bcrypt('password'),
        ]);

        $createDirectSalePermission = Permission::query()->create([
            'name' => 'Create:DirectSale',
            'guard_name' => 'web',
        ]);

        $this->actingAs($user);

        // Without permission: neither resource nor page is accessible
        $this->assertFalse(SalesResource::canViewAny());
        $this->assertFalse(DirectSales::canAccess());

        // Grant permission
        $user->givePermissionTo($createDirectSalePermission);

        // With permission: both resource and page are accessible
        $this->assertTrue(SalesResource::canViewAny());
        $this->assertTrue(DirectSales::canAccess());
    }
}
