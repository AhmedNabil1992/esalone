<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenant\Sales;

use App\Filament\Resources\Tenant\Sales\Pages\DirectSales;
use App\Models\Tenant\DirectSale;
use BackedEnum;
use Filament\Resources\Resource;
use UnitEnum;

class SalesResource extends Resource
{
    protected static ?string $model = DirectSale::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static string|UnitEnum|null $navigationGroup = 'المبيعات';

    protected static ?string $navigationLabel = 'المبيعات المباشرة';

    protected static ?string $label = 'مبيعات الأصناف';

    protected static ?string $modelLabel = 'المبيعات المباشرة';

    protected static ?string $pluralModelLabel = 'المبيعات المباشرة';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('Create:DirectSale') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => DirectSales::route('/'),
        ];
    }
}
