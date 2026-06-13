<x-filament-panels::page>
    <div class="space-y-6" wire:poll.5s>

        {{-- ===== أنواع الأجهزة ===== --}}
        <div class="flex gap-3 overflow-x-auto pb-4 mb-2 border-b border-gray-200 dark:border-gray-700">
            @forelse ($this->deviceTypes as $type)
                <button
                    wire:click="selectType({{ $type->id }})"
                    type="button"
                    @class([
                        'flex-shrink-0 flex flex-col items-center gap-2 rounded-xl border-2 px-4 py-3 transition-all duration-150 focus:outline-none',
                        'border-primary-500 bg-primary-50 dark:bg-primary-950/60 shadow ring-2 ring-primary-300' => $selectedTypeId === $type->id,
                        'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-primary-300 hover:shadow-sm' => $selectedTypeId !== $type->id,
                    ])
                    style="width: 110px;"
                >
                    {{-- حاوية صورة ثابتة مع overflow-hidden --}}
                    <span class="flex items-center justify-center rounded-lg overflow-hidden bg-gray-50 dark:bg-gray-700/50" style="width:56px;height:56px;flex-shrink:0;">
                        @if ($type->picture)
                            <img
                                src="{{ tenant_asset($type->picture) }}"
                                alt="{{ $type->name }}"
                                style="width:56px;height:56px;object-fit:contain;display:block;"
                            />
                        @else
                            <x-heroicon-o-computer-desktop class="w-8 h-8 text-gray-400" />
                        @endif
                    </span>
                    <span @class([
                        'text-xs font-semibold text-center leading-tight',
                        'text-primary-700 dark:text-primary-300' => $selectedTypeId === $type->id,
                        'text-gray-600 dark:text-gray-300' => $selectedTypeId !== $type->id,
                    ]) style="max-width:90px;word-break:break-word;">
                        {{ $type->name }}
                    </span>
                </button>
            @empty
                <p class="text-sm text-gray-400 py-2">لا توجد أنواع أجهزة مفعّلة.</p>
            @endforelse
        </div>

        {{-- ===== كروت الأجهزة ===== --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-4 2xl:grid-cols-5 gap-5">
            @forelse ($this->devices as $device)
                @php
                    $isBusy  = $device->status === 'busy';
                    $isInactive = $device->status === 'inactive';
                @endphp
                <div
                    @class([
                        'relative flex flex-col rounded-2xl border-2 shadow-sm overflow-hidden transition-all duration-200',
                        'border-red-400 bg-red-50 dark:bg-red-950/40'  => $isBusy,
                        'border-green-400 bg-green-50 dark:bg-green-950/40' => ! $isBusy && ! $isInactive,
                        'border-gray-300 bg-gray-50 dark:bg-gray-800 opacity-60' => $isInactive,
                    ])
                >
                    {{-- شريط اللون العلوي --}}
                    <div @class([
                        'h-1.5 w-full',
                        'bg-red-500'   => $isBusy,
                        'bg-green-500' => ! $isBusy && ! $isInactive,
                        'bg-gray-400'  => $isInactive,
                    ])></div>

                    <div class="p-4 flex flex-col gap-3">

                        {{-- اسم الجهاز + أيقونة الحالة --}}
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-base text-gray-800 dark:text-gray-100 truncate">
                                {{ $device->name }}
                            </span>
                            @if ($isBusy)
                                <span class="inline-flex items-center gap-1 rounded-full bg-red-100 dark:bg-red-900 px-2 py-0.5 text-xs font-semibold text-red-700 dark:text-red-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                                    مشغول
                                </span>
                            @elseif ($isInactive)
                                <span class="inline-flex items-center gap-1 rounded-full bg-gray-200 dark:bg-gray-700 px-2 py-0.5 text-xs font-semibold text-gray-500">
                                    معطّل
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-green-100 dark:bg-green-900 px-2 py-0.5 text-xs font-semibold text-green-700 dark:text-green-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                    متاح
                                </span>
                            @endif
                        </div>

                        {{-- منطقة الصورة: ارتفاع ثابت + overflow-hidden دائماً --}}
                        <div
                            @class([
                                'mx-1 rounded-xl overflow-hidden flex items-center justify-center',
                                'bg-red-100 dark:bg-red-900/30'     => $isBusy,
                                'bg-green-100 dark:bg-green-900/30' => ! $isBusy && ! $isInactive,
                                'bg-gray-200 dark:bg-gray-700'      => $isInactive,
                            ])
                            style="height:120px;"
                        >
                            @if ($device->type?->picture)
                                <img
                                    src="{{ tenant_asset($device->type->picture) }}"
                                    alt="{{ $device->type->name }}"
                                    style="max-width:100%;max-height:108px;object-fit:contain;display:block;"
                                />
                            @else
                                <x-heroicon-o-computer-desktop @class([
                                    'w-16 h-16',
                                    'text-red-300 dark:text-red-500'   => $isBusy,
                                    'text-green-300 dark:text-green-500' => ! $isBusy && ! $isInactive,
                                    'text-gray-400' => $isInactive,
                                ]) />
                            @endif
                        </div>

                        {{-- معلومات الوقت (تُملأ لاحقاً من جلسة اللعب النشطة) --}}
                        <div class="grid grid-cols-2 gap-1 text-xs text-gray-600 dark:text-gray-400">
                            <div>
                                <p class="text-gray-400 dark:text-gray-500">بداية الوقت</p>
                                <p class="font-medium text-gray-700 dark:text-gray-200">
                                    {{-- يُعبَّأ من جلسة اللعب النشطة مستقبلاً --}}
                                    &mdash;
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-400 dark:text-gray-500">نهاية الوقت</p>
                                <p class="font-medium text-gray-700 dark:text-gray-200">&mdash;</p>
                            </div>
                            <div>
                                <p class="text-gray-400 dark:text-gray-500">مدة اللعب</p>
                                <p class="font-medium text-gray-700 dark:text-gray-200">&mdash;</p>
                            </div>
                            <div>
                                <p class="text-gray-400 dark:text-gray-500">الوقت المنقضي</p>
                                <p class="font-medium text-gray-700 dark:text-gray-200">&mdash;</p>
                            </div>
                        </div>

                        {{-- أزرار نوع اللعب (فردي / زوجي) --}}
                        @unless ($isBusy || $isInactive)
                            <div class="flex gap-2 pt-1">
                                <button
                                    disabled
                                    class="flex-1 flex items-center justify-center gap-1 rounded-lg border border-green-300 bg-white dark:bg-green-900/20 py-1.5 text-xs font-semibold text-green-700 dark:text-green-300 cursor-not-allowed opacity-70"
                                >
                                    <x-heroicon-o-user class="w-3.5 h-3.5" />
                                    فردي
                                </button>
                                <button
                                    disabled
                                    class="flex-1 flex items-center justify-center gap-1 rounded-lg border border-green-300 bg-white dark:bg-green-900/20 py-1.5 text-xs font-semibold text-green-700 dark:text-green-300 cursor-not-allowed opacity-70"
                                >
                                    <x-heroicon-o-user-group class="w-3.5 h-3.5" />
                                    زوجي
                                </button>
                            </div>
                        @endunless

                        @if ($isBusy)
                            <button
                                disabled
                                class="mt-1 w-full rounded-lg border border-red-300 bg-white dark:bg-red-900/20 py-1.5 text-xs font-semibold text-red-700 dark:text-red-300 cursor-not-allowed opacity-70"
                            >
                                إيقاف الجلسة
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full flex flex-col items-center justify-center py-16 text-gray-400">
                    <x-heroicon-o-computer-desktop class="w-12 h-12 mb-3" />
                    <p class="text-sm">لا توجد أجهزة مفعّلة في هذا النوع.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
