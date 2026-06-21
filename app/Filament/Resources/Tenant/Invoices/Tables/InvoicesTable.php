<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenant\Invoices\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_no')
                    ->label('رقم الفاتورة')
                    ->searchable()
                    ->sortable(),
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
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
