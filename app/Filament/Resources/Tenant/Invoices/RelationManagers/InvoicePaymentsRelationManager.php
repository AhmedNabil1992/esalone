<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenant\Invoices\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvoicePaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'المدفوعات';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('cashBox.name')
                    ->label('الخزينة')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money()
                    ->color('success'),
                TextColumn::make('payment_method')
                    ->label('طريقة الدفع')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'transfer' => 'تحويل',
                        'card' => 'بطاقة',
                        'wallet' => 'محفظة',
                        default => 'كاش',
                    }),
                TextColumn::make('createdBy.name')
                    ->label('بواسطة')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('notes')
                    ->label('ملاحظات')
                    ->placeholder('-')
                    ->limit(50),
                TextColumn::make('created_at')
                    ->label('وقت التسجيل')
                    ->dateTime(),
            ])
            ->defaultSort('id', 'asc')
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
