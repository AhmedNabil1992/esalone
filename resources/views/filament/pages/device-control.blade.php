<x-filament-panels::page>
    @include('filament.pages.device-control.styles')
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
        x-data="{
            sessions: [],
            warned5Min: {},
            warnedFinished: {},
            enableSpeech: @js($alarmSoundActive),
            init() {
                const channelName = @js($this->getRealtimeDeviceControlChannel());

                const setupEcho = () => {
                    if (window.Echo) {
                        window.Echo.channel(channelName)
                            .listen('.device-control.updated', () => {
                                $wire.$refresh();
                            });
                    } else {
                        setTimeout(setupEcho, 300);
                    }
                };
                setupEcho();

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
        class="device-control-page"
    >

        {{-- بيانات الجلسات النشطة ممررة للـ JS --}}
        <div
            id="active-sessions-data"
            style="display:none;"
            data-sessions="{{ json_encode($activeTimedSessions) }}"
            x-ref="sessionsData"
        ></div>

        {{-- ===== تبويبات أنواع الأجهزة ===== --}}
        @include('filament.pages.device-control.device-overview')
        @include('filament.pages.device-control.busy-actions-modal')
        @include('filament.pages.device-control.busy-action-details-modal')
        @include('filament.pages.device-control.start-session-modal')
    </div>

    @include('filament.pages.device-control.printing-script')
    @include('filament.pages.device-control.bridge-script')
    @include('filament.pages.device-control.reservation-alert-modal')
</x-filament-panels::page>
