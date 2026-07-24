<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('المعلومات الأساسية')
                    ->schema([
                        TextInput::make('name')
                            ->label('إسم المستخدم')
                            ->required(),
                        TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->required(),
                        TextInput::make('password')
                            ->label('كلمة المرور')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? $state : null),
                        DateTimePicker::make('email_verified_at')
                            ->label('تاريخ التحقق من البريد الإلكتروني')
                            ->default(now()),
                    ])
                    ->columns(2),

                Section::make('التحكم في الوصول')
                    ->description('تعيين الأدوار والصلاحيات المباشرة لهذا المستخدم')
                    ->schema([
                        Select::make('roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->label('الأدوار'),
                        Select::make('permissions')
                            ->relationship('permissions', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->label('الصلاحيات المباشرة'),
                    ])
                    ->columns(2),

                Section::make('المصادقة الثنائية')
                    ->schema([
                        Textarea::make('two_factor_secret')
                            ->columnSpanFull(),
                        Textarea::make('two_factor_recovery_codes')
                            ->columnSpanFull(),
                        DateTimePicker::make('two_factor_confirmed_at'),
                    ])
                    ->collapsed(),
            ]);
    }
}
