<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenant\Sales;

use App\Filament\Resources\Tenant\Sales\Pages\DirectSales;
use App\Models\Tenant\Invoice;
use BackedEnum;
use Filament\Resources\Resource;
use UnitEnum;

class SalesResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static string|UnitEnum|null $navigationGroup = 'اللعب';

    protected static ?string $navigationLabel = 'المبيعات المباشرة';

    protected static ?string $label = 'مبيعات الأصناف';

    protected static ?int $navigationSort = 2;

    public static function getPages(): array
    {
        return [
            'index' => DirectSales::route('/'),
        ];
    }
}
