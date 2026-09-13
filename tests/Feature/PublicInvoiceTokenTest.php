<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Support\Tenant\PublicInvoiceToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use LogicException;
use Tests\TestCase;

class PublicInvoiceTokenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', 'base64:public-invoice-test-key');
        config()->set('tenancy.bootstrappers', []);
    }

    protected function tearDown(): void
    {
        tenancy()->end();

        parent::tearDown();
    }

    public function test_same_invoice_id_has_a_different_token_for_each_tenant(): void
    {
        $firstTenant = $this->createTenant();
        $secondTenant = $this->createTenant();
        $tokens = app(PublicInvoiceToken::class);

        tenancy()->initialize($firstTenant);
        $firstToken = $tokens->generate(173);
        $this->assertTrue($tokens->isValid(173, $firstToken));

        tenancy()->end();
        tenancy()->initialize($secondTenant);
        $secondToken = $tokens->generate(173);

        $this->assertNotSame($firstToken, $secondToken);
        $this->assertFalse($tokens->isValid(173, $firstToken));
        $this->assertTrue($tokens->isValid(173, $secondToken));
        $this->assertFalse($tokens->isValid(174, $secondToken));
    }

    public function test_token_cannot_be_generated_without_a_tenant_context(): void
    {
        $this->expectException(LogicException::class);

        app(PublicInvoiceToken::class)->generate(173);
    }

    private function createTenant(): Tenant
    {
        return Tenant::withoutEvents(fn (): Tenant => Tenant::create([
            'id' => (string) Str::ulid(),
            'is_active' => true,
        ]));
    }
}
