<?php

namespace App\Filament\Resources\Tenant\Devices\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlayPricesRelationManager extends RelationManager
{
    protected static string $relationship = 'playPrices';

    protected static ?string $title = 'أسعار اللعب';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('play_type_id')
                    ->label('نوع اللعب')
                    ->relationship('playType', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('description')
                    ->label('الوصف')
                    ->maxLength(255),
                TextInput::make('price')
                    ->label('السعر')
                    ->numeric()
                    ->required(),
                TextInput::make('start_from')
                    ->label('يبدأ من (دقيقة)')
                    ->numeric(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('playType.name')
                    ->label('نوع اللعب')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('الوصف')
                    ->searchable(),
                TextColumn::make('price')
                    ->label('السعر')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('start_from')
                    ->label('يبدأ من (دقيقة)')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()->label('أضافة سعر'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
