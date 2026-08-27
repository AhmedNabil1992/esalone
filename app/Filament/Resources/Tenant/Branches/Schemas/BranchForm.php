<?php

namespace App\Filament\Resources\Tenant\Branches\Schemas;

use App\Models\Tenant\Branch;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الفرع')
                    ->schema([
                        TextInput::make('name')
                            ->label('اسم الفرع')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('code')
                            ->label('كود الفرع')
                            ->default(function (): string {
                                $nextId = ((int) Branch::max('id')) + 1;

                                return 'BR-'.str_pad((string) $nextId, 3, '0', STR_PAD_LEFT);
                            })
                            ->readOnly()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->helperText('يتم توليد كود الفرع تلقائياً من النظام.'),
                        TextInput::make('phone')
                            ->label('رقم الهاتف')
                            ->tel()
                            ->nullable(),
                        TextInput::make('address')
                            ->label('العنوان')
                            ->nullable(),
                        Toggle::make('is_active')
                            ->label('حالة النشاط')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
