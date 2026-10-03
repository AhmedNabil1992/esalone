<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenant\Invoices\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Support\Tenant\TenantClock;

class InvoiceItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'marketHistories';

    protected static ?string $title = 'تفاصيل الطلبات';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('subject_name')
                    ->label('الصنف')
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('unit_price')
                    ->label('سعر الوحدة')
                    ->money()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('الكمية')
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('الإجمالي')
                    ->money()
                    ->sortable(),
                TextColumn::make('transaction_date')
                    ->label('تاريخ العملية')
                    ->dateTime('Y-m-d')
                    ->sortable(),
                TextColumn::make('transaction_time')
                    ->label('وقت العملية')
                    ->time('h:i A')
                    ->timezone(app(TenantClock::class)->timezone())
                    ->placeholder('-'),
                TextColumn::make('createdBy.name')
                    ->label('بواسطة')
                    ->placeholder('-')
                    ->searchable(),
            ])
            ->defaultSort('id', 'asc')
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
