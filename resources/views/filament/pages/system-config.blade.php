<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-start gap-3">
            <x-filament::button type="submit" size="lg" icon="heroicon-m-device-phone-mobile">
                حفظ الإعدادات بالكامل
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>