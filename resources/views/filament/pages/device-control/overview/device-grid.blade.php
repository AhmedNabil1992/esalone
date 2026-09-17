        {{-- ===== شبكة الكروت ===== --}}
        <div class="device-control-device-grid" style="display:grid;grid-template-columns:repeat(auto-fill, minmax(200px, 1fr));gap:16px;">
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
                        @php
                            $soonReservation = isset($upcomingReservations[$device->id]) ? $upcomingReservations[$device->id]->first() : null;
                        @endphp
                        @if ($soonReservation)
                            @php
                                $resStartTime = \Carbon\Carbon::parse($soonReservation->start_time);
                                $resStartTimeLocal = app(\App\Support\Tenant\TenantClock::class)->utcToLocal($resStartTime);
                                $resMinutesLeft = max(1, (int) now()->diffInMinutes($resStartTime));
                            @endphp
                            <div style="background: rgba(254, 242, 242, 0.95); border: 1.5px solid #ef4444; color: #991b1b; font-size: 0.72rem; padding: 6px 10px; border-radius: 10px; font-weight: 700; display: flex; align-items: center; gap: 6px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 4px; animation: pulse-border 2s infinite;">
                                <svg style="width: 14px; height: 14px; color: #ef4444; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span style="flex:1;text-align:right;">
                                    حجز قادم بعد {{ $resMinutesLeft }} دقيقة
                                    ({{ $resStartTimeLocal->format('h:i A') }})
                                </span>
                            </div>
                        @endif

                        @if ($isBusy)
                            @php
                                $activeDetail = $device->activeSession?->latestDetail;
                                $startTime = $device->activeSession?->getOriginalStartTime() ?? $activeDetail?->start_time;
                                $endTime = $activeDetail?->end_time;
                                $displayStartTime = $startTime ? app(\App\Support\Tenant\TenantClock::class)->utcToLocal($startTime) : null;
                                $displayEndTime = $endTime ? app(\App\Support\Tenant\TenantClock::class)->utcToLocal($endTime) : null;
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

                                $totalPeriod = $device->activeSession?->getTotalPeriod() ?? (int) $period;
                                $playDuration = $isTimed ? ((string) $totalPeriod . ' د') : 'مفتوح';

                                $activePlayTypeName = trim((string) ($activeDetail?->play_type ?? ''));
                                $activePlayType = $device->playTypes->first(function ($playType) use ($activePlayTypeName) {
                                    return trim((string) $playType->name) === $activePlayTypeName;
                                });
                            @endphp

                            {{-- وقت البداية والنهاية --}}
                            <div style="display:grid;grid-template-columns:1fr 1px 1fr;align-items:center;gap:8px;width:100%;">
                                <div style="text-align:center;">
                                    <div style="font-size:.6rem;opacity:.7;font-weight:600;margin-bottom:2px;">بداية الوقت</div>
                                    <div style="font-size:.85rem;font-weight:800;font-family:monospace;direction:ltr;unicode-bidi:isolate;">{{ $displayStartTime?->format('h:i A') ?? '—' }}</div>
                                </div>
                                <div style="width:1px;background:rgba(255,255,255,.25);"></div>
                                <div style="text-align:center;">
                                    <div style="font-size:.6rem;opacity:.7;font-weight:600;margin-bottom:2px;">نهاية الوقت</div>
                                    <div style="font-size:.85rem;font-weight:800;font-family:monospace;direction:ltr;unicode-bidi:isolate;">{{ $isTimed ? ($displayEndTime?->format('h:i A') ?? '—') : '—' }}</div>
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
                                    <div
                                        style="font-size:.9rem;font-weight:800;font-family:monospace;letter-spacing:.5px;color:#fef08a;direction:ltr;unicode-bidi:isolate;"
                                        @if ($startTime)
                                            x-data="{ initialSeconds: {{ $secondsValue }}, mountedAt: Math.floor(Date.now() / 1000) }"
                                            @if ($isTimed)
                                                x-text="formatDuration(Math.max(0, initialSeconds - (clockNow - mountedAt)))"
                                            @else
                                                x-text="formatDuration(Math.max(0, initialSeconds + (clockNow - mountedAt)))"
                                            @endif
                                        @endif
                                    >{{ $runningCounter }}</div>
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

                            {{-- اسم العميل إن وجد --}}
                            @if ($device->activeSession?->customer)
                                <div style="display:flex;align-items:center;justify-content:center;gap:6px;background:rgba(255,255,255,.1);padding:4px 10px;border-radius:8px;border:1px solid rgba(255,255,255,.15);margin-top:2px;">
                                    <svg style="width:14px;height:14px;opacity:.8;flex-shrink:0;color:#a5f3fc;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                                    </svg>
                                    <span style="font-size:.78rem;font-weight:700;color:#a5f3fc;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;max-width:130px;" title="{{ $device->activeSession->customer->name }}">
                                        {{ $device->activeSession->customer->name }}
                                    </span>
                                </div>
                            @endif

                            {{-- زر تقفيل الحساب --}}
                            <button
                                type="button"
                                @if ($isTimed && $secondsValue > 0)
                                    @click.stop="if (confirm('الوقت الخاص بالجهاز ده لسه منتهاش هل أنت متأكد من إنك تريد تقفيل الوقت الأن ؟')) { $wire.openCloseInvoiceModal({{ $device->id }}) }"
                                @else
                                    wire:click.stop="openCloseInvoiceModal({{ $device->id }})"
                                @endif
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
                                            wire:click.stop="checkReservationBeforeStart({{ $device->id }}, {{ $pp->id }})"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="opacity-60 cursor-wait"
                                            wire:target="checkReservationBeforeStart({{ $device->id }}, {{ $pp->id }})"
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
                                                <span wire:loading wire:target="checkReservationBeforeStart({{ $device->id }}, {{ $pp->id }})">
                                                    <svg style="width:13px;height:13px;flex-shrink:0;animation:spin 1s linear infinite;" fill="none" viewBox="0 0 24 24">
                                                        <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                                        <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                                    </svg>
                                                </span>
                                                <span wire:loading.remove wire:target="checkReservationBeforeStart({{ $device->id }}, {{ $pp->id }})">
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
