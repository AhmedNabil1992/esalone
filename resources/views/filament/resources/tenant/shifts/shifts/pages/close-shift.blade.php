<x-filament-panels::page>
    <div style="direction: rtl;" class="space-y-6 text-slate-900 dark:!text-zinc-100">
        <!-- 1. Shift Header Info Card -->
        <div style="background: linear-gradient(135deg, #1e293b, #0f172a); border-radius: 16px; padding: 20px; color: #fff; box-shadow: 0 10px 25px rgba(0,0,0,0.1);" class="border border-slate-800 dark:!border-zinc-800">
            <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 15px;">
                <div>
                    <h2 style="font-size: 1.4rem; font-weight: 800; margin: 0; color: #ffffff;">الوردية رقم: #{{ $record->shift_no }}</h2>
                    <p style="font-size: 0.85rem; color: #94a3b8; margin: 4px 0 0 0;">
                        تاريخ الوردية: {{ $record->shift_date?->format('Y-m-d') ?? '-' }}
                    </p>
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                    <div>
                        <span style="display: block; font-size: 0.75rem; color: #94a3b8;">فتح بواسطة</span>
                        <span style="font-weight: 700; font-size: 0.9rem; color: #ffffff;">{{ $record->opener?->name ?? 'غير معروف' }}</span>
                    </div>
                    <div>
                        <span style="display: block; font-size: 0.75rem; color: #94a3b8;">بداية الوردية</span>
                        <span style="font-weight: 700; font-size: 0.9rem; color: #ffffff;">{{ $record->shift_from ? app(\App\Support\Tenant\TenantClock::class)->utcToLocal($record->shift_from)->format('Y-m-d H:i') : '-' }}</span>
                    </div>
                    <div>
                        <span style="display: block; font-size: 0.75rem; color: #94a3b8;">نهاية الوردية</span>
                        <span style="font-weight: 700; font-size: 0.9rem; color: #ffffff;">{{ $record->shift_to ? app(\App\Support\Tenant\TenantClock::class)->utcToLocal($record->shift_to)->format('Y-m-d H:i') : 'الوردية نشطة حالياً' }}</span>
                    </div>
                    @if($record->status === 'open')
                        <div>
                            <span style="display: block; font-size: 0.75rem; color: #94a3b8;">المدة المنقضية</span>
                            <span style="font-weight: 700; font-size: 0.9rem; color: #38bdf8;">
                                {{ $record->shift_from?->diffForHumans(null, true) ?? '-' }}
                            </span>
                        </div>
                    @endif
                </div>
                @if($record->status === 'close')
                    <div style="background: rgba(34, 197, 94, 0.2); color: #22c55e; padding: 6px 16px; border-radius: 99px; font-weight: 800; font-size: 0.85rem;">
                        الوردية مغلقة
                    </div>
                @else
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <button 
                            type="button" 
                            wire:click="refreshStats" 
                            class="flex items-center gap-1.5 text-xs text-slate-200 hover:text-white bg-slate-800/90 hover:bg-slate-700 px-3 py-1.5 rounded-xl border border-slate-700 transition shadow-sm cursor-pointer"
                            title="تحديث البيانات يدويّاً"
                        >
                            <svg wire:loading.class="animate-spin" class="w-3.5 h-3.5 text-sky-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                            </svg>
                            <span>تحديث الأرقام</span>
                        </button>
                        <div style="background: rgba(59, 130, 246, 0.2); color: #3b82f6; padding: 6px 16px; border-radius: 99px; font-weight: 800; font-size: 0.85rem;">
                            الوردية مفتوحة
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if($record->status === 'open')
            <div wire:poll.4s.visible="checkForUpdates"></div>
        @endif

        @if($hasNewTransactions)
            <div class="p-4 rounded-2xl border-2 border-amber-400 bg-amber-50 dark:!bg-amber-950/40 text-amber-900 dark:!text-amber-200 shadow-md flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="p-2.5 bg-amber-500/20 text-amber-600 dark:!text-amber-400 rounded-xl">
                        <svg class="w-7 h-7 animate-pulse" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h4 class="font-black text-base m-0 text-amber-950 dark:!text-amber-100">
                                تنبيه: تم تسجيل عمليات جديدة أثناء فتح هذه الصفحة!
                            </h4>
                            @if($lastUpdatedTime)
                                <span class="text-xs px-2 py-0.5 rounded-md bg-amber-200/60 dark:!bg-amber-900/60 text-amber-900 dark:!text-amber-200 font-bold">
                                    تحديث: {{ $lastUpdatedTime }}
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-amber-800 dark:!text-amber-300 mt-1 mb-0 leading-relaxed font-medium">
                            تم تحديث الحسابات والقوائم المالية تلقائياً لتعكس العمليات الجديدة. يرجى مراجعة الأرقام قبل تأكيد تقفيل الوردية.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button 
                        type="button" 
                        wire:click="acknowledgeNewTransactions" 
                        class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold transition shadow-sm cursor-pointer"
                    >
                        تمت المراجعة
                    </button>
                </div>
            </div>
        @endif

        @if($this->canViewFullShiftDetails)
            <!-- 2. Financial Breakdown Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                
                <!-- Incomes Section -->
                <div class="bg-white dark:!bg-zinc-900 border border-slate-200 dark:!border-zinc-800 rounded-2xl p-5 shadow-sm text-slate-900 dark:!text-zinc-100">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 15px; border-bottom: 2px solid #22c55e; padding-bottom: 8px;">
                        <svg style="width: 20px; height: 20px; color: #22c55e;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        <h3 class="text-base font-extrabold text-slate-900 dark:!text-zinc-100 m-0">تفاصيل الإيرادات كاش ودفتري (Incomes)</h3>
                    </div>
                    <div style="display: grid; gap: 12px; font-size: 0.85rem;">
                        <div style="display: flex; justify-content: space-between;">
                            <span class="text-slate-600 dark:!text-zinc-400">إيراد بلايستيشن</span>
                            <span class="font-bold text-slate-900 dark:!text-zinc-100">{{ number_format($stats['playstation_amount'], 2) }} ج.م</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span class="text-slate-600 dark:!text-zinc-400">مبيعات المتجر والطلبات</span>
                            <span class="font-bold text-slate-900 dark:!text-zinc-100">{{ number_format($stats['sales_amount'], 2) }}  ج.م</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span class="text-slate-600 dark:!text-zinc-400">إيداع كاش بمحفظة العملاء</span>
                            <span class="font-bold text-emerald-600 dark:!text-emerald-400">+ {{ number_format($stats['customer_add_amount'], 2) }} ج.م</span>
                        </div>
                        <div class="flex justify-between border-t border-dashed border-slate-200 dark:!border-zinc-800 pt-2">
                            <span class="text-slate-600 dark:!text-zinc-400">إجمالي الضرائب المستلمة</span>
                            <span class="font-bold text-slate-900 dark:!text-zinc-100">{{ number_format($stats['tax_amount'], 2) }} ج.م</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span class="text-slate-600 dark:!text-zinc-400">إجمالي خدمة الصالة المستلمة</span>
                            <span class="font-bold text-slate-900 dark:!text-zinc-100">{{ number_format($stats['service_amount'], 2) }} ج.م</span>
                        </div>
                        <div class="flex justify-between border-t border-slate-300 dark:!border-zinc-700 pt-2 mt-2 font-extrabold text-slate-900 dark:!text-zinc-100">
                            <span>إجمالي الإيرادات:</span>
                            <span>{{ number_format($stats['playstation_amount'] + $stats['sales_amount'] + $stats['customer_add_amount'] + $stats['tax_amount'] + $stats['service_amount'], 2) }} ج.م</span>
                        </div>
                    </div>
                </div>

                <!-- Outcomes & Deductions Section -->
                <div class="bg-white dark:!bg-zinc-900 border border-slate-200 dark:!border-zinc-800 rounded-2xl p-5 shadow-sm text-slate-900 dark:!text-zinc-100">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 15px; border-bottom: 2px solid #ef4444; padding-bottom: 8px;">
                        <svg style="width: 20px; height: 20px; color: #ef4444;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"/>
                        </svg>
                        <h3 class="text-base font-extrabold text-slate-900 dark:!text-zinc-100 m-0">المصروفات والخصومات كاش ودفتري</h3>
                    </div>
                    <div style="display: grid; gap: 12px; font-size: 0.85rem;">
                        <div style="display: flex; justify-content: space-between;">
                            <span class="text-slate-600 dark:!text-zinc-400">المصروفات النقدية (Expenses)</span>
                            <span class="font-bold text-red-600 dark:!text-red-400">- {{ number_format($stats['expenses_amount'], 2) }} ج.م</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span class="text-slate-600 dark:!text-zinc-400">المشتريات للمخازن (Purchases)</span>
                            <span class="font-bold text-red-600 dark:!text-red-400">- {{ number_format($stats['purchase_amount'], 2) }} ج.م</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span class="text-slate-600 dark:!text-zinc-400">سحب نقدي من محافظ العملاء</span>
                            <span class="font-bold text-red-600 dark:!text-red-400">- {{ number_format($stats['customer_minus_amount'], 2) }} ج.م</span>
                        </div>
                        <div class="flex justify-between border-t border-dashed border-slate-200 dark:!border-zinc-800 pt-2">
                            <span class="text-slate-600 dark:!text-zinc-400">إجمالي الخصومات المعطاة</span>
                            <span class="font-bold text-rose-600 dark:!text-rose-400">- {{ number_format($stats['discount_amount'], 2) }} ج.م</span>
                        </div>
                        <div class="flex justify-between border-t border-slate-300 dark:!border-zinc-700 pt-2 mt-2 font-extrabold text-red-600 dark:!text-red-400">
                            <span>إجمالي المصروفات والخصومات:</span>
                            <span>- {{ number_format($stats['expenses_amount'] + $stats['purchase_amount'] + $stats['customer_minus_amount'] + $stats['discount_amount'], 2) }} ج.م</span>
                        </div>
                    </div>
                </div>

                <!-- Non-Cash Settlements (Customer Balance & Unpaid) -->
                <div class="bg-white dark:!bg-zinc-900 border border-slate-200 dark:!border-zinc-800 rounded-2xl p-5 shadow-sm text-slate-900 dark:!text-zinc-100">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 15px; border-bottom: 2px solid #eab308; padding-bottom: 8px;">
                        <svg style="width: 20px; height: 20px; color: #eab308;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/>
                        </svg>
                        <h3 class="text-base font-extrabold text-slate-900 dark:!text-zinc-100 m-0">تسويات غير نقدية (تخصم من رصيد الكاش المتوقع)</h3>
                    </div>
                    <div style="display: grid; gap: 12px; font-size: 0.85rem;">
                        <div style="display: flex; justify-content: space-between;">
                            <span class="text-slate-600 dark:!text-zinc-400">مدفوعات من رصيد محافظ العملاء</span>
                            <span class="font-bold text-amber-600 dark:!text-amber-400">- {{ number_format($stats['customer_credit_amount'], 2) }} ج.م</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span class="text-slate-600 dark:!text-zinc-400">ديون آجلة غير مدفوعة (على الفواتير)</span>
                            <span class="font-bold text-orange-600 dark:!text-orange-400">- {{ number_format(abs($stats['credit_amount']), 2) }} ج.م</span>
                        </div>
                        <div class="flex justify-between border-t border-slate-300 dark:!border-zinc-700 pt-2 mt-2 font-extrabold text-orange-600 dark:!text-orange-400">
                            <span>إجمالي التسويات غير النقدية:</span>
                            <span>- {{ number_format($stats['customer_credit_amount'] + abs($stats['credit_amount']), 2) }} ج.م</span>
                        </div>
                        <div class="text-xs text-slate-500 dark:!text-zinc-400 leading-relaxed border-t border-dashed border-slate-200 dark:!border-zinc-800 pt-2">
                            * ملحوظة: المعاملات غير النقدية (مثل الدفع برصيد العميل أو المتبقي الآجل) يتم استبعادها من الكاش المتوقع بالدرج لأنها معاملات دفتريّة.
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Expected Cash Flow Summary Banner -->
            <div class="bg-slate-50 dark:!bg-zinc-900/90 border border-slate-200 dark:!border-zinc-800 rounded-2xl p-6 shadow-sm text-slate-900 dark:!text-zinc-100">
                <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 20px;">
                    <div style="display: flex; flex-wrap: wrap; gap: 30px;">
                        <div>
                            <span class="block text-xs text-slate-600 dark:!text-zinc-400 mb-1">رصيد بداية الوردية (الافتتاحي)</span>
                            <span class="text-xl font-extrabold text-slate-900 dark:!text-zinc-100">{{ number_format($stats['start_amount'], 2) }} ج.م</span>
                        </div>
                        <div class="self-center text-2xl text-slate-400 dark:!text-zinc-500">+</div>
                        <div>
                            <span class="block text-xs text-slate-600 dark:!text-zinc-400 mb-1">صافي الحركة النقدية بالوردية</span>
                            @php
                                $netCash = $stats['expected_cash'] - $stats['start_amount'];
                            @endphp
                            <span class="text-xl font-extrabold {{ $netCash >= 0 ? 'text-emerald-600 dark:!text-emerald-400' : 'text-red-600 dark:!text-red-400' }}">
                                {{ $netCash >= 0 ? '+' : '' }}{{ number_format($netCash, 2) }} ج.م
                            </span>
                        </div>
                    </div>
                    <div class="bg-slate-900 dark:!bg-zinc-950 text-white p-4 rounded-xl text-left min-w-[200px] border border-slate-800 dark:!border-zinc-800">
                        <span class="block text-xs text-slate-400 dark:!text-zinc-400 mb-1 text-right">الرصيد النقدي المتوقع بالدرج</span>
                        <span style="font-size: 1.8rem; font-weight: 900; color: #38bdf8;">{{ number_format($stats['expected_cash'], 2) }} ج.م</span>
                    </div>
                </div>
            </div>

            <!-- 4. Cashbox Breakdowns -->
            @if(count($cashBoxSummaries) > 0)
                <div class="bg-white dark:!bg-zinc-900 border border-slate-200 dark:!border-zinc-800 rounded-2xl p-5 shadow-sm text-slate-900 dark:!text-zinc-100">
                    <h3 class="text-base font-extrabold text-slate-900 dark:!text-zinc-100 mb-4 m-0">تفاصيل حركة السيولة النقدية لكل خزينة</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 15px;">
                        @foreach($cashBoxSummaries as $box)
                            <div class="border border-slate-200 dark:!border-zinc-800 bg-slate-50/70 dark:!bg-zinc-950/70 rounded-xl p-4 flex flex-col gap-2">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span class="font-extrabold text-slate-900 dark:!text-zinc-100 text-sm">{{ $box['name'] }}</span>
                                    <span style="font-size: 0.72rem; padding: 2px 8px; border-radius: 99px; background: rgba(56, 189, 248, 0.1); color: #0284c7; font-weight: 700;">خزينة نشطة</span>
                                </div>
                                <div class="flex justify-between text-xs border-t border-dashed border-slate-200 dark:!border-zinc-800 pt-2 mt-1">
                                    <span class="text-slate-600 dark:!text-zinc-400">صافي حركة الشيفت:</span>
                                    <span class="font-bold {{ $box['net_change'] >= 0 ? 'text-emerald-600 dark:!text-emerald-400' : 'text-red-600 dark:!text-red-400' }}">
                                        {{ $box['net_change'] >= 0 ? '+' : '' }}{{ number_format($box['net_change'], 2) }} ج.م
                                    </span>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span class="text-slate-600 dark:!text-zinc-400">الرصيد الإجمالي المتوقع:</span>
                                    <span class="font-extrabold text-slate-900 dark:!text-zinc-100">{{ number_format($box['current_balance'], 2) }} ج.م</span>
                                </div>

                                @if($record->status === 'open')
                                    <div class="flex items-center justify-between gap-2 border-t border-dashed border-slate-300 dark:!border-zinc-700 pt-2 mt-1">
                                        <span class="text-xs text-slate-700 dark:!text-zinc-300 font-bold">الرصيد الفعلي بالخزنة:</span>
                                        <input 
                                            type="number" 
                                            step="0.01" 
                                            wire:model.live="cashbox_actuals.{{ $box['id'] }}" 
                                            placeholder="0.00" 
                                            class="w-[110px] border border-slate-300 dark:!border-zinc-700 bg-white dark:!bg-zinc-950 text-slate-900 dark:!text-zinc-100 rounded-lg px-2 py-1 text-sm font-bold text-center" 
                                        />
                                    </div>
                                    @php
                                        $actVal = $cashbox_actuals[$box['id']] ?? null;
                                        $diffVal = ($actVal !== null && $actVal !== '') ? ((float)$actVal - (float)$box['current_balance']) : null;
                                    @endphp
                                    @if($diffVal !== null)
                                        <div class="flex justify-between text-xs font-bold mt-0.5">
                                            <span class="text-slate-600 dark:!text-zinc-400">فرق التسوية:</span>
                                            @if($diffVal == 0.0)
                                                <span class="text-emerald-600 dark:!text-emerald-400">مطابق تماماً</span>
                                            @elseif($diffVal < 0)
                                                <span class="text-red-600 dark:!text-red-400">عجز: {{ number_format(abs($diffVal), 2) }} ج.م</span>
                                            @else
                                                <span class="text-emerald-600 dark:!text-emerald-400">زيادة: +{{ number_format(abs($diffVal), 2) }} ج.م</span>
                                            @endif
                                        </div>
                                    @endif
                                @else
                                    <div class="flex justify-between text-xs border-t border-dashed border-slate-300 dark:!border-zinc-700 pt-2 mt-1">
                                        <span class="text-slate-600 dark:!text-zinc-400">الرصيد الفعلي المسجل:</span>
                                        <span class="font-extrabold text-slate-900 dark:!text-zinc-100">{{ number_format($cashbox_actuals[$box['id']] ?? 0.0, 2) }} ج.م</span>
                                    </div>
                                    @php
                                        $actVal = $cashbox_actuals[$box['id']] ?? 0.0;
                                        $diffVal = (float)$actVal - (float)$box['current_balance'];
                                    @endphp
                                    <div class="flex justify-between text-xs font-bold mt-0.5">
                                        <span class="text-slate-600 dark:!text-zinc-400">فرق التسوية:</span>
                                        @if($diffVal == 0.0)
                                            <span class="text-emerald-600 dark:!text-emerald-400">مطابق تماماً</span>
                                        @elseif($diffVal < 0)
                                            <span class="text-red-600 dark:!text-red-400">عجز: {{ number_format(abs($diffVal), 2) }} ج.م</span>
                                        @else
                                            <span class="text-emerald-600 dark:!text-emerald-400">زيادة: +{{ number_format(abs($diffVal), 2) }} ج.م</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- 5. Discrepancy & Close Submission Form -->
            <div 
                x-data="{ expected: @entangle('stats.expected_cash'), actual: @entangle('actual_amount') }" 
                class="bg-white dark:!bg-zinc-900 border border-slate-200 dark:!border-zinc-800 rounded-2xl p-6 shadow-sm text-slate-900 dark:!text-zinc-100"
            >
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
                    <!-- Inputs -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-extrabold text-slate-900 dark:!text-zinc-100 m-0 pb-2 border-b-2 border-slate-200 dark:!border-zinc-800">تقفيل وتسوية الوردية</h3>
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:!text-zinc-300 mb-1.5">الرصيد الفعلي المتواجد بالدرج (كاش - إجمالي) <span style="color:#dc2626;">*</span></label>
                            @if($record->status === 'open')
                                <div class="bg-slate-50 dark:!bg-zinc-950 border border-slate-200 dark:!border-zinc-800 rounded-lg p-3 text-lg font-bold text-slate-900 dark:!text-zinc-100 flex justify-between items-center">
                                    <span>{{ number_format($actual_amount ?? 0.0, 2) }} ج.م</span>
                                    <span class="text-xs font-normal text-slate-500 dark:!text-zinc-400">(محتسب تلقائياً من مجموع الخزائن بالوردية)</span>
                                </div>
                            @else
                                <div class="bg-slate-50 dark:!bg-zinc-950 border border-slate-200 dark:!border-zinc-800 rounded-lg p-3 text-lg font-bold text-slate-900 dark:!text-zinc-100">
                                    {{ number_format($actual_amount, 2) }} ج.م
                                </div>
                            @endif
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:!text-zinc-300 mb-1.5">ملاحظات التسوية والتسليم</label>
                            @if($record->status === 'open')
                                <textarea 
                                    wire:model.live="notes" 
                                    rows="3" 
                                    placeholder="اكتب أي ملاحظات أو تبرير للعجز والزيادة إن وجد..." 
                                    class="w-full border border-slate-300 dark:!border-zinc-700 bg-white dark:!bg-zinc-950 text-slate-900 dark:!text-zinc-100 rounded-lg p-3 text-sm focus:ring-primary-500"
                                ></textarea>
                            @else
                                <div class="bg-slate-50 dark:!bg-zinc-950 border border-slate-200 dark:!border-zinc-800 rounded-lg p-3 text-sm text-slate-700 dark:!text-zinc-300 min-h-[80px] whitespace-pre-wrap">
                                    {{ $notes ?: 'لا توجد ملاحظات.' }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Discrepancy Box -->
                    <div class="flex flex-col justify-center bg-slate-50/60 dark:!bg-zinc-950/70 border border-slate-200 dark:!border-zinc-800 rounded-xl p-5">
                        <h4 class="text-xs font-bold text-slate-500 dark:!text-zinc-400 m-0 text-center mb-2.5">حالة التسوية والفرق</h4>
                        
                        <!-- Perfect match -->
                        <div x-show="actual !== null && actual === expected" class="text-center text-emerald-600 dark:!text-emerald-400 p-4">
                            <svg style="width: 48px; height: 48px; margin: 0 auto 10px auto;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/>
                            </svg>
                            <span class="text-lg font-extrabold block">الحساب مطابق تماماً!</span>
                            <span class="text-xs text-slate-500 dark:!text-zinc-400 mt-1 block">الرصيد الفعلي يطابق الرصيد المتوقع بالمليم.</span>
                        </div>

                        <!-- Discrepancy (Surplus or Shortage) -->
                        <div x-show="actual !== null && actual !== expected" style="text-align: center; padding: 15px;">
                            <template x-if="actual < expected">
                                <div>
                                    <div class="bg-red-500/10 text-red-600 dark:!text-red-400 inline-block px-4 py-1.5 rounded-full font-extrabold text-xs mb-2">
                                        عجز بالخزينة
                                    </div>
                                    <span class="text-3xl font-black text-red-600 dark:!text-red-400 block" style="direction: ltr;" x-text="'-' + Math.abs(expected - actual).toFixed(2) + ' ج.م'"></span>
                                    <span class="text-xs text-slate-500 dark:!text-zinc-400 mt-1 block">
                                        الرصيد الفعلي بالدرج أقل من المطلوب بمقدار الفرق أعلاه.
                                    </span>
                                </div>
                            </template>
                            <template x-if="actual > expected">
                                <div>
                                    <div class="bg-emerald-500/10 text-emerald-600 dark:!text-emerald-400 inline-block px-4 py-1.5 rounded-full font-extrabold text-xs mb-2">
                                        زيادة بالخزينة
                                    </div>
                                    <span class="text-3xl font-black text-emerald-600 dark:!text-emerald-400 block" style="direction: ltr;" x-text="'+' + Math.abs(actual - expected).toFixed(2) + ' ج.م'"></span>
                                    <span class="text-xs text-slate-500 dark:!text-zinc-400 mt-1 block">
                                        الرصيد الفعلي بالدرج أكثر من المطلوب بمقدار الفرق أعلاه.
                                    </span>
                                </div>
                            </template>
                        </div>

                        <!-- Prompt to fill -->
                        <div x-show="actual === null || actual === ''" class="text-center text-slate-500 dark:!text-zinc-400 p-6">
                            <span class="text-xs italic">يرجى كتابة الرصيد الفعلي بالدرج ليتم احتساب الفروقات تلقائياً.</span>
                        </div>

                        <!-- Submission buttons -->
                        @if($record->status === 'open')
                            <div style="margin-top: 20px; display: grid;">
                                <button 
                                    type="button" 
                                    wire:click="close" 
                                    style="background: #ef4444; color: #fff; padding: 12px; border-radius: 8px; font-weight: bold; border: none; cursor: pointer; text-align: center; box-shadow: 0 4px 6px rgba(239, 68, 68, 0.2);"
                                >
                                    تأكيد وتقفيل الوردية نهائياً
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 6. Detailed transactions lists (Accordions) -->
            <h3 class="text-lg font-extrabold text-slate-900 dark:!text-zinc-100 mt-6 mb-2.5">سجل العمليات التفصيلي خلال الوردية</h3>
            
            <div style="display: grid; gap: 12px;">
                <!-- PlayStation Invoices Accordion -->
                <details class="bg-white dark:!bg-zinc-900 border border-slate-200 dark:!border-zinc-800 rounded-xl overflow-hidden px-4 py-2.5 text-slate-900 dark:!text-zinc-100">
                    <summary class="font-extrabold text-slate-800 dark:!text-zinc-200 cursor-pointer flex justify-between items-center list-none">
                        <span style="display: flex; align-items: center; gap: 8px;">
                            <span style="background: #eff6ff; color: #2563eb; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.72rem; font-weight: 800;">
                                {{ count($playInvoices) }}
                            </span>
                            فواتير البلايستيشن والأجهزة
                        </span>
                        <svg class="w-4 h-4 text-slate-500 dark:!text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                    </summary>
                    <div style="margin-top: 15px; overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: right; font-size: 0.8rem;">
                            <thead>
                                <tr class="border-b-2 border-slate-200 dark:!border-zinc-800 text-slate-600 dark:!text-zinc-400">
                                    <th style="padding: 8px;">رقم الفاتورة</th>
                                    <th style="padding: 8px;">الجهاز</th>
                                    <th style="padding: 8px;">العميل</th>
                                    <th style="padding: 8px;">وقت اللعب</th>
                                    <th style="padding: 8px;">إيراد اللعب</th>
                                    <th style="padding: 8px;">إيراد الطلبات</th>
                                    <th style="padding: 8px;">الخصم</th>
                                    <th style="padding: 8px;">إجمالي الفاتورة</th>
                                    <th style="padding: 8px;">المدفوع كاش</th>
                                    <th style="padding: 8px;">المدفوع رصيد</th>
                                    <th style="padding: 8px;">المتبقي (دين)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($playInvoices as $inv)
                                    <tr class="border-b border-slate-100 dark:!border-zinc-800/60 text-slate-700 dark:!text-zinc-300">
                                        <td style="padding: 8px; font-weight: 700;" class="text-slate-900 dark:!text-zinc-100">{{ $inv['invoice_no'] }}</td>
                                        <td style="padding: 8px;" class="text-slate-800 dark:!text-zinc-200">{{ $inv['device']['name'] ?? '-' }}</td>
                                        <td style="padding: 8px;" class="text-slate-800 dark:!text-zinc-200">{{ $inv['customer']['name'] ?? 'بدون عميل' }}</td>
                                        <td style="padding: 8px;" class="text-slate-800 dark:!text-zinc-200">{{ $inv['play_session_details'][0]['period'] ?? '-' }} دقيقة</td>
                                        <td style="padding: 8px;" class="text-slate-800 dark:!text-zinc-200">{{ number_format($inv['play_amount'], 2) }} ج.م</td>
                                        <td style="padding: 8px;" class="text-slate-800 dark:!text-zinc-200">{{ number_format($inv['order_amount'], 2) }} ج.م</td>
                                        <td style="padding: 8px;" class="text-rose-600 dark:!text-rose-400 font-bold">{{ number_format($inv['discount_amount'], 2) }} ج.م</td>
                                        <td style="padding: 8px; font-weight: bold;" class="text-slate-900 dark:!text-zinc-100">{{ number_format($inv['final_amount'], 2) }} ج.م</td>
                                        <td style="padding: 8px;" class="text-emerald-600 dark:!text-emerald-400 font-bold">
                                            {{ number_format(collect($inv['payments'])->where('payment_method', 'cash')->sum('amount'), 2) }} ج.م
                                        </td>
                                        <td style="padding: 8px;" class="text-amber-600 dark:!text-amber-400 font-bold">
                                            {{ number_format(collect($inv['payments'])->where('payment_method', 'customer_balance')->sum('amount'), 2) }} ج.م
                                        </td>
                                        <td style="padding: 8px;" class="{{ $inv['remain_amount'] > 0 ? 'text-orange-600 dark:!text-orange-400' : 'text-slate-500 dark:!text-zinc-400' }}">
                                            {{ number_format($inv['remain_amount'], 2) }} ج.م
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center p-5 text-slate-400 dark:!text-zinc-500">لا توجد فواتير لعب بالوردية حتى الآن.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if(count($playInvoices) > 0)
                                <tfoot>
                                    <tr class="border-t-2 border-slate-300 dark:!border-zinc-700 font-extrabold bg-slate-50 dark:!bg-zinc-950 text-slate-900 dark:!text-zinc-100">
                                        <td colspan="4" style="padding: 8px; text-align: center;">الإجمالي:</td>
                                        <td style="padding: 8px;">{{ number_format(collect($playInvoices)->sum('play_amount'), 2) }} ج.م</td>
                                        <td style="padding: 8px;">{{ number_format(collect($playInvoices)->sum('order_amount'), 2) }} ج.م</td>
                                        <td style="padding: 8px;" class="text-rose-600 dark:!text-rose-400">{{ number_format(collect($playInvoices)->sum('discount_amount'), 2) }} ج.م</td>
                                        <td style="padding: 8px; font-weight: bold;">{{ number_format(collect($playInvoices)->sum('final_amount'), 2) }} ج.م</td>
                                        <td style="padding: 8px;" class="text-emerald-600 dark:!text-emerald-400">
                                            {{ number_format(collect($playInvoices)->flatMap(fn($inv) => $inv['payments'] ?? [])->where('payment_method', 'cash')->sum('amount'), 2) }} ج.م
                                        </td>
                                        <td style="padding: 8px;" class="text-amber-600 dark:!text-amber-400">
                                            {{ number_format(collect($playInvoices)->flatMap(fn($inv) => $inv['payments'] ?? [])->where('payment_method', 'customer_balance')->sum('amount'), 2) }} ج.م
                                        </td>
                                        <td style="padding: 8px;" class="text-orange-600 dark:!text-orange-400">{{ number_format(collect($playInvoices)->sum('remain_amount'), 2) }} ج.م</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </details>

                <!-- Direct Sales Accordion -->
                <details class="bg-white dark:!bg-zinc-900 border border-slate-200 dark:!border-zinc-800 rounded-xl overflow-hidden px-4 py-2.5 text-slate-900 dark:!text-zinc-100">
                    <summary class="font-extrabold text-slate-800 dark:!text-zinc-200 cursor-pointer flex justify-between items-center list-none">
                        <span style="display: flex; align-items: center; gap: 8px;">
                            <span style="background: #f0fdf4; color: #16a34a; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.72rem; font-weight: 800;">
                                {{ count($directSales) }}
                            </span>
                            فواتير البيع المباشر (المتجر)
                        </span>
                        <svg class="w-4 h-4 text-slate-500 dark:!text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                    </summary>
                    <div style="margin-top: 15px; overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: right; font-size: 0.8rem;">
                            <thead>
                                <tr class="border-b-2 border-slate-200 dark:!border-zinc-800 text-slate-600 dark:!text-zinc-400">
                                    <th style="padding: 8px;">رقم الفاتورة</th>
                                    <th style="padding: 8px;">العميل</th>
                                    <th style="padding: 8px;">إجمالي المبيعات</th>
                                    <th style="padding: 8px;">الخصم</th>
                                    <th style="padding: 8px;">الصافي المطلوب</th>
                                    <th style="padding: 8px;">المدفوع كاش</th>
                                    <th style="padding: 8px;">المدفوع رصيد</th>
                                    <th style="padding: 8px;">المتبقي (دين)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($directSales as $sale)
                                    <tr class="border-b border-slate-100 dark:!border-zinc-800/60 text-slate-700 dark:!text-zinc-300">
                                        <td style="padding: 8px; font-weight: 700;" class="text-slate-900 dark:!text-zinc-100">{{ $sale['invoice_no'] }}</td>
                                        <td style="padding: 8px;" class="text-slate-800 dark:!text-zinc-200">{{ $sale['customer']['name'] ?? 'بدون عميل' }}</td>
                                        <td style="padding: 8px;" class="text-slate-800 dark:!text-zinc-200">{{ number_format($sale['subtotal'], 2) }} ج.م</td>
                                        <td style="padding: 8px;" class="text-rose-600 dark:!text-rose-400 font-bold">{{ number_format($sale['discount_amount'], 2) }} ج.م</td>
                                        <td style="padding: 8px; font-weight: bold;" class="text-slate-900 dark:!text-zinc-100">{{ number_format($sale['final_amount'], 2) }} ج.م</td>
                                        <td style="padding: 8px;" class="text-emerald-600 dark:!text-emerald-400 font-bold">
                                            {{ number_format(collect($sale['payments'])->where('payment_method', 'cash')->sum('amount'), 2) }} ج.م
                                        </td>
                                        <td style="padding: 8px;" class="text-amber-600 dark:!text-amber-400 font-bold">
                                            {{ number_format(collect($sale['payments'])->where('payment_method', 'customer_balance')->sum('amount'), 2) }} ج.م
                                        </td>
                                        <td style="padding: 8px;" class="{{ $sale['remain_amount'] > 0 ? 'text-orange-600 dark:!text-orange-400' : 'text-slate-500 dark:!text-zinc-400' }}">
                                            {{ number_format($sale['remain_amount'], 2) }} ج.م
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center p-5 text-slate-400 dark:!text-zinc-500">لا توجد فواتير بيع مباشر بالوردية حتى الآن.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if(count($directSales) > 0)
                                <tfoot>
                                    <tr class="border-t-2 border-slate-300 dark:!border-zinc-700 font-extrabold bg-slate-50 dark:!bg-zinc-950 text-slate-900 dark:!text-zinc-100">
                                        <td colspan="2" style="padding: 8px; text-align: center;">الإجمالي:</td>
                                        <td style="padding: 8px;">{{ number_format(collect($directSales)->sum('subtotal'), 2) }} ج.م</td>
                                        <td style="padding: 8px;" class="text-rose-600 dark:!text-rose-400">{{ number_format(collect($directSales)->sum('discount_amount'), 2) }} ج.م</td>
                                        <td style="padding: 8px; font-weight: bold;">{{ number_format(collect($directSales)->sum('final_amount'), 2) }} ج.م</td>
                                        <td style="padding: 8px;" class="text-emerald-600 dark:!text-emerald-400">
                                            {{ number_format(collect($directSales)->flatMap(fn($sale) => $sale['payments'] ?? [])->where('payment_method', 'cash')->sum('amount'), 2) }} ج.م
                                        </td>
                                        <td style="padding: 8px;" class="text-amber-600 dark:!text-amber-400">
                                            {{ number_format(collect($directSales)->flatMap(fn($sale) => $sale['payments'] ?? [])->where('payment_method', 'customer_balance')->sum('amount'), 2) }} ج.م
                                        </td>
                                        <td style="padding: 8px;" class="text-orange-600 dark:!text-orange-400">{{ number_format(collect($directSales)->sum('remain_amount'), 2) }} ج.م</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </details>

                <!-- Expenses Accordion -->
                <details class="bg-white dark:!bg-zinc-900 border border-slate-200 dark:!border-zinc-800 rounded-xl overflow-hidden px-4 py-2.5 text-slate-900 dark:!text-zinc-100">
                    <summary class="font-extrabold text-slate-800 dark:!text-zinc-200 cursor-pointer flex justify-between items-center list-none">
                        <span style="display: flex; align-items: center; gap: 8px;">
                            <span style="background: #fef2f2; color: #ef4444; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.72rem; font-weight: 800;">
                                {{ count($expenses) }}
                            </span>
                            المصروفات النقدية المسجلة
                        </span>
                        <svg class="w-4 h-4 text-slate-500 dark:!text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                    </summary>
                    <div style="margin-top: 15px; overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: right; font-size: 0.8rem;">
                            <thead>
                                <tr class="border-b-2 border-slate-200 dark:!border-zinc-800 text-slate-600 dark:!text-zinc-400">
                                    <th style="padding: 8px;">التاريخ/الوقت</th>
                                    <th style="padding: 8px;">بند المصروف</th>
                                    <th style="padding: 8px;">الخزينة المدفوع منها</th>
                                    <th style="padding: 8px;">البيان/الوصف</th>
                                    <th style="padding: 8px;">القيمة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expenses as $exp)
                                    <tr class="border-b border-slate-100 dark:!border-zinc-800/60 text-slate-700 dark:!text-zinc-300">
                                        <td style="padding: 8px;" class="text-slate-800 dark:!text-zinc-200">{{ app(\App\Support\Tenant\TenantClock::class)->utcToLocal($exp['created_at'])->format('H:i') }}</td>
                                        <td style="padding: 8px; font-weight: 700;" class="text-slate-900 dark:!text-zinc-100">{{ $exp['expense_type']['name'] ?? '-' }}</td>
                                        <td style="padding: 8px;" class="text-slate-800 dark:!text-zinc-200">{{ $exp['cash_box']['name'] ?? '-' }}</td>
                                        <td style="padding: 8px;" class="text-slate-800 dark:!text-zinc-200">{{ $exp['description'] }}</td>
                                        <td style="padding: 8px;" class="text-red-600 dark:!text-red-400 font-bold">- {{ number_format($exp['amount'], 2) }} ج.م</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center p-5 text-slate-400 dark:!text-zinc-500">لا توجد مصروفات مسجلة بالوردية.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if(count($expenses) > 0)
                                <tfoot>
                                    <tr class="border-t-2 border-slate-300 dark:!border-zinc-700 font-extrabold bg-slate-50 dark:!bg-zinc-950 text-slate-900 dark:!text-zinc-100">
                                        <td colspan="4" style="padding: 8px; text-align: center;">الإجمالي:</td>
                                        <td style="padding: 8px;" class="text-red-600 dark:!text-red-400">- {{ number_format(collect($expenses)->sum('amount'), 2) }} ج.م</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </details>

                <!-- Purchases Accordion -->
                <details class="bg-white dark:!bg-zinc-900 border border-slate-200 dark:!border-zinc-800 rounded-xl overflow-hidden px-4 py-2.5 text-slate-900 dark:!text-zinc-100">
                    <summary class="font-extrabold text-slate-800 dark:!text-zinc-200 cursor-pointer flex justify-between items-center list-none">
                        <span style="display: flex; align-items: center; gap: 8px;">
                            <span style="background: #fff7ed; color: #f97316; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.72rem; font-weight: 800;">
                                {{ count($purchases) }}
                            </span>
                            فواتير شراء المخزون
                        </span>
                        <svg class="w-4 h-4 text-slate-500 dark:!text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                    </summary>
                    <div style="margin-top: 15px; overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: right; font-size: 0.8rem;">
                            <thead>
                                <tr class="border-b-2 border-slate-200 dark:!border-zinc-800 text-slate-600 dark:!text-zinc-400">
                                    <th style="padding: 8px;">رقم فاتورة الشراء</th>
                                    <th style="padding: 8px;">المورد</th>
                                    <th style="padding: 8px;">الخزينة المستعملة</th>
                                    <th style="padding: 8px;">طريقة الدفع</th>
                                    <th style="padding: 8px;">المبلغ الإجمالي</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($purchases as $pur)
                                    <tr class="border-b border-slate-100 dark:!border-zinc-800/60 text-slate-700 dark:!text-zinc-300">
                                        <td style="padding: 8px; font-weight: 700;" class="text-slate-900 dark:!text-zinc-100">{{ $pur['reference'] ?: ('#' . $pur['id']) }}</td>
                                        <td style="padding: 8px;" class="text-slate-800 dark:!text-zinc-200">{{ $pur['supplier']['name'] ?? '-' }}</td>
                                        <td style="padding: 8px;" class="text-slate-800 dark:!text-zinc-200">{{ $pur['cash_box']['name'] ?? '-' }}</td>
                                        <td style="padding: 8px;" class="text-slate-800 dark:!text-zinc-200">{{ $pur['cash_box_id'] ? 'كاش' : 'آجل' }}</td>
                                        <td style="padding: 8px;" class="text-orange-600 dark:!text-orange-400 font-bold">- {{ number_format($pur['total_amount'], 2) }} ج.م</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center p-5 text-slate-400 dark:!text-zinc-500">لا توجد فواتير شراء بالوردية.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if(count($purchases) > 0)
                                <tfoot>
                                    <tr class="border-t-2 border-slate-300 dark:!border-zinc-700 font-extrabold bg-slate-50 dark:!bg-zinc-950 text-slate-900 dark:!text-zinc-100">
                                        <td colspan="4" style="padding: 8px; text-align: center;">الإجمالي:</td>
                                        <td style="padding: 8px;" class="text-orange-600 dark:!text-orange-400">- {{ number_format(collect($purchases)->sum('total_amount'), 2) }} ج.م</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </details>
            </div>
        @else
            <!-- Simplified Form for cashiers (Blind Close) -->
            <div class="bg-white dark:!bg-zinc-900 border border-slate-200 dark:!border-zinc-800 rounded-2xl p-6 shadow-sm max-w-[500px] mx-auto text-slate-900 dark:!text-zinc-100">
                <h3 class="text-lg font-extrabold text-slate-900 dark:!text-zinc-100 m-0 mb-4 pb-2 border-b-2 border-slate-200 dark:!border-zinc-800 text-center">تقفيل وتسوية الوردية</h3>
                <div class="space-y-4">
                    @if($record->status === 'open')
                        @foreach($cashBoxSummaries as $box)
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:!text-zinc-300 mb-1.5 text-right">
                                    الرصيد الفعلي في ({{ $box['name'] }}) <span style="color:#dc2626;">*</span>
                                </label>
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    wire:model.live="cashbox_actuals.{{ $box['id'] }}" 
                                    placeholder="ادخل المبلغ الفعلي في {{ $box['name'] }}" 
                                    class="w-full border border-slate-300 dark:!border-zinc-700 bg-white dark:!bg-zinc-950 text-slate-900 dark:!text-zinc-100 rounded-lg p-3 text-lg font-bold text-center" 
                                    required
                                />
                            </div>
                        @endforeach

                        <div class="border-t-2 border-slate-200 dark:!border-zinc-800 pt-4 mt-4">
                            <label class="block text-xs font-bold text-slate-700 dark:!text-zinc-300 mb-1.5 text-right">إجمالي الرصيد الفعلي (كافة الخزائن)</label>
                            <div class="bg-slate-50 dark:!bg-zinc-950 border border-slate-200 dark:!border-zinc-800 rounded-lg p-3 text-lg font-bold text-slate-900 dark:!text-zinc-100 text-center">
                                {{ number_format($actual_amount ?? 0.0, 2) }} ج.م
                            </div>
                        </div>
                    @else
                        @foreach($cashBoxSummaries as $box)
                            <div class="flex justify-between text-sm py-1.5 border-b border-dashed border-slate-200 dark:!border-zinc-800">
                                <span class="text-slate-600 dark:!text-zinc-400">رصيد {{ $box['name'] }} الفعلي:</span>
                                <span class="font-bold text-slate-900 dark:!text-zinc-100">{{ number_format($cashbox_actuals[$box['id']] ?? 0.0, 2) }} ج.م</span>
                            </div>
                        @endforeach

                        <div class="border-t-2 border-slate-200 dark:!border-zinc-800 pt-4 mt-4 flex justify-between text-base font-bold">
                            <span class="text-slate-900 dark:!text-zinc-100">إجمالي الرصيد الفعلي:</span>
                            <span class="text-emerald-600 dark:!text-emerald-400">{{ number_format($actual_amount, 2) }} ج.م</span>
                        </div>
                    @endif

                    @if($record->status === 'open')
                        <div class="pt-2">
                            <button 
                                type="button" 
                                wire:click="close" 
                                style="width: 100%; background: #ef4444; color: #fff; padding: 12px; border-radius: 8px; font-weight: bold; border: none; cursor: pointer; text-align: center; box-shadow: 0 4px 6px rgba(239, 68, 68, 0.2);"
                            >
                                تأكيد وتقفيل الوردية نهائياً
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
