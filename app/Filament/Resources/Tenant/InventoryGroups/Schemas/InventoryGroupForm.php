<?php

namespace App\Filament\Resources\Tenant\InventoryGroups\Schemas;

use App\Models\Tenant\SystemConfig as Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
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
                Select::make('printer_name')
                    ->label('طابعة الأوردرات : ')
                    ->searchable()
                    ->options(fn () => self::getPrintersOptions())
                    ->suffixAction(
                        Action::make('fetchOrderPrinters')
                            ->icon('heroicon-m-arrow-path')
                            ->action(fn () => self::refreshPrintersList())
                    ),
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

    public static function refreshPrintersList(): void
    {
        Notification::make()
            ->title('تحديث قائمة الطابعات')
            ->body('يمكنك تحديث قائمة الطابعات مباشرة من شاشة "إعدادات النظام".')
            ->info()
            ->send();
    }

    public static function getPrintersOptions(): array
    {
        $printers = Setting::get('sys_available_printers');
        if (! $printers) {
            return [];
        }

        $decoded = json_decode($printers, true);
        if (! is_array($decoded)) {
            return [];
        }

        return array_combine($decoded, $decoded);
    }
}
