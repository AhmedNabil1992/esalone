<?php

namespace App\Filament\Resources\Tenant\CatalogItems\RelationManagers;

use App\Models\Tenant\CatalogItem;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ComponentsRelationManager extends RelationManager
{
    protected static string $relationship = 'components';

    protected static ?string $recordTitleAttribute = 'notes';

    protected static ?string $title = 'مكونات المنتج (BOM)';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) ($ownerRecord->is_product ?? false);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('component_id')
                    ->label('المكون (الخامة)')
                    ->options(fn () => CatalogItem::query()
                        ->where('is_component', true)
                        ->where('is_active', true)
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('quantity')
                    ->label('الكمية المطلوبة (لكل وحدة منتج)')
                    ->numeric()
                    ->minValue(0.001)
                    ->required(),
                TextInput::make('notes')
                    ->label('ملاحظات')
                    ->maxLength(100),
                TextInput::make('sort_order')
                    ->label('ترتيب العرض')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('component.name')
                    ->label('المكون (الخامة)')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('الكمية المطلوبة')
                    ->formatStateUsing(fn ($state) => (float) $state)
                    ->sortable(),
                TextColumn::make('notes')
                    ->label('ملاحظات')
                    ->limit(50),
                TextColumn::make('sort_order')
                    ->label('ترتيب العرض')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()->label('إضافة مكون'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->label('حذف'),
            ]);
    }
}
