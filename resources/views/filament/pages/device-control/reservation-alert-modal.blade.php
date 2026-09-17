        @if ($showReservationAlertModal)
            @php
                $pendingDevice = \App\Models\Tenant\Devices::find($pendingStartDeviceId);
                $todayReservations = $this->getTodayReservations();
            @endphp
            <div class="device-control-modal-backdrop" style="position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:70;display:flex;align-items:center;justify-content:center;padding:16px;" wire:click="closeReservationAlertModal">
                <div style="width:min(500px,100%);border-radius:18px;box-shadow:0 24px 64px rgba(0,0,0,.35);padding:24px;" wire:click.stop class="device-control-modal-panel bg-white dark:!bg-zinc-900 border border-slate-200 dark:!border-zinc-800 text-slate-900 dark:!text-zinc-100">
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
                                $startTime = app(\App\Support\Tenant\TenantClock::class)->utcToLocal($res->start_time)->format('h:i A');
                                $endTime = app(\App\Support\Tenant\TenantClock::class)->utcToLocal($res->end_time)->format('h:i A');
                            @endphp
                            <div style="font-size:.76rem;color:#374151;font-weight:600;padding:6px 0;display:flex;justify-content:space-between;border-bottom:1px solid #f3f4f6;align-items:center;">
                                <span>• من {{ $startTime }} إلى {{ $endTime }}</span>
                                <span>العميل: {{ $res->customer?->name ?? 'بدون اسم' }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="device-control-modal-actions" style="display:flex;gap:10px;justify-content:flex-end;">
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
