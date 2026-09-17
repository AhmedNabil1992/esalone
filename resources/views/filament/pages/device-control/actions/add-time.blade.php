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
                                <strong style="direction:ltr;unicode-bidi:isolate;">{{ $addTimeDetail?->end_time ? app(\App\Support\Tenant\TenantClock::class)->utcToLocal($addTimeDetail->end_time)->format('h:i A') : '—' }}</strong>
                            </div>

                            <input
                                type="number"
                                min="1"
                                max="999"
                                step="1"
                                wire:model.live="busyActionMinutes"
                                placeholder="اكتب عدد الدقائق المطلوب إضافتها"
                                style="width:100%;border-radius:10px;padding:8px 10px;font-size:.85rem;margin-bottom:12px;"
                                class="bg-white dark:!bg-zinc-950 border border-slate-300 dark:!border-zinc-700 text-slate-900 dark:!text-zinc-100 placeholder-slate-400 dark:!placeholder-zinc-500"
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
