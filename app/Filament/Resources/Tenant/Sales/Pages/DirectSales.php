<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenant\Sales\Pages;

use App\Filament\Resources\Tenant\Sales\SalesResource;
use Filament\Resources\Pages\Page;

class DirectSales extends Page
{
    protected static string $resource = SalesResource::class;

    protected string $view = 'filament.resources.tenant.sales.pages.direct-sales';

    public function getTitle(): string
    {
        return 'مبيعات الأصناف';
    }
}
