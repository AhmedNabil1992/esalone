<?php

namespace App\Filament\Resources\Tenant\InventoryGroups\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $title = 'الأصناف';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('inventory_group_id')
                    ->label('مجموعة الصنف')
                    ->relationship('inventoryGroup', 'name', fn ($query) => $query->where('is_active', 1))
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->label('اسم الصنف')
                    ->required()
                    ->maxLength(20),
                TextInput::make('description')
                    ->label('الوصف')
                    ->maxLength(50),
                TextInput::make('purchase_price')
                    ->label('سعر الشراء')
                    ->numeric()
                    ->required(),
                TextInput::make('sale_price')
                    ->label('سعر البيع')
                    ->numeric()
                    ->required(),
                Toggle::make('is_product')
                    ->label('هل هذا صنف منتج؟')
                    ->helperText('حدد هذا الخيار إذا كان الصنف هو منتج نهائي يمكن بيعه للعملاء.'),
                Toggle::make('is_component')
                    ->label('هل هذا صنف مكون؟')
                    ->helperText('حدد هذا الخيار إذا كان الصنف هو مكون يستخدم في تكوين المنتجات.'),

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('inventory_group_id')
            ->columns([
                TextColumn::make('name')
                    ->label('اسم الصنف')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('الوصف')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('purchase_price')
                    ->label('سعر الشراء')
                    ->money('usd', true)
                    ->searchable(),
                TextColumn::make('sale_price')
                    ->label('سعر البيع')
                    ->money('usd', true)
                    ->searchable(),
                ToggleColumn::make('is_product')
                    ->label('هل هذا صنف منتج؟')
                    ->onColor('success')
                    ->offColor('danger'),
                ToggleColumn::make('is_component')
                    ->label('هل هذا صنف مكون؟')
                    ->onColor('success')
                    ->offColor('danger'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()->label('أضافة صنف'),
                // AssociateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                // DissociateAction::make(),
                // DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // DissociateBulkAction::make(),
                    // DeleteBulkAction::make(),
                ]),
            ]);
    }
}
