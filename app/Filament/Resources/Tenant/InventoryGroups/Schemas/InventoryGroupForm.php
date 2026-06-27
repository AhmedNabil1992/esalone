<?php

namespace App\Filament\Resources\Tenant\InventoryGroups\Schemas;

use App\Actions\Tenant\SendDeviceControlCommandViaBridgeAction;
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
        try {
            app(SendDeviceControlCommandViaBridgeAction::class)->fetchPrinters();

            Notification::make()
                ->title('تم تحديث قائمة الطابعات بنجاح')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('فشل الاتصال بالـ Bridge')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function getPrintersOptions(): array
    {
        try {
            $printers = app(SendDeviceControlCommandViaBridgeAction::class)->fetchPrinters();
            if (is_array($printers) && $printers !== []) {
                return array_combine($printers, $printers);
            }
        } catch (\Throwable) {
            // Gracefully handle bridge offline or connection errors
        }

        return [];
    }
}
