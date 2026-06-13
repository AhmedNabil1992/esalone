<?php

namespace App\Filament\Resources\Tenant\Suppliers\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('اسم المورد')
                    ->required()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label('رقم الهاتف')
                    ->tel()
                    ->maxLength(50),
                TextInput::make('email')
                    ->label('البريد الإلكتروني')
                    ->email()
                    ->maxLength(255),
                Textarea::make('address')
                    ->label('العنوان')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('مورد نشط')
                    ->default(true),
            ]);
    }
}
