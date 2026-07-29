<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenant\Invoices\Tables;

use Carbon\Carbon;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        $defaultFrom = Carbon::now()->startOfMonth()->toDateString();
        $defaultUntil = Carbon::today()->toDateString();

        return $table
            ->columns([
                TextColumn::make('invoice_no')
                    ->label('رقم الفاتورة')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice_type')
                    ->label('نوع الفاتورة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'direct_sale' => 'بيع مباشر',
                        'device_sale' => 'جهاز',
                        default => '-',
                    })
                    ->color(fn (?string $state): string => $state === 'direct_sale' ? 'success' : 'primary'),
                TextColumn::make('issued_at')
                    ->label('التاريخ')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('device.name')
                    ->label('الجهاز')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('customer.phone')
                    ->label('الهاتف')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('cashBox.name')
                    ->label('الخزينة')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('shift.shift_no')
                    ->label('الشيفت')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('final_amount')
                    ->label('الصافي')
                    ->money()
                    ->sortable(),
                TextColumn::make('paid_amount')
                    ->label('المدفوع')
                    ->money()
                    ->sortable(),
                TextColumn::make('remain_amount')
                    ->label('المتبقي')
                    ->money()
                    ->color(fn ($state): string => (float) $state < 0 ? 'danger' : 'success')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('shift_id')
                    ->label('رقم الشيفت')
                    ->relationship('shift', 'shift_no', fn (Builder $query) => $query->orderByDesc('shift_no'))
                    ->searchable()
                    ->preload(),
                Filter::make('issued_at')
                    ->label('التاريخ')
                    ->form([
                        DatePicker::make('from')->label('من تاريخ')->default($defaultFrom),
                        DatePicker::make('until')->label('إلى تاريخ')->default($defaultUntil),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['from'] ?? null),
                                fn (Builder $builder): Builder => $builder->whereDate('issued_at', '>=', $data['from'])
                            )
                            ->when(
                                filled($data['until'] ?? null),
                                fn (Builder $builder): Builder => $builder->whereDate('issued_at', '<=', $data['until'])
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
