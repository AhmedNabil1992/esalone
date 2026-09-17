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
                            <strong style="direction:ltr;unicode-bidi:isolate;">{{ $limitSessionStart ? app(\App\Support\Tenant\TenantClock::class)->utcToLocal($limitSessionStart)->format('h:i A') : '—' }}</strong>
                            <br>
                            الوقت الحالي المنقضي تقريبيًا: <strong>{{ $elapsedFromSessionStart }} د</strong>
                            <br>
                            أقل وقت إجمالي مسموح الآن: <strong>{{ $minimumAllowedMinutes }} د</strong>
                            @if ($limitCurrentDetail?->end_time)
                                <br>
                                نهاية الجزء الحالي:
                                <strong style="direction:ltr;unicode-bidi:isolate;">{{ app(\App\Support\Tenant\TenantClock::class)->utcToLocal($limitCurrentDetail->end_time)->format('h:i A') }}</strong>
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
                            style="width:100%;border-radius:10px;padding:8px 10px;font-size:.85rem;margin-bottom:12px;"
                            class="bg-white dark:!bg-zinc-950 border border-slate-300 dark:!border-zinc-700 text-slate-900 dark:!text-zinc-100 placeholder-slate-400 dark:!placeholder-zinc-500"
                        />

                        <div style="display:flex;justify-content:flex-end;gap:8px;">
                            <button type="button" wire:click="closeBusyActionDetailsModal" style="border:1px solid #d1d5db;background:#fff;color:#111827;padding:8px 14px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                إلغاء
                            </button>
                            <button type="button" wire:click="limitBusySessionTime" style="border:1px solid #2563eb;background:#2563eb;color:#fff;padding:8px 14px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                حفظ
                            </button>
                        </div>
