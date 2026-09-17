<?php

namespace App\Filament\Resources\Tenant\Purchases\Tables;

use App\Support\Tenant\TenantClock;
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

class PurchasesTable
{
    public static function configure(Table $table): Table
    {
        $clock = app(TenantClock::class);
        $defaultFrom = $clock->nowLocal()->startOfMonth()->toDateString();
        $defaultUntil = $clock->nowLocal()->toDateString();

        return $table
            ->columns([
                TextColumn::make('branch.name')
                    ->label('فرع'),
                TextColumn::make('reference')
                    ->label('المرجع')
                    ->searchable(),
                TextColumn::make('shift.shift_no')
                    ->label('رقم الشيفت')
                    ->formatStateUsing(fn ($state) => $state ? "#{$state}" : '-')
                    ->sortable(),
                TextColumn::make('warehouse.name')
                    ->label('المخزن')
                    ->searchable(),
                TextColumn::make('cashBox.name')
                    ->label('الخزنة')
                    ->searchable(),
                TextColumn::make('supplier.name')
                    ->label('المورد')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('total_amount')
                    ->label('الإجمالي')
                    ->money()
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('إجمالي المشتريات')->money(),
                        Count::make()->label('عدد الفواتير'),
                    ]),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'posted' => 'مكتملة',
                        'draft' => 'مسودة',
                        'cancelled' => 'ملغاة',
                        default => $state ?? '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'posted' => 'success',
                        'draft' => 'gray',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('purchased_at')
                    ->label('تاريخ الشراء')
                    ->dateTime()
                    ->sortable(),
            ])
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

                        return $query
                            ->when(
                                filled($from),
                                fn (Builder $builder): Builder => $builder->where('purchased_at', '>=', app(TenantClock::class)->localDateStartUtc($from))
                            )
                            ->when(
                                filled($until),
                                fn (Builder $builder): Builder => $builder->where('purchased_at', '<=', app(TenantClock::class)->localDateEndUtc($until))
                            );
                    }),
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'posted' => 'مكتملة',
                        'draft' => 'مسودة',
                        'cancelled' => 'ملغاة',
                    ]),
                SelectFilter::make('warehouse_id')
                    ->label('المخزن')
                    ->relationship('warehouse', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('cash_box_id')
                    ->label('الخزنة')
                    ->relationship('cashBox', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('supplier_id')
                    ->label('المورد')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('shift_id')
                    ->label('رقم الشيفت')
                    ->relationship('shift', 'shift_no', fn (Builder $query) => $query->orderByDesc('shift_no'))
                    ->searchable()
                    ->preload(),
            ])
            ->filtersLayout(FiltersLayout::Modal)
            ->pluralModelLabel('الصفحات')
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
