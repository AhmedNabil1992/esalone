        @if ($showBusyActionDetailsModal)
            @php
                $detailModalWidth = $selectedBusyActionLabel === 'بيانات الجهاز'
                    ? 'min(95vw, 1100px)'
                    : ($selectedBusyActionLabel === 'تقفيل الحساب' ? 'min(96vw, 1180px)' : 'min(92vw, 460px)');
            @endphp
            <div class="device-control-modal-backdrop" style="position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:71;display:flex;align-items:center;justify-content:center;padding:12px;" wire:click="closeBusyActionDetailsModal">
                <div style="width:{{ $detailModalWidth }};max-height:92vh;overflow-y:auto;overflow-x:hidden;border-radius:16px;box-shadow:0 24px 64px rgba(0,0,0,.35);padding:18px 20px;" wire:click.stop class="device-control-modal-panel bg-white dark:!bg-zinc-900 border border-slate-200 dark:!border-zinc-800 text-slate-900 dark:!text-zinc-100">
                    <div style="font-size:1rem;font-weight:800;margin-bottom:8px;" class="text-slate-900 dark:!text-zinc-100">{{ $selectedBusyActionLabel }}</div>

                    @if ($selectedBusyActionLabel === 'تقفيل الحساب')
                        @include('filament.pages.device-control.actions.close-session')
                    @elseif ($selectedBusyActionLabel === 'إضافة فلوس')
                        @include('filament.pages.device-control.actions.add-money')
                    @elseif ($selectedBusyActionLabel === 'إضافة طلبات')
                        @include('filament.pages.device-control.actions.add-orders')
                    @elseif ($selectedBusyActionLabel === 'بيانات الجهاز')
                        @include('filament.pages.device-control.actions.device-details')
                    @elseif ($selectedBusyActionLabel === 'تبديل اللعب')
                        @include('filament.pages.device-control.actions.switch-play')
                    @elseif ($selectedBusyActionLabel === 'تبديل أجهزة')
                        @include('filament.pages.device-control.actions.switch-device')
                    @elseif ($selectedBusyActionLabel === 'تحديد الوقت')
                        @include('filament.pages.device-control.actions.limit-time')
                    @elseif ($selectedBusyActionLabel === 'إضافة وقت')
                        @include('filament.pages.device-control.actions.add-time')
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
