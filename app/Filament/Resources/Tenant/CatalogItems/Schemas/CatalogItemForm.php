<?php

namespace App\Filament\Resources\Tenant\CatalogItems\Schemas;

use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Str;

class CatalogItemForm
{
    public static function configure(Schema $schema): Schema
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
                TextInput::make('min_quantity')
                    ->label('حد الأمان (الحد الأدنى)')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Toggle::make('is_product')
                    ->label('هل هذا صنف منتج؟')
                    ->helperText('حدد هذا الخيار إذا كان الصنف هو منتج نهائي يمكن بيعه للعملاء.'),
                Toggle::make('is_component')
                    ->label('هل هذا صنف مكون؟')
                    ->helperText('حدد هذا الخيار إذا كان الصنف هو مكون يستخدم في تكوين المنتجات.'),
                FileUpload::make('picture')
                    ->label('صورة الصنف')
                    ->image()
                    ->disk('public')
                    ->directory('catalog-items')
                    ->visibility('public')
                    ->getUploadedFileUsing(static function (BaseFileUpload $component, string $file, string|array|null $storedFileNames): ?array {
                        /** @var FilesystemAdapter $storage */
                        $storage = $component->getDisk();

                        if (! $storage->exists($file)) {
                            return null;
                        }

                        return [
                            'name' => (is_array($storedFileNames) ? ($storedFileNames[$file] ?? null) : $storedFileNames) ?? basename($file),
                            'size' => $storage->size($file),
                            'type' => $storage->mimeType($file),
                            'url' => Str::sanitizeUrl(tenant_asset($file)),
                        ];
                    })
                    ->imageEditor()
                    ->columnSpanFull(),
            ]);
    }
}
