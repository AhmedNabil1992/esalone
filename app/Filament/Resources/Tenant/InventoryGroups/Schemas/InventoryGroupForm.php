<?php

namespace App\Filament\Resources\Tenant\InventoryGroups\Schemas;

use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Str;

class InventoryGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('إسم المجموعة')
                    ->required(),
                TextInput::make('description')
                    ->label('الوصف')
                    ->helperText('وصف المجموعة (اختياري)'),
                FileUpload::make('picture')
                    ->label('صورة مجموعة الصنف')
                    ->image()
                    ->disk('public')
                    ->directory('inventory-groups')
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
