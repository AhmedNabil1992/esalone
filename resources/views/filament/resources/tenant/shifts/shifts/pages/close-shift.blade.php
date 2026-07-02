<x-filament-panels::page>
    <div style="direction: rtl;" class="space-y-6">
        <!-- 1. Shift Header Info Card -->
        <div style="background: linear-gradient(135deg, #1e293b, #0f172a); border-radius: 16px; padding: 20px; color: #fff; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
            <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 15px;">
                <div>
                    <h2 style="font-size: 1.4rem; font-weight: 800; margin: 0;">الوردية رقم: #{{ $record->shift_no }}</h2>
                    <p style="font-size: 0.85rem; color: #94a3b8; margin: 4px 0 0 0;">
                        تاريخ الوردية: {{ $record->shift_date?->format('Y-m-d') ?? '-' }}
                    </p>
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                    <div>
                        <span style="display: block; font-size: 0.75rem; color: #94a3b8;">فتح بواسطة</span>
                        <span style="font-weight: 700; font-size: 0.9rem;">{{ $record->opener?->name ?? 'غير معروف' }}</span>
                    </div>
                    <div>
                        <span style="display: block; font-size: 0.75rem; color: #94a3b8;">بداية الوردية</span>
                        <span style="font-weight: 700; font-size: 0.9rem;">{{ $record->shift_from?->format('Y-m-d H:i') ?? '-' }}</span>
                    </div>
                    <div>
                        <span style="display: block; font-size: 0.75rem; color: #94a3b8;">نهاية الوردية</span>
                        <span style="font-weight: 700; font-size: 0.9rem;">{{ $record->shift_to?->format('Y-m-d H:i') ?? 'الوردية نشطة حالياً' }}</span>
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
                    <div style="background: rgba(59, 130, 246, 0.2); color: #3b82f6; padding: 6px 16px; border-radius: 99px; font-weight: 800; font-size: 0.85rem;">
                        الوردية مفتوحة
                    </div>
                @endif
            </div>
        </div>

        @if(auth()->user()->can('update', $record))
            <!-- 2. Financial Breakdown Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                
                <!-- Incomes Section -->
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 15px; border-bottom: 2px solid #22c55e; padding-bottom: 8px;">
                        <svg style="width: 20px; height: 20px; color: #22c55e;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        <h3 style="font-size: 1rem; font-weight: 800; color: #1e293b; margin: 0;">تفاصيل الإيرادات كاش ودفتري (Incomes)</h3>
                    </div>
                    <div style="display: grid; gap: 12px; font-size: 0.85rem;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #64748b;">إيراد بلايستيشن (صافي دفتري)</span>
                            <span style="font-weight: 700; color: #0f172a;">{{ number_format($stats['playstation_amount'], 2) }} ج.م</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #64748b;">مبيعات المتجر والطلبات (دفتري)</span>
                            <span style="font-weight: 700; color: #0f172a;">{{ number_format($stats['sales_amount'], 2) }}  ج.م</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #64748b;">إيداع كاش بمحفظة العملاء</span>
                            <span style="font-weight: 700; color: #22c55e;">+ {{ number_format($stats['customer_add_amount'], 2) }} ج.م</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-top: 1px dashed #e2e8f0; padding-top: 8px;">
                            <span style="color: #64748b;">إجمالي الضرائب المستلمة</span>
                            <span style="font-weight: 700; color: #0f172a;">{{ number_format($stats['tax_amount'], 2) }} ج.م</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #64748b;">إجمالي خدمة الصالة المستلمة</span>
                            <span style="font-weight: 700; color: #0f172a;">{{ number_format($stats['service_amount'], 2) }} ج.م</span>
                        </div>
                    </div>
                </div>

                <!-- Outcomes & Deductions Section -->
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 15px; border-bottom: 2px solid #ef4444; padding-bottom: 8px;">
                        <svg style="width: 20px; height: 20px; color: #ef4444;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"/>
                        </svg>
                        <h3 style="font-size: 1rem; font-weight: 800; color: #1e293b; margin: 0;">المصروفات والخصومات كاش ودفتري</h3>
                    </div>
                    <div style="display: grid; gap: 12px; font-size: 0.85rem;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #64748b;">المصروفات النقدية (Expenses)</span>
                            <span style="font-weight: 700; color: #ef4444;">- {{ number_format($stats['expenses_amount'], 2) }} ج.م</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #64748b;">المشتريات للمخازن (Purchases)</span>
                            <span style="font-weight: 700; color: #ef4444;">- {{ number_format($stats['purchase_amount'], 2) }} ج.م</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #64748b;">سحب نقدي من محافظ العملاء</span>
                            <span style="font-weight: 700; color: #ef4444;">- {{ number_format($stats['customer_minus_amount'], 2) }} ج.م</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-top: 1px dashed #e2e8f0; padding-top: 8px;">
                            <span style="color: #64748b;">إجمالي الخصومات المعطاة</span>
                            <span style="font-weight: 700; color: #e11d48;">- {{ number_format($stats['discount_amount'], 2) }} ج.م</span>
                        </div>
                    </div>
                </div>

                <!-- Non-Cash Settlements (Customer Balance & Unpaid) -->
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 15px; border-bottom: 2px solid #eab308; padding-bottom: 8px;">
                        <svg style="width: 20px; height: 20px; color: #eab308;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/>
                        </svg>
                        <h3 style="font-size: 1rem; font-weight: 800; color: #1e293b; margin: 0;">تسويات غير نقدية (تخصم من رصيد الكاش المتوقع)</h3>
                    </div>
                    <div style="display: grid; gap: 12px; font-size: 0.85rem;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #64748b;">مدفوعات من رصيد محافظ العملاء</span>
                            <span style="font-weight: 700; color: #eab308;">- {{ number_format($stats['customer_credit_amount'], 2) }} ج.م</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #64748b;">ديون آجلة غير مدفوعة (على الفواتير)</span>
                            <span style="font-weight: 700; color: #ea580c;">- {{ number_format($stats['credit_amount'], 2) }} ج.م</span>
                        </div>
                        <div style="font-size: 0.72rem; color: #64748b; line-height: 1.4; border-top: 1px dashed #e2e8f0; padding-top: 8px;">
                            * ملحوظة: المعاملات غير النقدية (مثل الدفع برصيد العميل أو المتبقي الآجل) يتم استبعادها من الكاش المتوقع بالدرج لأنها معاملات دفتريّة.
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Expected Cash Flow Summary Banner -->
            <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 20px;">
                    <div style="display: flex; flex-wrap: wrap; gap: 30px;">
                        <div>
                            <span style="display: block; font-size: 0.8rem; color: #64748b; margin-bottom: 4px;">رصيد بداية الوردية (الافتتاحي)</span>
                            <span style="font-size: 1.3rem; font-weight: 800; color: #0f172a;">{{ number_format($stats['start_amount'], 2) }} ج.م</span>
                        </div>
                        <div style="align-self: center; font-size: 1.5rem; color: #94a3b8;">+</div>
                        <div>
                            <span style="display: block; font-size: 0.8rem; color: #64748b; margin-bottom: 4px;">صافي الحركة النقدية بالوردية</span>
                            @php
                                $netCash = $stats['expected_cash'] - $stats['start_amount'];
                            @endphp
                            <span style="font-size: 1.3rem; font-weight: 800; color: {{ $netCash >= 0 ? '#16a34a' : '#dc2626' }};">
                                {{ $netCash >= 0 ? '+' : '' }}{{ number_format($netCash, 2) }} ج.م
                            </span>
                        </div>
                    </div>
                    <div style="background: #0f172a; color: #fff; padding: 16px 24px; border-radius: 12px; text-align: left; min-width: 200px;">
                        <span style="display: block; font-size: 0.78rem; color: #94a3b8; margin-bottom: 4px; text-align: right;">الرصيد النقدي المتوقع بالدرج</span>
                        <span style="font-size: 1.8rem; font-weight: 900; color: #38bdf8;">{{ number_format($stats['expected_cash'], 2) }} ج.م</span>
                    </div>
                </div>
            </div>

            <!-- 4. Cashbox Breakdowns -->
            @if(count($cashBoxSummaries) > 0)
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                    <h3 style="font-size: 1rem; font-weight: 800; color: #1e293b; margin: 0 0 15px 0;">تفاصيل حركة السيولة النقدية لكل خزينة</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 15px;">
                        @foreach($cashBoxSummaries as $box)
                            <div style="border: 1px solid #f1f5f9; background: #fafafb; border-radius: 12px; padding: 16px; display: flex; flex-direction: column; gap: 8px;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-weight: 800; color: #1e293b; font-size: 0.95rem;">{{ $box['name'] }}</span>
                                    <span style="font-size: 0.72rem; padding: 2px 8px; border-radius: 99px; background: rgba(56, 189, 248, 0.1); color: #0284c7; font-weight: 700;">خزينة نشطة</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; border-top: 1px dashed #e2e8f0; padding-top: 8px; margin-top: 4px;">
                                    <span style="color: #64748b;">صافي حركة الشيفت:</span>
                                    <span style="font-weight: 700; color: {{ $box['net_change'] >= 0 ? '#16a34a' : '#dc2626' }};">
                                        {{ $box['net_change'] >= 0 ? '+' : '' }}{{ number_format($box['net_change'], 2) }} ج.م
                                    </span>
                                </div>
                                <div style="display: flex; justify-content: space-between; font-size: 0.8rem;">
                                    <span style="color: #64748b;">الرصيد الإجمالي المتوقع:</span>
                                    <span style="font-weight: 800; color: #0f172a;">{{ number_format($box['current_balance'], 2) }} ج.م</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- 5. Discrepancy & Close Submission Form -->
            <div 
                x-data="{ expected: {{ $stats['expected_cash'] }}, actual: @entangle('actual_amount') }" 
                style="background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);"
            >
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
                    <!-- Inputs -->
                    <div class="space-y-4">
                        <h3 style="font-size: 1.1rem; font-weight: 800; color: #1e293b; margin: 0 0 8px 0; border-bottom: 2px solid #cbd5e1; padding-bottom: 6px;">تقفيل وتسوية الوردية</h3>
                        
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #374151; margin-bottom: 6px;">الرصيد الفعلي المتواجد بالدرج (كاش) <span style="color:#dc2626;">*</span></label>
                            @if($record->status === 'open')
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    x-model.number="actual" 
                                    placeholder="ادخل المبلغ الفعلي بعد جرد الدرج" 
                                    style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; font-size: 1.1rem; font-weight: bold;" 
                                    required
                                />
                            @else
                                <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; font-size: 1.1rem; font-weight: bold; color: #1e293b;">
                                    {{ number_format($actual_amount, 2) }} ج.م
                                </div>
                            @endif
                        </div>

                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #374151; margin-bottom: 6px;">ملاحظات التسوية والتسليم</label>
                            @if($record->status === 'open')
                                <textarea 
                                    wire:model.live="notes" 
                                    rows="3" 
                                    placeholder="اكتب أي ملاحظات أو تبرير للعجز والزيادة إن وجد..." 
                                    style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; font-size: 0.85rem;"
                                ></textarea>
                            @else
                                <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; font-size: 0.85rem; color: #334155; min-height: 80px; white-space: pre-wrap;">
                                    {{ $notes ?: 'لا توجد ملاحظات.' }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Discrepancy Box -->
                    <div style="display: flex; flex-direction: column; justify-content: center; background: #fafafb; border: 1px solid #f1f5f9; border-radius: 12px; padding: 20px;">
                        <h4 style="font-size: 0.85rem; font-weight: 700; color: #64748b; margin: 0 0 10px 0; text-align: center;">حالة التسوية والفرق</h4>
                        
                        <!-- Perfect match -->
                        <div x-show="actual !== null && actual === expected" style="text-align: center; color: #16a34a; padding: 15px;">
                            <svg style="width: 48px; height: 48px; margin: 0 auto 10px auto;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/>
                            </svg>
                            <span style="font-size: 1.1rem; font-weight: 800; display: block;">الحساب مطابق تماماً!</span>
                            <span style="font-size: 0.8rem; color: #64748b; margin-top: 4px; display: block;">الرصيد الفعلي يطابق الرصيد المتوقع بالمليم.</span>
                        </div>

                        <!-- Discrepancy (Surplus or Shortage) -->
                        <div x-show="actual !== null && actual !== expected" style="text-align: center; padding: 15px;">
                            <template x-if="actual < expected">
                                <div>
                                    <div style="background: rgba(220, 38, 38, 0.1); color: #dc2626; display: inline-block; padding: 6px 16px; border-radius: 99px; font-weight: 800; font-size: 0.85rem; margin-bottom: 8px;">
                                        عجز بالخزينة
                                    </div>
                                    <span style="font-size: 2.2rem; font-weight: 900; color: #dc2626; display: block; direction: ltr;" x-text="'-' + Math.abs(expected - actual).toFixed(2) + ' ج.م'"></span>
                                    <span style="font-size: 0.8rem; color: #64748b; margin-top: 4px; display: block;">
                                        الرصيد الفعلي بالدرج أقل من المطلوب بمقدار الفرق أعلاه.
                                    </span>
                                </div>
                            </template>
                            <template x-if="actual > expected">
                                <div>
                                    <div style="background: rgba(22, 163, 74, 0.1); color: #16a34a; display: inline-block; padding: 6px 16px; border-radius: 99px; font-weight: 800; font-size: 0.85rem; margin-bottom: 8px;">
                                        زيادة بالخزينة
                                    </div>
                                    <span style="font-size: 2.2rem; font-weight: 900; color: #16a34a; display: block; direction: ltr;" x-text="'+' + Math.abs(actual - expected).toFixed(2) + ' ج.م'"></span>
                                    <span style="font-size: 0.8rem; color: #64748b; margin-top: 4px; display: block;">
                                        الرصيد الفعلي بالدرج أكثر من المطلوب بمقدار الفرق أعلاه.
                                    </span>
                                </div>
                            </template>
                        </div>

                        <!-- Prompt to fill -->
                        <div x-show="actual === null || actual === ''" style="text-align: center; color: #64748b; padding: 25px;">
                            <span style="font-size: 0.85rem; font-style: italic;">يرجى كتابة الرصيد الفعلي بالدرج ليتم احتساب الفروقات تلقائياً.</span>
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
            <h3 style="font-size: 1.1rem; font-weight: 800; color: #1e293b; margin: 25px 0 10px 0;">سجل العمليات التفصيلي خلال الوردية</h3>
            
            <div style="display: grid; gap: 12px;">
                <!-- PlayStation Invoices Accordion -->
                <details style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; padding: 10px 16px;">
                    <summary style="font-weight: 800; color: #334155; cursor: pointer; display: flex; justify-content: space-between; align-items: center; list-style: none;">
                        <span style="display: flex; align-items: center; gap: 8px;">
                            <span style="background: #eff6ff; color: #2563eb; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.72rem; font-weight: 800;">
                                {{ count($playInvoices) }}
                            </span>
                            فواتير البلايستيشن والأجهزة
                        </span>
                        <svg style="width: 16px; height: 16px; color: #64748b;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                    </summary>
                    <div style="margin-top: 15px; overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: right; font-size: 0.8rem;">
                            <thead>
                                <tr style="border-bottom: 2px solid #cbd5e1; color: #64748b;">
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
                                    <tr style="border-bottom: 1px solid #f1f5f9; color: #334155;">
                                        <td style="padding: 8px; font-weight: 700;">{{ $inv['invoice_no'] }}</td>
                                        <td style="padding: 8px;">{{ $inv['device']['name'] ?? '-' }}</td>
                                        <td style="padding: 8px;">{{ $inv['customer']['name'] ?? 'بدون عميل' }}</td>
                                        <td style="padding: 8px;">{{ $inv['play_session_details'][0]['period'] ?? '-' }} دقيقة</td>
                                        <td style="padding: 8px;">{{ number_format($inv['play_amount'], 2) }} ج.م</td>
                                        <td style="padding: 8px;">{{ number_format($inv['order_amount'], 2) }} ج.م</td>
                                        <td style="padding: 8px; color: #e11d48;">{{ number_format($inv['discount_amount'], 2) }} ج.م</td>
                                        <td style="padding: 8px; font-weight: bold;">{{ number_format($inv['final_amount'], 2) }} ج.م</td>
                                        <td style="padding: 8px; color: #16a34a; font-weight: bold;">
                                            {{ number_format(collect($inv['payments'])->where('payment_method', 'cash')->sum('amount'), 2) }} ج.م
                                        </td>
                                        <td style="padding: 8px; color: #eab308;">
                                            {{ number_format(collect($inv['payments'])->where('payment_method', 'customer_balance')->sum('amount'), 2) }} ج.م
                                        </td>
                                        <td style="padding: 8px; color: {{ $inv['remain_amount'] > 0 ? '#ea580c' : '#64748b' }};">
                                            {{ number_format($inv['remain_amount'], 2) }} ج.م
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" style="text-align: center; padding: 20px; color: #94a3b8;">لا توجد فواتير لعب بالوردية حتى الآن.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </details>

                <!-- Direct Sales Accordion -->
                <details style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; padding: 10px 16px;">
                    <summary style="font-weight: 800; color: #334155; cursor: pointer; display: flex; justify-content: space-between; align-items: center; list-style: none;">
                        <span style="display: flex; align-items: center; gap: 8px;">
                            <span style="background: #f0fdf4; color: #16a34a; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.72rem; font-weight: 800;">
                                {{ count($directSales) }}
                            </span>
                            فواتير البيع المباشر (المتجر)
                        </span>
                        <svg style="width: 16px; height: 16px; color: #64748b;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                    </summary>
                    <div style="margin-top: 15px; overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: right; font-size: 0.8rem;">
                            <thead>
                                <tr style="border-bottom: 2px solid #cbd5e1; color: #64748b;">
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
                                    <tr style="border-bottom: 1px solid #f1f5f9; color: #334155;">
                                        <td style="padding: 8px; font-weight: 700;">{{ $sale['invoice_no'] }}</td>
                                        <td style="padding: 8px;">{{ $sale['customer']['name'] ?? 'بدون عميل' }}</td>
                                        <td style="padding: 8px;">{{ number_format($sale['subtotal'], 2) }} ج.م</td>
                                        <td style="padding: 8px; color: #e11d48;">{{ number_format($sale['discount_amount'], 2) }} ج.م</td>
                                        <td style="padding: 8px; font-weight: bold;">{{ number_format($sale['final_amount'], 2) }} ج.م</td>
                                        <td style="padding: 8px; color: #16a34a; font-weight: bold;">
                                            {{ number_format(collect($sale['payments'])->where('payment_method', 'cash')->sum('amount'), 2) }} ج.م
                                        </td>
                                        <td style="padding: 8px; color: #eab308;">
                                            {{ number_format(collect($sale['payments'])->where('payment_method', 'customer_balance')->sum('amount'), 2) }} ج.م
                                        </td>
                                        <td style="padding: 8px; color: {{ $sale['remain_amount'] > 0 ? '#ea580c' : '#64748b' }};">
                                            {{ number_format($sale['remain_amount'], 2) }} ج.م
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 20px; color: #94a3b8;">لا توجد فواتير بيع مباشر بالوردية حتى الآن.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </details>

                <!-- Expenses Accordion -->
                <details style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; padding: 10px 16px;">
                    <summary style="font-weight: 800; color: #334155; cursor: pointer; display: flex; justify-content: space-between; align-items: center; list-style: none;">
                        <span style="display: flex; align-items: center; gap: 8px;">
                            <span style="background: #fef2f2; color: #ef4444; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.72rem; font-weight: 800;">
                                {{ count($expenses) }}
                            </span>
                            المصروفات النقدية المسجلة
                        </span>
                        <svg style="width: 16px; height: 16px; color: #64748b;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                    </summary>
                    <div style="margin-top: 15px; overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: right; font-size: 0.8rem;">
                            <thead>
                                <tr style="border-bottom: 2px solid #cbd5e1; color: #64748b;">
                                    <th style="padding: 8px;">التاريخ/الوقت</th>
                                    <th style="padding: 8px;">بند المصروف</th>
                                    <th style="padding: 8px;">الخزينة المدفوع منها</th>
                                    <th style="padding: 8px;">البيان/الوصف</th>
                                    <th style="padding: 8px;">القيمة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expenses as $exp)
                                    <tr style="border-bottom: 1px solid #f1f5f9; color: #334155;">
                                        <td style="padding: 8px;">{{ \Illuminate\Support\Carbon::parse($exp['created_at'])->format('H:i') }}</td>
                                        <td style="padding: 8px; font-weight: 700;">{{ $exp['expense_type']['name'] ?? '-' }}</td>
                                        <td style="padding: 8px;">{{ $exp['cash_box']['name'] ?? '-' }}</td>
                                        <td style="padding: 8px;">{{ $exp['description'] }}</td>
                                        <td style="padding: 8px; color: #dc2626; font-weight: bold;">- {{ number_format($exp['amount'], 2) }} ج.م</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" style="text-align: center; padding: 20px; color: #94a3b8;">لا توجد مصروفات مسجلة بالوردية.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </details>

                <!-- Purchases Accordion -->
                <details style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; padding: 10px 16px;">
                    <summary style="font-weight: 800; color: #334155; cursor: pointer; display: flex; justify-content: space-between; align-items: center; list-style: none;">
                        <span style="display: flex; align-items: center; gap: 8px;">
                            <span style="background: #fff7ed; color: #f97316; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.72rem; font-weight: 800;">
                                {{ count($purchases) }}
                            </span>
                            فواتير شراء المخزون
                        </span>
                        <svg style="width: 16px; height: 16px; color: #64748b;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                    </summary>
                    <div style="margin-top: 15px; overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: right; font-size: 0.8rem;">
                            <thead>
                                <tr style="border-bottom: 2px solid #cbd5e1; color: #64748b;">
                                    <th style="padding: 8px;">رقم فاتورة الشراء</th>
                                    <th style="padding: 8px;">المورد</th>
                                    <th style="padding: 8px;">الخزينة المستعملة</th>
                                    <th style="padding: 8px;">طريقة الدفع</th>
                                    <th style="padding: 8px;">المبلغ الإجمالي</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($purchases as $pur)
                                    <tr style="border-bottom: 1px solid #f1f5f9; color: #334155;">
                                        <td style="padding: 8px; font-weight: 700;">{{ $pur['reference'] ?: ('#' . $pur['id']) }}</td>
                                        <td style="padding: 8px;">{{ $pur['supplier']['name'] ?? '-' }}</td>
                                        <td style="padding: 8px;">{{ $pur['cash_box']['name'] ?? '-' }}</td>
                                        <td style="padding: 8px;">{{ $pur['cash_box_id'] ? 'كاش' : 'آجل' }}</td>
                                        <td style="padding: 8px; color: #f97316; font-weight: bold;">- {{ number_format($pur['total_amount'], 2) }} ج.م</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" style="text-align: center; padding: 20px; color: #94a3b8;">لا توجد فواتير شراء بالوردية.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </details>
            </div>
        @else
            <!-- Simplified Form for cashiers (Blind Close) -->
            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); max-width: 500px; margin: 0 auto;">
                <h3 style="font-size: 1.1rem; font-weight: 800; color: #1e293b; margin: 0 0 15px 0; border-bottom: 2px solid #cbd5e1; padding-bottom: 6px; text-align: center;">تقفيل وتسوية الوردية</h3>
                <div class="space-y-4">
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #374151; margin-bottom: 6px; text-align: right;">الرصيد الفعلي المتواجد بالدرج (كاش) <span style="color:#dc2626;">*</span></label>
                        @if($record->status === 'open')
                            <input 
                                type="number" 
                                step="0.01" 
                                wire:model.live="actual_amount"
                                placeholder="ادخل المبلغ الفعلي بالكامل المتواجد بالدرج" 
                                style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; font-size: 1.1rem; font-weight: bold; text-align: center;" 
                                required
                            />
                        @else
                            <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; font-size: 1.1rem; font-weight: bold; color: #1e293b; text-align: center;">
                                {{ number_format($actual_amount, 2) }} ج.م
                            </div>
                        @endif
                    </div>

                    @if($record->status === 'open')
                        <div style="padding-top: 10px;">
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
