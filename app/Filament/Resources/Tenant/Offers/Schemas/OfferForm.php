<?php

namespace App\Filament\Resources\Tenant\Offers\Schemas;

use App\Models\Tenant\Devices;
use App\Models\Tenant\PlayType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class OfferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label('اسم العرض')
                ->required()
                ->maxLength(255),

            Select::make('devices')
                ->label('الأجهزة')
                ->options(fn (): array => Devices::query()
                    ->where('is_active', true)
                    ->orderBy('name', 'asc')
                    ->pluck('name', 'name')
                    ->all())
                ->multiple()
                ->searchable()
                ->preload()
                ->formatStateUsing(fn ($state): array => self::explodeCsv($state))
                ->dehydrateStateUsing(fn ($state): string => self::implodeCsv($state))
                ->helperText('يمكنك اختيار جهاز واحد أو أكثر ليُطبَّق عليهم العرض.'),

            Select::make('play_type')
                ->label('نوع اللعب')
                ->options(fn (): array => PlayType::query()
                    ->orderBy('name', 'asc')
                    ->pluck('name', 'name')
                    ->all())
                ->searchable()
                ->preload()
                ->native(false)
                ->helperText('اتركه فارغًا إذا كان العرض ينطبق على كل أنواع اللعب.'),

            TimePicker::make('time_from')
                ->label('من وقت')
                ->seconds(),

            TimePicker::make('time_to')
                ->label('إلى وقت')
                ->seconds(),

            TextInput::make('period')
                ->label('المدة بالدقائق')
                ->numeric()
                ->minValue(1)
                ->helperText('اتركها فارغة إذا لم يكن العرض مرتبطًا بمدة محددة.'),

            TextInput::make('price')
                ->label('سعر العرض')
                ->numeric()
                ->minValue(0)
                ->step('0.01')
                ->required(),

            Textarea::make('remark')
                ->label('ملاحظات')
                ->rows(3)
                ->maxLength(255),

            Toggle::make('is_active')
                ->label('مفعّل')
                ->default(true),
        ]);
    }

    private static function explodeCsv(mixed $state): array
    {
        if (! is_string($state) || trim($state) === '') {
            return [];
        }

        return collect(explode(',', $state))
            ->map(static fn (string $item): string => trim($item))
            ->filter(static fn (string $item): bool => $item !== '')
            ->values()
            ->all();
    }

    private static function implodeCsv(mixed $state): string
    {
        if (! is_array($state)) {
            return '';
        }

        return collect($state)
            ->map(static fn (mixed $item): string => trim((string) $item))
            ->filter(static fn (string $item): bool => $item !== '')
            ->implode(', ');
    }
}
