<div class="relative flex items-center" x-data="{ open: false }">
    @if ($branches->count() > 0 || $isOwner)
        <button
            type="button"
            x-on:click="open = !open"
            x-on:click.outside="open = false"
            class="flex items-center gap-x-2 rounded-lg px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-white/5 transition border border-gray-200 dark:border-gray-800"
            title="تغيير الفرع الحالي"
        >
            <x-filament::icon icon="heroicon-o-building-storefront" class="h-4 w-4 text-primary-500" />

            <span class="truncate max-w-[120px] sm:max-w-[180px]">
                @if ($activeBranchId === 'all')
                    جميع الفروع (شامل)
                @else
                    {{ $branches->firstWhere('id', (int) $activeBranchId)?->name ?? 'اختر الفرع' }}
                @endif
            </span>

            <x-filament::icon icon="heroicon-m-chevron-down" class="h-3.5 w-3.5 text-gray-400" />
        </button>

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="transform opacity-100 scale-100"
            x-transition:leave-end="transform opacity-0 scale-95"
            style="display: none;"
            class="absolute left-0 top-full mt-2 w-56 rounded-xl bg-white p-1 shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 z-50 divide-y divide-gray-100 dark:divide-gray-800"
        >
            <div class="py-1">
                @if ($isOwner)
                    <button
                        type="button"
                        wire:click="switchBranch('all')"
                        x-on:click="open = false"
                        class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-medium transition {{ $activeBranchId === 'all' ? 'bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400' : 'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-white/5' }}"
                    >
                        <span class="flex items-center gap-x-2">
                            <x-filament::icon icon="heroicon-o-globe-alt" class="h-4 w-4 text-primary-500" />
                            جميع الفروع (شامل)
                        </span>
                        @if ($activeBranchId === 'all')
                            <x-filament::icon icon="heroicon-m-check" class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                        @endif
                    </button>
                @endif

                @foreach ($branches as $branch)
                    <button
                        type="button"
                        wire:click="switchBranch({{ $branch->id }})"
                        x-on:click="open = false"
                        class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-medium transition {{ (string) $activeBranchId === (string) $branch->id ? 'bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400' : 'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-white/5' }}"
                    >
                        <span class="flex items-center gap-x-2 truncate">
                            <x-filament::icon icon="heroicon-o-building-storefront" class="h-4 w-4 text-gray-400" />
                            <span class="truncate">{{ $branch->name }}</span>
                        </span>
                        @if ((string) $activeBranchId === (string) $branch->id)
                            <x-filament::icon icon="heroicon-m-check" class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    @endif
</div>
