@component('mail::message')
# تقرير غلق الوردية رقم {{ $shift->shift_no }} 📊

تم إغلاق الوردية بنجاح. إليك تفاصيل التقرير المالي للوردية:

### 👤 معلومات الوردية:
* **تاريخ الوردية:** {{ $shift->shift_date->format('Y-m-d') }}
* **افتتح بواسطة:** {{ $shift->opener?->name ?? 'غير معروف' }}
* **أغلق بواسطة:** {{ $shift->closer?->name ?? 'غير معروف' }}
* **بداية الوردية:** {{ $shift->shift_from?->format('Y-m-d h:i A') }}
* **نهاية الوردية:** {{ $shift->shift_to?->format('Y-m-d h:i A') }}

---

### 💵 الملخص المالي وتسوية النقدية:
* **رصيد البداية (الافتتاحي):** {{ number_format($shift->start_amount, 2) }} ج.م
* **الرصيد الدفتري المتوقع:** {{ number_format($expected_cash, 2) }} ج.م
* **الرصيد الفعلي بالدرج:** {{ number_format($shift->actual_amount, 2) }} ج.م
* **الفارق (العجز/الزيادة):**
@if($discrepancy < 0.0)
🔴 **عجز بقيمة: {{ number_format(abs($discrepancy), 2) }} ج.م**
@elseif($discrepancy > 0.0)
🟢 **زيادة بقيمة: {{ number_format($discrepancy, 2) }} ج.م**
@else
✅ **مطابق تماماً (لا توجد فروقات)**
@endif

---

### 📈 تفاصيل الإيرادات والمصروفات:
* **إيرادات اللعب (البلاي ستيشن):** {{ number_format($shift->playstation_amount, 2) }} ج.م
* **إيرادات المبيعات (البوفيه/المتجر):** {{ number_format($shift->sales_amount, 2) }} ج.م
* **مجموع الخدمات المضافة:** {{ number_format($shift->service_amount, 2) }} ج.م
* **مجموع الضرائب المحصلة:** {{ number_format($shift->tax_amount, 2) }} ج.م
* **إجمالي الخصومات الممنوحة:** {{ number_format($shift->discount_amount, 2) }} ج.م
* **المشتريات:** {{ number_format($shift->purchase_amount, 2) }} ج.م
* **المصروفات:** {{ number_format($shift->expenses_amount, 2) }} ج.م
* **الآجل (ديون الفواتير المتبقية):** {{ number_format($shift->credit_amount, 2) }} ج.م

---

### 📝 ملاحظات الكاشير:
{{ $shift->notes ?: 'لا توجد ملاحظات إضافية' }}

مع تحياتنا،  
نظام إدارة الصالات
@endcomponent
