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

            <div class="device-control-modal-backdrop" style="position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:70;display:flex;align-items:center;justify-content:center;padding:16px;" wire:click="closeBusyActionsModal">
                <div style="width:min(560px,100%);max-height:90vh;overflow:auto;border-radius:18px;box-shadow:0 24px 64px rgba(0,0,0,.35);padding:18px;" wire:click.stop class="device-control-modal-panel bg-white dark:!bg-zinc-900 border border-slate-200 dark:!border-zinc-800 text-slate-900 dark:!text-zinc-100">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:14px;">
                        <div>
                            <div style="font-size:1.05rem;font-weight:800;" class="text-slate-900 dark:!text-zinc-100">{{ $busyDevice?->name ?? 'الجهاز' }}</div>
                            <div style="font-size:.76rem;margin-top:2px;" class="text-slate-500 dark:!text-zinc-400">خيارات الجهاز</div>
                        </div>
                        <button type="button" wire:click="closeBusyActionsModal" style="width:34px;height:34px;border-radius:10px;cursor:pointer;" class="bg-slate-50 dark:!bg-zinc-800 border border-slate-200 dark:!border-zinc-700 text-slate-700 dark:!text-zinc-300">
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
                                style="display:flex;align-items:center;gap:8px;border-radius:12px;padding:10px 12px;cursor:pointer;text-align:right;font-size:.82rem;font-weight:700;"
                                class="bg-white dark:!bg-zinc-950 border border-slate-200 dark:!border-zinc-800 text-slate-900 dark:!text-zinc-100 hover:bg-blue-50/50 dark:hover:!bg-zinc-800/80"
                            >
                                <x-filament::icon :icon="$action['icon']" style="width:18px;height:18px;color:#2563eb;" />
                                <span>{{ $action['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif


