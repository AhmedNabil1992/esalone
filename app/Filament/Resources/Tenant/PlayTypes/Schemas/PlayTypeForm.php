<?php

namespace App\Filament\Resources\Tenant\PlayTypes\Schemas;

use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

class PlayTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('device_type_id')
                    ->label('نوع الجهاز')
                    ->relationship('deviceType', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->label('إسم نوع اللعب')
                    ->required()
                    ->maxLength(20)
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, $get) => $rule->where('device_type_id', $get('device_type_id')),
                    )
                    ->placeholder('مثال: فردي، زوجي، لعب تنافسي...')
                    ->helperText('يجب أن يكون إسم نوع اللعب فريدًا لـنوع الجهاز ولا يتجاوز 20 حرفًا.'),
                TextInput::make('description')
                    ->label('وصف نوع اللعب')
                    ->maxLength(50)
                    ->placeholder('وصف مختصر لنوع اللعب')
                    ->helperText('يمكنك إضافة وصف مختصر لنوع اللعب لا يتجاوز 50 حرفًا.'),
                FileUpload::make('picture')
                    ->label('صورة نوع اللعب')
                    ->image()
                    ->required()
                    ->disk('public')
                    ->directory('play-types')
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
