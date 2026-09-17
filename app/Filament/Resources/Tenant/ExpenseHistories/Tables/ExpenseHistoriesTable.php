<?php

namespace App\Filament\Resources\Tenant\ExpenseHistories\Tables;

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

class ExpenseHistoriesTable
{
    public static function configure(Table $table): Table
    {
        $clock = app(TenantClock::class);
        $defaultFrom = $clock->nowLocal()->startOfMonth()->toDateString();
        $defaultUntil = $clock->nowLocal()->toDateString();

        return $table
            ->columns([
                TextColumn::make('branch.name')
                    ->label('الفرع')
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('cashBox.name')
                    ->label('الخزنة')
                    ->searchable(),
                TextColumn::make('expenseType.name')
                    ->label('نوع المصروف')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('الوصف')
                    ->placeholder('-')
                    ->limit(40),
                TextColumn::make('amount')
                    ->label('القيمة')
                    ->money()
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('إجمالي المصروفات')->money(),
                        Count::make()->label('عدد المصروفات'),
                    ]),
                TextColumn::make('createdBy.name')
                    ->label('بواسطة')
                    ->placeholder('-'),
                TextColumn::make('shift_id')
                    ->label('رقم الشفت')
                    ->formatStateUsing(fn ($state, $record) => $record->shift ? "#{$record->shift->shift_no}" : ($state ? "#{$state}" : '-'))
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('occurred_at')
                    ->label('تاريخ العملية')
                    ->dateTime('Y-m-d h:i A')
                    ->sortable(),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->filters([
                Filter::make('occurred_at')
                    ->label('تاريخ العملية')
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
                SelectFilter::make('cash_box_id')
                    ->label('الخزنة')
                    ->relationship('cashBox', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('expense_type_id')
                    ->label('نوع المصروف')
                    ->relationship('expenseType', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('created_by')
                    ->label('بواسطة')
                    ->relationship('createdBy', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('shift_id')
                    ->label('رقم الشفت')
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
