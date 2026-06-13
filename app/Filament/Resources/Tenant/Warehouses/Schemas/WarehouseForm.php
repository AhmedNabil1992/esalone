<?php

namespace App\Filament\Resources\Tenant\Warehouses\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WarehouseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('إسم المخزن')
                    ->required(),
                TextInput::make('code')
                    ->label('كود المخزن'),
                TextInput::make('address')
                    ->label('عنوان المخزن')
                    ->helperText('عنوان المخزن (اختياري)'),

            ]);
    }
}
