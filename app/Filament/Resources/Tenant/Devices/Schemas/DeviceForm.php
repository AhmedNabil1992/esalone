<?php

namespace App\Filament\Resources\Tenant\Devices\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DeviceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label('الفرع')
                    ->required()
                    ->default(fn () => session('active_branch_id') !== 'all' ? (int) session('active_branch_id') : null)
                    ->searchable()
                    ->preload(),
                Select::make('devices_type_id')
                    ->label('نوع الجهاز')
                    ->relationship('type', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->label('إسم الجهاز')
                    ->required(),
                TextInput::make('ip_address')
                    ->label('عنوان IP')
                    ->default('127.0.0.1')
                    ->helperText('مثال: 192.168.1.201'),
                TextInput::make('mac_address')
                    ->label('عنوان MAC لجهاز التحكم')
                    ->helperText('مطلوب عند استخدام MQTT. مثال: 00:1A:2B:3C:4D:5E')
                    ->maxLength(17)
                    ->regex('/^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$/'),
                TextInput::make('limit_time')
                    ->label('أقل وقت للتشغيل (بالدقائق)')
                    ->default(0)
                    ->numeric(),
                Toggle::make('is_time')
                    ->label('جهاز يعتمد على الوقت')
                    ->onColor('success')
                    ->offColor('danger')
                    ->default(true),
            ]);
    }
}
