<?php

namespace App\Filament\Resources\Tenant\CashBoxes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CashBoxForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('الإسم')->required()->unique('cash_boxes', 'name', ignoreRecord: true),
                TextInput::make('code')->label('الرمز')->required()->unique('cash_boxes', 'code', ignoreRecord: true)->numeric(),
                Select::make('type')->label('النوع')->options([
                    'cash' => 'كاش',
                    'bank' => 'بنك',
                    'wallet' => 'محفظة إلكترونية',
                ])->required(),
                TextInput::make('opening_balance')->label('الرصيد الافتتاحي')->numeric()->required()->default(0)->minValue(0),
            ]);
    }
}
