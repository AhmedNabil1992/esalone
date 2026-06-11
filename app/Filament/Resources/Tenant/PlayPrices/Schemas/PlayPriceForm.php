<?php

namespace App\Filament\Resources\Tenant\PlayPrices\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use App\Models\Tenant\Devices;
use Filament\Forms\Components\TimePicker;

class PlayPriceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('play_type_id')
                    ->label('نوع اللعب')
                    ->relationship('playType', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('device_ids') // غيرنا الاسم لـ device_ids لأنه هيرجع مصفوفة
                    ->label('الأجهزة')
                    ->options(Devices::where('is_active', true)->pluck('name', 'id')) // جلب الأجهزة النشطة
                    ->multiple() // تحويله لـ مالتي سيلكت
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
                    ->seconds() // تفعيل إدخال الثواني عشان يتوافق مع الدقة العالية في الـ DB
                    // ->required()
                    ->default(now()->startOfDay()) // تعيين قيمة افتراضية لوقت بدء السعر (مثلاً بداية اليوم)
                    ->helperText('حدد الوقت المحدد لبدء تطبيق هذا السعر (ساعة:دقيقة:ثانية).'),   
            ]);
    }
}
