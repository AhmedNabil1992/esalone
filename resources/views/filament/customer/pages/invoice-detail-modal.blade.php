<div class="space-y-6" dir="rtl" style="font-family: 'Cairo', sans-serif; color: #f3f4f6; background-color: #0d1423; padding: 10px; border-radius: 12px;">
    
    <!-- معلومات الفاتورة الأساسية -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; border-bottom: 1px dashed rgba(255, 255, 255, 0.1); padding-bottom: 16px;">
        <div>
            <span style="font-size: 0.85rem; color: #9ca3af; display: block; margin-bottom: 4px;">رقم الفاتورة</span>
            <strong style="font-size: 1.1rem; color: #fff;">{{ $invoice->invoice_no }}</strong>
        </div>
        <div>
            <span style="font-size: 0.85rem; color: #9ca3af; display: block; margin-bottom: 4px;">التاريخ والوقت</span>
            <strong style="font-size: 0.95rem; color: #fff;">{{ $invoice->issued_at ? $invoice->issued_at->format('d/m/Y h:i A') : '-' }}</strong>
        </div>
        <div>
            <span style="font-size: 0.85rem; color: #9ca3af; display: block; margin-bottom: 4px;">نوع الفاتورة</span>
            <strong style="font-size: 0.95rem; color: #fff;">
                {{ $invoice->invoice_type === 'direct_sale' ? 'بيع مباشر' : 'لعب على الأجهزة' }}
            </strong>
        </div>
    </div>

    <!-- تفاصيل ساعات اللعب -->
    @if($invoice->playSessionDetails->count() > 0)
        <div>
            <h3 style="font-size: 1.05rem; font-weight: 700; color: #3b82f6; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <span>🎮</span> تفاصيل ساعات اللعب
            </h3>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: right; font-size: 0.88rem;">
                    <thead>
                        <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.08); color: #9ca3af;">
                            <th style="padding: 10px 8px; font-weight: 700;">الجهاز</th>
                            <th style="padding: 10px 8px; font-weight: 700;">نوع اللعب</th>
                            <th style="padding: 10px 8px; font-weight: 700;">وقت البدء</th>
                            <th style="padding: 10px 8px; font-weight: 700;">وقت النهاية</th>
                            <th style="padding: 10px 8px; font-weight: 700;">المدة (دقائق)</th>
                            <th style="padding: 10px 8px; font-weight: 700; text-align: left;">التكلفة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->playSessionDetails as $play)
                            <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.04); color: #e5e7eb;">
                                <td style="padding: 10px 8px; font-weight: bold; color: #fff;">{{ $play->device?->name ?? $invoice->device?->name ?? 'جهاز رئيسي' }}</td>
                                <td style="padding: 10px 8px;">{{ $play->play_type }}</td>
                                <td style="padding: 10px 8px; font-size: 0.8rem; color: #9ca3af;">{{ $play->start_time ? $play->start_time->format('h:i A') : '-' }}</td>
                                <td style="padding: 10px 8px; font-size: 0.8rem; color: #9ca3af;">{{ $play->actual_end_time ? $play->actual_end_time->format('h:i A') : '-' }}</td>
                                <td style="padding: 10px 8px; font-weight: 700;">{{ $play->play_time }} دقيقة</td>
                                <td style="padding: 10px 8px; text-align: left; font-weight: bold; color: #10b981;">{{ number_format($play->cost, 2) }} ج.م</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- تفاصيل الماركت والطلبات -->
    @if($invoice->marketHistories->count() > 0)
        <div style="margin-top: 10px;">
            <h3 style="font-size: 1.05rem; font-weight: 700; color: #10b981; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <span>🍹</span> تفاصيل طلبات الماركت
            </h3>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: right; font-size: 0.88rem;">
                    <thead>
                        <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.08); color: #9ca3af;">
                            <th style="padding: 10px 8px; font-weight: 700;">المنتج</th>
                            <th style="padding: 10px 8px; font-weight: 700; text-align: center;">الكمية</th>
                            <th style="padding: 10px 8px; font-weight: 700; text-align: center;">سعر الوحدة</th>
                            <th style="padding: 10px 8px; font-weight: 700; text-align: left;">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->marketHistories as $order)
                            <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.04); color: #e5e7eb;">
                                <td style="padding: 10px 8px; font-weight: bold; color: #fff;">{{ $order->catalogItem?->name ?? $order->item?->name ?? ($order->subject_name !== $invoice->device?->name ? $order->subject_name : 'منتج ماركت') }}</td>
                                <td style="padding: 10px 8px; text-align: center; font-weight: 700; color: #f59e0b;">{{ $order->quantity }}</td>
                                <td style="padding: 10px 8px; text-align: center;">{{ number_format($order->unit_price, 2) }} ج.م</td>
                                <td style="padding: 10px 8px; text-align: left; font-weight: bold; color: #10b981;">{{ number_format($order->total_amount, 2) }} ج.م</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- الحساب المالي للفاتورة -->
    <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.06); border-radius: 12px; padding: 18px; margin-top: 10px;">
        <h4 style="font-size: 0.95rem; font-weight: 800; color: #fff; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 8px; margin-bottom: 14px;">
            الملخص المالي للفاتورة
        </h4>
        
        <div style="display: flex; flex-direction: column; gap: 8px; font-size: 0.85rem;">
            @if($invoice->play_amount > 0)
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #9ca3af;">إجمالي اللعب:</span>
                    <span>{{ number_format($invoice->play_amount, 2) }} ج.م</span>
                </div>
            @endif
            @if($invoice->order_amount > 0)
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #9ca3af;">إجمالي الماركت:</span>
                    <span>{{ number_format($invoice->order_amount, 2) }} ج.م</span>
                </div>
            @endif
            @if($invoice->discount_amount > 0)
                <div style="display: flex; justify-content: space-between; color: #ef4444;">
                    <span>الخصومات والعروض:</span>
                    <span>-{{ number_format($invoice->discount_amount, 2) }} ج.م</span>
                </div>
            @endif
            @if($invoice->service_amount > 0)
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #9ca3af;">رسوم الخدمة:</span>
                    <span>+{{ number_format($invoice->service_amount, 2) }} ج.م</span>
                </div>
            @endif
            
            <div style="display: flex; justify-content: space-between; font-size: 1.15rem; font-weight: 900; color: #fff; border-top: 1px dashed rgba(255, 255, 255, 0.1); padding-top: 10px; margin-top: 4px;">
                <span>المبلغ الإجمالي (الصافي):</span>
                <span style="color: #10b981;">{{ number_format($invoice->final_amount, 2) }} ج.م</span>
            </div>

            <div style="display: flex; justify-content: space-between; font-weight: 700; color: #9ca3af; margin-top: 6px;">
                <span>المبلغ المدفوع:</span>
                <span style="color: #fff;">{{ number_format($invoice->paid_amount, 2) }} ج.م</span>
            </div>

            @if($invoice->remain_amount > 0)
                <div style="display: flex; justify-content: space-between; font-weight: 700; color: #ef4444;">
                    <span>المبلغ المتبقي (آجل):</span>
                    <span>{{ number_format($invoice->remain_amount, 2) }} ج.م</span>
                </div>
            @endif
        </div>
    </div>
</div>
