@php
    $record = $getRecord();
    $expected = (float) $record->expected_amount;
    $actual = (float) $record->actual_amount;
    $difference = round($actual - $expected, 2);
    
    // Formatting helper
    $money = function($amount) {
        return number_format((float)$amount, 2) . ' EGP';
    };

    // Fetch details for the dashboard
    $playInvoices = \App\Models\Tenant\Invoice::query()
        ->where('shift_id', $record->id)
        ->where('invoice_type', 'device_sale')
        ->where('status', 'posted')
        ->with(['customer', 'device', 'payments', 'playSessionDetails'])
        ->get();

    $directSales = \App\Models\Tenant\Invoice::query()
        ->where('shift_id', $record->id)
        ->where('invoice_type', 'direct_sale')
        ->where('status', 'posted')
        ->with(['customer', 'payments'])
        ->get();

    $expenses = \App\Models\Tenant\ExpenseHistory::query()
        ->where('shift_id', $record->id)
        ->with(['cashBox', 'expenseType'])
        ->get();

    $purchases = \App\Models\Tenant\Purchase::query()
        ->where('shift_id', $record->id)
        ->with(['cashBox', 'supplier'])
        ->get();
@endphp

<div class="space-y-6" dir="rtl">
    <!-- Top Header Card -->
    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="background: linear-gradient(135deg, #4f46e5, #7c3aed); color: #fff; width: 56px; height: 56px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 900; box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);">
                #{{ $record->shift_no }}
            </div>
            <div>
                <h2 style="font-size: 1.3rem; font-weight: 800; color: #0f172a; margin: 0;">تفاصيل الوردية رقم {{ $record->shift_no }}</h2>
                <p style="font-size: 0.85rem; color: #64748b; margin: 4px 0 0 0; font-weight: 500;">
                    تاريخ الوردية: {{ $record->shift_date?->format('Y-m-d') }}
                </p>
            </div>
        </div>
        
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <!-- Status Badge -->
            @if($record->status === 'open')
                <div style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; font-weight: 700; font-size: 0.85rem; padding: 8px 16px; border-radius: 9999px; display: flex; align-items: center; gap: 8px;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981; display: inline-block; animation: pulse 2s infinite;"></span>
                    الوردية نشطة ومفتوحة حالياً
                </div>
            @else
                <div style="background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; font-weight: 700; font-size: 0.85rem; padding: 8px 16px; border-radius: 9999px; display: flex; align-items: center; gap: 8px;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #ef4444; display: inline-block;"></span>
                    الوردية مغلقة ومؤكدة
                </div>
            @endif
        </div>
    </div>

    <!-- Main Grid Metrics -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
        <!-- Starting Amount -->
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); display: flex; flex-direction: column; gap: 8px;">
            <span style="font-size: 0.85rem; color: #64748b; font-weight: 600;">رصيد افتتاح الدرج</span>
            <span style="font-size: 1.5rem; font-weight: 800; color: #1e293b;">{{ $money($record->start_amount) }}</span>
            <span style="font-size: 0.72rem; color: #94a3b8; font-weight: 500;">المبلغ الافتتاحي المستلم بالدرج</span>
        </div>

        <!-- PlayStation Revenue -->
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); display: flex; flex-direction: column; gap: 8px;">
            <span style="font-size: 0.85rem; color: #4f46e5; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                <span style="width: 6px; height: 6px; border-radius: 50%; background: #4f46e5; display: inline-block;"></span>
                إيرادات البلايستيشن
            </span>
            <span style="font-size: 1.5rem; font-weight: 800; color: #4f46e5;">{{ $money($record->playstation_amount) }}</span>
            <span style="font-size: 0.72rem; color: #94a3b8; font-weight: 500;">ألعاب الوقت المعتمدة بالشيفت</span>
        </div>

        <!-- Cafe/Sales Revenue -->
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); display: flex; flex-direction: column; gap: 8px;">
            <span style="font-size: 0.85rem; color: #d97706; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                <span style="width: 6px; height: 6px; border-radius: 50%; background: #d97706; display: inline-block;"></span>
                مبيعات الكوفى شوب
            </span>
            <span style="font-size: 1.5rem; font-weight: 800; color: #d97706;">{{ $money($record->sales_amount) }}</span>
            <span style="font-size: 0.72rem; color: #94a3b8; font-weight: 500;">المشروبات والمأكولات والمبيعات المباشرة</span>
        </div>

        <!-- Expected Cash -->
        <div style="background: linear-gradient(135deg, #eff6ff, #dbeafe); border: 1px solid #bfdbfe; border-radius: 20px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); display: flex; flex-direction: column; gap: 8px;">
            <span style="font-size: 0.85rem; color: #1e3a8a; font-weight: 700;">الرصيد الدفتري المتوقع</span>
            <span style="font-size: 1.5rem; font-weight: 900; color: #1d4ed8;">{{ $money($record->expected_amount) }}</span>
            <span style="font-size: 0.72rem; color: #1e3a8a; opacity: 0.8; font-weight: 600;">الرصيد الافتراضي حسب عمليات النظام</span>
        </div>

        <!-- Actual Cash Closed -->
        <div style="background: linear-gradient(135deg, #f0fdf4, #dcfce7); border: 1px solid #bbf7d0; border-radius: 20px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); display: flex; flex-direction: column; gap: 8px;">
            <span style="font-size: 0.85rem; color: #065f46; font-weight: 700;">المبلغ الفعلي بالدرج</span>
            <span style="font-size: 1.5rem; font-weight: 900; color: #15803d;">
                @if($record->status === 'open')
                    -
                @else
                    {{ $money($record->actual_amount) }}
                @endif
            </span>
            <span style="font-size: 0.72rem; color: #065f46; opacity: 0.8; font-weight: 600;">المبلغ المٌسلم فعلياً عند الإغلاق</span>
        </div>

        <!-- Difference / Deficit -->
        @if($record->status !== 'open')
            @if($difference < 0)
                <div style="background: linear-gradient(135deg, #fdf2f2, #fee2e2); border: 1px solid #fecaca; border-radius: 20px; padding: 20px; display: flex; flex-direction: column; gap: 8px;">
                    <span style="font-size: 0.85rem; color: #991b1b; font-weight: 700;">العجز بالدرج</span>
                    <span style="font-size: 1.5rem; font-weight: 900; color: #dc2626;">{{ $money(abs($difference)) }} -</span>
                    <span style="font-size: 0.72rem; color: #991b1b; font-weight: 600;">نقص نقدية بالخزينة عن الدفتري</span>
                </div>
            @elseif($difference > 0)
                <div style="background: linear-gradient(135deg, #f0fdf4, #dcfce7); border: 1px solid #bbf7d0; border-radius: 20px; padding: 20px; display: flex; flex-direction: column; gap: 8px;">
                    <span style="font-size: 0.85rem; color: #065f46; font-weight: 700;">الزيادة بالدرج</span>
                    <span style="font-size: 1.5rem; font-weight: 900; color: #16a34a;">{{ $money($difference) }} +</span>
                    <span style="font-size: 0.72rem; color: #065f46; font-weight: 600;">زيادة نقدية غير مبررة بالدرج</span>
                </div>
            @else
                <div style="background: linear-gradient(135deg, #f8fafc, #f1f5f9); border: 1px solid #e2e8f0; border-radius: 20px; padding: 20px; display: flex; flex-direction: column; gap: 8px;">
                    <span style="font-size: 0.85rem; color: #475569; font-weight: 700;">تطابق الدرج</span>
                    <span style="font-size: 1.5rem; font-weight: 900; color: #475569;">0.00 EGP</span>
                    <span style="font-size: 0.72rem; color: #475569; font-weight: 600;">الدرج سليم ومتطابق تماماً</span>
                </div>
            @endif
        @else
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 20px; padding: 20px; display: flex; flex-direction: column; gap: 8px;">
                <span style="font-size: 0.85rem; color: #64748b; font-weight: 600;">العجز والزيادة</span>
                <span style="font-size: 1.5rem; font-weight: 800; color: #94a3b8;">قيد العمل</span>
                <span style="font-size: 0.72rem; color: #94a3b8; font-weight: 500;">يتم احتسابه فور إغلاق الشيفت</span>
            </div>
        @endif
    </div>

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
                    <span style="color: #64748b;">إيراد بلايستيشن</span>
                    <span style="font-weight: 700; color: #0f172a;">{{ number_format($record->playstation_amount, 2) }} ج.م</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #64748b;">مبيعات المتجر والطلبات</span>
                    <span style="font-weight: 700; color: #0f172a;">{{ number_format($record->sales_amount, 2) }} ج.م</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #64748b;">إيداع كاش بمحفظة العملاء</span>
                    <span style="font-weight: 700; color: #22c55e;">+ {{ number_format($record->customer_add_amount, 2) }} ج.م</span>
                </div>
                <div style="display: flex; justify-content: space-between; border-top: 1px dashed #e2e8f0; padding-top: 8px;">
                    <span style="color: #64748b;">إجمالي الضرائب المستلمة</span>
                    <span style="font-weight: 700; color: #0f172a;">{{ number_format($record->tax_amount, 2) }} ج.م</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #64748b;">إجمالي خدمة الصالة المستلمة</span>
                    <span style="font-weight: 700; color: #0f172a;">{{ number_format($record->service_amount, 2) }} ج.م</span>
                </div>
                <div style="display: flex; justify-content: space-between; border-top: 1px solid #cbd5e1; padding-top: 8px; margin-top: 8px; font-weight: 800; color: #1e293b;">
                    <span>إجمالي الإيرادات:</span>
                    <span>{{ number_format($record->playstation_amount + $record->sales_amount + $record->customer_add_amount + $record->tax_amount + $record->service_amount, 2) }} ج.م</span>
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
                    <span style="font-weight: 700; color: #ef4444;">- {{ number_format($record->expenses_amount, 2) }} ج.م</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #64748b;">المشتريات للمخازن (Purchases)</span>
                    <span style="font-weight: 700; color: #ef4444;">- {{ number_format($record->purchase_amount, 2) }} ج.م</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #64748b;">سحب نقدي من محافظ العملاء</span>
                    <span style="font-weight: 700; color: #ef4444;">- {{ number_format($record->customer_minus_amount, 2) }} ج.م</span>
                </div>
                <div style="display: flex; justify-content: space-between; border-top: 1px dashed #e2e8f0; padding-top: 8px;">
                    <span style="color: #64748b;">إجمالي الخصومات المعطاة</span>
                    <span style="font-weight: 700; color: #e11d48;">- {{ number_format($record->discount_amount, 2) }} ج.م</span>
                </div>
                <div style="display: flex; justify-content: space-between; border-top: 1px solid #cbd5e1; padding-top: 8px; margin-top: 8px; font-weight: 800; color: #dc2626;">
                    <span>إجمالي المصروفات والخصومات:</span>
                    <span>- {{ number_format($record->expenses_amount + $record->purchase_amount + $record->customer_minus_amount + $record->discount_amount, 2) }} ج.م</span>
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
                    <span style="font-weight: 700; color: #eab308;">- {{ number_format($record->customer_credit_amount, 2) }} ج.م</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #64748b;">ديون آجلة غير مدفوعة (على الفواتير)</span>
                    <span style="font-weight: 700; color: #ea580c;">- {{ number_format(abs($record->credit_amount), 2) }} ج.م</span>
                </div>
                <div style="display: flex; justify-content: space-between; border-top: 1px solid #cbd5e1; padding-top: 8px; margin-top: 8px; font-weight: 800; color: #ea580c;">
                    <span>إجمالي التسويات غير النقدية:</span>
                    <span>- {{ number_format($record->customer_credit_amount + abs($record->credit_amount), 2) }} ج.م</span>
                </div>
                <div style="font-size: 0.72rem; color: #64748b; line-height: 1.4; border-top: 1px dashed #e2e8f0; padding-top: 8px;">
                    * ملحوظة: المعاملات غير النقدية (مثل الدفع برصيد العميل أو المتبقي الآجل) يتم استبعادها من الكاش المتوقع بالدرج لأنها معاملات دفتريّة.
                </div>
            </div>
        </div>
    </div>

    <!-- Standalone Cashbox Breakdown -->
    @if(is_array($record->cashbox_actual_amounts) && count($record->cashbox_actual_amounts) > 0)
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <h4 style="font-size: 0.9rem; font-weight: 800; color: #475569; margin: 0 0 12px 0; border-bottom: 2px solid #cbd5e1; padding-bottom: 6px;">توزيع النقدية الفعلية بالخزن:</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px;">
                @foreach($record->cashbox_actual_amounts as $box => $amt)
                    @php
                        $boxName = \App\Models\Tenant\CashBox::find($box)?->name ?? ("خزينة رقم " . $box);
                    @endphp
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 14px; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: 700; color: #334155; font-size: 0.85rem;">{{ $boxName }}</span>
                        <span style="color: #059669; font-weight: 800; font-size: 0.95rem;">{{ number_format($amt, 2) }} ج.م</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Opener & Closer Info Footer -->
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 20px; padding: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;">
        <div style="display: flex; flex-direction: column; gap: 4px;">
            <span style="font-size: 0.75rem; color: #64748b; font-weight: 600;">الموظف المسؤول عن فتح الوردية:</span>
            <span style="font-size: 0.95rem; font-weight: 800; color: #0f172a;">{{ $record->opener?->name ?? 'غير محدد' }}</span>
            <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 500;">وقت الفتح: {{ $record->shift_from ? app(\App\Support\Tenant\TenantClock::class)->utcToLocal($record->shift_from)->format('Y-m-d H:i:s') : '-' }}</span>
        </div>
        
        <div style="display: flex; flex-direction: column; gap: 4px;">
            <span style="font-size: 0.75rem; color: #64748b; font-weight: 600;">الموظف المسؤول عن إغلاق الوردية:</span>
            <span style="font-size: 0.95rem; font-weight: 800; color: #0f172a;">{{ $record->closer?->name ?? '-' }}</span>
            <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 500;">
                وقت الإغلاق: {{ $record->shift_to ? app(\App\Support\Tenant\TenantClock::class)->utcToLocal($record->shift_to)->format('Y-m-d H:i:s') : '-' }}
            </span>
        </div>

        @if($record->notes)
            <div style="grid-column: 1 / -1; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 12px; padding: 14px; display: flex; flex-direction: column; gap: 4px;">
                <span style="font-size: 0.75rem; color: #b45309; font-weight: 700;">ملاحظات الوردية:</span>
                <span style="font-size: 0.9rem; color: #78350f; font-weight: 500;">{{ $record->notes }}</span>
            </div>
        @endif
    </div>

    <!-- Detailed operations lists (Accordions) -->
    <h3 style="font-size: 1.15rem; font-weight: 800; color: #1e293b; margin: 25px 0 10px 0; border-right: 4px solid #4f46e5; padding-right: 10px;">سجل العمليات التفصيلي خلال الوردية</h3>
    
    <div style="display: grid; gap: 16px;">
        <!-- PlayStation Invoices Accordion -->
        <details style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; padding: 12px 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);">
            <summary style="font-weight: 800; color: #334155; cursor: pointer; display: flex; justify-content: space-between; align-items: center; list-style: none;">
                <span style="display: flex; align-items: center; gap: 8px;">
                    <span style="background: #eff6ff; color: #2563eb; width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 800;">
                        {{ count($playInvoices) }}
                    </span>
                    فواتير البلايستيشن والأجهزة
                </span>
                <svg style="width: 18px; height: 18px; color: #64748b;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
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
                                <td style="padding: 8px; font-weight: 700;">{{ $inv->invoice_no }}</td>
                                <td style="padding: 8px;">{{ $inv->device->name ?? '-' }}</td>
                                <td style="padding: 8px;">{{ $inv->customer->name ?? 'بدون عميل' }}</td>
                                <td style="padding: 8px;">{{ $inv->playSessionDetails[0]->period ?? '-' }} دقيقة</td>
                                <td style="padding: 8px;">{{ number_format($inv->play_amount, 2) }} ج.م</td>
                                <td style="padding: 8px;">{{ number_format($inv->order_amount, 2) }} ج.م</td>
                                <td style="padding: 8px; color: #e11d48;">{{ number_format($inv->discount_amount, 2) }} ج.م</td>
                                <td style="padding: 8px; font-weight: bold;">{{ number_format($inv->final_amount, 2) }} ج.م</td>
                                <td style="padding: 8px; color: #16a34a; font-weight: bold;">
                                    {{ number_format($inv->payments->where('payment_method', 'cash')->sum('amount'), 2) }} ج.م
                                </td>
                                <td style="padding: 8px; color: #eab308;">
                                    {{ number_format($inv->payments->where('payment_method', 'customer_balance')->sum('amount'), 2) }} ج.م
                                </td>
                                <td style="padding: 8px; color: {{ $inv->remain_amount > 0 ? '#ea580c' : '#64748b' }};">
                                    {{ number_format($inv->remain_amount, 2) }} ج.م
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" style="text-align: center; padding: 20px; color: #94a3b8;">لا توجد فواتير لعب بالوردية حتى الآن.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($playInvoices) > 0)
                        <tfoot>
                            <tr style="border-top: 2px solid #cbd5e1; font-weight: 800; background: #f8fafc; color: #1e293b;">
                                <td colspan="4" style="padding: 8px; text-align: center;">الإجمالي:</td>
                                <td style="padding: 8px;">{{ number_format($playInvoices->sum('play_amount'), 2) }} ج.م</td>
                                <td style="padding: 8px;">{{ number_format($playInvoices->sum('order_amount'), 2) }} ج.م</td>
                                <td style="padding: 8px; color: #e11d48;">{{ number_format($playInvoices->sum('discount_amount'), 2) }} ج.م</td>
                                <td style="padding: 8px; font-weight: bold;">{{ number_format($playInvoices->sum('final_amount'), 2) }} ج.م</td>
                                <td style="padding: 8px; color: #16a34a;">
                                    {{ number_format($playInvoices->flatMap->payments->where('payment_method', 'cash')->sum('amount'), 2) }} ج.م
                                </td>
                                <td style="padding: 8px; color: #eab308;">
                                    {{ number_format($playInvoices->flatMap->payments->where('payment_method', 'customer_balance')->sum('amount'), 2) }} ج.م
                                </td>
                                <td style="padding: 8px; color: #ea580c;">{{ number_format($playInvoices->sum('remain_amount'), 2) }} ج.م</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </details>

        <!-- Direct Sales Accordion -->
        <details style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; padding: 12px 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);">
            <summary style="font-weight: 800; color: #334155; cursor: pointer; display: flex; justify-content: space-between; align-items: center; list-style: none;">
                <span style="display: flex; align-items: center; gap: 8px;">
                    <span style="background: #f0fdf4; color: #16a34a; width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 800;">
                        {{ count($directSales) }}
                    </span>
                    فواتير البيع المباشر (المتجر)
                </span>
                <svg style="width: 18px; height: 18px; color: #64748b;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
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
                                <td style="padding: 8px; font-weight: 700;">{{ $sale->invoice_no }}</td>
                                <td style="padding: 8px;">{{ $sale->customer->name ?? 'بدون عميل' }}</td>
                                <td style="padding: 8px;">{{ number_format($sale->subtotal, 2) }} ج.م</td>
                                <td style="padding: 8px; color: #e11d48;">{{ number_format($sale->discount_amount, 2) }} ج.م</td>
                                <td style="padding: 8px; font-weight: bold;">{{ number_format($sale->final_amount, 2) }} ج.م</td>
                                <td style="padding: 8px; color: #16a34a; font-weight: bold;">
                                    {{ number_format($sale->payments->where('payment_method', 'cash')->sum('amount'), 2) }} ج.م
                                </td>
                                <td style="padding: 8px; color: #eab308;">
                                    {{ number_format($sale->payments->where('payment_method', 'customer_balance')->sum('amount'), 2) }} ج.م
                                </td>
                                <td style="padding: 8px; color: {{ $sale->remain_amount > 0 ? '#ea580c' : '#64748b' }};">
                                    {{ number_format($sale->remain_amount, 2) }} ج.م
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 20px; color: #94a3b8;">لا توجد فواتير بيع مباشر بالوردية حتى الآن.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($directSales) > 0)
                        <tfoot>
                            <tr style="border-top: 2px solid #cbd5e1; font-weight: 800; background: #f8fafc; color: #1e293b;">
                                <td colspan="2" style="padding: 8px; text-align: center;">الإجمالي:</td>
                                <td style="padding: 8px;">{{ number_format($directSales->sum('subtotal'), 2) }} ج.م</td>
                                <td style="padding: 8px; color: #e11d48;">{{ number_format($directSales->sum('discount_amount'), 2) }} ج.م</td>
                                <td style="padding: 8px; font-weight: bold;">{{ number_format($directSales->sum('final_amount'), 2) }} ج.م</td>
                                <td style="padding: 8px; color: #16a34a;">
                                    {{ number_format($directSales->flatMap->payments->where('payment_method', 'cash')->sum('amount'), 2) }} ج.م
                                </td>
                                <td style="padding: 8px; color: #eab308;">
                                    {{ number_format($directSales->flatMap->payments->where('payment_method', 'customer_balance')->sum('amount'), 2) }} ج.م
                                </td>
                                <td style="padding: 8px; color: #ea580c;">{{ number_format($directSales->sum('remain_amount'), 2) }} ج.م</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </details>

        <!-- Expenses Accordion -->
        <details style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; padding: 12px 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);">
            <summary style="font-weight: 800; color: #334155; cursor: pointer; display: flex; justify-content: space-between; align-items: center; list-style: none;">
                <span style="display: flex; align-items: center; gap: 8px;">
                    <span style="background: #fef2f2; color: #ef4444; width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 800;">
                        {{ count($expenses) }}
                    </span>
                    المصروفات النقدية المسجلة
                </span>
                <svg style="width: 18px; height: 18px; color: #64748b;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
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
                                <td style="padding: 8px;">{{ app(\App\Support\Tenant\TenantClock::class)->utcToLocal($exp->created_at)->format('H:i') }}</td>
                                <td style="padding: 8px; font-weight: 700;">{{ $exp->expenseType->name ?? '-' }}</td>
                                <td style="padding: 8px;">{{ $exp->cashBox->name ?? '-' }}</td>
                                <td style="padding: 8px;">{{ $exp->description }}</td>
                                <td style="padding: 8px; color: #dc2626; font-weight: bold;">- {{ number_format($exp->amount, 2) }} ج.م</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 20px; color: #94a3b8;">لا توجد مصروفات مسجلة بالوردية.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($expenses) > 0)
                        <tfoot>
                            <tr style="border-top: 2px solid #cbd5e1; font-weight: 800; background: #f8fafc; color: #1e293b;">
                                <td colspan="4" style="padding: 8px; text-align: center;">الإجمالي:</td>
                                <td style="padding: 8px; color: #dc2626;">- {{ number_format($expenses->sum('amount'), 2) }} ج.م</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </details>

        <!-- Purchases Accordion -->
        <details style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; padding: 12px 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);">
            <summary style="font-weight: 800; color: #334155; cursor: pointer; display: flex; justify-content: space-between; align-items: center; list-style: none;">
                <span style="display: flex; align-items: center; gap: 8px;">
                    <span style="background: #fff7ed; color: #f97316; width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 800;">
                        {{ count($purchases) }}
                    </span>
                    فواتير شراء المخزون
                </span>
                <svg style="width: 18px; height: 18px; color: #64748b;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
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
                                <td style="padding: 8px; font-weight: 700;">{{ $pur->reference ?: ('#' . $pur->id) }}</td>
                                <td style="padding: 8px;">{{ $pur->supplier->name ?? '-' }}</td>
                                <td style="padding: 8px;">{{ $pur->cashBox->name ?? '-' }}</td>
                                <td style="padding: 8px;">{{ $pur->cash_box_id ? 'كاش' : 'آجل' }}</td>
                                <td style="padding: 8px; color: #f97316; font-weight: bold;">- {{ number_format($pur->total_amount, 2) }} ج.م</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 20px; color: #94a3b8;">لا توجد فواتير شراء بالوردية.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($purchases) > 0)
                        <tfoot>
                            <tr style="border-top: 2px solid #cbd5e1; font-weight: 800; background: #f8fafc; color: #1e293b;">
                                <td colspan="4" style="padding: 8px; text-align: center;">الإجمالي:</td>
                                <td style="padding: 8px; color: #f97316;">- {{ number_format($purchases->sum('total_amount'), 2) }} ج.م</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </details>
    </div>
</div>

<style>
    @keyframes pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: .5; transform: scale(1.15); }
    }
</style>
