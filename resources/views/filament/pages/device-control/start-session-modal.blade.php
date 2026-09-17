        @if ($showStartSessionModal)
            @php
                $selectedDevice = $this->selectedDevice;
                $todayReservations = $this->getTodayReservations();
                $hasReservationToday = $todayReservations->isNotEmpty();
            @endphp

            <div class="device-control-modal-backdrop" style="position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:70;display:flex;align-items:center;justify-content:center;padding:16px;" wire:click="closeStartSessionModal">
                <div style="width:min(680px,100%);max-height:90vh;overflow:auto;border-radius:18px;box-shadow:0 24px 64px rgba(0,0,0,.35);padding:18px;" wire:click.stop class="device-control-modal-panel bg-white dark:!bg-zinc-900 border border-slate-200 dark:!border-zinc-800 text-slate-900 dark:!text-zinc-100">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:14px;">
                        <div>
                            <div style="font-size:1.05rem;font-weight:800;" class="text-slate-900 dark:!text-zinc-100">{{ $selectedDevice?->name ?? 'الجهاز' }}</div>
                            <div style="font-size:.76rem;margin-top:2px;" class="text-slate-500 dark:!text-zinc-400">بدء جلسة جديدة</div>
                        </div>
                        <button type="button" wire:click="closeStartSessionModal" style="width:34px;height:34px;border-radius:10px;cursor:pointer;" class="bg-slate-50 dark:!bg-zinc-800 border border-slate-200 dark:!border-zinc-700 text-slate-700 dark:!text-zinc-300">
                            ✕
                        </button>
                    </div>

                    <div style="padding:10px 14px;border-radius:12px;margin-bottom:14px;" class="{{ $hasReservationToday ? 'bg-rose-50 dark:!bg-rose-950/40 border border-rose-200 dark:!border-rose-900' : 'bg-emerald-50 dark:!bg-emerald-950/40 border border-emerald-200 dark:!border-emerald-900' }}">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:{{ $hasReservationToday ? '8px' : '0' }};">
                            <span style="width:8px;height:8px;border-radius:9999px;display:inline-block;" class="{{ $hasReservationToday ? 'bg-rose-500' : 'bg-emerald-500' }}"></span>
                            <span style="font-size:.78rem;font-weight:700;" class="{{ $hasReservationToday ? 'text-rose-900 dark:!text-rose-200' : 'text-emerald-900 dark:!text-emerald-200' }}">
                                حالة الحجز اليوم: {{ $hasReservationToday ? 'يوجد حجز' : 'لا يوجد حجز' }}
                            </span>
                        </div>
                        @if($hasReservationToday)
                            <div style="display:grid;gap:6px;padding-right:16px;border-top:1px dashed #fca5a5;padding-top:8px;">
                                @foreach($todayReservations as $res)
                                    @php
                                        $startTime = app(\App\Support\Tenant\TenantClock::class)->utcToLocal($res->start_time)->format('h:i A');
                                        $endTime = app(\App\Support\Tenant\TenantClock::class)->utcToLocal($res->end_time)->format('h:i A');
                                    @endphp
                                    <div style="font-size:.74rem;font-weight:600;display:flex;justify-content:space-between;align-items:center;" class="text-rose-800 dark:!text-rose-300">
                                        <span>• من {{ $startTime }} إلى {{ $endTime }}</span>
                                        <span>العميل: {{ $res->customer?->name ?? 'بدون اسم' }} ({{ $res->playPrice?->playType?->name }})</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div style="display:grid;gap:10px;margin-bottom:14px;">
                        <label style="font-size:.78rem;font-weight:700;" class="text-slate-700 dark:!text-zinc-300">اختيار العميل (اختياري)</label>

                        <div x-data="{ open: false }" style="position:relative;">
                            <button
                                type="button"
                                @click="open = !open"
                                style="width:100%;border-radius:10px;padding:8px 10px;font-size:.85rem;display:flex;align-items:center;justify-content:space-between;gap:8px;cursor:pointer;"
                                class="bg-white dark:!bg-zinc-950 border border-slate-300 dark:!border-zinc-700 text-slate-900 dark:!text-zinc-100"
                            >
                                <span style="overflow:hidden;white-space:nowrap;text-overflow:ellipsis;" class="text-slate-900 dark:!text-zinc-100">
                                    {{ $this->selectedCustomer?->name ? ($this->selectedCustomer->name . ' - ' . $this->selectedCustomer->phone) : 'بدون عميل' }}
                                </span>
                                <svg style="width:14px;height:14px;flex-shrink:0;" class="text-slate-400 dark:!text-zinc-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <div
                                x-cloak
                                x-show="open"
                                @click.outside="open = false"
                                style="position:absolute;z-index:30;top:calc(100% + 6px);left:0;right:0;border-radius:10px;box-shadow:0 12px 28px rgba(0,0,0,.12);padding:8px;display:grid;gap:8px;"
                                class="bg-white dark:!bg-zinc-900 border border-slate-300 dark:!border-zinc-700"
                            >
                                <input
                                    type="text"
                                    wire:model.live.debounce.300ms="customerSearch"
                                    placeholder="ابحث بالاسم أو رقم الهاتف"
                                    style="width:100%;border-radius:8px;padding:7px 9px;font-size:.82rem;"
                                    class="bg-white dark:!bg-zinc-950 border border-slate-300 dark:!border-zinc-700 text-slate-900 dark:!text-zinc-100 placeholder-slate-400 dark:!placeholder-zinc-500"
                                />

                                <button
                                    type="button"
                                    wire:click="clearCustomerSelection"
                                    @click="open = false"
                                    style="text-align:right;border-radius:8px;padding:7px 9px;font-size:.8rem;cursor:pointer;"
                                    class="bg-slate-50 dark:!bg-zinc-950 border border-dashed border-slate-300 dark:!border-zinc-700 text-slate-700 dark:!text-zinc-300"
                                >
                                    بدون عميل
                                </button>

                                <div style="max-height:180px;overflow:auto;display:grid;gap:6px;">
                                    @forelse ($this->filteredCustomers as $customer)
                                        <button
                                            type="button"
                                            wire:click="selectCustomer({{ $customer->id }})"
                                            @click="open = false"
                                            style="text-align:right;border-radius:8px;padding:7px 9px;font-size:.8rem;cursor:pointer;"
                                            class="bg-white dark:!bg-zinc-950 border border-slate-200 dark:!border-zinc-800 text-slate-900 dark:!text-zinc-100 hover:bg-emerald-50 dark:hover:!bg-emerald-950/40"
                                        >
                                            {{ $customer->name }} - {{ $customer->phone }}
                                        </button>
                                    @empty
                                        <div style="text-align:center;padding:8px;font-size:.78rem;" class="text-slate-500 dark:!text-zinc-400">لا يوجد عميل مطابق للبحث</div>
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
