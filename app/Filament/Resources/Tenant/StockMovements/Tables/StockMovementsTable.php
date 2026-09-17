<?php

namespace App\Filament\Resources\Tenant\StockMovements\Tables;

use App\Support\Tenant\TenantClock;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockMovementsTable
{
    public static function configure(Table $table): Table
    {
        $clock = app(TenantClock::class);
        $defaultFrom = $clock->nowLocal()->startOfMonth()->toDateString();
        $defaultUntil = $clock->nowLocal()->toDateString();

        return $table
            ->columns([
                TextColumn::make('warehouse.branch.name')
                    ->label('الفرع')
                    ->badge()
                    ->sortable(),
                TextColumn::make('warehouse.name')
                    ->label('المخزن')
                    ->searchable(),
                TextColumn::make('item.inventoryGroup.name')
                    ->label('مجموعة الصنف')
                    ->searchable(),
                TextColumn::make('item.name')
                    ->label('المنتج')
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label('الكمية')
                    ->sortable(),
                TextColumn::make('movement_type')
                    ->label('نوع الحركة')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'purchase' => 'شراء',
                        'purchase_reverse' => 'عكس شراء',
                        'transfer' => 'تحويل مخزني',
                        'direct_sale' => 'بيع مباشر',
                        'sale' => 'مبيعات',
                        'sale_reverse' => 'عكس مبيعات',
                        'inventory_adjustment' => 'تعديل مخزني',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'purchase' => 'info',
                        'transfer' => 'warning',
                        'direct_sale', 'sale' => 'success',
                        'purchase_reverse', 'sale_reverse' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('occurred_at')
                    ->dateTime()
                    ->label('تاريخ الحركة')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->label('تاريخ الإنشاء')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->label('تاريخ التحديث')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->filters([
                Filter::make('occurred_at')
                    ->label('تاريخ الحركة')
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
                                fn (Builder $builder): Builder => $builder->where('occurred_at', '>=', app(TenantClock::class)->localDateStartUtc($from))
                            )
                            ->when(
                                filled($until),
                                fn (Builder $builder): Builder => $builder->where('occurred_at', '<=', app(TenantClock::class)->localDateEndUtc($until))
                            );
                    }),
                SelectFilter::make('movement_type')
                    ->label('نوع الحركة')
                    ->options([
                        'purchase' => 'شراء',
                        'purchase_reverse' => 'عكس شراء',
                        'transfer' => 'تحويل مخزني',
                        'direct_sale' => 'بيع مباشر',
                        'sale' => 'مبيعات',
                        'sale_reverse' => 'عكس مبيعات',
                        'inventory_adjustment' => 'تعديل مخزني',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // DeleteBulkAction::make(),
                ]),
            ]);
    }
}
