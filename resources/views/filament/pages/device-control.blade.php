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
                    style="
                        border-radius:18px;
                        overflow:hidden;
                        box-shadow:0 4px 16px rgba(0,0,0,.18);
                        background:{{ $cardBg }};
                        color:#fff;
                        display:flex;
                        flex-direction:column;
                        position:relative;
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
                            {{-- وقت البداية والنهاية --}}
                            <div style="display:flex;justify-content:space-around;gap:8px;">
                                <div style="text-align:center;">
                                    <div style="font-size:.6rem;opacity:.7;font-weight:600;margin-bottom:2px;">بداية الوقت</div>
                                    <div style="font-size:.85rem;font-weight:800;font-family:monospace;">—</div>
                                </div>
                                <div style="width:1px;background:rgba(255,255,255,.25);"></div>
                                <div style="text-align:center;">
                                    <div style="font-size:.6rem;opacity:.7;font-weight:600;margin-bottom:2px;">نهاية الوقت</div>
                                    <div style="font-size:.85rem;font-weight:800;font-family:monospace;">—</div>
                                </div>
                            </div>

                            {{-- مدة اللعب + المنقضي --}}
                            <div style="background:rgba(0,0,0,.2);border-radius:12px;padding:10px;display:flex;justify-content:space-around;gap:8px;">
                                <div style="text-align:center;">
                                    <div style="font-size:.58rem;opacity:.7;font-weight:600;margin-bottom:3px;">مدة اللعب</div>
                                    <div style="font-size:.9rem;font-weight:800;font-family:monospace;letter-spacing:.5px;">—</div>
                                </div>
                                <div style="width:1px;background:rgba(255,255,255,.2);"></div>
                                <div style="text-align:center;">
                                    <div style="font-size:.58rem;opacity:.7;font-weight:600;margin-bottom:3px;">الوقت المنقضي</div>
                                    <div style="font-size:.9rem;font-weight:800;font-family:monospace;letter-spacing:.5px;color:#fef08a;">—</div>
                                </div>
                            </div>

                            {{-- زر إيقاف (معطّل مؤقتاً) --}}
                            <button
                                type="button"
                                disabled
                                style="width:100%;display:flex;align-items:center;justify-content:center;gap:6px;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);color:#fff;font-size:.78rem;font-weight:700;padding:8px;border-radius:10px;cursor:not-allowed;opacity:.7;margin-top:auto;"
                            >
                                <svg style="width:14px;height:14px;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 7.5A2.25 2.25 0 0 1 7.5 5.25h9a2.25 2.25 0 0 1 2.25 2.25v9a2.25 2.25 0 0 1-2.25 2.25h-9a2.25 2.25 0 0 1-2.25-2.25v-9z"/>
                                </svg>
                                إيقاف الجلسة
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
                                            wire:click="startSession({{ $device->id }}, {{ $pp->id }})"
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
                                <div style="flex:1;display:flex;align-items:center;justify-content:center;padding:10px 0;opacity:.6;font-size:.78rem;">
                                    لا توجد أسعار مضافة
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
    </div>
</x-filament-panels::page>
