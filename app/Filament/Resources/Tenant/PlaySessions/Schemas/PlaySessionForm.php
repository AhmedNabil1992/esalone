<?php

namespace App\Filament\Resources\Tenant\PlaySessions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PlaySessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('device.type.name')
                    ->label('نوع الجهاز'),
                TextInput::make('device.name')
                    ->label('إسم الجهاز'),
                TextInput::make('customer.name')
                    ->label('إسم العميل'),
                TextInput::make('prepaid_amount')
                    ->label('المبلغ المدفوع مسبقاً')
                    ->numeric(),

            ]);
    }
}
