<x-filament-panels::page>
    <div dir="rtl" wire:poll.10000ms style="display:flex;flex-direction:column;gap:20px;">

        {{-- ===== تبويبات أنواع الأجهزة ===== --}}
        <div style="display:flex;gap:10px;overflow-x:auto;padding-bottom:4px;border-bottom:2px solid #e5e7eb;">
            @forelse ($this->deviceTypes as $type)
                <button
                    wire:click="selectType({{ $type->id }})"
                    type="button"
                    style="
                        flex-shrink:0;
                        display:flex;
                        flex-direction:column;
                        align-items:center;
                        gap:8px;
                        padding:10px 16px;
                        border-radius:14px;
                        border:2px solid {{ $selectedTypeId === $type->id ? '#6366f1' : '#e5e7eb' }};
                        background:{{ $selectedTypeId === $type->id ? '#eef2ff' : '#ffffff' }};
                        box-shadow:{{ $selectedTypeId === $type->id ? '0 0 0 3px #c7d2fe' : 'none' }};
                        cursor:pointer;
                        transition:all .15s;
                        min-width:90px;
                    "
                >
                    <span style="width:52px;height:52px;border-radius:10px;overflow:hidden;display:flex;align-items:center;justify-content:center;background:#f3f4f6;flex-shrink:0;">
                        @if ($type->picture)
                            <img src="{{ tenant_asset($type->picture) }}" alt="{{ $type->name }}"
                                 style="width:52px;height:52px;object-fit:contain;display:block;" />
                        @else
                            <svg style="width:28px;height:28px;color:#9ca3af;" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/>
                            </svg>
                        @endif
                    </span>
                    <span style="font-size:.75rem;font-weight:700;color:{{ $selectedTypeId === $type->id ? '#4338ca' : '#374151' }};text-align:center;line-height:1.3;max-width:80px;word-break:break-word;">
                        {{ $type->name }}
                    </span>
                </button>
            @empty
                <p style="font-size:.875rem;color:#9ca3af;padding:8px 0;">لا توجد أنواع أجهزة مفعّلة.</p>
            @endforelse
        </div>

        {{-- ===== شريط الإحصائيات ===== --}}
        @php
            $allDevices  = $this->devices;
            $busyCount   = $allDevices->where('status', 'busy')->count();
            $availCount  = $allDevices->where('status', 'active')->count();
            $total       = $allDevices->count();
        @endphp
        @if ($allDevices->isNotEmpty())
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
            <div style="display:flex;align-items:center;gap:8px;">
                <span style="background:#22c55e;color:#fff;font-size:.72rem;font-weight:700;padding:3px 12px;border-radius:9999px;">
                    متاح {{ $availCount }}
                </span>
                <span style="background:#ef4444;color:#fff;font-size:.72rem;font-weight:700;padding:3px 12px;border-radius:9999px;">
                    مشغول {{ $busyCount }}
                </span>
                <span style="color:#6b7280;font-size:.75rem;">
                    / {{ $total }} جهاز
                </span>
            </div>
            <span style="font-size:.7rem;color:#9ca3af;display:flex;align-items:center;gap:4px;">
                <svg style="width:12px;height:12px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M4 9a8 8 0 0 1 16 0M20 15a8 8 0 0 1-16 0"/>
                </svg>
                تحديث كل 10 ث
            </span>
        </div>
        @endif

        {{-- ===== شبكة الكروت ===== --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(200px, 1fr));gap:16px;">
            @forelse ($allDevices as $device)
                @php
                    $isBusy  = $device->status === 'busy';
                    $cardBg  = $isBusy ? '#dc2626' : '#16a34a';
                    $statusLabel = $isBusy ? 'مشغول' : 'متاح';
                @endphp

                <div
                    wire:key="device-{{ $device->id }}"
                    @if ($isBusy)
                        wire:click="openBusyActionsModal({{ $device->id }})"
                    @else
                        wire:click="openStartSessionModal({{ $device->id }})"
                    @endif
                    style="
                        border-radius:18px;
                        overflow:hidden;
                        box-shadow:0 4px 16px rgba(0,0,0,.18);
                        background:{{ $cardBg }};
                        color:#fff;
                        display:flex;
                        flex-direction:column;
                        position:relative;
                        cursor:{{ $isBusy ? 'default' : 'pointer' }};
                    "
                >
                    {{-- رأس الكارت: اسم + حالة --}}
                    <div style="padding:14px 14px 10px;border-bottom:1px solid rgba(255,255,255,.18);display:flex;align-items:center;justify-content:space-between;gap:8px;">
                        <span style="font-size:1rem;font-weight:800;line-height:1.2;flex:1;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">
                            {{ $device->name }}
                        </span>
                        <span style="background:rgba(255,255,255,.22);font-size:.65rem;font-weight:700;padding:2px 9px;border-radius:9999px;white-space:nowrap;flex-shrink:0;display:inline-flex;align-items:center;gap:4px;">
                            @if ($isBusy)
                                <span style="width:6px;height:6px;border-radius:9999px;background:#fca5a5;display:inline-block;"></span>
                            @else
                                <span style="width:6px;height:6px;border-radius:9999px;background:rgba(255,255,255,.9);display:inline-block;"></span>
                            @endif
                            {{ $statusLabel }}
                        </span>
                    </div>

                    {{-- جسم الكارت --}}
                    <div style="padding:12px 14px;flex:1;display:flex;flex-direction:column;gap:10px;">

                        @if ($isBusy)
                            @php
                                $activeDetail = $device->activeSession?->latestDetail;
                                $startTime = $activeDetail?->start_time;
                                $endTime = $activeDetail?->end_time;
                                $period = $activeDetail?->period;
                                $isTimed = $period !== null && $endTime !== null;

                                $secondsValue = 0;
                                if ($startTime) {
                                    if ($isTimed) {
                                        $secondsValue = now()->lt($endTime)
                                            ? now()->diffInSeconds($endTime)
                                            : 0;
                                    } else {
                                        $secondsValue = $startTime->diffInSeconds(now());
                                    }
                                }

                                $hours = intdiv($secondsValue, 3600);
                                $minutes = intdiv(($secondsValue % 3600), 60);
                                $runningCounter = sprintf('%02d:%02d', $hours, $minutes);

                                $playDuration = $isTimed ? ((string) $period . ' د') : 'مفتوح';

                                $activePlayTypeName = trim((string) ($activeDetail?->play_type ?? ''));
                                $activePlayType = $device->playTypes->first(function ($playType) use ($activePlayTypeName) {
                                    return trim((string) $playType->name) === $activePlayTypeName;
                                });
                            @endphp

                            {{-- وقت البداية والنهاية --}}
                            <div style="display:grid;grid-template-columns:1fr 1px 1fr;align-items:center;gap:8px;width:100%;">
                                <div style="text-align:center;">
                                    <div style="font-size:.6rem;opacity:.7;font-weight:600;margin-bottom:2px;">بداية الوقت</div>
                                    <div style="font-size:.85rem;font-weight:800;font-family:monospace;direction:ltr;unicode-bidi:isolate;">{{ $startTime?->format('h:i A') ?? '—' }}</div>
                                </div>
                                <div style="width:1px;background:rgba(255,255,255,.25);"></div>
                                <div style="text-align:center;">
                                    <div style="font-size:.6rem;opacity:.7;font-weight:600;margin-bottom:2px;">نهاية الوقت</div>
                                    <div style="font-size:.85rem;font-weight:800;font-family:monospace;direction:ltr;unicode-bidi:isolate;">{{ $isTimed ? ($endTime?->format('h:i A') ?? '—') : '—' }}</div>
                                </div>
                            </div>

                            {{-- مدة اللعب + المنقضي/المتبقي --}}
                            <div style="background:rgba(0,0,0,.2);border-radius:12px;padding:10px;display:grid;grid-template-columns:1fr 1px 1fr;align-items:center;gap:8px;width:100%;">
                                <div style="text-align:center;">
                                    <div style="font-size:.58rem;opacity:.7;font-weight:600;margin-bottom:3px;">مدة اللعب</div>
                                    <div style="font-size:.9rem;font-weight:800;font-family:monospace;letter-spacing:.5px;">{{ $playDuration }}</div>
                                </div>
                                <div style="width:1px;background:rgba(255,255,255,.2);"></div>
                                <div style="text-align:center;">
                                    <div style="font-size:.58rem;opacity:.7;font-weight:600;margin-bottom:3px;">{{ $isTimed ? 'الوقت المتبقي' : 'الوقت المنقضي' }}</div>
                                    <div style="font-size:.9rem;font-weight:800;font-family:monospace;letter-spacing:.5px;color:#fef08a;direction:ltr;unicode-bidi:isolate;">{{ $runningCounter }}</div>
                                </div>
                            </div>

                            {{-- صورة نوع اللعب الحالي --}}
                            <div style="display:flex;align-items:center;justify-content:center;padding-top:2px;">
                                <div style="width:64px;height:64px;border-radius:12px;overflow:hidden;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.22);">
                                    @if ($activePlayType?->picture)
                                        <img
                                            src="{{ tenant_asset($activePlayType->picture) }}"
                                            alt="{{ $activePlayType->name ?? 'نوع اللعب' }}"
                                            style="max-width:58px;max-height:58px;object-fit:contain;display:block;"
                                        />
                                    @else
                                        <svg style="width:30px;height:30px;opacity:.7;" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9V5.25A2.25 2.25 0 0012 3h0a2.25 2.25 0 00-2.25 2.25V9m-3 0h10.5A2.25 2.25 0 0119.5 11.25v3A2.25 2.25 0 0117.25 16.5H6.75A2.25 2.25 0 014.5 14.25v-3A2.25 2.25 0 016.75 9z"/>
                                        </svg>
                                    @endif
                                </div>
                            </div>

                            {{-- زر تقفيل الحساب --}}
                            <button
                                type="button"
                                wire:click.stop="openCloseInvoiceModal({{ $device->id }})"
                                style="width:100%;display:flex;align-items:center;justify-content:center;gap:6px;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);color:#fff;font-size:.78rem;font-weight:700;padding:8px;border-radius:10px;cursor:pointer;opacity:.95;margin-top:auto;"
                            >
                                <svg style="width:14px;height:14px;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 7.5A2.25 2.25 0 0 1 7.5 5.25h9a2.25 2.25 0 0 1 2.25 2.25v9a2.25 2.25 0 0 1-2.25 2.25h-9a2.25 2.25 0 0 1-2.25-2.25v-9z"/>
                                </svg>
                                تقفيل الحساب
                            </button>

                        @else
                            {{-- صورة نوع الجهاز --}}
                            <div style="display:flex;align-items:center;justify-content:center;padding:4px 0;">
                                <div style="width:72px;height:72px;border-radius:14px;overflow:hidden;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.15);">
                                    @if ($device->type?->picture)
                                        <img src="{{ tenant_asset($device->type->picture) }}"
                                             alt="{{ $device->type->name }}"
                                             style="max-width:68px;max-height:68px;object-fit:contain;display:block;" />
                                    @else
                                        <svg style="width:36px;height:36px;opacity:.6;" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/>
                                        </svg>
                                    @endif
                                </div>
                            </div>

                            {{-- أزرار أنواع اللعب الديناميكية من PlayPrice --}}
                            @if ($device->playPrices->isNotEmpty())
                                <div style="display:flex;flex-direction:column;gap:6px;margin-top:auto;">
                                    @foreach ($device->playPrices as $pp)
                                        <button
                                            wire:click.stop="startSession({{ $device->id }}, {{ $pp->id }})"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="opacity-60 cursor-wait"
                                            wire:target="startSession({{ $device->id }}, {{ $pp->id }})"
                                            type="button"
                                            title="{{ $pp->description ?? $pp->playType?->name }}"
                                            style="
                                                width:100%;
                                                display:flex;
                                                align-items:center;
                                                justify-content:space-between;
                                                gap:6px;
                                                background:rgba(255,255,255,.22);
                                                border:1px solid rgba(255,255,255,.35);
                                                color:#fff;
                                                font-size:.78rem;
                                                font-weight:700;
                                                padding:8px 12px;
                                                border-radius:10px;
                                                cursor:pointer;
                                                text-align:right;
                                                transition:background .15s;
                                            "
                                            onmouseover="this.style.background='rgba(255,255,255,.35)'"
                                            onmouseout="this.style.background='rgba(255,255,255,.22)'"
                                        >
                                            <span style="display:flex;align-items:center;gap:5px;overflow:hidden;">
                                                <span wire:loading wire:target="startSession({{ $device->id }}, {{ $pp->id }})">
                                                    <svg style="width:13px;height:13px;flex-shrink:0;animation:spin 1s linear infinite;" fill="none" viewBox="0 0 24 24">
                                                        <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                                        <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                                    </svg>
                                                </span>
                                                <span wire:loading.remove wire:target="startSession({{ $device->id }}, {{ $pp->id }})">
                                                    <svg style="width:13px;height:13px;flex-shrink:0;opacity:.85;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z"/>
                                                    </svg>
                                                </span>
                                                <span style="overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">
                                                    {{ $pp->playType?->name ?? '—' }}
                                                    @if ($pp->description)
                                                        <span style="opacity:.75;font-size:.7rem;"> – {{ $pp->description }}</span>
                                                    @endif
                                                </span>
                                            </span>
                                            <span style="flex-shrink:0;background:rgba(0,0,0,.2);border-radius:6px;padding:2px 7px;font-size:.72rem;font-family:monospace;">
                                                {{ number_format((float) $pp->price, 0) }}
                                            </span>
                                        </button>
                                    @endforeach
                                </div>
                            @else
                                <div style="margin-top:auto;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.3);border-radius:10px;padding:8px 10px;font-size:.75rem;font-weight:700;text-align:center;">
                                    اضغط على الكارت لفتح الجلسة
                                </div>
                            @endif
                        @endif

                    </div>
                </div>
            @empty
                <div style="grid-column:1/-1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:80px 0;color:#9ca3af;gap:12px;">
                    <svg style="width:56px;height:56px;opacity:.3;" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/>
                    </svg>
                    <p style="font-size:.875rem;">لا توجد أجهزة مفعّلة في هذا النوع.</p>
                </div>
            @endforelse
        </div>

        @if ($showBusyActionsModal)
            @php
                $busyDevice = $this->selectedBusyDevice;
                $busyLatestDetail = $busyDevice?->activeSession?->latestDetail;
                $busyIsTimed = $busyLatestDetail?->period !== null && $busyLatestDetail?->end_time !== null;
                $busyActions = [
                    ['label' => 'إضافة طلبات', 'icon' => 'heroicon-o-plus-circle'],
                    ['label' => 'إضافة وقت', 'icon' => 'heroicon-o-clock'],
                    ['label' => 'تحديد الوقت', 'icon' => 'heroicon-o-adjustments-horizontal'],
                    ['label' => 'بيانات الجهاز', 'icon' => 'heroicon-o-information-circle'],
                    ['label' => 'تبديل أجهزة', 'icon' => 'heroicon-o-arrows-right-left'],
                    ['label' => 'تبديل اللعب', 'icon' => 'heroicon-o-arrow-path'],
                    ['label' => 'مراجعة', 'icon' => 'heroicon-o-document-magnifying-glass'],
                    ['label' => 'إضافة فلوس', 'icon' => 'heroicon-o-banknotes'],
                ];
            @endphp

            <div style="position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:70;display:flex;align-items:center;justify-content:center;padding:16px;" wire:click="closeBusyActionsModal">
                <div style="width:min(560px,100%);max-height:90vh;overflow:auto;background:#fff;border-radius:18px;box-shadow:0 24px 64px rgba(0,0,0,.35);padding:18px;" wire:click.stop>
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:14px;">
                        <div>
                            <div style="font-size:1.05rem;font-weight:800;color:#111827;">{{ $busyDevice?->name ?? 'الجهاز' }}</div>
                            <div style="font-size:.76rem;color:#6b7280;margin-top:2px;">خيارات الجهاز</div>
                        </div>
                        <button type="button" wire:click="closeBusyActionsModal" style="width:34px;height:34px;border-radius:10px;border:1px solid #e5e7eb;background:#f9fafb;cursor:pointer;">
                            ✕
                        </button>
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;">
                        @foreach ($busyActions as $action)
                            <button
                                type="button"
                                wire:click="openBusyActionDetails('{{ $action['label'] }}')"
                                style="display:flex;align-items:center;gap:8px;border:1px solid #e5e7eb;background:#fff;border-radius:12px;padding:10px 12px;cursor:pointer;text-align:right;font-size:.82rem;font-weight:700;color:#111827;"
                            >
                                <x-filament::icon :icon="$action['icon']" style="width:18px;height:18px;color:#2563eb;" />
                                <span>{{ $action['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @if ($showBusyActionDetailsModal)
            @php
                $detailModalWidth = $selectedBusyActionLabel === 'بيانات الجهاز'
                    ? 'min(95vw, 1100px)'
                    : ($selectedBusyActionLabel === 'تقفيل الحساب' ? 'min(96vw, 1180px)' : 'min(92vw, 460px)');
            @endphp
            <div style="position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:71;display:flex;align-items:center;justify-content:center;padding:12px;" wire:click="closeBusyActionDetailsModal">
                <div style="width:{{ $detailModalWidth }};max-height:92vh;overflow-y:auto;overflow-x:hidden;background:#fff;border-radius:16px;box-shadow:0 24px 64px rgba(0,0,0,.35);padding:18px 20px;" wire:click.stop>
                    <div style="font-size:1rem;font-weight:800;color:#111827;margin-bottom:8px;">{{ $selectedBusyActionLabel }}</div>

                    @if ($selectedBusyActionLabel === 'تقفيل الحساب')
                        @php
                            $closePreview = $this->busyClosePreview;
                            $timeRows = collect($closePreview['time_rows'] ?? []);
                            $orderRows = collect($closePreview['order_rows'] ?? []);
                        @endphp

                        @if (! $closePreview)
                            <div style="padding:10px;border:1px dashed #d1d5db;border-radius:10px;text-align:center;color:#6b7280;font-size:.82rem;">
                                تعذر تحميل بيانات التقفيل حالياً. حاول مرة أخرى.
                            </div>
                            <div style="display:flex;justify-content:flex-end;margin-top:10px;">
                                <button type="button" wire:click="closeBusyActionDetailsModal" style="border:1px solid #d1d5db;background:#fff;color:#111827;padding:8px 12px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                    إغلاق
                                </button>
                            </div>
                        @else
                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:12px;align-items:start;">
                                <div style="border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                                    <div style="padding:10px 12px;background:#f0fdf4;color:#15803d;font-size:.82rem;font-weight:800;">
                                        تفاصيل الوقت
                                    </div>
                                    <div style="max-height:320px;overflow:auto;">
                                        <table style="width:100%;border-collapse:collapse;font-size:.76rem;">
                                            <thead style="background:#f9fafb;color:#374151;">
                                                <tr>
                                                    <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:right;">نوع اللعب</th>
                                                    <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;">الفئة</th>
                                                    <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;">البداية</th>
                                                    <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;">النهاية</th>
                                                    <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;">المدة</th>
                                                    <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;">القيمة</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($timeRows as $row)
                                                    <tr>
                                                        <td style="padding:8px;border-bottom:1px solid #f3f4f6;color:#111827;">{{ $row['play_type'] ?: '—' }}</td>
                                                        <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;color:#374151;">{{ $row['play_category'] ?: '—' }}</td>
                                                        <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;direction:ltr;unicode-bidi:isolate;color:#374151;">{{ isset($row['start_time']) && $row['start_time'] ? \Illuminate\Support\Carbon::parse($row['start_time'])->format('h:i A') : '—' }}</td>
                                                        <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;direction:ltr;unicode-bidi:isolate;color:#374151;">{{ isset($row['end_time']) && $row['end_time'] ? \Illuminate\Support\Carbon::parse($row['end_time'])->format('h:i A') : '—' }}</td>
                                                        <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;color:#111827;">{{ ($row['period'] ?? 0) > 0 ? ($row['period'] . ' د') : 'مفتوح' }}</td>
                                                        <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;color:#16a34a;font-weight:700;direction:ltr;unicode-bidi:isolate;">{{ number_format((float) ($row['cost'] ?? 0), 2) }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="6" style="padding:16px;text-align:center;color:#6b7280;">لا توجد تفاصيل وقت.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div style="border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                                    <div style="padding:10px 12px;background:#eff6ff;color:#1d4ed8;font-size:.82rem;font-weight:800;">
                                        تفاصيل الطلبات
                                    </div>
                                    <div style="max-height:320px;overflow:auto;">
                                        <table style="width:100%;border-collapse:collapse;font-size:.76rem;">
                                            <thead style="background:#f9fafb;color:#374151;">
                                                <tr>
                                                    <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:right;">الصنف</th>
                                                    <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;">الكمية</th>
                                                    <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;">السعر</th>
                                                    <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;">الإجمالي</th>
                                                    <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;">الحالة</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($orderRows as $row)
                                                    <tr>
                                                        <td style="padding:8px;border-bottom:1px solid #f3f4f6;color:#111827;">{{ $row['item_name'] ?? '—' }}</td>
                                                        <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;color:#374151;">{{ $row['quantity'] ?? 0 }}</td>
                                                        <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;color:#374151;direction:ltr;unicode-bidi:isolate;">{{ number_format((float) ($row['price'] ?? 0), 2) }}</td>
                                                        <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;color:#16a34a;font-weight:700;direction:ltr;unicode-bidi:isolate;">{{ number_format((float) ($row['total'] ?? 0), 2) }}</td>
                                                        <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;color:#6b7280;">{{ $row['status'] ?? '—' }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="5" style="padding:16px;text-align:center;color:#6b7280;">لا توجد طلبات على الجلسة.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div style="margin-top:14px;border:1px solid #e5e7eb;border-radius:12px;padding:12px;display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;">
                                <div style="font-size:.78rem;color:#374151;grid-column:1/-1;">
                                    الخزينة المستهدفة: <strong style="color:{{ data_get($closePreview, 'cash_box.name') ? '#111827' : '#dc2626' }};">{{ data_get($closePreview, 'cash_box.name', 'غير محددة') }}</strong>
                                </div>
                                <div style="font-size:.78rem;color:#374151;">وقت اللعب: <strong>{{ number_format((float) ($closePreview['play_amount'] ?? 0), 2) }}</strong></div>
                                <div style="font-size:.78rem;color:#374151;">الطلبات: <strong>{{ number_format((float) ($closePreview['order_amount'] ?? 0), 2) }}</strong></div>
                                <div style="font-size:.78rem;color:#374151;">الإجمالي: <strong>{{ number_format((float) ($closePreview['subtotal'] ?? 0), 2) }}</strong></div>
                                <div style="font-size:.78rem;color:#374151;">الخدمة: <strong>{{ number_format((float) ($closePreview['service_amount'] ?? 0), 2) }}</strong></div>
                                <div style="font-size:.78rem;color:#374151;">الضريبة: <strong>{{ number_format((float) ($closePreview['tax_amount'] ?? 0), 2) }}</strong></div>
                                <div style="font-size:.78rem;color:#374151;">المقدم: <strong>{{ number_format((float) ($closePreview['deposit_amount'] ?? 0), 2) }}</strong></div>
                            </div>

                            @if (data_get($closePreview, 'limit_time.applied'))
                                <div style="margin-top:10px;border:1px solid #f59e0b;background:#fffbeb;border-radius:12px;padding:10px 12px;color:#92400e;font-size:.78rem;line-height:1.6;">
                                    تم تطبيق الحد الأدنى لوقت الجهاز تلقائياً عند التقفيل.
                                    <br>
                                    الوقت المنقضي من بداية الجلسة: <strong>{{ (int) data_get($closePreview, 'limit_time.elapsed_from_start', 0) }} د</strong>
                                    — الحد الأدنى المضبوط: <strong>{{ (int) data_get($closePreview, 'limit_time.configured_minutes', 0) }} د</strong>.
                                </div>
                            @endif

                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;margin-top:12px;">
                                <div>
                                    <label style="display:block;font-size:.74rem;color:#374151;margin-bottom:4px;">قيمة الخصم</label>
                                    <input type="number" min="0" max="999" step="0.01" wire:model.live="busyActionDiscountAmount" style="width:100%;border:1px solid #d1d5db;border-radius:10px;padding:8px 10px;font-size:.84rem;" />
                                </div>
                                <div>
                                    <label style="display:block;font-size:.74rem;color:#374151;margin-bottom:4px;">سبب الخصم (إجباري عند وجود خصم)</label>
                                    <input type="text" wire:model.live="busyActionDiscountReason" placeholder="اكتب سبب الخصم" style="width:100%;border:1px solid #d1d5db;border-radius:10px;padding:8px 10px;font-size:.84rem;" />
                                </div>
                                <div style="grid-column:1/-1;display:grid;gap:6px;">
                                    <label style="display:block;font-size:.74rem;color:#374151;">تعيين عميل (لازم لو فيه متبقي على الحساب)</label>

                                    <div x-data="{ open: false }" style="position:relative;">
                                        <button
                                            type="button"
                                            @click="open = !open"
                                            style="width:100%;border:1px solid #d1d5db;border-radius:10px;padding:8px 10px;font-size:.84rem;background:#fff;display:flex;align-items:center;justify-content:space-between;gap:8px;cursor:pointer;"
                                        >
                                            <span style="overflow:hidden;white-space:nowrap;text-overflow:ellipsis;color:#111827;">
                                                {{ $this->busySelectedCustomer?->name ? ($this->busySelectedCustomer->name . ' - ' . $this->busySelectedCustomer->phone) : 'بدون عميل' }}
                                            </span>
                                            <svg style="width:14px;height:14px;color:#6b7280;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>

                                        <div
                                            x-cloak
                                            x-show="open"
                                            @click.outside="open = false"
                                            style="position:absolute;z-index:30;top:calc(100% + 6px);left:0;right:0;background:#fff;border:1px solid #d1d5db;border-radius:10px;box-shadow:0 12px 28px rgba(0,0,0,.12);padding:8px;display:grid;gap:8px;"
                                        >
                                            <input
                                                type="text"
                                                wire:model.live.debounce.300ms="busyActionCustomerSearch"
                                                placeholder="ابحث بالاسم أو رقم الهاتف"
                                                style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:7px 9px;font-size:.82rem;"
                                            />

                                            <button
                                                type="button"
                                                wire:click="clearBusyActionCustomer"
                                                @click="open = false"
                                                style="text-align:right;border:1px dashed #d1d5db;background:#f9fafb;border-radius:8px;padding:7px 9px;font-size:.8rem;color:#374151;cursor:pointer;"
                                            >
                                                بدون عميل
                                            </button>

                                            <div style="max-height:180px;overflow:auto;display:grid;gap:6px;">
                                                @forelse ($this->busyFilteredCustomers as $customer)
                                                    <button
                                                        type="button"
                                                        wire:click="selectBusyActionCustomer({{ $customer->id }})"
                                                        @click="open = false"
                                                        style="text-align:right;border:1px solid #e5e7eb;background:#fff;border-radius:8px;padding:7px 9px;font-size:.8rem;color:#111827;cursor:pointer;"
                                                    >
                                                        {{ $customer->name }} - {{ $customer->phone }}
                                                    </button>
                                                @empty
                                                    <div style="text-align:center;padding:8px;color:#6b7280;font-size:.78rem;">لا يوجد عميل مطابق للبحث</div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div style="grid-column:1/-1;border:1px solid #e5e7eb;border-radius:12px;padding:10px;">
                                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:8px;">
                                        <div style="font-size:.76rem;font-weight:800;color:#374151;">تفاصيل الدفع (يمكن التقسيم على أكثر من خزينة)</div>
                                        <button type="button" wire:click="addBusyActionPaymentRow" style="border:1px solid #2563eb;background:#eff6ff;color:#1d4ed8;padding:4px 8px;border-radius:8px;font-size:.72rem;font-weight:700;cursor:pointer;">
                                            + إضافة دفعة
                                        </button>
                                    </div>

                                    <div style="display:grid;gap:8px;">
                                        @foreach ($busyActionPayments as $index => $payment)
                                            <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:8px;align-items:end;">
                                                <div>
                                                    <label style="display:block;font-size:.7rem;color:#6b7280;margin-bottom:3px;">الخزينة</label>
                                                    <select wire:model.live="busyActionPayments.{{ $index }}.cash_box_id" style="width:100%;border:1px solid #d1d5db;border-radius:10px;padding:8px 10px;font-size:.8rem;">
                                                        <option value="">اختر خزينة</option>
                                                        @foreach ($this->busyPaymentCashBoxes as $cashBox)
                                                            <option value="{{ $cashBox->id }}">{{ $cashBox->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div>
                                                    <label style="display:block;font-size:.7rem;color:#6b7280;margin-bottom:3px;">المبلغ</label>
                                                    <input type="number" min="0" step="0.01" wire:model.live.debounce.250ms="busyActionPayments.{{ $index }}.amount" style="width:100%;border:1px solid #d1d5db;border-radius:10px;padding:8px 10px;font-size:.8rem;" />
                                                </div>

                                                <button type="button" wire:click="removeBusyActionPaymentRow({{ $index }})" style="border:1px solid #ef4444;background:#fef2f2;color:#b91c1c;padding:8px 10px;border-radius:10px;font-size:.72rem;font-weight:700;cursor:pointer;">
                                                    حذف
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div style="margin-top:8px;font-size:.78rem;color:#111827;font-weight:800;">
                                        إجمالي المدفوع: <span style="direction:ltr;unicode-bidi:isolate;color:#2563eb;">{{ number_format((float) $this->busyActionPaidTotal, 2) }}</span>
                                    </div>

                                    @if ($this->busySelectedCustomer && (float) ($closePreview['remain_amount'] ?? 0) > 0)
                                        <label style="margin-top:10px;display:flex;align-items:center;gap:8px;font-size:.78rem;color:#374151;font-weight:700;cursor:pointer;">
                                            <input type="checkbox" wire:model.live="busyCloseDepositRemainderToCustomerBalance" style="width:16px;height:16px;accent-color:#16a34a;cursor:pointer;" />
                                            <span>إضافة الباقي إلى رصيد العميل بدل إرجاعه نقداً</span>
                                        </label>
                                    @endif
                                </div>
                                <div style="display:flex;align-items:flex-end;">
                                    <div style="font-size:.86rem;font-weight:800;color:#111827;">
                                        الصافي: <span style="color:#2563eb;direction:ltr;unicode-bidi:isolate;">{{ number_format((float) ($closePreview['final_amount'] ?? 0), 2) }}</span>
                                        <br>
                                        المتبقي: <span style="color:{{ (float) ($closePreview['remain_amount'] ?? 0) < 0 ? '#dc2626' : '#16a34a' }};direction:ltr;unicode-bidi:isolate;">{{ number_format((float) ($closePreview['remain_amount'] ?? 0), 2) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
                                <button type="button" wire:click="closeBusyActionDetailsModal" style="border:1px solid #d1d5db;background:#fff;color:#111827;padding:8px 14px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">إلغاء</button>
                                <button type="button" wire:click="closeBusySessionInvoice" style="border:1px solid #16a34a;background:#16a34a;color:#fff;padding:8px 14px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">تقفيل الحساب</button>
                            </div>

                            @if ($lastCloseInvoiceError)
                                <div style="margin-top:16px;padding:12px;border:1px solid #fca5a5;background:#fef2f2;border-radius:12px;display:flex;gap:10px;align-items:flex-start;">
                                    <svg style="width:18px;height:18px;color:#dc2626;flex-shrink:0;margin-top:2px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <div style="flex:1;">
                                        <div style="font-size:.82rem;font-weight:700;color:#dc2626;">
                                            ❌ فشل تقفيل الجهاز: <strong>{{ $lastCloseInvoiceDeviceName }}</strong>
                                        </div>
                                        <div style="font-size:.78rem;color:#991b1b;margin-top:4px;line-height:1.5;">
                                            {{ $lastCloseInvoiceError }}
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endif
                    @elseif ($selectedBusyActionLabel === 'إضافة فلوس')
                        @php
                            $currentPrepaid = (float) ($this->selectedBusyDevice?->activeSession?->prepaid_amount ?? 0);
                        @endphp

                        <div style="font-size:.82rem;color:#4b5563;margin-bottom:10px;">
                            الرصيد الحالي المقدم: <strong>{{ number_format($currentPrepaid, 2) }}</strong>
                        </div>

                        <input
                            type="number"
                            min="0.01"
                            max="999"
                            step="0.01"
                            wire:model.live="busyActionAmount"
                            placeholder="اكتب المبلغ المطلوب إضافته"
                            style="width:100%;border:1px solid #d1d5db;border-radius:10px;padding:8px 10px;font-size:.85rem;margin-bottom:12px;"
                        />

                        <div style="display:flex;justify-content:flex-end;gap:8px;">
                            <button type="button" wire:click="closeBusyActionDetailsModal" style="border:1px solid #d1d5db;background:#fff;color:#111827;padding:8px 12px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                إلغاء
                            </button>
                            <button type="button" wire:click="addBusySessionAmount" style="border:1px solid #16a34a;background:#16a34a;color:#fff;padding:8px 12px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                إضافة المبلغ
                            </button>
                        </div>
                    @elseif ($selectedBusyActionLabel === 'إضافة طلبات')
                        <div style="display:grid;gap:12px;">
                            <div>
                                <div style="font-size:.8rem;font-weight:700;color:#374151;margin-bottom:6px;">مجموعات الأصناف</div>
                                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(96px,1fr));gap:8px;">
                                    @forelse ($this->orderGroups as $group)
                                        <button
                                            type="button"
                                            wire:click="selectOrderGroup({{ $group->id }})"
                                            style="border:1px solid {{ $selectedOrderGroupId === $group->id ? '#16a34a' : '#d1d5db' }};background:{{ $selectedOrderGroupId === $group->id ? '#f0fdf4' : '#fff' }};border-radius:12px;padding:8px;display:flex;flex-direction:column;align-items:center;gap:6px;cursor:pointer;"
                                        >
                                            <div style="width:56px;height:56px;border-radius:10px;overflow:hidden;background:#f3f4f6;display:flex;align-items:center;justify-content:center;">
                                                @if ($group->picture)
                                                    <img src="{{ tenant_asset($group->picture) }}" alt="{{ $group->name }}" style="width:56px;height:56px;object-fit:contain;display:block;" />
                                                @else
                                                    <svg style="width:24px;height:24px;color:#9ca3af;" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5A1.5 1.5 0 014.5 6h15A1.5 1.5 0 0121 7.5v9A1.5 1.5 0 0119.5 18h-15A1.5 1.5 0 013 16.5v-9z"/>
                                                    </svg>
                                                @endif
                                            </div>
                                            <div style="font-size:.74rem;font-weight:700;color:#111827;text-align:center;line-height:1.2;word-break:break-word;">{{ $group->name }}</div>
                                        </button>
                                    @empty
                                        <div style="grid-column:1/-1;text-align:center;color:#6b7280;font-size:.78rem;padding:8px;">لا توجد مجموعات أصناف مفعلة</div>
                                    @endforelse
                                </div>
                            </div>

                            <div>
                                <div style="font-size:.8rem;font-weight:700;color:#374151;margin-bottom:6px;">الأصناف</div>
                                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:8px;max-height:260px;overflow:auto;padding-inline-end:2px;">
                                    @forelse ($this->orderItems as $item)
                                        <button
                                            type="button"
                                            wire:click="openOrderItemQuantityModal({{ $item->id }})"
                                            style="border:1px solid #d1d5db;background:#fff;border-radius:12px;padding:8px;display:flex;flex-direction:column;align-items:center;gap:6px;cursor:pointer;text-align:center;"
                                        >
                                            <div style="width:60px;height:60px;border-radius:10px;overflow:hidden;background:#f3f4f6;display:flex;align-items:center;justify-content:center;">
                                                @if ($item->picture)
                                                    <img src="{{ tenant_asset($item->picture) }}" alt="{{ $item->name }}" style="width:60px;height:60px;object-fit:contain;display:block;" />
                                                @else
                                                    <svg style="width:24px;height:24px;color:#9ca3af;" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.11 1.5 2.076v8.662a2.25 2.25 0 01-2.25 2.25H4.5a2.25 2.25 0 01-2.25-2.25v-8.662c0-.966.616-1.792 1.5-2.076"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v10.5m0 0l3-3m-3 3l-3-3"/>
                                                    </svg>
                                                @endif
                                            </div>
                                            <div style="font-size:.75rem;font-weight:700;color:#111827;line-height:1.2;word-break:break-word;">{{ $item->name }}</div>
                                            <div style="font-size:.74rem;font-weight:700;color:#16a34a;">{{ number_format((float) ($item->sale_price ?? 0), 2) }}</div>
                                        </button>
                                    @empty
                                        <div style="grid-column:1/-1;text-align:center;color:#6b7280;font-size:.78rem;padding:10px;">اختر مجموعة لإظهار الأصناف</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        @if ($selectedCatalogItemId && $this->selectedOrderItem)
                            <div style="position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:72;display:flex;align-items:center;justify-content:center;padding:16px;" wire:click="closeOrderItemQuantityModal">
                                <div style="width:min(360px,100%);background:#fff;border-radius:14px;box-shadow:0 20px 48px rgba(0,0,0,.28);padding:14px;" wire:click.stop>
                                    <div style="font-size:.92rem;font-weight:800;color:#111827;margin-bottom:6px;">{{ $this->selectedOrderItem->name }}</div>
                                    <div style="font-size:.78rem;color:#6b7280;margin-bottom:10px;">سعر الوحدة: {{ number_format((float) ($this->selectedOrderItem->sale_price ?? 0), 2) }}</div>

                                    <input
                                        type="number"
                                        min="1"
                                        step="1"
                                        wire:model.live="orderItemQuantity"
                                        placeholder="اكتب الكمية المطلوبة"
                                        style="width:100%;border:1px solid #d1d5db;border-radius:10px;padding:8px 10px;font-size:.85rem;margin-bottom:12px;"
                                    />

                                    <div style="display:flex;justify-content:flex-end;gap:8px;">
                                        <button type="button" wire:click="closeOrderItemQuantityModal" style="border:1px solid #d1d5db;background:#fff;color:#111827;padding:8px 12px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                            إلغاء
                                        </button>
                                        <button type="button" wire:click="addOrderItemToBusySession" style="border:1px solid #16a34a;background:#16a34a;color:#fff;padding:8px 12px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                            حفظ
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @elseif ($selectedBusyActionLabel === 'بيانات الجهاز')
                        @php
                            $busySession = $this->selectedBusyDevice?->activeSession;
                            $timeDetails = $busySession?->details?->sortByDesc('id') ?? collect();
                            $orderDetails = $busySession?->orderSessionDetails?->sortByDesc('id') ?? collect();
                        @endphp

                        <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:start;">
                            <div style="flex:1;min-width:min(100%,300px);border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                                <div style="padding:10px 12px;background:#eff6ff;color:#1d4ed8;font-size:.8rem;font-weight:800;">
                                    بيانات الطلبات
                                </div>

                                <div style="max-height:320px;overflow:auto;">
                                    <table style="width:100%;border-collapse:collapse;font-size:.76rem;">
                                        <thead style="background:#f9fafb;color:#374151;">
                                            <tr>
                                                <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:right;">الصنف</th>
                                                <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;">الكمية</th>
                                                <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;">السعر</th>
                                                <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;">الإجمالي</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($orderDetails as $order)
                                                <tr>
                                                    <td style="padding:8px;border-bottom:1px solid #f3f4f6;color:#111827;">
                                                        {{ $order->catalogItem?->name ?? '—' }}
                                                    </td>
                                                    <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;color:#374151;">
                                                        {{ $order->quantity ?? '—' }}
                                                    </td>
                                                    <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;color:#374151;direction:ltr;unicode-bidi:isolate;">
                                                        {{ number_format((float) ($order->price ?? 0), 2) }}
                                                    </td>
                                                    <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;color:#16a34a;font-weight:700;direction:ltr;unicode-bidi:isolate;">
                                                        {{ number_format((float) ($order->total ?? 0), 2) }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" style="padding:16px;text-align:center;color:#6b7280;">لا توجد طلبات على هذه الجلسة.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div style="flex:1;min-width:min(100%,300px);border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                                <div style="padding:10px 12px;background:#f0fdf4;color:#15803d;font-size:.8rem;font-weight:800;">
                                    بيانات الوقت
                                </div>

                                <div style="max-height:320px;overflow:auto;">
                                    <table style="width:100%;border-collapse:collapse;font-size:.76rem;">
                                        <thead style="background:#f9fafb;color:#374151;">
                                            <tr>
                                                <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:right;">نوع اللعب</th>
                                                <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;">النوع</th>
                                                <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;">البداية</th>
                                                <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;">النهاية</th>
                                                <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;">المدة</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($timeDetails as $detail)
                                                <tr>
                                                    <td style="padding:8px;border-bottom:1px solid #f3f4f6;color:#111827;">
                                                        {{ $detail->play_type ?: '—' }}
                                                    </td>
                                                    <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;color:#374151;">
                                                        {{ $detail->play_category ?: '—' }}
                                                    </td>
                                                    <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;color:#374151;direction:ltr;unicode-bidi:isolate;">
                                                        {{ $detail->start_time?->format('h:i A') ?? '—' }}
                                                    </td>
                                                    <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;color:#374151;direction:ltr;unicode-bidi:isolate;">
                                                        {{ $detail->end_time?->format('h:i A') ?? '—' }}
                                                    </td>
                                                    <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;color:#111827;">
                                                        {{ $detail->period ? $detail->period . ' د' : 'مفتوح' }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" style="padding:16px;text-align:center;color:#6b7280;">لا توجد بيانات وقت على هذه الجلسة.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div style="display:flex;justify-content:flex-end;margin-top:12px;">
                            <button type="button" wire:click="closeBusyActionDetailsModal" style="border:1px solid #d1d5db;background:#fff;color:#111827;padding:8px 12px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                إغلاق
                            </button>
                        </div>
                    @elseif ($selectedBusyActionLabel === 'تبديل اللعب')
                        @php
                            $currentDetail = $this->selectedBusyDevice?->activeSession?->latestDetail;
                            $switchPlayPrices = $this->availableSwitchPlayPrices;
                        @endphp

                        <div style="display:grid;gap:10px;">
                            <div style="font-size:.8rem;color:#4b5563;">
                                نوع اللعب الحالي:
                                <strong>{{ $currentDetail?->play_type ?: '—' }}</strong>
                            </div>

                            <div style="font-size:.78rem;font-weight:700;color:#374151;">اختر نوع اللعب الجديد</div>

                            @if ($switchPlayPrices->isNotEmpty())
                                <div style="display:grid;gap:8px;max-height:240px;overflow:auto;padding-inline-end:2px;">
                                    @foreach ($switchPlayPrices as $price)
                                        @php
                                            $isSelected = (int) $selectedSwitchPlayPriceId === (int) $price->id;
                                        @endphp

                                        <button
                                            type="button"
                                            wire:click="selectSwitchPlayPrice({{ $price->id }})"
                                            style="display:flex;align-items:center;justify-content:space-between;gap:8px;border:1px solid {{ $isSelected ? '#2563eb' : '#d1d5db' }};background:{{ $isSelected ? '#eff6ff' : '#fff' }};border-radius:10px;padding:9px 10px;cursor:pointer;text-align:right;"
                                        >
                                            <span style="display:flex;flex-direction:column;align-items:flex-start;gap:2px;overflow:hidden;">
                                                <span style="font-size:.82rem;font-weight:700;color:#111827;white-space:nowrap;text-overflow:ellipsis;overflow:hidden;max-width:220px;">
                                                    {{ $price->playType?->name ?? '—' }}
                                                </span>
                                                @if ($price->description)
                                                    <span style="font-size:.72rem;color:#6b7280;white-space:nowrap;text-overflow:ellipsis;overflow:hidden;max-width:220px;">
                                                        {{ $price->description }}
                                                    </span>
                                                @endif
                                            </span>

                                            <span style="flex-shrink:0;background:{{ $isSelected ? '#2563eb' : '#f3f4f6' }};color:{{ $isSelected ? '#fff' : '#111827' }};border-radius:8px;padding:3px 8px;font-size:.75rem;font-weight:700;">
                                                {{ number_format((float) $price->price, 2) }}
                                            </span>
                                        </button>
                                    @endforeach
                                </div>

                                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:2px;">
                                    <button type="button" wire:click="closeBusyActionDetailsModal" style="border:1px solid #d1d5db;background:#fff;color:#111827;padding:8px 12px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                        إلغاء
                                    </button>
                                    <button type="button" wire:click="switchBusySessionPlay" style="border:1px solid #2563eb;background:#2563eb;color:#fff;padding:8px 12px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                        تنفيذ التبديل
                                    </button>
                                </div>
                            @else
                                <div style="padding:10px;border:1px dashed #d1d5db;border-radius:10px;text-align:center;color:#6b7280;font-size:.8rem;">
                                    لا توجد أنواع لعب أخرى متاحة لهذا الجهاز.
                                </div>
                                <div style="display:flex;justify-content:flex-end;">
                                    <button type="button" wire:click="closeBusyActionDetailsModal" style="border:1px solid #d1d5db;background:#fff;color:#111827;padding:8px 12px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                        إغلاق
                                    </button>
                                </div>
                            @endif
                        </div>
                    @elseif ($selectedBusyActionLabel === 'تبديل أجهزة')
                        @php
                            $currentBusyDevice = $this->selectedBusyDevice;
                            $currentBusyDetail = $currentBusyDevice?->activeSession?->latestDetail;
                            $switchDevices = $this->availableSwitchDevices;
                        @endphp

                        <div style="display:grid;gap:10px;">
                            <div style="font-size:.8rem;color:#4b5563;line-height:1.6;">
                                الجهاز الحالي:
                                <strong>{{ $currentBusyDevice?->name ?? '—' }}</strong>
                                <br>
                                نوع اللعب الحالي:
                                <strong>{{ $currentBusyDetail?->play_type ?: '—' }}</strong>
                                <br>
                                فئة اللعب:
                                <strong>{{ $currentBusyDetail?->play_category ?: '—' }}</strong>
                            </div>

                            <div style="font-size:.78rem;font-weight:700;color:#374151;">اختر الجهاز الجديد</div>
                            <div style="font-size:.74rem;color:#6b7280;line-height:1.6;">
                                يظهر هنا كل الأجهزة المتاحة.
                                <br>
                                التبديل يتم بنفس نوع اللعب الحالي، وإذا لم يوجد نفس النوع على الجهاز الجديد يتم اختيار أول نوع لعب متاح عليه تلقائياً.
                            </div>

                            @if ($switchDevices->isNotEmpty())
                                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:8px;max-height:280px;overflow:auto;padding-inline-end:2px;">
                                    @foreach ($switchDevices as $candidateDevice)
                                        @php
                                            $isSelectedDevice = (int) $selectedSwitchDeviceId === (int) $candidateDevice->id;
                                            $playTypesText = $candidateDevice->playPrices
                                                ->map(fn ($price) => trim((string) ($price->playType?->name ?? '')))
                                                ->filter(fn (string $name) => $name !== '')
                                                ->unique()
                                                ->values()
                                                ->join(' / ');
                                        @endphp

                                        <button
                                            type="button"
                                            wire:click="selectSwitchDevice({{ $candidateDevice->id }})"
                                            style="border:1px solid {{ $isSelectedDevice ? '#2563eb' : '#d1d5db' }};background:{{ $isSelectedDevice ? '#eff6ff' : '#fff' }};border-radius:12px;padding:8px;display:flex;flex-direction:column;align-items:center;gap:6px;cursor:pointer;text-align:center;"
                                        >
                                            <div style="width:60px;height:60px;border-radius:10px;overflow:hidden;background:#f3f4f6;display:flex;align-items:center;justify-content:center;">
                                                @if ($candidateDevice->type?->picture)
                                                    <img src="{{ tenant_asset($candidateDevice->type->picture) }}" alt="{{ $candidateDevice->name }}" style="width:60px;height:60px;object-fit:contain;display:block;" />
                                                @else
                                                    <svg style="width:24px;height:24px;color:#9ca3af;" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/>
                                                    </svg>
                                                @endif
                                            </div>
                                            <div style="font-size:.74rem;font-weight:700;color:#111827;line-height:1.2;word-break:break-word;max-width:100%;">{{ $candidateDevice->name }}</div>
                                            <div style="font-size:.68rem;color:#6b7280;line-height:1.2;word-break:break-word;max-width:100%;">{{ $playTypesText !== '' ? $playTypesText : 'بدون أنواع لعب' }}</div>
                                            <span style="background:{{ $isSelectedDevice ? '#2563eb' : '#f3f4f6' }};color:{{ $isSelectedDevice ? '#fff' : '#111827' }};border-radius:8px;padding:3px 8px;font-size:.72rem;font-weight:700;">
                                                {{ $isSelectedDevice ? 'مختار' : 'متاح' }}
                                            </span>
                                        </button>
                                    @endforeach
                                </div>

                                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:2px;">
                                    <button type="button" wire:click="closeBusyActionDetailsModal" style="border:1px solid #d1d5db;background:#fff;color:#111827;padding:8px 12px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                        إلغاء
                                    </button>
                                    <button type="button" wire:click="switchBusySessionDevice" style="border:1px solid #2563eb;background:#2563eb;color:#fff;padding:8px 12px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                        تنفيذ التبديل
                                    </button>
                                </div>
                            @else
                                <div style="padding:10px;border:1px dashed #d1d5db;border-radius:10px;text-align:center;color:#6b7280;font-size:.8rem;">
                                    لا توجد أجهزة متاحة للتبديل الآن.
                                </div>
                                <div style="display:flex;justify-content:flex-end;">
                                    <button type="button" wire:click="closeBusyActionDetailsModal" style="border:1px solid #d1d5db;background:#fff;color:#111827;padding:8px 12px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                        إغلاق
                                    </button>
                                </div>
                            @endif
                        </div>
                    @elseif ($selectedBusyActionLabel === 'تحديد الوقت')
                        @php
                            $limitDevice = $this->selectedBusyDevice;
                            $limitSession = $limitDevice?->activeSession;
                            $limitCurrentDetail = $limitSession?->latestDetail;
                            $limitSessionStart = $limitSession?->details?->sortBy('id')->first()?->start_time;
                            $elapsedFromSessionStart = $limitSessionStart ? (int) ceil((float) $limitSessionStart->diffInMinutes(now())) : 0;
                            $minimumAllowedMinutes = max(1, $elapsedFromSessionStart + 1);
                            $quickLimitMinutes = [60, 90, 120, 180, 240, 300];
                        @endphp

                        <div style="font-size:.82rem;color:#4b5563;margin-bottom:8px;line-height:1.6;">
                            أدخل إجمالي وقت الجلسة بالدقائق من بداية الجلسة.
                        </div>

                        <div style="font-size:.78rem;color:#6b7280;margin-bottom:10px;line-height:1.6;">
                            وقت بداية الجلسة:
                            <strong style="direction:ltr;unicode-bidi:isolate;">{{ $limitSessionStart?->format('h:i A') ?? '—' }}</strong>
                            <br>
                            الوقت الحالي المنقضي تقريبيًا: <strong>{{ $elapsedFromSessionStart }} د</strong>
                            <br>
                            أقل وقت إجمالي مسموح الآن: <strong>{{ $minimumAllowedMinutes }} د</strong>
                            @if ($limitCurrentDetail?->end_time)
                                <br>
                                نهاية الجزء الحالي:
                                <strong style="direction:ltr;unicode-bidi:isolate;">{{ $limitCurrentDetail->end_time->format('h:i A') }}</strong>
                            @endif
                        </div>

                        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px;">
                            @foreach ($quickLimitMinutes as $quickMinutes)
                                @php
                                    $quickDisabled = $quickMinutes < $minimumAllowedMinutes;
                                @endphp
                                <button
                                    type="button"
                                    wire:click="$set('busyActionLimitMinutes', {{ $quickMinutes }})"
                                    @disabled($quickDisabled)
                                    style="border:1px solid {{ $quickDisabled ? '#e5e7eb' : '#bfdbfe' }};background:{{ $quickDisabled ? '#f9fafb' : '#eff6ff' }};color:{{ $quickDisabled ? '#9ca3af' : '#1d4ed8' }};padding:5px 10px;border-radius:9999px;cursor:{{ $quickDisabled ? 'not-allowed' : 'pointer' }};font-size:.74rem;font-weight:700;"
                                >
                                    {{ $quickMinutes }} د
                                </button>
                            @endforeach
                        </div>

                        <input
                            type="number"
                            min="{{ $minimumAllowedMinutes }}"
                            max="999"
                            step="1"
                            wire:model.live="busyActionLimitMinutes"
                            placeholder="اكتب إجمالي الدقائق (مثال: 180)"
                            style="width:100%;border:1px solid #d1d5db;border-radius:10px;padding:8px 10px;font-size:.85rem;margin-bottom:12px;"
                        />

                        <div style="display:flex;justify-content:flex-end;gap:8px;">
                            <button type="button" wire:click="closeBusyActionDetailsModal" style="border:1px solid #d1d5db;background:#fff;color:#111827;padding:8px 14px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                إلغاء
                            </button>
                            <button type="button" wire:click="limitBusySessionTime" style="border:1px solid #2563eb;background:#2563eb;color:#fff;padding:8px 14px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                حفظ
                            </button>
                        </div>
                    @elseif ($selectedBusyActionLabel === 'إضافة وقت')
                        @php
                            $addTimeDevice = $this->selectedBusyDevice;
                            $addTimeDetail = $addTimeDevice?->activeSession?->latestDetail;
                            $addTimeIsTimed = $addTimeDetail?->period !== null && $addTimeDetail?->end_time !== null;
                        @endphp

                        @if (! $addTimeIsTimed)
                            <div style="display:flex;align-items:flex-start;gap:10px;padding:12px 14px;background:#fef9c3;border:1px solid #fde047;border-radius:12px;margin-bottom:14px;">
                                <svg style="width:20px;height:20px;color:#ca8a04;flex-shrink:0;margin-top:2px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                                </svg>
                                <div>
                                    <div style="font-size:.82rem;font-weight:700;color:#92400e;margin-bottom:3px;">لا يمكن إضافة وقت</div>
                                    <div style="font-size:.78rem;color:#78350f;line-height:1.5;">هذا الجهاز يعمل على وقت مفتوح، لا يمكن إضافة وقت محدد إلا للجلسات ذات وقت محدد.</div>
                                </div>
                            </div>
                        @else
                            <div style="font-size:.82rem;color:#4b5563;margin-bottom:10px;">
                                الوقت الحالي ينتهي عند:
                                <strong style="direction:ltr;unicode-bidi:isolate;">{{ $addTimeDetail?->end_time?->format('h:i A') ?? '—' }}</strong>
                            </div>

                            <input
                                type="number"
                                min="1"
                                max="999"
                                step="1"
                                wire:model.live="busyActionMinutes"
                                placeholder="اكتب عدد الدقائق المطلوب إضافتها"
                                style="width:100%;border:1px solid #d1d5db;border-radius:10px;padding:8px 10px;font-size:.85rem;margin-bottom:12px;"
                            />

                            <div style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:4px;">
                                <button type="button" wire:click="closeBusyActionDetailsModal" style="border:1px solid #d1d5db;background:#fff;color:#111827;padding:8px 14px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                    إلغاء
                                </button>
                                <button type="button" wire:click="addBusySessionTime" style="border:1px solid #2563eb;background:#2563eb;color:#fff;padding:8px 14px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                    حفظ
                                </button>
                            </div>
                        @endif

                        @if (! $addTimeIsTimed)
                            <div style="display:flex;justify-content:flex-end;">
                                <button type="button" wire:click="closeBusyActionDetailsModal" style="border:1px solid #d1d5db;background:#fff;color:#111827;padding:8px 14px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                    إغلاق
                                </button>
                            </div>
                        @endif
                    @else
                        <div style="font-size:.82rem;color:#4b5563;margin-bottom:14px;">تم تجهيز النافذة كبداية. ارسل لي تفاصيل هذا الإجراء وسأربطه بالعملية الفعلية مباشرة.</div>
                        <div style="display:flex;justify-content:flex-end;">
                            <button type="button" wire:click="closeBusyActionDetailsModal" style="border:1px solid #d1d5db;background:#fff;color:#111827;padding:8px 12px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                إغلاق
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        @if ($showStartSessionModal)
            @php
                $selectedDevice = $this->selectedDevice;
                $hasReservationToday = $this->hasReservationToday();
            @endphp

            <div style="position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:70;display:flex;align-items:center;justify-content:center;padding:16px;" wire:click="closeStartSessionModal">
                <div style="width:min(680px,100%);max-height:90vh;overflow:auto;background:#fff;border-radius:18px;box-shadow:0 24px 64px rgba(0,0,0,.35);padding:18px;" wire:click.stop>
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:14px;">
                        <div>
                            <div style="font-size:1.05rem;font-weight:800;color:#111827;">{{ $selectedDevice?->name ?? 'الجهاز' }}</div>
                            <div style="font-size:.76rem;color:#6b7280;margin-top:2px;">بدء جلسة جديدة</div>
                        </div>
                        <button type="button" wire:click="closeStartSessionModal" style="width:34px;height:34px;border-radius:10px;border:1px solid #e5e7eb;background:#f9fafb;cursor:pointer;">
                            ✕
                        </button>
                    </div>

                    <div style="display:flex;align-items:center;gap:8px;padding:9px 12px;border-radius:12px;background:{{ $hasReservationToday ? '#fef2f2' : '#f0fdf4' }};border:1px solid {{ $hasReservationToday ? '#fecaca' : '#bbf7d0' }};margin-bottom:14px;">
                        <span style="width:8px;height:8px;border-radius:9999px;background:{{ $hasReservationToday ? '#ef4444' : '#16a34a' }};display:inline-block;"></span>
                        <span style="font-size:.78rem;font-weight:700;color:#374151;">
                            حالة الحجز اليوم: {{ $hasReservationToday ? 'يوجد حجز' : 'لا يوجد حجز' }}
                        </span>
                    </div>

                    <div style="display:grid;gap:10px;margin-bottom:14px;">
                        <label style="font-size:.78rem;font-weight:700;color:#374151;">اختيار العميل (اختياري)</label>

                        <div x-data="{ open: false }" style="position:relative;">
                            <button
                                type="button"
                                @click="open = !open"
                                style="width:100%;border:1px solid #d1d5db;border-radius:10px;padding:8px 10px;font-size:.85rem;background:#fff;display:flex;align-items:center;justify-content:space-between;gap:8px;cursor:pointer;"
                            >
                                <span style="overflow:hidden;white-space:nowrap;text-overflow:ellipsis;color:#111827;">
                                    {{ $this->selectedCustomer?->name ? ($this->selectedCustomer->name . ' - ' . $this->selectedCustomer->phone) : 'بدون عميل' }}
                                </span>
                                <svg style="width:14px;height:14px;color:#6b7280;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <div
                                x-cloak
                                x-show="open"
                                @click.outside="open = false"
                                style="position:absolute;z-index:30;top:calc(100% + 6px);left:0;right:0;background:#fff;border:1px solid #d1d5db;border-radius:10px;box-shadow:0 12px 28px rgba(0,0,0,.12);padding:8px;display:grid;gap:8px;"
                            >
                                <input
                                    type="text"
                                    wire:model.live.debounce.300ms="customerSearch"
                                    placeholder="ابحث بالاسم أو رقم الهاتف"
                                    style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:7px 9px;font-size:.82rem;"
                                />

                                <button
                                    type="button"
                                    wire:click="clearCustomerSelection"
                                    @click="open = false"
                                    style="text-align:right;border:1px dashed #d1d5db;background:#f9fafb;border-radius:8px;padding:7px 9px;font-size:.8rem;color:#374151;cursor:pointer;"
                                >
                                    بدون عميل
                                </button>

                                <div style="max-height:180px;overflow:auto;display:grid;gap:6px;">
                                    @forelse ($this->filteredCustomers as $customer)
                                        <button
                                            type="button"
                                            wire:click="selectCustomer({{ $customer->id }})"
                                            @click="open = false"
                                            style="text-align:right;border:1px solid #e5e7eb;background:#fff;border-radius:8px;padding:7px 9px;font-size:.8rem;color:#111827;cursor:pointer;"
                                        >
                                            {{ $customer->name }} - {{ $customer->phone }}
                                        </button>
                                    @empty
                                        <div style="text-align:center;padding:8px;color:#6b7280;font-size:.78rem;">لا يوجد عميل مطابق للبحث</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="display:grid;gap:10px;margin-bottom:14px;">
                        <label style="font-size:.78rem;font-weight:700;color:#374151;">مدة اللعب (اختياري)</label>
                        <input
                            type="number"
                            min="1"
                            wire:model.live="customDurationMinutes"
                            placeholder="اكتب مدة بالدقائق"
                            style="width:100%;border:1px solid #d1d5db;border-radius:10px;padding:8px 10px;font-size:.85rem;"
                        />

                        <div style="display:flex;flex-wrap:wrap;gap:8px;">
                            @foreach ([15, 30, 60, 120] as $fixedMinute)
                                <button
                                    type="button"
                                    wire:click="setQuickDuration({{ $fixedMinute }})"
                                    style="border:1px solid {{ $selectedDurationMinutes === $fixedMinute ? '#4f46e5' : '#d1d5db' }};background:{{ $selectedDurationMinutes === $fixedMinute ? '#eef2ff' : '#ffffff' }};color:{{ $selectedDurationMinutes === $fixedMinute ? '#3730a3' : '#374151' }};padding:6px 12px;border-radius:10px;font-size:.78rem;font-weight:700;cursor:pointer;"
                                >
                                    {{ $fixedMinute }} دقيقة
                                </button>
                            @endforeach

                            <button
                                type="button"
                                wire:click="clearDuration"
                                style="border:1px solid #d1d5db;background:#ffffff;color:#374151;padding:6px 12px;border-radius:10px;font-size:.78rem;font-weight:700;cursor:pointer;"
                            >
                                وقت مفتوح
                            </button>
                        </div>
                    </div>

                    <div style="display:grid;gap:8px;">
                        <label style="font-size:.78rem;font-weight:700;color:#374151;">اختيار نوع اللعب</label>

                        @if ($selectedDevice && $selectedDevice->playPrices->isNotEmpty())
                            @foreach ($selectedDevice->playPrices as $pp)
                                <button
                                    type="button"
                                    wire:click="startSessionFromModal({{ $pp->id }})"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-70"
                                    wire:target="startSessionFromModal({{ $pp->id }})"
                                    style="width:100%;display:flex;align-items:center;justify-content:space-between;gap:8px;background:#111827;color:#fff;border:0;border-radius:12px;padding:10px 12px;cursor:pointer;text-align:right;"
                                >
                                    <span style="font-size:.82rem;font-weight:700;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">
                                        {{ $pp->playType?->name ?? '—' }}
                                        @if ($pp->description)
                                            <span style="opacity:.72;font-size:.72rem;"> - {{ $pp->description }}</span>
                                        @endif
                                    </span>
                                    <span style="font-size:.78rem;font-weight:700;background:rgba(255,255,255,.12);padding:3px 8px;border-radius:8px;">
                                        {{ number_format((float) $pp->price, 0) }}
                                    </span>
                                </button>
                            @endforeach
                        @else
                            <div style="padding:12px;text-align:center;border:1px dashed #d1d5db;border-radius:12px;color:#6b7280;font-size:.8rem;">
                                لا توجد أنواع لعب متاحة لهذا الجهاز
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
            const silentPrintFrameId = 'silent-print-bridge-frame';

            Livewire.on('open-print-window', (payload) => {
                const eventData = Array.isArray(payload) ? payload[0] : payload;
                const url = eventData && typeof eventData.url === 'string' ? eventData.url : '';

                if (!url) {
                    return;
                }

                const isSilent = url.includes('silent=1');

                if (!isSilent) {
                    const popup = window.open(url, '_blank', 'noopener,noreferrer');
                    if (!popup) {
                        console.warn('Print popup was blocked by the browser.');
                    }

                    return;
                }

                const previousFrame = document.getElementById(silentPrintFrameId);
                if (previousFrame) {
                    previousFrame.remove();
                }

                const iframe = document.createElement('iframe');
                iframe.id = silentPrintFrameId;
                iframe.src = url;
                iframe.setAttribute('aria-hidden', 'true');
                iframe.style.position = 'fixed';
                iframe.style.width = '1px';
                iframe.style.height = '1px';
                iframe.style.left = '-9999px';
                iframe.style.top = '0';
                iframe.style.border = '0';
                iframe.style.opacity = '0';
                iframe.style.pointerEvents = 'none';

                document.body.appendChild(iframe);
            });
        });
    </script>
</x-filament-panels::page>
