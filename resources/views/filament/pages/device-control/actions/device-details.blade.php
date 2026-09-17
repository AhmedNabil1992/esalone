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
                                                    style="width:100%; border-radius:8px; padding:8px 12px; font-size:.82rem; padding-left:32px;"
                                                    class="bg-white dark:!bg-zinc-950 border border-slate-300 dark:!border-zinc-700 text-slate-900 dark:!text-zinc-100 placeholder-slate-400 dark:!placeholder-zinc-500"
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
                                                <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:right;">نوع الجهاز</th>
                                                <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:right;">اسم الجهاز</th>
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
                                                        {{ $detail->device?->type?->name ?: '—' }}
                                                    </td>
                                                    <td style="padding:8px;border-bottom:1px solid #f3f4f6;color:#111827;">
                                                        {{ $detail->device?->name ?: '—' }}
                                                    </td>
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
                                                        {{ $detail->start_time ? app(\App\Support\Tenant\TenantClock::class)->utcToLocal($detail->start_time)->format('h:i A') : '—' }}
                                                    </td>
                                                    <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;color:#374151;direction:ltr;unicode-bidi:isolate;">
                                                        {{ $detail->end_time ? app(\App\Support\Tenant\TenantClock::class)->utcToLocal($detail->end_time)->format('h:i A') : '—' }}
                                                    </td>
                                                    <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;color:#111827;">
                                                        {{ $detail->period ? $detail->period . ' د' : 'مفتوح' }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="8" style="padding:16px;text-align:center;color:#6b7280;">لا توجد بيانات وقت على هذه الجلسة.</td>
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
