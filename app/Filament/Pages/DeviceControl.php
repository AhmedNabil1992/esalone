<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Actions\Tenant\StartSessionAction;
use App\Models\Tenant\Devices;
use App\Models\Tenant\DevicesType;
use App\Models\Tenant\User;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class DeviceControl extends Page
{
    protected static ?string $title = 'التحكم في الأجهزة';

    protected static ?string $navigationLabel = 'التحكم في الأجهزة';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-computer-desktop';

    protected static string|\UnitEnum|null $navigationGroup = 'اللعب';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.device-control';

    public ?int $selectedTypeId = null;

    public function mount(): void
    {
        $first = DevicesType::query()
            ->where('is_active', true)
            ->orderBy('id', 'asc')
            ->first();

        $this->selectedTypeId = $first?->id;
    }

    public function selectType(int $typeId): void
    {
        $this->selectedTypeId = $typeId;
    }

    /** @return Collection<int, DevicesType> */
    public function getDeviceTypesProperty(): Collection
    {
        return DevicesType::query()
            ->where('is_active', true)
            ->orderBy('id', 'asc')
            ->get();
    }

    /** @return Collection<int, Devices> */
    public function getDevicesProperty(): Collection
    {
        if (! $this->selectedTypeId) {
            return collect();
        }

        return Devices::query()
            ->where('devices_type_id', $this->selectedTypeId)
            ->where('is_active', true)
            ->with(['type', 'playPrices.playType'])
            ->orderBy('name', 'asc')
            ->get();
    }

    public function startSession(int $deviceId, int $playPriceId): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user instanceof User) {
            Notification::make()->title('غير مصرح')->danger()->send();

            return;
        }

        try {
            app(StartSessionAction::class)->execute($deviceId, $playPriceId, $user);

            Notification::make()
                ->title('تم فتح الجلسة بنجاح')
                ->success()
                ->send();
        } catch (RuntimeException $e) {
            Notification::make()
                ->title('فشل فتح الجلسة')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function getNavigationBadge(): ?string
    {
        $busy = Devices::query()->where('status', 'busy')->count('*');

        return $busy > 0 ? (string) $busy : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
