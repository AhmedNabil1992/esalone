<?php

namespace App\Filament\Resources\Tenant\DeviceTypes\Schemas;

use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Str;

class DeviceTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->label('إسم نوع الجهاز')
                    ->maxLength(20)
                    ->columnSpanFull()
                    ->unique(ignoreRecord: true)
                    ->placeholder('مثال: بلايستيشن، كمبيوتر، بلياردو ، بنج بونج ، إكس بوكس...')
                    ->helperText('يجب أن يكون إسم نوع الجهاز فريدًا ولا يتجاوز 20 حرفًا.'),
                TextInput::make('description')
                    ->label('وصف نوع الجهاز')
                    ->maxLength(50)
                    ->columnSpanFull()
                    ->placeholder('وصف مختصر لنوع الجهاز')
                    ->helperText('يمكنك إضافة وصف مختصر لنوع الجهاز لا يتجاوز 50 حرفًا.'),
                FileUpload::make('picture')
                    ->label('صورة نوع الجهاز')
                    ->image()
                    ->disk('public')
                    ->directory('device-types')
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
