<?php

namespace App\Filament\Resources\Tenant\StaffMembers\Schemas;

use Closure;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class StaffMemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('الاسم')
                    ->maxLength(120)
                    ->required(),
                TextInput::make('job_title')
                    ->label('التخصص')
                    ->placeholder('حلاق، مصفف شعر، أخصائية تجميل...')
                    ->maxLength(80),
                TextInput::make('phone')
                    ->label('رقم الهاتف')
                    ->tel()
                    ->maxLength(30),
                TextInput::make('email')
                    ->label('البريد الإلكتروني')
                    ->email()
                    ->maxLength(120),
                TextInput::make('commission_rate')
                    ->label('نسبة العمولة (%)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->step(0.01)
                    ->default(0)
                    ->required(),
                Section::make('الدوام الأسبوعي')
                    ->description('أضف فترات عمل الموظف. يمكن إضافة أكثر من فترة لليوم نفسه، ويُستخدم الجدول للتحقق من طلبات المواعيد.')
                    ->schema([
                        Repeater::make('weekly_schedule')
                            ->label('فترات الدوام')
                            ->schema([
                                Select::make('day_of_week')
                                    ->label('اليوم')
                                    ->options([
                                        0 => 'الأحد',
                                        1 => 'الاثنين',
                                        2 => 'الثلاثاء',
                                        3 => 'الأربعاء',
                                        4 => 'الخميس',
                                        5 => 'الجمعة',
                                        6 => 'السبت',
                                    ])
                                    ->required(),
                                TimePicker::make('opens_at')
                                    ->label('من')
                                    ->seconds(false)
                                    ->format('H:i')
                                    ->required()
                                    ->rules(['date_format:H:i']),
                                TimePicker::make('closes_at')
                                    ->label('إلى')
                                    ->seconds(false)
                                    ->format('H:i')
                                    ->required()
                                    ->rules([
                                        'date_format:H:i',
                                        fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                            $opensAt = $get('opens_at');

                                            if (is_string($value) && is_string($opensAt) && $value <= $opensAt) {
                                                $fail('يجب أن يكون وقت الانصراف بعد وقت الحضور.');
                                            }
                                        },
                                    ]),
                            ])
                            ->columns(3)
                            ->maxItems(14)
                            ->reorderable(false)
                            ->addActionLabel('إضافة فترة دوام'),
                    ])
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('يستقبل الحجوزات')
                    ->default(true)
                    ->required(),
            ]);
    }
}
