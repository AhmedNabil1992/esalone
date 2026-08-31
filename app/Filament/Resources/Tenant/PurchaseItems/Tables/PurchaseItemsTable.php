<?php

namespace App\Filament\Resources\Tenant\PurchaseItems\Tables;

use Carbon\Carbon;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PurchaseItemsTable
{
    public static function configure(Table $table): Table
    {
        $defaultFrom = Carbon::now()->startOfMonth()->toDateString();
        $defaultUntil = Carbon::today()->toDateString();

        return $table
            ->columns([
                TextColumn::make('purchase.branch.name')
                    ->label('الفرع')
                    ->badge()
                    ->sortable(),

                TextColumn::make('purchase.reference')
                    ->label('مرجع الفاتورة')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('purchase.shift.shift_no')
                    ->label('رقم الشيفت')
                    ->formatStateUsing(fn ($state) => $state ? "#{$state}" : '-')
                    ->sortable(),

                TextColumn::make('item.inventoryGroup.name')
                    ->label('مجموعة المخزون')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('item.name')
                    ->label('اسم الصنف')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('purchase.warehouse.name')
                    ->label('المخزن')
                    ->searchable(),

                TextColumn::make('purchase.supplier.name')
                    ->label('المورد')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('quantity')
                    ->label('الكمية')
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('إجمالي الكمية'),
                    ]),

                TextColumn::make('unit_cost')
                    ->label('سعر الوحدة')
                    ->money()
                    ->sortable(),

                TextColumn::make('total_cost')
                    ->label('التكلفة الإجمالية')
                    ->money()
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('إجمالي التكلفة')->money(),
                        Count::make()->label('عدد البنود'),
                    ]),

                TextColumn::make('purchase.purchased_at')
                    ->label('تاريخ الشراء')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('purchased_at')
                    ->label('تاريخ الشراء')
                    ->form([
                        DatePicker::make('from')
                            ->label('من تاريخ')
                            ->default($defaultFrom),
                        DatePicker::make('until')
                            ->label('إلى تاريخ')
                            ->default($defaultUntil),
                    ])
                    ->query(function (Builder $query, array $data) use ($defaultFrom, $defaultUntil): Builder {
                        $from = $data['from'] ?? $defaultFrom;
                        $until = $data['until'] ?? $defaultUntil;

                        return $query->whereHas('purchase', function (Builder $b) use ($from, $until): void {
                            $b->when(
                                filled($from),
                                fn (Builder $builder): Builder => $builder->whereDate('purchased_at', '>=', $from)
                            )->when(
                                filled($until),
                                fn (Builder $builder): Builder => $builder->whereDate('purchased_at', '<=', $until)
                            );
                        });
                    }),

                SelectFilter::make('catalog_item_id')
                    ->label('الصنف')
                    ->relationship('item', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('inventory_group_id')
                    ->label('مجموعة المخزون')
                    ->relationship('item.inventoryGroup', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('warehouse_id')
                    ->label('المخزن')
                    ->relationship('purchase.warehouse', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('supplier_id')
                    ->label('المورد')
                    ->relationship('purchase.supplier', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('shift_id')
                    ->label('رقم الشيفت')
                    ->relationship('purchase.shift', 'shift_no', fn (Builder $query) => $query->orderByDesc('shift_no'))
                    ->searchable()
                    ->preload(),
            ])
            ->filtersLayout(FiltersLayout::Modal)
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
