<?php

namespace App\Filament\Resources\Tenant\Purchases\Schemas;

use App\Models\Tenant\CatalogItem;
use App\Models\Tenant\Shift;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Schema as SchemaFacade;

class PurchaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('shift_no_display')
                    ->label('الشيفت الحالي')
                    ->default(fn (): string => Shift::currentOpenShiftNumber() ? 'شيفت #'.Shift::currentOpenShiftNumber() : 'لا يوجد شيفت مفتوح')
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('يتم ترحيل الفاتورة وتأثيرها المالي تلقائياً على الشيفت المفتوح الحالي بالنظام.'),
                Select::make('warehouse_id')
                    ->label('المخزن')
                    ->relationship('warehouse', 'name', fn ($query) => $query->where('is_active', true))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('cash_box_id')
                    ->label('الخزنة')
                    ->relationship('cashBox', 'name', fn ($query) => $query->where('is_active', true))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('supplier_id')
                    ->label('المورد')
                    ->relationship('supplier', 'name', fn ($query) => $query->where('is_active', true))
                    ->searchable()
                    ->preload(),
                TextInput::make('reference')
                    ->label('رقم الفاتورة / الإيصال')
                    ->maxLength(255),
                // DateTimePicker::make('purchased_at')
                //     ->label('تاريخ الشراء')
                //     ->default(now())
                //     ->required(),
                Textarea::make('notes')
                    ->label('ملاحظات')
                    ->columnSpanFull(),
                Repeater::make('items')
                    ->label('بنود المشتريات')
                    ->schema([
                        Select::make('catalog_item_id')
                            ->label('الصنف')
                            ->options(fn (): array => CatalogItem::query()
                                ->where('is_active', true)
                                ->where('is_product', false)
                                ->orderBy('name', 'asc')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (callable $set, $state) {
                                if ($state) {
                                    $item = CatalogItem::find($state);
                                    if ($item) {
                                        $set('unit_cost', $item->purchase_price);
                                    }
                                }
                            }),
                        TextInput::make('quantity')
                            ->label('الكمية')
                            ->numeric()
                            ->minValue(0.001)
                            ->required(),
                        TextInput::make('unit_cost')
                            ->label('تكلفة الوحدة')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                    ])
                    ->defaultItems(1)
                    ->columnSpanFull()
                    ->required(),
            ]);
    }

    protected static function shiftOptions(): array
    {
        if (! SchemaFacade::connection('tenant')->hasTable('shifts')) {
            return [];
        }

        return Shift::query()
            ->where('status', 'open')
            ->where('is_active', true)
            ->orderBy('shift_no', 'asc')
            ->pluck('shift_no', 'id')
            ->all();
    }
}
