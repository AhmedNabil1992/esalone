<x-filament-panels::page>
    <style>
        @keyframes pulse-border {
            0% { border-color: #ef4444; box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            70% { border-color: #ef4444; box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
            100% { border-color: #ef4444; box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
    </style>
    @php
        $allDevices = $this->devices;
        $alarmSoundActive = in_array(
            (string) \App\Models\Tenant\SystemConfig::get('sys_alarm_sound', '0'),
            ['1', 'true', 'on', 'yes'],
            true
        );

        $activeTimedSessions = [];
        foreach ($allDevices as $dev) {
            if ($dev->status === 'busy' && $dev->activeSession) {
                $latest = $dev->activeSession->latestDetail;
                if ($latest && $latest->period !== null && $latest->end_time !== null) {
                    $endTime = $latest->end_time;
                    $remSec = now()->lt($endTime) ? now()->diffInSeconds($endTime) : 0;
                    $activeTimedSessions[] = [
                        'deviceId' => $dev->id,
                        'deviceName' => $dev->name,
                        'sessionId' => $dev->activeSession->id,
                        'remainingSeconds' => $remSec,
                    ];
                }
            }
        }

        $upcomingReservations = \App\Models\Tenant\Reservation::query()
            ->where('status', 'active')
            ->where('start_time', '>=', now())
            ->where('start_time', '<=', now()->addMinutes(30))
            ->with('customer')
            ->get()
            ->groupBy('device_id');
    @endphp

    <div
        dir="rtl"
        wire:poll.30000ms
        x-data="{
            sessions: [],
            warned5Min: {},
            warnedFinished: {},
            enableSpeech: @js($alarmSoundActive),
            init() {
                const channelName = @js($this->getRealtimeDeviceControlChannel());

                if (window.Echo) {
                    window.Echo.channel(channelName)
                        .listen('.device-control.updated', () => {
                            $wire.$refresh();
                        });
                }

                // Initial load of sessions
                this.syncSessionsFromDOM();

                // Start the countdown timer
                setInterval(() => {
                    this.syncSessionsFromDOM();

                    this.sessions.forEach(session => {
                        if (session.remainingSeconds > 0) {
                            session.remainingSeconds--;
                        }

                        // 5 Minutes Warning (300 seconds)
                        if (session.remainingSeconds <= 300 && session.remainingSeconds > 0) {
                            const key = session.sessionId;
                            if (!this.warned5Min[key]) {
                                this.warned5Min[key] = true;
                                this.triggerAlarm(session.deviceName, 'five_minutes');
                            }
                        }

                        // Time finished Warning (0 seconds)
                        if (session.remainingSeconds <= 0) {
                            const key = session.sessionId;
                            if (!this.warnedFinished[key]) {
                                this.warnedFinished[key] = true;
                                this.triggerAlarm(session.deviceName, 'finished');
                                $wire.turnOffDeviceViaBridge(session.deviceId);
                            }
                        }
                    });
                }, 1000);
            },
            syncSessionsFromDOM() {
                const dataEl = this.$refs.sessionsData;
                if (!dataEl) return;
                
                try {
                    const newSessions = JSON.parse(dataEl.getAttribute('data-sessions') || '[]');
                    
                    newSessions.forEach(ns => {
                        const existing = this.sessions.find(s => s.sessionId === ns.sessionId);
                        if (existing) {
                            if (Math.abs(existing.remainingSeconds - ns.remainingSeconds) > 10) {
                                existing.remainingSeconds = ns.remainingSeconds;
                            }
                        } else {
                            this.sessions.push({
                                deviceId: ns.deviceId,
                                deviceName: ns.deviceName,
                                sessionId: ns.sessionId,
                                remainingSeconds: ns.remainingSeconds
                            });
                        }
                    });

                    this.sessions = this.sessions.filter(s => newSessions.some(ns => ns.sessionId === s.sessionId));

                    // Cleanup warning states of inactive sessions
                    Object.keys(this.warned5Min).forEach(key => {
                        if (!this.sessions.some(s => s.sessionId == key)) {
                            delete this.warned5Min[key];
                        }
                    });
                    Object.keys(this.warnedFinished).forEach(key => {
                        if (!this.sessions.some(s => s.sessionId == key)) {
                            delete this.warnedFinished[key];
                        }
                    });
                } catch (e) {
                    console.error('Error parsing active sessions:', e);
                }
            },
            triggerAlarm(deviceName, type) {
                if (!this.enableSpeech) return;

                this.playBeep();

                let text = '';
                if (type === 'five_minutes') {
                    text = `جهاز ${deviceName} متبقي خمس دقائق لانتهاء الوقت`;
                } else {
                    text = `جهاز ${deviceName} انتهى الوقت المحدد`;
                }

                // TTS (Text to Speech)
                if ('speechSynthesis' in window) {
                    const synth = window.speechSynthesis;
                    synth.cancel();
                    const utterance = new SpeechSynthesisUtterance(text);
                    utterance.lang = 'ar';
                    utterance.rate = 0.95;
                    
                    const voices = synth.getVoices();
                    const arabicVoice = voices.find(v => v.lang.startsWith('ar'));
                    if (arabicVoice) {
                        utterance.voice = arabicVoice;
                    }
                    
                    synth.speak(utterance);
                }

                // Browser Notification
                if ('Notification' in window && Notification.permission === 'granted') {
                    new Notification(type === 'five_minutes' ? 'الوقت يقترب من الانتهاء' : 'انتهى الوقت المحدد', {
                        body: text,
                        dir: 'rtl',
                        lang: 'ar'
                    });
                }
            },
            playBeep() {
                try {
                    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                    
                    const playSingleBeep = (time, freq) => {
                        const oscillator = audioCtx.createOscillator();
                        const gainNode = audioCtx.createGain();
                        oscillator.connect(gainNode);
                        gainNode.connect(audioCtx.destination);
                        oscillator.type = 'sine';
                        oscillator.frequency.value = freq;
                        gainNode.gain.setValueAtTime(0.8, time);
                        oscillator.start(time);
                        oscillator.stop(time + 0.12);
                    };

                    const now = audioCtx.currentTime;
                    playSingleBeep(now, 900);
                    playSingleBeep(now + 0.2, 900);
                } catch (e) {
                    console.error('Error playing beep sound:', e);
                }
            }
        }"
        x-init="
            init();
            if (enableSpeech && 'Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission();
            }
        "
        style="display:flex;flex-direction:column;gap:20px;"
    >

        {{-- بيانات الجلسات النشطة ممررة للـ JS --}}
        <div
            id="active-sessions-data"
            style="display:none;"
            data-sessions="{{ json_encode($activeTimedSessions) }}"
            x-ref="sessionsData"
        ></div>

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
            $busyCount   = $allDevices->where('status', 'busy')->count();
            $availCount  = $allDevices->where('status', 'active')->count();
            $total       = $allDevices->count();
        @endphp
        @if ($allDevices->isNotEmpty())
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
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
                تحديث لحظي + احتياطي كل 30 ث
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
                        @php
                            $soonReservation = isset($upcomingReservations[$device->id]) ? $upcomingReservations[$device->id]->first() : null;
                        @endphp
                        @if ($soonReservation)
                            @php
                                $resStartTime = \Carbon\Carbon::parse($soonReservation->start_time);
                                $resMinutesLeft = max(1, (int) now()->diffInMinutes($resStartTime));
                            @endphp
                            <div style="background: rgba(254, 242, 242, 0.95); border: 1.5px solid #ef4444; color: #991b1b; font-size: 0.72rem; padding: 6px 10px; border-radius: 10px; font-weight: 700; display: flex; align-items: center; gap: 6px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 4px; animation: pulse-border 2s infinite;">
                                <svg style="width: 14px; height: 14px; color: #ef4444; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span style="flex:1;text-align:right;">
                                    حجز قادم بعد {{ $resMinutesLeft }} دقيقة
                                    ({{ $resStartTime->format('h:i A') }})
                                </span>
                            </div>
                        @endif

                        @if ($isBusy)
                            @php
                                $activeDetail = $device->activeSession?->latestDetail;
                                $startTime = $device->activeSession?->getOriginalStartTime() ?? $activeDetail?->start_time;
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

        @if ($showBusyActionsModal)
            @php
                $busyDevice = $this->selectedBusyDevice;
                $busyLatestDetail = $busyDevice?->activeSession?->latestDetail;
                $busyIsTimed = $busyLatestDetail?->period !== null && $busyLatestDetail?->end_time !== null;
                $busyActions = [
                    ['label' => 'إضافة طلبات', 'icon' => 'heroicon-o-plus-circle'],
                    ['label' => 'إضافة وقت', 'icon' => 'heroicon-o-clock'],
                    ['label' => 'بيانات الجهاز', 'icon' => 'heroicon-o-information-circle'],
                    ['label' => 'تحديد الوقت', 'icon' => 'heroicon-o-adjustments-horizontal'],
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
                                @if ($action['label'] === 'تقفيل الحساب' && $busyIsTimed && $busyLatestDetail?->end_time?->isFuture())
                                    @click.stop="if (confirm('الوقت الخاص بالجهاز ده لسه منتهاش هل أنت متأكد من إنك تريد تقفيل الوقت الأن ؟')) { $wire.openBusyActionDetails('تقفيل الحساب') }"
                                @else
                                    wire:click="openBusyActionDetails('{{ $action['label'] }}')"
                                @endif
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
                                                    <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;">السعر</th>
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
                                                        <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;color:#374151;direction:ltr;unicode-bidi:isolate;">{{ number_format((float) ($row['play_price'] ?? 0), 2) }}</td>
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
                                @if ((float) ($closePreview['discount_amount'] ?? 0) > 0)
                                    <div style="font-size:.78rem;color:#ef4444;font-weight:800;">الخصم المطبق: <strong>{{ number_format((float) ($closePreview['discount_amount'] ?? 0), 2) }}</strong></div>
                                @endif
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
                                            <span style="overflow:hidden;white-space:nowrap;text-overflow:ellipsis;color:#111827;display:flex;align-items:center;gap:6px;">
                                                @if ($this->busySelectedCustomer)
                                                    <span>{{ $this->busySelectedCustomer->name }} - {{ $this->busySelectedCustomer->phone }}</span>
                                                    <span style="color: {{ $this->busySelectedCustomer->balance < 0 ? '#dc2626' : '#16a34a' }}; font-weight: bold; font-size: .78rem;">
                                                        (الرصيد: {{ number_format($this->busySelectedCustomer->balance, 2) }} ج.م)
                                                    </span>
                                                @else
                                                    بدون عميل
                                                @endif
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
                                                        style="text-align:right;border:1px solid #e5e7eb;background:#fff;border-radius:8px;padding:7px 9px;font-size:.8rem;color:#111827;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:8px;width:100%;"
                                                    >
                                                        <span>{{ $customer->name }} - {{ $customer->phone }}</span>
                                                        <span style="color: {{ $customer->balance < 0 ? '#dc2626' : '#16a34a' }}; font-weight: bold; font-size: .78rem;">
                                                            (الرصيد: {{ number_format($customer->balance, 2) }} ج.م)
                                                        </span>
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

                                    <div style="margin-top:10px;">
                                        <label style="display:block;font-size:.7rem;color:#6b7280;margin-bottom:3px;">رقم الواتساب لإرسال الفاتورة (اختياري)</label>
                                        <input
                                            type="text"
                                            wire:model="busyActionWhatsappPhone"
                                            placeholder="مثال: 01012345678"
                                            style="width:100%;border:1px solid #d1d5db;border-radius:10px;padding:8px 10px;font-size:.8rem;"
                                        />
                                    </div>
                                </div>
                                <div style="display:flex;align-items:flex-end;">
                                    <div style="font-size:.86rem;font-weight:800;color:#111827;">
                                        الصافي: <span style="color:#2563eb;direction:ltr;unicode-bidi:isolate;">{{ number_format((float) ($closePreview['final_amount'] ?? 0), 2) }}</span>
                                        <br>
                                        المتبقي: <span style="color:{{ (float) ($closePreview['remain_amount'] ?? 0) < 0 ? '#dc2626' : '#16a34a' }};direction:ltr;unicode-bidi:isolate;">{{ number_format((float) ($closePreview['remain_amount'] ?? 0), 2) }}</span>
                                    </div>
                                </div>
                            </div>

                            @php
                                $canCancelOrder = $this->canCancelBusySessionOrder;
                                $cancelOrderWindow = $this->cancelOrderAllowedMinutes;
                                $cancelOrderRemaining = $this->busyCancelOrderRemainingMinutes;
                            @endphp

                            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
                                <button type="button" wire:click="closeBusyActionDetailsModal" style="border:1px solid #d1d5db;background:#fff;color:#111827;padding:8px 14px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">إلغاء</button>
                                @if ($canCancelOrder)
                                    <button type="button" wire:click="cancelBusySessionOrder" style="border:1px solid #dc2626;background:#dc2626;color:#fff;padding:8px 14px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                        إلغاء الأوردر
                                    </button>
                                @endif
                                <button type="button" wire:click="closeBusySessionInvoice" style="border:1px solid #16a34a;background:#16a34a;color:#fff;padding:8px 14px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">تقفيل الحساب</button>
                            </div>

                            @if (! $canCancelOrder && $cancelOrderWindow > 0)
                                <div style="margin-top:8px;font-size:.74rem;color:#b45309;">
                                    تم تجاوز وقت السماح لإلغاء الأوردر. وقت السماح: {{ $cancelOrderWindow }} دقيقة.
                                </div>
                            @elseif ($canCancelOrder && $cancelOrderRemaining !== null)
                                <div style="margin-top:8px;font-size:.74rem;color:#065f46;">
                                    متاح إلغاء الأوردر خلال {{ $cancelOrderRemaining }} دقيقة متبقية.
                                </div>
                            @endif

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
                                    <input
                                        type="text"
                                        wire:model.live="orderItemNotes"
                                        placeholder="ملاحظات (اختياري)"
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

                        <!-- قسم إدارة العميل والمستحقات -->
                        <div style="grid-column:1/-1; background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:16px; margin-bottom:14px;">
                            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:16px; align-items:center;">
                                
                                {{-- الجزء الأيمن: اختيار العميل والبحث --}}
                                <div style="display:grid; gap:6px;">
                                    <label style="display:flex; align-items:center; gap:6px; font-size:.78rem; font-weight:700; color:#374151;">
                                        <svg style="width:14px;height:14px;color:#4b5563;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.052-.03c0-.225.012-.447.038-.667A11.94 11.94 0 0112 3c2.17 0 4.207.576 5.963 1.584A6.06 6.06 0 0118 5.281m-12 0a5.969 5.969 0 00-.94 3.197m0 0A5.996 5.996 0 0012 11.25a5.996 5.996 0 005.058-2.772m0 0a3 3 0 004.681-2.72 8.986 8.986 0 00-3.74-.477m-.053.03c0 .225-.012.447-.038.667"/>
                                        </svg>
                                        تعيين عميل للجلسة
                                        <span style="font-size:.7rem; font-weight:normal; color:#6b7280;">(ضروري لترحيل الديون)</span>
                                    </label>

                                    <div x-data="{ open: false }" style="position:relative;">
                                        <button
                                            type="button"
                                            @click="open = !open"
                                            style="width:100%; border:1px solid #cbd5e1; border-radius:10px; padding:9px 12px; font-size:.84rem; background:#fff; display:flex; align-items:center; justify-content:space-between; gap:8px; cursor:pointer; box-shadow: 0 1px 2px rgba(0,0,0,0.05); transition: border-color 0.15s ease;"
                                            onmouseover="this.style.borderColor='#94a3b8'"
                                            onmouseout="this.style.borderColor='#cbd5e1'"
                                        >
                                            <span style="overflow:hidden; white-space:nowrap; text-overflow:ellipsis; color:#0f172a; font-weight:600; display:flex; align-items:center; gap:6px;">
                                                @if ($this->busySelectedCustomer)
                                                    <span style="width:8px; height:8px; border-radius:50%; background:#10b981; display:inline-block;"></span>
                                                    {{ $this->busySelectedCustomer->name }}
                                                @else
                                                    <span style="width:8px; height:8px; border-radius:50%; background:#94a3b8; display:inline-block;"></span>
                                                    بدون عميل (عام)
                                                @endif
                                            </span>
                                            <svg style="width:14px; height:14px; color:#64748b; flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>

                                        <div
                                            x-cloak
                                            x-show="open"
                                            @click.outside="open = false"
                                            style="position:absolute; z-index:30; top:calc(100% + 6px); left:0; right:0; background:#fff; border:1px solid #cbd5e1; border-radius:12px; box-shadow:0 16px 36px rgba(15,23,42,0.15); padding:10px; display:grid; gap:8px;"
                                        >
                                            <div style="position:relative; display:flex; align-items:center;">
                                                <input
                                                    type="text"
                                                    wire:model.live.debounce.300ms="busyActionCustomerSearch"
                                                    placeholder="ابحث بالاسم أو رقم الهاتف..."
                                                    style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px 12px; font-size:.82rem; padding-left:32px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);"
                                                />
                                                <svg style="width:14px; height:14px; color:#94a3b8; position:absolute; left:10px; pointer-events:none;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                                </svg>
                                            </div>

                                            <button
                                                type="button"
                                                wire:click="clearBusyActionCustomer"
                                                @click="open = false"
                                                style="text-align:right; border:1px dashed #cbd5e1; background:#f8fafc; border-radius:8px; padding:8px 12px; font-size:.8rem; color:#475569; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:6px;"
                                                onmouseover="this.style.background='#f1f5f9'"
                                                onmouseout="this.style.background='#f8fafc'"
                                            >
                                                <span style="width:6px; height:6px; border-radius:50%; background:#ef4444; display:inline-block;"></span>
                                                إلغاء التعيين (بدون عميل)
                                            </button>

                                            <div style="max-height:160px; overflow-y:auto; display:grid; gap:4px; padding-right:2px;">
                                                @forelse ($this->busyFilteredCustomers as $customer)
                                                    <button
                                                        type="button"
                                                        wire:click="selectBusyActionCustomer({{ $customer->id }})"
                                                        @click="open = false"
                                                        style="text-align:right; border:1px solid #f1f5f9; background:#fff; border-radius:8px; padding:8px 12px; font-size:.8rem; color:#1e293b; cursor:pointer; display:flex; flex-direction:column; gap:2px; transition: all 0.15s ease;"
                                                        onmouseover="this.style.background='#f1f5f9'; this.style.borderColor='#cbd5e1'"
                                                        onmouseout="this.style.background='#fff'; this.style.borderColor='#f1f5f9'"
                                                    >
                                                        <span style="font-weight:700;">{{ $customer->name }}</span>
                                                        <span style="font-size:.72rem; color:#64748b;">{{ $customer->phone ?: 'بدون هاتف' }}</span>
                                                    </button>
                                                @empty
                                                    <div style="text-align:center; padding:12px; color:#64748b; font-size:.78rem;">لا يوجد عميل مطابق للبحث</div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- الجزء الأيسر: بطاقة بيانات العميل المختار --}}
                                <div>
                                    @if ($this->busySelectedCustomer)
                                        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:10px 12px; display:flex; flex-direction:column; gap:6px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                                            <div style="display:flex; align-items:center; gap:6px;">
                                                <svg style="width:16px; height:16px; color:#3b82f6;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
                                                </svg>
                                                <span style="font-size:.8rem; font-weight:700; color:#334155;">معلومات الحساب</span>
                                            </div>
                                            
                                            <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:2px;">
                                                {{-- رقم الهاتف --}}
                                                <span style="font-size:.72rem; font-weight:600; color:#2563eb; background:#eff6ff; padding:3px 8px; border-radius:6px; display:flex; align-items:center; gap:4px;">
                                                    <svg style="width:11px; height:11px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-2.824-1.802-5.194-4.174-7-7l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                                                    </svg>
                                                    {{ $this->busySelectedCustomer->phone ?: 'لا يوجد' }}
                                                </span>

                                                {{-- الرصيد الحالي --}}
                                                @php
                                                    $balance = (float) ($this->busySelectedCustomer->balance ?? 0);
                                                    $balanceColor = $balance >= 0 ? '#10b981' : '#ef4444';
                                                    $balanceBg = $balance >= 0 ? '#ecfdf5' : '#fef2f2';
                                                    $balanceText = $balance >= 0 ? 'رصيد: ' . number_format($balance, 2) : 'دين: ' . number_format(abs($balance), 2);
                                                @endphp
                                                <span style="font-size:.72rem; font-weight:700; color:{{ $balanceColor }}; background:{{ $balanceBg }}; padding:3px 8px; border-radius:6px; display:flex; align-items:center; gap:4px;">
                                                    <svg style="width:11px; height:11px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    {{ $balanceText }}
                                                </span>

                                                {{-- النقاط --}}
                                                @if ((float) ($this->busySelectedCustomer->points ?? 0) > 0)
                                                    <span style="font-size:.72rem; font-weight:600; color:#d97706; background:#fffbeb; padding:3px 8px; border-radius:6px; display:flex; align-items:center; gap:4px;">
                                                        <svg style="width:11px; height:11px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499c.174-.612 1.042-.612 1.216 0l1.919 6.755a1 1 0 00.95.69h7.108c.637 0 .902.817.387 1.19l-5.75 4.18a1 1 0 00-.364 1.118l2.225 6.756c.203.616-.506 1.127-1.023.778l-5.75-4.18a1 1 0 00-1.175 0l-5.75 4.18c-.517.349-1.226-.162-1.022-.778l2.225-6.756a1 1 0 00-.364-1.118l-5.75-4.18c-.515-.373-.25-1.19.387-1.19h7.108a1 1 0 00.95-.69l1.918-6.755z"/>
                                                        </svg>
                                                        نقاط: {{ number_format((float) $this->busySelectedCustomer->points, 0) }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <div style="border: 2px dashed #cbd5e1; border-radius:10px; padding:12px; text-align:center; color:#64748b; font-size:.78rem; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:4px; height:100%;">
                                            <svg style="width:20px; height:20px; opacity:.6;" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 111.063 1.06l-.041.02a.75.75 0 11-1.063-1.06zm0 0a2.25 2.25 0 104.5 0 2.25 2.25 0 00-4.5 0zM21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span>جلسة عامة بدون عميل معين</span>
                                        </div>
                                    @endif
                                </div>

                            </div>
                        </div>
                        <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:start;">
                            
                            <div style="flex:1;min-width:min(100%,300px);border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                                <div style="padding:10px 12px;background:#f0fdf4;color:#15803d;font-size:.8rem;font-weight:800;">
                                    بيانات الوقت
                                </div>

                                <div style="max-height:320px;overflow:auto;">
                                    <table style="width:100%;border-collapse:collapse;font-size:.76rem;">
                                        <thead style="background:#f9fafb;color:#374151;">
                                            <tr>
                                                <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:right;">نوع اللعب</th>
                                                <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;">السعر</th>
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
                                                        {{ $detail->play_price ?: '—' }}
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
                                                    <td colspan="6" style="padding:16px;text-align:center;color:#6b7280;">لا توجد بيانات وقت على هذه الجلسة.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

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
                                                <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;">ملاحظات</th>
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
                                                    <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;color:#374151;">
                                                        {{ $order->notes ?? '—' }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" style="padding:16px;text-align:center;color:#6b7280;">لا توجد طلبات على هذه الجلسة.</td>
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
                            $quickLimitMinutes = array_map('intval', $limitDevice?->type?->fixed_times ?? [60, 90, 120, 180, 240, 300]);
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
                $todayReservations = $this->getTodayReservations();
                $hasReservationToday = $todayReservations->isNotEmpty();
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

                    <div style="padding:10px 14px;border-radius:12px;background:{{ $hasReservationToday ? '#fef2f2' : '#f0fdf4' }};border:1px solid {{ $hasReservationToday ? '#fecaca' : '#bbf7d0' }};margin-bottom:14px;">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:{{ $hasReservationToday ? '8px' : '0' }};">
                            <span style="width:8px;height:8px;border-radius:9999px;background:{{ $hasReservationToday ? '#ef4444' : '#16a34a' }};display:inline-block;"></span>
                            <span style="font-size:.78rem;font-weight:700;color:#374151;">
                                حالة الحجز اليوم: {{ $hasReservationToday ? 'يوجد حجز' : 'لا يوجد حجز' }}
                            </span>
                        </div>
                        @if($hasReservationToday)
                            <div style="display:grid;gap:6px;padding-right:16px;border-top:1px dashed #fca5a5;padding-top:8px;">
                                @foreach($todayReservations as $res)
                                    @php
                                        $startTime = \Carbon\Carbon::parse($res->start_time)->format('h:i A');
                                        $endTime = \Carbon\Carbon::parse($res->end_time)->format('h:i A');
                                    @endphp
                                    <div style="font-size:.74rem;color:#7f1d1d;font-weight:600;display:flex;justify-content:space-between;align-items:center;">
                                        <span>• من {{ $startTime }} إلى {{ $endTime }}</span>
                                        <span>العميل: {{ $res->customer?->name ?? 'بدون اسم' }} ({{ $res->playPrice?->playType?->name }})</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
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
                            @php
                                $fixedMinutes = array_map('intval', $selectedDevice?->type?->fixed_times ?? [15, 30, 60, 120]);
                            @endphp
                            @foreach ($fixedMinutes as $fixedMinute)
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

    @php
        $invoicePrinter = \App\Models\Tenant\SystemConfig::get('sys_invoice_printer', '');
        $orderPrinter = \App\Models\Tenant\SystemConfig::get('sys_order_printer', '');
        $bridgeUrl = config('services.bridge.url', 'ws://127.0.0.1:9000');
    @endphp

    <script>
        document.addEventListener('livewire:init', () => {
            const silentPrintFrameId = 'silent-print-bridge-frame';
            const invoicePrinter = @js($invoicePrinter);
            const orderPrinter = @js($orderPrinter);
            const bridgeUrl = @js($bridgeUrl);

            function printFallback(url) {
                console.warn('Bridge printing failed or offline. Falling back to browser window.');
                try {
                    const urlObj = new URL(url, window.location.origin);
                    urlObj.searchParams.set('fallback', '1');
                    url = urlObj.toString();
                } catch (e) {
                    console.error('Failed to append fallback parameter:', e);
                }
                const popup = window.open(url, '_blank', 'noopener,noreferrer');
                if (!popup) {
                    alert('تم حظر فتح نافذة الطباعة من قبل المتصفح، يرجى السماح بالنوافذ المنبثقة لطباعة الفواتير.');
                }
            }

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

                // Silent printing via Bridge
                let targetPrinter = invoicePrinter;
                if (url.includes('order') || url.includes('prepare')) {
                    targetPrinter = orderPrinter;
                }

                try {
                    const urlObj = new URL(url, window.location.origin);
                    const printerParam = urlObj.searchParams.get('printer');
                    if (printerParam) {
                        targetPrinter = printerParam;
                    }
                } catch (e) {
                    console.error('Failed to parse printer param from URL:', e);
                }

                if (!bridgeUrl || !targetPrinter) {
                    console.warn('Bridge URL or printer not configured. Falling back to browser.');
                    printFallback(url);
                    return;
                }

                fetch(url)
                    .then(response => response.text())
                    .then(htmlContent => {
                        const cleanedHtml = htmlContent.replace(/window\.print\(\);?/gi, '');
                        
                        let socket;
                        let timeoutId;
                        
                        const handleFallback = (reason) => {
                            if (timeoutId) {
                                clearTimeout(timeoutId);
                                timeoutId = null;
                            }
                            if (socket) {
                                try {
                                    socket.close();
                                } catch (e) {}
                            }
                            console.warn('Bridge printing failed (' + reason + '). Falling back to browser.');
                            printFallback(url);
                        };

                        timeoutId = setTimeout(() => {
                            handleFallback('timeout');
                        }, 5000);

                        try {
                            socket = new WebSocket(bridgeUrl);
                            
                            socket.onopen = () => {
                                socket.send('PRINT|' + targetPrinter + '|' + cleanedHtml);
                            };

                            socket.onmessage = (event) => {
                                if (timeoutId) {
                                    clearTimeout(timeoutId);
                                    timeoutId = null;
                                }
                                try {
                                    const resp = JSON.parse(event.data);
                                    if (resp.status === 'success' || resp.status === 'printed') {
                                        console.log('Successfully printed via Bridge on ' + targetPrinter);
                                    } else {
                                        console.error('Bridge print failed:', resp.message);
                                        handleFallback('print_error: ' + resp.message);
                                    }
                                } catch (e) {
                                    console.error('Failed to parse bridge response:', e);
                                    handleFallback('parse_error');
                                }
                                try { socket.close(); } catch (e) {}
                            };

                            socket.onerror = (err) => {
                                console.error('Bridge connection error:', err);
                                handleFallback('connection_error');
                            };
                        } catch (e) {
                            console.error('Failed to establish WebSocket connection:', e);
                            handleFallback('websocket_init_error');
                        }
                    })
                    .catch(err => {
                        console.error('Failed to fetch invoice HTML, falling back to browser window:', err);
                        printFallback(url);
                    });
            });
        });
    </script>
        @if ($showReservationAlertModal)
            @php
                $pendingDevice = \App\Models\Tenant\Devices::find($pendingStartDeviceId);
                $todayReservations = $this->getTodayReservations();
            @endphp
            <div style="position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:70;display:flex;align-items:center;justify-content:center;padding:16px;" wire:click="closeReservationAlertModal">
                <div style="width:min(500px,100%);background:#fff;border-radius:18px;box-shadow:0 24px 64px rgba(0,0,0,.35);padding:24px;" wire:click.stop>
                    <div style="text-align:center;margin-bottom:16px;">
                        <div style="width:56px;height:56px;border-radius:9999px;background:#fef2f2;border:1px solid #fca5a5;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                            <svg style="width:28px;height:28px;color:#ef4444;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                        </div>
                        <h3 style="font-size:1.1rem;font-weight:800;color:#111827;margin-bottom:6px;">تنبيه: يوجد حجز مسجل اليوم</h3>
                        <p style="font-size:.82rem;color:#4b5563;line-height:1.4;">
                            جهاز <strong>{{ $pendingDevice?->name }}</strong> لديه حجوزات نشطة اليوم. يرجى التحقق من أوقات الحجز لتجنب تعارض الجلسات:
                        </p>
                    </div>

                    <div style="background:#f9fafb;border-radius:12px;border:1px solid #e5e7eb;padding:12px;margin-bottom:20px;max-height:150px;overflow:auto;">
                        @foreach($todayReservations as $res)
                            @php
                                $startTime = \Carbon\Carbon::parse($res->start_time)->format('h:i A');
                                $endTime = \Carbon\Carbon::parse($res->end_time)->format('h:i A');
                            @endphp
                            <div style="font-size:.76rem;color:#374151;font-weight:600;padding:6px 0;display:flex;justify-content:space-between;border-bottom:1px solid #f3f4f6;align-items:center;">
                                <span>• من {{ $startTime }} إلى {{ $endTime }}</span>
                                <span>العميل: {{ $res->customer?->name ?? 'بدون اسم' }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div style="display:flex;gap:10px;justify-content:flex-end;">
                        <button type="button" wire:click="closeReservationAlertModal" style="flex:1;border:1px solid #d1d5db;background:#fff;color:#374151;padding:10px;border-radius:10px;cursor:pointer;font-size:.82rem;font-weight:700;text-align:center;">
                            إلغاء العملية
                        </button>
                        <button type="button" wire:click="confirmPendingStartSession" style="flex:1;border:none;background:#ef4444;color:#fff;padding:10px;border-radius:10px;cursor:pointer;font-size:.82rem;font-weight:700;text-align:center;">
                            الاستمرار وتشغيل اللعب
                        </button>
                    </div>
                </div>
            </div>
        @endif
</x-filament-panels::page>
