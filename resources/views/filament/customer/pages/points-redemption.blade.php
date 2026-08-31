<x-filament-panels::page>
    <div class="space-y-6" dir="rtl" style="font-family: 'Cairo', sans-serif;">
        <!-- كارت الملخص والتحويل -->
        <div style="background: linear-gradient(135deg, #064e3b 0%, #022c22 100%); border: 1px solid #10b981; border-radius: 16px; padding: 24px; color: #fff;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                <div>
                    <h2 style="font-size: 1.4rem; font-weight: 800; color: #34d399; margin-bottom: 6px;">
                        ⭐ نقاط الولاء والمكافآت
                    </h2>
                    <p style="font-size: 0.95rem; color: #a7f3d0;">
                        يمكنك استبدال نقاطك المجمعة وتحويلها فوراً إلى رصيد مالي يضاف لاستخدامك في اللعب والطلبات.
                    </p>
                </div>
                <div style="text-align: center; background: rgba(0, 0, 0, 0.3); padding: 12px 24px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1);">
                    <span style="font-size: 0.85rem; color: #a7f3d0; display: block;">رصيد نقاطك الحالية</span>
                    <strong style="font-size: 2rem; color: #fbbf24;">{{ number_format((float) ($this->customer?->points ?? 0), 0) }}</strong>
                    <span style="font-size: 0.8rem; color: #9ca3af; display: block; margin-top: 2px;">نقطة</span>
                </div>
            </div>

            <div style="margin-top: 20px; border-top: 1px dashed rgba(255, 255, 255, 0.15); padding-top: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <div style="font-size: 0.9rem; color: #d1fae5;">
                    <span>💡 معدل التحويل الحالي: </span>
                    <strong style="color: #fff;">{{ $this->pointsPerCurrency }} نقاط = 1.00 ج.م رصيد</strong>
                </div>

                <div>
                    {{ ($this->getHeaderActions()[0]) }}
                </div>
            </div>
        </div>

        <!-- جدول أو معلومات توضيحية -->
        <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 14px; padding: 20px; color: #e5e7eb;">
            <h3 style="font-size: 1.05rem; font-weight: 700; color: #fff; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <span>ℹ️</span> كيف تعمل نقاط الولاء؟
            </h3>
            <ul style="list-style: disc; padding-right: 20px; font-size: 0.9rem; color: #9ca3af; space-y: 8px;">
                <li>تحصل على نقاط ولاء تلقائية مع كل فاتورة لعب أو مشتريات تسجل باسمك.</li>
                <li>يمكنك تحويل أي كمية من نقاطك طالما تملك رصيد نقاط متاح.</li>
                <li>يتحول المبلغ الناتج فوراً إلى رصيد حسابك المالي المتاح للاستخدام دون تاريخ انتهاء.</li>
            </ul>
        </div>
    </div>
</x-filament-panels::page>
