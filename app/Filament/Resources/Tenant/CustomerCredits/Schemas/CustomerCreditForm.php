<?php

namespace App\Filament\Resources\Tenant\CustomerCredits\Schemas;

use App\Models\Tenant\Customer;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerCreditForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('customer_id')
                ->label('العميل')
                ->options(fn () => Customer::query()
                    ->where('is_active', true)
                    ->orderBy('name', 'asc')
                    ->pluck('name', 'id')
                    ->toArray())
                ->searchable()
                ->required(),

            Select::make('type')
                ->label('نوع العملية')
                ->options([
                    'deposit' => 'إيداع (إضافة رصيد)',
                    'withdraw' => 'سحب (خصم رصيد)',
                ])
                ->required(),

            TextInput::make('amount')
                ->label('المبلغ')
                ->numeric()
                ->minValue(0.01)
                ->required(),

            Textarea::make('description')
                ->label('ملاحظة')
                ->rows(2),
        ]);
    }
}
