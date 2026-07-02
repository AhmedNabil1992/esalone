<?php

namespace App\Filament\Resources\Tenant\Customers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label('اسم العميل')
                ->required()
                ->maxLength(60),

            TextInput::make('phone')
                ->label('رقم الهاتف')
                ->tel()
                ->maxLength(20),

            TextInput::make('email')
                ->label('البريد الإلكتروني')
                ->email()
                ->maxLength(100),

            Textarea::make('address')
                ->label('العنوان')
                ->rows(2),

            Toggle::make('is_active')
                ->label('مفعّل')
                ->default(true),

            Select::make('customer_level_id')
                ->label('فئة العميل (المستوى)')
                ->relationship('level', 'name')
                ->placeholder('بدون فئة (عميل عادي)')
                ->nullable()
                ->searchable()
                ->preload(),
        ]);
    }
}
