<?php

namespace App\Filament\Resources\Tenant\WaitingLists\Schemas;

use App\Models\Tenant\DevicesType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WaitingListForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('customer_name')
                ->label('اسم العميل')
                ->required()
                ->maxLength(255),

            TextInput::make('customer_phone')
                ->label('رقم الهاتف')
                ->maxLength(50),

            Select::make('devices_type_id')
                ->label('نوع الجهاز المطلوب')
                ->options(fn (): array => DevicesType::query()
                    ->where('is_active', true)
                    ->orderBy('name', 'asc')
                    ->pluck('name', 'id')
                    ->all())
                ->required()
                ->searchable()
                ->preload(),

            Select::make('status')
                ->label('الحالة')
                ->options([
                    'waiting' => 'في الانتظار',
                    'playing' => 'يلعب حالياً',
                    'cancelled' => 'ملغي',
                ])
                ->default('waiting')
                ->required(),

            Textarea::make('notes')
                ->label('ملاحظات')
                ->maxLength(500)
                ->columnSpanFull(),
        ]);
    }
}
