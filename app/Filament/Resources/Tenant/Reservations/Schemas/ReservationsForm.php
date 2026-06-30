<?php

namespace App\Filament\Resources\Tenant\Reservations\Schemas;

use App\Models\Tenant\Reservation;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ReservationsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('device_id')
                    ->label('الجهاز')
                    ->relationship('device', 'name')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn ($set) => $set('play_price_id', null))
                    ->default(fn ($operation) => $operation === 'create' ? request()->query('device_id') : null)
                    ->required(),

                Select::make('customer_id')
                    ->label('العميل')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),

                Select::make('play_price_id')
                    ->label('فئة السعر')
                    ->relationship('playPrice', 'description', function ($query, $get) {
                        return $query->where('device_id', $get('device_id'));
                    })
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->playType?->name} - {$record->description} ({$record->price} ج.م)")
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(fn ($get) => ! $get('device_id')),

                DateTimePicker::make('start_time')
                    ->label('وقت البدء')
                    ->required()
                    ->live()
                    ->default(fn ($operation) => $operation === 'create' && request()->query('start_time') ? Carbon::parse(request()->query('start_time')) : null)
                    ->minDate(fn ($operation) => $operation === 'create' ? now()->subMinutes(5) : null)
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        self::updateDuration($get, $set);
                    }),

                DateTimePicker::make('end_time')
                    ->label('وقت الانتهاء')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        self::updateDuration($get, $set);
                    })
                    ->rules([
                        fn (Get $get) => function (string $attribute, $value, $fail) use ($get) {
                            $deviceId = $get('device_id');
                            $startTime = $get('start_time');
                            $endTime = $value;

                            if (! $deviceId || ! $startTime || ! $endTime) {
                                return;
                            }

                            // Check for overlap of active reservations using the model helper
                            if (Reservation::checkOverlap($deviceId, $startTime, $endTime, $get('id'))) {
                                $fail('هذا الجهاز محجوز بالفعل في هذه الفترة الزمنية.');
                            }
                        },
                    ]),

                TextInput::make('duration_minutes')
                    ->label('المدة (بالدقائق)')
                    ->numeric()
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                        $start = $get('start_time');
                        if ($start && $state) {
                            try {
                                $startTime = Carbon::parse($start);
                                $endTime = $startTime->addMinutes((int) $state);
                                $set('end_time', $endTime->toDateTimeString());
                            } catch (\Exception $e) {
                                //
                            }
                        }
                    })
                    ->dehydrated()
                    ->helperText('يمكنك كتابة المدة بالدقائق لتحديد وقت الانتهاء، أو اختيار وقت البدء والانتهاء ليتم حساب المدة تلقائياً.'),
            ]);
    }

    protected static function updateDuration(Get $get, Set $set): void
    {
        $start = $get('start_time');
        $end = $get('end_time');

        if ($start && $end) {
            try {
                $startTime = Carbon::parse($start);
                $endTime = Carbon::parse($end);

                if ($endTime->greaterThan($startTime)) {
                    $set('duration_minutes', $startTime->diffInMinutes($endTime));
                } else {
                    $set('duration_minutes', 0);
                }
            } catch (\Exception $e) {
                $set('duration_minutes', null);
            }
        } else {
            $set('duration_minutes', null);
        }
    }
}
