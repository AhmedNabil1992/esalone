<?php

namespace App\Filament\Resources\Tenant\FinancialTransactions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FinancialTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('cash_box_id')
                    ->label('الخزنة')
                    ->relationship('cashBox', 'name', fn ($query) => $query->where('is_active', true))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('expense_type_id')
                    ->label('نوع المصروف')
                    ->relationship('expenseType', 'name', fn ($query) => $query->where('is_active', true))
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('amount')
                    ->label('قيمة المصروف')
                    ->numeric()
                    ->minValue(0.01)
                    ->required(),
                DateTimePicker::make('occurred_at')
                    ->label('تاريخ العملية')
                    ->default(now())
                    ->required(),
                // Toggle::make('affects_profit')
                //     ->label('يؤثر على الربحية')
                //     ->default(true),
                Textarea::make('description')
                    ->label('الوصف')
                    ->columnSpanFull()
                    ->required(),
            ]);
    }
}
