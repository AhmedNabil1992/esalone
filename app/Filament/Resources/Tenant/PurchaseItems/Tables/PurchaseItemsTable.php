<?php

namespace App\Filament\Resources\Tenant\PurchaseItems\Tables;

use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
                TextColumn::make('item.inventoryGroup.name')
                    ->label('مجموعة المخزون')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('item.name')
                    ->label('اسم الصنف')
                    ->searchable()
                    ->sortable(),
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
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('created_at')
                    ->label('تاريخ الإنشاء')
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

                        return $query
                            ->when(
                                filled($from),
                                fn (Builder $builder): Builder => $builder->whereDate('created_at', '>=', $from)
                            )
                            ->when(
                                filled($until),
                                fn (Builder $builder): Builder => $builder->whereDate('created_at', '<=', $until)
                            );
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
            ])
            ->filtersLayout(FiltersLayout::Modal)
            ->pluralModelLabel('الصفحات')
            ->recordActions([
                ViewAction::make(),
                // EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // DeleteBulkAction::make(),
                ]),
            ]);
    }
}
