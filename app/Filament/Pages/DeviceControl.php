<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Tenant\Devices;
use App\Models\Tenant\DevicesType;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

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
            ->orderBy('name', 'asc')
            ->get();
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
