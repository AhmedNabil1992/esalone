<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Filament\Resources\Tenant\Branches\BranchResource;
use App\Http\Middleware\CheckTenantBranchLimit;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BranchLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure branches table exists for tenant DB in testing
        if (! Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('code')->nullable();
                $table->string('phone')->nullable();
                $table->string('address')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('plans')) {
            Schema::create('plans', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('interval')->default('monthly');
                $table->integer('price')->default(100);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('plan_features')) {
            Schema::create('plan_features', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
                $table->string('feature');
                $table->integer('limit_value')->nullable();
                $table->timestamps();
            });
        }
    }

    protected function createTestTenant(array $attributes = []): Tenant
    {
        return Tenant::withoutEvents(function () use ($attributes) {
            return Tenant::create(array_merge([
                'id' => 'test_tenant_'.uniqid(),
                'branches_limit' => 1,
            ], $attributes));
        });
    }

    public function test_tenant_has_default_branches_limit_of_one(): void
    {
        $tenant = $this->createTestTenant([
            'id' => 'tenant_default',
            'branches_limit' => 1,
        ]);

        $this->assertEquals(1, $tenant->getBranchesLimit());
        $this->assertTrue($tenant->canCreateBranch(0));
        $this->assertFalse($tenant->canCreateBranch(1));
    }

    public function test_tenant_can_have_custom_branches_limit(): void
    {
        $tenant = $this->createTestTenant([
            'id' => 'tenant_custom',
            'branches_limit' => 3,
        ]);

        $this->assertEquals(3, $tenant->getBranchesLimit());
        $this->assertTrue($tenant->canCreateBranch(0));
        $this->assertTrue($tenant->canCreateBranch(1));
        $this->assertTrue($tenant->canCreateBranch(2));
        $this->assertFalse($tenant->canCreateBranch(3));
    }

    public function test_tenant_falls_back_to_plan_limit_if_branches_limit_is_null(): void
    {
        $planId = DB::table('plans')->insertGetId([
            'name' => 'Gold Plan',
            'interval' => 'monthly',
            'price' => 500,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('plan_features')->insert([
            'plan_id' => $planId,
            'feature' => 'branches',
            'limit_value' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tenant = $this->createTestTenant([
            'id' => 'tenant_with_plan',
            'plan_id' => $planId,
            'branches_limit' => null,
        ]);

        $this->assertEquals(2, $tenant->getBranchesLimit());
        $this->assertTrue($tenant->canCreateBranch(1));
        $this->assertFalse($tenant->canCreateBranch(2));
    }

    public function test_branch_resource_can_create_respects_branch_limit(): void
    {
        Branch::query()->delete();

        $tenant = $this->createTestTenant([
            'id' => 'tenant_filament_test',
            'branches_limit' => 1,
        ]);

        tenancy()->tenant = $tenant;

        $this->assertTrue(BranchResource::canCreate());

        Branch::create([
            'name' => 'الفرع الرئيسي',
            'code' => 'BR-001',
        ]);

        $this->assertFalse(BranchResource::canCreate());

        $tenant->branches_limit = 2;
        $tenant->saveQuietly();
        $this->assertTrue(BranchResource::canCreate());
    }

    public function test_middleware_blocks_json_request_when_limit_is_reached(): void
    {
        Branch::query()->delete();

        $tenant = $this->createTestTenant([
            'id' => 'tenant_middleware_json',
            'branches_limit' => 1,
        ]);

        tenancy()->tenant = $tenant;

        Branch::create([
            'name' => 'الفرع الأول',
            'code' => 'BR-001',
        ]);

        $middleware = new CheckTenantBranchLimit;
        $request = Request::create('/admin/branches/create', 'GET');
        $request->headers->set('Accept', 'application/json');

        $response = $middleware->handle($request, function () {
            return new Response('OK', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals(1, $responseData['branches_limit']);
    }

    public function test_middleware_allows_request_when_limit_is_not_reached(): void
    {
        Branch::query()->delete();

        $tenant = $this->createTestTenant([
            'id' => 'tenant_middleware_allowed',
            'branches_limit' => 2,
        ]);

        tenancy()->tenant = $tenant;

        Branch::create([
            'name' => 'الفرع الأول',
            'code' => 'BR-001',
        ]);

        $middleware = new CheckTenantBranchLimit;
        $request = Request::create('/admin/branches/create', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }
}
