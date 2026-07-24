<?php

namespace App\Filament\Resources\Tenant\PlayPrices\Schemas;

use App\Models\Tenant\Devices;
use App\Models\Tenant\DevicesType;
use App\Models\Tenant\PlayType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PlayPriceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('device_type_id')
                    ->label('نوع الجهاز')
                    ->options(DevicesType::pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($set) {
                        $set('play_type_id', null);
                        $set('device_ids', []);
                    })
                    ->required(),
                Select::make('play_type_id')
                    ->label('نوع اللعب')
                    ->options(fn (Get $get) => PlayType::query()
                        ->when($get('device_type_id'), fn ($query, $typeId) => $query->where('device_type_id', $typeId))
                        ->pluck('name', 'id')
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('device_ids')
                    ->label('الأجهزة')
                    ->options(fn (Get $get) => Devices::query()
                        ->where('is_active', true)
                        ->when($get('device_type_id'), fn ($query, $typeId) => $query->where('device_type_id', $typeId))
                        ->pluck('name', 'id')
                    )
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('description')
                    ->label('وصف سعر اللعب')
                    ->maxLength(50)
                    ->placeholder('وصف مختصر لسعر اللعب')
                    ->helperText('يمكنك إضافة وصف مختصر لسعر اللعب لا يتجاوز 50 حرفًا.'),
                TextInput::make('price')
                    ->label('سعر اللعب')
                    ->numeric()
                    ->required()
                    ->helperText('أدخل سعر اللعب كقيمة رقمية.'),
                TimePicker::make('start_from')
                    ->label('وقت بدء السعر')
                    ->seconds()
                    ->default('00:00:00')
                    ->required()
                    ->helperText('حدد الوقت المحدد لبدء تطبيق هذا السعر (ساعة:دقيقة:ثانية).'),
            ]);
    }
}
