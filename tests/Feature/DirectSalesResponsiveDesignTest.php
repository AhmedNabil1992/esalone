<?php

namespace Tests\Feature;

use App\Livewire\Tenant\Sales\DirectSalesForm;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

class DirectSalesResponsiveDesignTest extends TestCase
{
    #[Test]
    public function direct_sales_view_preserves_livewire_actions_and_responsive_theme_contract(): void
    {
        $view = file_get_contents(resource_path('views/livewire/tenant/sales/direct-sales-form.blade.php'));
        $styles = file_get_contents(resource_path('views/livewire/tenant/sales/direct-sales-styles.blade.php'));

        $this->assertIsString($view);
        $this->assertIsString($styles);
        $this->assertStringContainsString('.dark .direct-sales', $styles);
        $this->assertStringContainsString('@media(max-width:900px)', $styles);
        $this->assertStringContainsString('@media(max-width:560px)', $styles);
        $this->assertStringContainsString('grid-template-columns:repeat(2,minmax(0,1fr))', $styles);
        $this->assertStringContainsString('@if ($this->canApplyDiscount)', $view);

        preg_match_all('/wire:click="([A-Za-z_][A-Za-z0-9_]*)/', $view, $methodMatches);
        $reflection = new ReflectionClass(DirectSalesForm::class);

        foreach (array_unique($methodMatches[1]) as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Missing Livewire method [{$method}].");
            $this->assertTrue($reflection->getMethod($method)->isPublic(), "Livewire method [{$method}] must be public.");
        }

        foreach (['selected_cash_box_id', 'selected_customer_id', 'discount_amount', 'paid_amount', 'whatsapp_phone'] as $property) {
            $this->assertStringContainsString($property, $view);
            $this->assertTrue($reflection->hasProperty($property), "Missing Livewire property [{$property}].");
            $this->assertTrue($reflection->getProperty($property)->isPublic(), "Livewire property [{$property}] must be public.");
        }
    }
}
