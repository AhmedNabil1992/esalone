<x-filament-panels::page>
    @php
        $financials = $this->getFinancials();
    @endphp

    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div style="padding: 12px 16px; border-radius: 10px; background: #eff6ff; color: #1e40af; font-size: .84rem;">
            صافي الربح تقديري: يستبعد الضريبة وتكلفة المنتجات والخامات المباعة أو المستهلكة، ويعرض مشتريات المخزون منفصلة حتى لا تُخصم قبل بيعها.
        </div>
        
        <!-- Filters Card -->
        <div style="background: #fff; padding: 16px 20px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
            <div style="font-size: .88rem; font-weight: 700; color: #111827;">تصفية بالفترة الزمنية:</div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 6px;">
                    <span style="font-size: .78rem; color: #4b5563;">من تاريخ</span>
                    <input type="date" wire:model.live="from" style="border: 1px solid #d1d5db; border-radius: 8px; padding: 6px 12px; font-size: .82rem; color: #111827; background: #fff;" />
                </div>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <span style="font-size: .78rem; color: #4b5563;">إلى تاريخ</span>
                    <input type="date" wire:model.live="until" style="border: 1px solid #d1d5db; border-radius: 8px; padding: 6px 12px; font-size: .82rem; color: #111827; background: #fff;" />
                </div>
            </div>
        </div>

        <!-- KPI Grid: Revenues, Expenses, Purchases, Net Profit -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
            
            <!-- Play Revenue -->
            <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 16px;">
                <div style="background: #eff6ff; color: #2563eb; border-radius: 10px; padding: 12px; display: flex; align-items: center; justify-content: center;">
                    🎮
                </div>
                <div>
                    <div style="font-size: .78rem; color: #6b7280;">إيرادات المواعيد</div>
                    <div style="font-size: 1.25rem; font-weight: 800; color: #111827; margin-top: 4px;">
                        {{ number_format($financials['appointment_revenue'], 2) }} ج.م
                    </div>
                </div>
            </div>

            <!-- Cafe Revenue -->
            <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 16px;">
                <div style="background: #faf5ff; color: #7c3aed; border-radius: 10px; padding: 12px; display: flex; align-items: center; justify-content: center;">
                    ☕
                </div>
                <div>
                    <div style="font-size: .78rem; color: #6b7280;">مبيعات المنتجات والمبيعات الأخرى</div>
                    <div style="font-size: 1.25rem; font-weight: 800; color: #111827; margin-top: 4px;">
                        {{ number_format($financials['other_sales_revenue'], 2) }} ج.م
                    </div>
                </div>
            </div>

            <!-- Total Revenue (After Discount & Taxes) -->
            <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 16px;">
                <div style="background: #f0fdf4; color: #16a34a; border-radius: 10px; padding: 12px; display: flex; align-items: center; justify-content: center;">
                    💰
                </div>
                <div>
                    <div style="font-size: .78rem; color: #6b7280;">إجمالي الدخل الكلي للفواتير</div>
                    <div style="font-size: 1.25rem; font-weight: 800; color: #16a34a; margin-top: 4px;">
                        {{ number_format($financials['total_revenue'], 2) }} ج.م
                    </div>
                </div>
            </div>

            <!-- Expenses -->
            <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 16px;">
                <div style="background: #fff5f5; color: #dc2626; border-radius: 10px; padding: 12px; display: flex; align-items: center; justify-content: center;">
                    💸
                </div>
                <div>
                    <div style="font-size: .78rem; color: #6b7280;">إجمالي المصاريف التشغيلية</div>
                    <div style="font-size: 1.25rem; font-weight: 800; color: #dc2626; margin-top: 4px;">
                        {{ number_format($financials['expenses'], 2) }} ج.م
                    </div>
                </div>
            </div>

            <!-- Purchases -->
            <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 16px;">
                <div style="background: #fffbeb; color: #d97706; border-radius: 10px; padding: 12px; display: flex; align-items: center; justify-content: center;">
                    🛒
                </div>
                <div>
                    <div style="font-size: .78rem; color: #6b7280;">مشتريات المخزون خلال الفترة</div>
                    <div style="font-size: 1.25rem; font-weight: 800; color: #d97706; margin-top: 4px;">
                        {{ number_format($financials['purchases'], 2) }} ج.م
                    </div>
                </div>
            </div>

            <!-- Cost of Sales -->
            <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 16px;">
                <div style="background: #fff7ed; color: #c2410c; border-radius: 10px; padding: 12px; display: flex; align-items: center; justify-content: center;">
                    🧴
                </div>
                <div>
                    <div style="font-size: .78rem; color: #6b7280;">تكلفة المنتجات والخامات المستهلكة</div>
                    <div style="font-size: 1.25rem; font-weight: 800; color: #c2410c; margin-top: 4px;">
                        {{ number_format($financials['cost_of_sales'], 2) }} ج.م
                    </div>
                </div>
            </div>

            <!-- Net Profit -->
            <div style="background: {{ $financials['net_profit'] >= 0 ? '#f0fdf4' : '#fef2f2' }}; padding: 20px; border-radius: 12px; border: 2px solid {{ $financials['net_profit'] >= 0 ? '#b91c1c' : '#ef4444' }}; border-color: {{ $financials['net_profit'] >= 0 ? '#10b981' : '#ef4444' }}; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 16px;">
                <div style="background: {{ $financials['net_profit'] >= 0 ? '#10b981' : '#ef4444' }}; color: #fff; border-radius: 10px; padding: 12px; display: flex; align-items: center; justify-content: center;">
                    👑
                </div>
                <div>
                    <div style="font-size: .78rem; color: #4b5563;">صافي الربح التشغيلي التقريبي</div>
                    <div style="font-size: 1.4rem; font-weight: 900; color: {{ $financials['net_profit'] >= 0 ? '#065f46' : '#991b1b' }}; margin-top: 4px;">
                        {{ number_format($financials['net_profit'], 2) }} ج.م
                    </div>
                </div>
            </div>

        </div>

        <!-- Breakdown Section: Expenses vs Purchases -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px;">
            
            <!-- Expenses Breakdown Card -->
            <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden;">
                <div style="padding: 16px 20px; font-size: .94rem; font-weight: 700; color: #111827; border-bottom: 1px solid #e5e7eb; background: #fafafa; display: flex; justify-content: space-between;">
                    <span>تفاصيل المصاريف التشغيلية حسب التصنيف</span>
                    <span style="color: #dc2626;">{{ number_format($financials['expenses'], 2) }} ج.م</span>
                </div>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; text-align: right; border-collapse: collapse; font-size: .84rem;">
                        <thead>
                            <tr style="background: #f3f4f6; border-bottom: 1px solid #e5e7eb; color: #374151;">
                                <th style="padding: 12px 20px; font-weight: 700;">التصنيف</th>
                                <th style="padding: 12px 20px; font-weight: 700; text-align: center;">إجمالي المصروف</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($financials['expenses_breakdown'] as $exp)
                                <tr style="border-bottom: 1px solid #e5e7eb; background: #fff;">
                                    <td style="padding: 12px 20px; font-weight: 600; color: #111827;">{{ $exp['category'] }}</td>
                                    <td style="padding: 12px 20px; text-align: center; font-weight: 700; color: #dc2626;">{{ number_format($exp['amount'], 2) }} ج.م</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" style="padding: 24px; text-align: center; color: #6b7280;">لا توجد مصاريف مسجلة في هذه الفترة الزمنية.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Purchases Breakdown Card -->
            <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden;">
                <div style="padding: 16px 20px; font-size: .94rem; font-weight: 700; color: #111827; border-bottom: 1px solid #e5e7eb; background: #fafafa; display: flex; justify-content: space-between;">
                    <span>تفاصيل المشتريات حسب المورد</span>
                    <span style="color: #d97706;">{{ number_format($financials['purchases'], 2) }} ج.م</span>
                </div>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; text-align: right; border-collapse: collapse; font-size: .84rem;">
                        <thead>
                            <tr style="background: #f3f4f6; border-bottom: 1px solid #e5e7eb; color: #374151;">
                                <th style="padding: 12px 20px; font-weight: 700;">المورد</th>
                                <th style="padding: 12px 20px; font-weight: 700; text-align: center;">إجمالي المشتريات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($financials['purchases_breakdown'] as $pur)
                                <tr style="border-bottom: 1px solid #e5e7eb; background: #fff;">
                                    <td style="padding: 12px 20px; font-weight: 600; color: #111827;">{{ $pur['supplier'] }}</td>
                                    <td style="padding: 12px 20px; text-align: center; font-weight: 700; color: #d97706;">{{ number_format($pur['amount'], 2) }} ج.م</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" style="padding: 24px; text-align: center; color: #6b7280;">لا توجد مشتريات مسجلة في هذه الفترة الزمنية.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
</x-filament-panels::page>
