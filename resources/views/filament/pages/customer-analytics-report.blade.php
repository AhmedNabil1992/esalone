<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 24px;">
        
        <!-- Top Section Info Alert -->
        <div style="background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 16px; border-radius: 12px; font-size: .84rem; display: flex; align-items: center; gap: 10px; line-height: 1.5;">
            <span>ℹ️</span>
            <div>
                يساعدك هذا التقرير في فهم تفاعل عملائك وتصنيفهم بناءً على إنفاقهم وترددهم على المحل، بالإضافة إلى التعرف على العملاء الراكدين (المنقطعين عن المجيء منذ أكثر من 14 يوماً) لاستعادتهم بعروض خاصة.
            </div>
        </div>

        <!-- Three Tables Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
            
            <!-- Table 1: Top Spenders -->
            <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; display: flex; flex-direction: column;">
                <div style="padding: 16px 20px; font-size: .94rem; font-weight: 700; color: #111827; border-bottom: 1px solid #e5e7eb; background: #fafafa; display: flex; align-items: center; gap: 8px;">
                    👑 <span>الأكثر إنفاقاً (Top Spenders)</span>
                </div>
                <div style="overflow-x: auto; flex: 1;">
                    <table style="width: 100%; text-align: right; border-collapse: collapse; font-size: .82rem;">
                        <thead>
                            <tr style="background: #f3f4f6; border-bottom: 1px solid #e5e7eb; color: #374151;">
                                <th style="padding: 10px 16px; font-weight: 700;">العميل</th>
                                <th style="padding: 10px 16px; font-weight: 700; text-align: center;">الفئة</th>
                                <th style="padding: 10px 16px; font-weight: 700; text-align: center;">إجمالي المدفوعات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->getTopSpenders() as $index => $c)
                                <tr style="border-bottom: 1px solid #e5e7eb; background: #fff;">
                                    <td style="padding: 10px 16px; font-weight: 600; color: #111827;">
                                        <span style="color:#9ca3af; font-size:.74rem; font-weight:700; margin-left:6px;">#{{ $index + 1 }}</span>
                                        {{ $c['name'] }}
                                        <div style="font-size: .7rem; color: #6b7280; font-weight: normal; margin-top: 2px;">{{ $c['phone'] }}</div>
                                    </td>
                                    <td style="padding: 10px 16px; text-align: center;">
                                        @if($c['level_name'])
                                            <span style="background: #fef3c7; color: #d97706; font-size: .68rem; padding: 2px 8px; border-radius: 6px; font-weight: 700;">
                                                {{ $c['level_name'] }}
                                            </span>
                                        @else
                                            <span style="color: #9ca3af; font-size: .68rem;">—</span>
                                        @endif
                                    </td>
                                    <td style="padding: 10px 16px; text-align: center; font-weight: 700; color: #16a34a;">
                                        {{ number_format($c['total_spent'], 2) }} ج.م
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" style="padding: 24px; text-align: center; color: #6b7280;">لا يوجد عملاء مسجلين لديهم فواتير.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Table 2: Most Frequent -->
            <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; display: flex; flex-direction: column;">
                <div style="padding: 16px 20px; font-size: .94rem; font-weight: 700; color: #111827; border-bottom: 1px solid #e5e7eb; background: #fafafa; display: flex; align-items: center; gap: 8px;">
                    🔥 <span>الأكثر تكراراً وتردداً (Most Frequent)</span>
                </div>
                <div style="overflow-x: auto; flex: 1;">
                    <table style="width: 100%; text-align: right; border-collapse: collapse; font-size: .82rem;">
                        <thead>
                            <tr style="background: #f3f4f6; border-bottom: 1px solid #e5e7eb; color: #374151;">
                                <th style="padding: 10px 16px; font-weight: 700;">العميل</th>
                                <th style="padding: 10px 16px; font-weight: 700; text-align: center;">الفئة</th>
                                <th style="padding: 10px 16px; font-weight: 700; text-align: center;">عدد الزيارات المدفوعة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->getMostFrequent() as $index => $c)
                                <tr style="border-bottom: 1px solid #e5e7eb; background: #fff;">
                                    <td style="padding: 10px 16px; font-weight: 600; color: #111827;">
                                        <span style="color:#9ca3af; font-size:.74rem; font-weight:700; margin-left:6px;">#{{ $index + 1 }}</span>
                                        {{ $c['name'] }}
                                        <div style="font-size: .7rem; color: #6b7280; font-weight: normal; margin-top: 2px;">{{ $c['phone'] }}</div>
                                    </td>
                                    <td style="padding: 10px 16px; text-align: center;">
                                        @if($c['level_name'])
                                            <span style="background: #fef3c7; color: #d97706; font-size: .68rem; padding: 2px 8px; border-radius: 6px; font-weight: 700;">
                                                {{ $c['level_name'] }}
                                            </span>
                                        @else
                                            <span style="color: #9ca3af; font-size: .68rem;">—</span>
                                        @endif
                                    </td>
                                    <td style="padding: 10px 16px; text-align: center; font-weight: 700; color: #2563eb;">
                                        {{ $c['visits_count'] }} زيارة
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" style="padding: 24px; text-align: center; color: #6b7280;">لا يوجد عملاء مسجلين لديهم فواتير.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Table 3: Churn Risk -->
            <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; display: flex; flex-direction: column;">
                <div style="padding: 16px 20px; font-size: .94rem; font-weight: 700; color: #111827; border-bottom: 1px solid #e5e7eb; background: #fafafa; display: flex; align-items: center; gap: 8px;">
                    ⚠️ <span>العملاء الراكدون (Churn Risk > 14 يوم)</span>
                </div>
                <div style="overflow-x: auto; flex: 1;">
                    <table style="width: 100%; text-align: right; border-collapse: collapse; font-size: .82rem;">
                        <thead>
                            <tr style="background: #f3f4f6; border-bottom: 1px solid #e5e7eb; color: #374151;">
                                <th style="padding: 10px 16px; font-weight: 700;">العميل</th>
                                <th style="padding: 10px 16px; font-weight: 700; text-align: center;">آخر زيارة</th>
                                <th style="padding: 10px 16px; font-weight: 700; text-align: center;">منذ كم يوم؟</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->getChurnRiskCustomers() as $c)
                                <tr style="border-bottom: 1px solid #e5e7eb; background: #fff5f5;">
                                    <td style="padding: 10px 16px; font-weight: 600; color: #991b1b;">
                                        {{ $c['name'] }}
                                        <div style="font-size: .7rem; color: #c53030; font-weight: normal; margin-top: 2px;">{{ $c['phone'] }}</div>
                                    </td>
                                    <td style="padding: 10px 16px; text-align: center; color: #4b5563; font-weight: 600;">
                                        {{ $c['last_visit'] }}
                                    </td>
                                    <td style="padding: 10px 16px; text-align: center; font-weight: 800; color: #ef4444;">
                                        {{ $c['days_since_last_visit'] }} يوم
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" style="padding: 24px; text-align: center; color: #6b7280;">لا يوجد عملاء منقطعين بالفترة المحددة.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
</x-filament-panels::page>
