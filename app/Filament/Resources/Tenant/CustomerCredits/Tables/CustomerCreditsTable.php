<?php

namespace App\Filament\Resources\Tenant\CustomerCredits\Tables;

use App\Support\Tenant\TenantClock;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerCreditsTable
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
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('العملية')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'deposit' => 'إيداع',
                        'withdraw' => 'سحب',
                        'redeem' => 'تبديل نقاط',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'deposit' => 'success',
                        'withdraw' => 'danger',
                        'redeem' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('balance_before')
                    ->label('الرصيد قبل')
                    ->money(),
                TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money()
                    ->sortable(),
                TextColumn::make('balance_after')
                    ->label('الرصيد بعد')
                    ->money(),
                TextColumn::make('user.name')
                    ->label('بواسطة')
                    ->toggleable(),
                TextColumn::make('description')
                    ->label('ملاحظة')
                    ->limit(30)
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->dateTime('Y-m-d h:i A')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('created_at')
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
                                fn (Builder $builder): Builder => $builder->where('created_at', '>=', app(TenantClock::class)->localDateStartUtc($from))
                            )
                            ->when(
                                filled($until),
                                fn (Builder $builder): Builder => $builder->where('created_at', '<=', app(TenantClock::class)->localDateEndUtc($until))
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
