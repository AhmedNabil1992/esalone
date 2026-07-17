<?php

namespace App\Filament\Resources\Tenant\Shifts\Shifts\Schemas;

use App\Models\Tenant\CashBox;
use App\Models\Tenant\Shift;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ShiftForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // TextInput::make('shift_no')
                //     ->label('رقم الشيفت')
                //     ->numeric()
                //     ->default(fn (): int => Shift::nextShiftNumber())
                //     ->readOnly()
                //     ->dehydrated(false)
                //     ->helperText('يتم توليد رقم الشيفت تلقائيًا بشكل تسلسلي.'),
                // DatePicker::make('shift_date')
                //     ->label('تاريخ الشيفت')
                //     ->default(today())
                //     ->required(),
                // DateTimePicker::make('shift_from')
                //     ->label('بداية الشيفت')
                //     ->default(now())
                //     ->required(),
                // DateTimePicker::make('shift_to')
                //     ->label('نهاية الشيفت'),
                // TextInput::make('start_amount')
                //     ->label('رصيد البداية')
                //     ->numeric()
                //     ->default(fn (): float => (float) CashBox::where('is_active', true)->sum('current_balance'))
                //     ->required(),
                // TextInput::make('actual_amount')
                //     ->label('الرصيد الفعلي')
                //     ->numeric()
                //     ->default(0),
                // TextInput::make('status')
                //     ->label('الحالة')
                //     ->default('open')
                //     ->required(),
                // Toggle::make('is_active')
                //     ->label('نشط')
                //     ->default(true),
                // Toggle::make('notify')
                //     ->label('تم إرسال التنبيه')
                //     ->default(false),
            ]);
    }
}
