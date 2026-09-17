                        @php
                            $closePreview = $this->busyClosePreview;
                            $timeRows = collect($closePreview['time_rows'] ?? []);
                            $orderRows = collect($closePreview['order_rows'] ?? []);
                        @endphp

                        @if (! $closePreview)
                            <div style="padding:10px;border-radius:10px;text-align:center;font-size:.82rem;" class="border border-dashed border-slate-300 dark:!border-zinc-700 text-slate-500 dark:!text-zinc-400">
                                تعذر تحميل بيانات التقفيل حالياً. حاول مرة أخرى.
                            </div>
                            <div style="display:flex;justify-content:flex-end;margin-top:10px;">
                                <button type="button" wire:click="closeBusyActionDetailsModal" style="padding:8px 12px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;" class="bg-white dark:!bg-zinc-950 border border-slate-300 dark:!border-zinc-700 text-slate-900 dark:!text-zinc-100">
                                    إغلاق
                                </button>
                            </div>
                        @else
                            <div class="device-control-close-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:12px;align-items:start;">
                                <div style="border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                                    <div style="padding:10px 12px;background:#f0fdf4;color:#15803d;font-size:.82rem;font-weight:800;">
                                        تفاصيل الوقت
                                    </div>
                                    <div class="device-control-table-scroll" style="max-height:320px;overflow:auto;">
                                        <table style="width:100%;border-collapse:collapse;font-size:.76rem;">
                                            <thead style="background:#f9fafb;color:#374151;">
                                                <tr>
                                                    <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:right;">نوع الجهاز</th>
                                                    <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:right;">اسم الجهاز</th>
                                                    <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:right;">نوع اللعب</th>
                                                    <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;">الفئة</th>
                                                    <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;">البداية</th>
                                                    <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;">النهاية</th>
                                                    <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;">السعر</th>
                                                    <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;">المدة</th>
                                                    <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;">القيمة</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($timeRows as $row)
                                                    <tr>
                                                        <td style="padding:8px;border-bottom:1px solid #f3f4f6;color:#111827;">{{ $row['device_type'] ?: '—' }}</td>
                                                        <td style="padding:8px;border-bottom:1px solid #f3f4f6;color:#111827;">{{ $row['device_name'] ?: '—' }}</td>
                                                        <td style="padding:8px;border-bottom:1px solid #f3f4f6;color:#111827;">{{ $row['play_type'] ?: '—' }}</td>
                                                        <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;color:#374151;">{{ $row['play_category'] ?: '—' }}</td>
                                                        <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;direction:ltr;unicode-bidi:isolate;color:#374151;">{{ isset($row['start_time']) && $row['start_time'] ? app(\App\Support\Tenant\TenantClock::class)->utcToLocal($row['start_time'])->format('h:i A') : '—' }}</td>
                                                        <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;direction:ltr;unicode-bidi:isolate;color:#374151;">{{ isset($row['end_time']) && $row['end_time'] ? app(\App\Support\Tenant\TenantClock::class)->utcToLocal($row['end_time'])->format('h:i A') : '—' }}</td>
                                                        <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;color:#374151;direction:ltr;unicode-bidi:isolate;">{{ number_format((float) ($row['play_price'] ?? 0), 2) }}</td>
                                                        <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;color:#111827;">{{ ($row['period'] ?? 0) > 0 ? ($row['period'] . ' د') : 'مفتوح' }}</td>
                                                        <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;color:#16a34a;font-weight:700;direction:ltr;unicode-bidi:isolate;">{{ number_format((float) ($row['cost'] ?? 0), 2) }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="9" style="padding:16px;text-align:center;color:#6b7280;">لا توجد تفاصيل وقت.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div style="border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                                    <div style="padding:10px 12px;background:#eff6ff;color:#1d4ed8;font-size:.82rem;font-weight:800;">
                                        تفاصيل الطلبات
                                    </div>
                                    <div class="device-control-table-scroll" style="max-height:320px;overflow:auto;">
                                        <table style="width:100%;border-collapse:collapse;font-size:.76rem;">
                                            <thead style="background:#f9fafb;color:#374151;">
                                                <tr>
                                                    <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:right;">الصنف</th>
                                                    <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;">الكمية</th>
                                                    <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;">السعر</th>
                                                    <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;">الإجمالي</th>
                                                    <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;">الحالة</th>
                                                    @if ($this->canUpdateDeviceControlOrders || $this->canDeleteDeviceControlOrders)
                                                        <th style="padding:8px;border-bottom:1px solid #e5e7eb;text-align:center;">الإجراءات</th>
                                                    @endif
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($orderRows as $row)
                                                    <tr>
                                                        <td style="padding:8px;border-bottom:1px solid #f3f4f6;color:#111827;">{{ $row['item_name'] ?? '—' }}</td>
                                                        <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;color:#374151;">
                                                            @if ($this->canUpdateDeviceControlOrders)
                                                                <div style="display:inline-flex;align-items:center;gap:6px;direction:ltr;">
                                                                    <button type="button" wire:click="updateBusySessionOrderQuantity({{ $row['id'] }}, {{ max(1, (int) ($row['quantity'] ?? 0) - 1) }})" @disabled((int) ($row['quantity'] ?? 0) <= 1) aria-label="تقليل الكمية" style="width:28px;height:28px;border:1px solid #d1d5db;border-radius:8px;background:#fff;cursor:pointer;">-</button>
                                                                    <strong>{{ (int) ($row['quantity'] ?? 0) }}</strong>
                                                                    <button type="button" wire:click="updateBusySessionOrderQuantity({{ $row['id'] }}, {{ (int) ($row['quantity'] ?? 0) + 1 }})" aria-label="زيادة الكمية" style="width:28px;height:28px;border:1px solid #d1d5db;border-radius:8px;background:#fff;cursor:pointer;">+</button>
                                                                </div>
                                                            @else
                                                                {{ $row['quantity'] ?? 0 }}
                                                            @endif
                                                        </td>
                                                        <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;color:#374151;direction:ltr;unicode-bidi:isolate;">{{ number_format((float) ($row['price'] ?? 0), 2) }}</td>
                                                        <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;color:#16a34a;font-weight:700;direction:ltr;unicode-bidi:isolate;">{{ number_format((float) ($row['total'] ?? 0), 2) }}</td>
                                                        <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;color:#6b7280;">{{ $row['status'] ?? '—' }}</td>
                                                        @if ($this->canUpdateDeviceControlOrders || $this->canDeleteDeviceControlOrders)
                                                            <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;">
                                                                @if ($this->canDeleteDeviceControlOrders)
                                                                    <button type="button" wire:click="deleteBusySessionOrder({{ $row['id'] }})" wire:confirm="هل أنت متأكد من حذف هذا الطلب؟" style="border:1px solid #fecaca;background:#fef2f2;color:#dc2626;padding:6px 9px;border-radius:8px;cursor:pointer;font-weight:700;">حذف</button>
                                                                @endif
                                                            </td>
                                                        @endif
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="{{ ($this->canUpdateDeviceControlOrders || $this->canDeleteDeviceControlOrders) ? 6 : 5 }}" style="padding:16px;text-align:center;color:#6b7280;">لا توجد طلبات على الجلسة.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div style="margin-top:14px;border:1px solid #e5e7eb;border-radius:12px;padding:12px;display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;">
                                <div style="font-size:.78rem;color:#374151;grid-column:1/-1;">
                                    الخزينة المستهدفة: <strong style="color:{{ data_get($closePreview, 'cash_box.name') ? '#111827' : '#dc2626' }};">{{ data_get($closePreview, 'cash_box.name', 'غير محددة') }}</strong>
                                </div>
                                <div style="font-size:.78rem;color:#374151;">وقت اللعب: <strong>{{ number_format((float) ($closePreview['play_amount'] ?? 0), 2) }}</strong></div>
                                <div style="font-size:.78rem;color:#374151;">الطلبات: <strong>{{ number_format((float) ($closePreview['order_amount'] ?? 0), 2) }}</strong></div>
                                <div style="font-size:.78rem;color:#374151;">الإجمالي: <strong>{{ number_format((float) ($closePreview['subtotal'] ?? 0), 2) }}</strong></div>
                                <div style="font-size:.78rem;color:#374151;">الخدمة: <strong>{{ number_format((float) ($closePreview['service_amount'] ?? 0), 2) }}</strong></div>
                                <div style="font-size:.78rem;color:#374151;">الضريبة: <strong>{{ number_format((float) ($closePreview['tax_amount'] ?? 0), 2) }}</strong></div>
                                <div style="font-size:.78rem;color:#374151;">المقدم: <strong>{{ number_format((float) ($closePreview['deposit_amount'] ?? 0), 2) }}</strong></div>
                                @if ((float) ($closePreview['discount_amount'] ?? 0) > 0)
                                    <div style="font-size:.78rem;color:#ef4444;font-weight:800;">الخصم المطبق: <strong>{{ number_format((float) ($closePreview['discount_amount'] ?? 0), 2) }}</strong></div>
                                @endif
                            </div>

                            @if (data_get($closePreview, 'limit_time.applied'))
                                <div style="margin-top:10px;border:1px solid #f59e0b;background:#fffbeb;border-radius:12px;padding:10px 12px;color:#92400e;font-size:.78rem;line-height:1.6;">
                                    تم تطبيق الحد الأدنى لوقت الجهاز تلقائياً عند التقفيل.
                                    <br>
                                    الوقت المنقضي من بداية الجلسة: <strong>{{ (int) data_get($closePreview, 'limit_time.elapsed_from_start', 0) }} د</strong>
                                    — الحد الأدنى المضبوط: <strong>{{ (int) data_get($closePreview, 'limit_time.configured_minutes', 0) }} د</strong>.
                                </div>
                            @endif

                            <div class="device-control-form-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;margin-top:12px;">
                                <div>
                                    <label style="display:block;font-size:.74rem;margin-bottom:4px;" class="text-slate-700 dark:!text-zinc-300">قيمة الخصم</label>
                                    <input type="number" min="0" max="999" step="0.01" wire:model.live="busyActionDiscountAmount" style="width:100%;border-radius:10px;padding:8px 10px;font-size:.84rem;" class="bg-white dark:!bg-zinc-950 border border-slate-300 dark:!border-zinc-700 text-slate-900 dark:!text-zinc-100 placeholder-slate-400 dark:!placeholder-zinc-500" />
                                </div>
                                <div>
                                    <label style="display:block;font-size:.74rem;margin-bottom:4px;" class="text-slate-700 dark:!text-zinc-300">سبب الخصم (إجباري عند وجود خصم)</label>
                                    <input type="text" wire:model.live="busyActionDiscountReason" placeholder="اكتب سبب الخصم" style="width:100%;border-radius:10px;padding:8px 10px;font-size:.84rem;" class="bg-white dark:!bg-zinc-950 border border-slate-300 dark:!border-zinc-700 text-slate-900 dark:!text-zinc-100 placeholder-slate-400 dark:!placeholder-zinc-500" />
                                </div>
                                <div style="grid-column:1/-1;display:grid;gap:6px;">
                                    <label style="display:block;font-size:.74rem;" class="text-slate-700 dark:!text-zinc-300">تعيين عميل (لازم لو فيه متبقي على الحساب)</label>

                                    <div x-data="{ open: false }" style="position:relative;">
                                        <button
                                            type="button"
                                            @click="open = !open"
                                            style="width:100%;border-radius:10px;padding:8px 10px;font-size:.84rem;display:flex;align-items:center;justify-content:space-between;gap:8px;cursor:pointer;"
                                            class="bg-white dark:!bg-zinc-950 border border-slate-300 dark:!border-zinc-700 text-slate-900 dark:!text-zinc-100"
                                        >
                                            <span style="overflow:hidden;white-space:nowrap;text-overflow:ellipsis;display:flex;align-items:center;gap:6px;" class="text-slate-900 dark:!text-zinc-100">
                                                @if ($this->busySelectedCustomer)
                                                    <span>{{ $this->busySelectedCustomer->name }} - {{ $this->busySelectedCustomer->phone }}</span>
                                                    <span style="font-weight: bold; font-size: .78rem;" class="{{ $this->busySelectedCustomer->balance < 0 ? 'text-rose-600 dark:!text-rose-400' : 'text-emerald-600 dark:!text-emerald-400' }}">
                                                        (الرصيد: {{ number_format($this->busySelectedCustomer->balance, 2) }} ج.م)
                                                    </span>
                                                @else
                                                    بدون عميل
                                                @endif
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
                                                wire:model.live.debounce.300ms="busyActionCustomerSearch"
                                                placeholder="ابحث بالاسم أو رقم الهاتف"
                                                style="width:100%;border-radius:8px;padding:7px 9px;font-size:.82rem;"
                                                class="bg-white dark:!bg-zinc-950 border border-slate-300 dark:!border-zinc-700 text-slate-900 dark:!text-zinc-100 placeholder-slate-400 dark:!placeholder-zinc-500"
                                            />

                                            <button
                                                type="button"
                                                wire:click="clearBusyActionCustomer"
                                                @click="open = false"
                                                style="text-align:right;border-radius:8px;padding:7px 9px;font-size:.8rem;cursor:pointer;"
                                                class="bg-slate-50 dark:!bg-zinc-950 border border-dashed border-slate-300 dark:!border-zinc-700 text-slate-700 dark:!text-zinc-300"
                                            >
                                                بدون عميل
                                            </button>

                                            <div style="max-height:180px;overflow:auto;display:grid;gap:6px;">
                                                @forelse ($this->busyFilteredCustomers as $customer)
                                                    <button
                                                        type="button"
                                                        wire:click="selectBusyActionCustomer({{ $customer->id }})"
                                                        @click="open = false"
                                                        style="text-align:right;border-radius:8px;padding:7px 9px;font-size:.8rem;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:8px;width:100%;"
                                                        class="bg-white dark:!bg-zinc-950 border border-slate-200 dark:!border-zinc-800 text-slate-900 dark:!text-zinc-100 hover:bg-emerald-50 dark:hover:!bg-emerald-950/40"
                                                    >
                                                        <span>{{ $customer->name }} - {{ $customer->phone }}</span>
                                                        <span style="font-weight: bold; font-size: .78rem;" class="{{ $customer->balance < 0 ? 'text-rose-600 dark:!text-rose-400' : 'text-emerald-600 dark:!text-emerald-400' }}">
                                                            (الرصيد: {{ number_format($customer->balance, 2) }} ج.م)
                                                        </span>
                                                    </button>
                                                @empty
                                                    <div style="text-align:center;padding:8px;font-size:.78rem;" class="text-slate-500 dark:!text-zinc-400">لا يوجد عميل مطابق للبحث</div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div style="grid-column:1/-1;border-radius:12px;padding:10px;" class="border border-slate-200 dark:!border-zinc-800 bg-white dark:!bg-zinc-950">
                                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:8px;">
                                        <div style="font-size:.76rem;font-weight:800;" class="text-slate-700 dark:!text-zinc-300">تفاصيل الدفع (يمكن التقسيم على أكثر من خزينة)</div>
                                        <button type="button" wire:click="addBusyActionPaymentRow" style="padding:4px 8px;border-radius:8px;font-size:.72rem;font-weight:700;cursor:pointer;" class="border border-blue-600 bg-blue-50 dark:!bg-blue-950/40 text-blue-700 dark:!text-blue-300">
                                            + إضافة دفعة
                                        </button>
                                    </div>

                                    <div style="display:grid;gap:8px;">
                                        @foreach ($busyActionPayments as $index => $payment)
                                            <div class="device-control-payment-row" style="display:grid;grid-template-columns:1fr 1fr auto;gap:8px;align-items:end;">
                                                <div>
                                                    <label style="display:block;font-size:.7rem;margin-bottom:3px;" class="text-slate-500 dark:!text-zinc-400">الخزينة</label>
                                                    <select wire:model.live="busyActionPayments.{{ $index }}.cash_box_id" style="width:100%;border-radius:10px;padding:8px 10px;font-size:.8rem;" class="bg-white dark:!bg-zinc-950 border border-slate-300 dark:!border-zinc-700 text-slate-900 dark:!text-zinc-100">
                                                        <option value="">اختر خزينة</option>
                                                        @foreach ($this->busyPaymentCashBoxes as $cashBox)
                                                            <option value="{{ $cashBox->id }}">{{ $cashBox->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div>
                                                    <label style="display:block;font-size:.7rem;margin-bottom:3px;" class="text-slate-500 dark:!text-zinc-400">المبلغ</label>
                                                    <input type="number" min="0" step="0.01" wire:model.live.debounce.250ms="busyActionPayments.{{ $index }}.amount" style="width:100%;border-radius:10px;padding:8px 10px;font-size:.8rem;" class="bg-white dark:!bg-zinc-950 border border-slate-300 dark:!border-zinc-700 text-slate-900 dark:!text-zinc-100 placeholder-slate-400 dark:!placeholder-zinc-500" />
                                                </div>

                                                <button type="button" wire:click="removeBusyActionPaymentRow({{ $index }})" style="padding:8px 10px;border-radius:10px;font-size:.72rem;font-weight:700;cursor:pointer;" class="border border-rose-300 dark:!border-rose-800 bg-rose-50 dark:!bg-rose-950/40 text-rose-700 dark:!text-rose-300">
                                                    حذف
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div style="margin-top:8px;font-size:.78rem;font-weight:800;" class="text-slate-900 dark:!text-zinc-100">
                                        إجمالي المدفوع: <span style="direction:ltr;unicode-bidi:isolate;" class="text-blue-600 dark:!text-blue-400">{{ number_format((float) $this->busyActionPaidTotal, 2) }}</span>
                                    </div>

                                    @if ($this->busySelectedCustomer && (float) ($closePreview['remain_amount'] ?? 0) > 0)
                                        <label style="margin-top:10px;display:flex;align-items:center;gap:8px;font-size:.78rem;font-weight:700;cursor:pointer;" class="text-slate-700 dark:!text-zinc-300">
                                            <input type="checkbox" wire:model.live="busyCloseDepositRemainderToCustomerBalance" style="width:16px;height:16px;accent-color:#16a34a;cursor:pointer;" />
                                            <span>إضافة الباقي إلى رصيد العميل بدل إرجاعه نقداً</span>
                                        </label>
                                    @endif

                                    <div style="margin-top:10px;">
                                        <label style="display:block;font-size:.7rem;margin-bottom:3px;" class="text-slate-500 dark:!text-zinc-400">رقم الواتساب لإرسال الفاتورة (اختياري)</label>
                                        <input
                                            type="text"
                                            wire:model="busyActionWhatsappPhone"
                                            placeholder="مثال: 01012345678"
                                            style="width:100%;border-radius:10px;padding:8px 10px;font-size:.8rem;"
                                            class="bg-white dark:!bg-zinc-950 border border-slate-300 dark:!border-zinc-700 text-slate-900 dark:!text-zinc-100 placeholder-slate-400 dark:!placeholder-zinc-500"
                                        />
                                    </div>
                                </div>
                                <div style="display:flex;align-items:flex-end;">
                                    <div style="font-size:.86rem;font-weight:800;color:#111827;">
                                        الصافي: <span style="color:#2563eb;direction:ltr;unicode-bidi:isolate;">{{ number_format((float) ($closePreview['final_amount'] ?? 0), 2) }}</span>
                                        <br>
                                        المتبقي: <span style="color:{{ (float) ($closePreview['remain_amount'] ?? 0) < 0 ? '#dc2626' : '#16a34a' }};direction:ltr;unicode-bidi:isolate;">{{ number_format((float) ($closePreview['remain_amount'] ?? 0), 2) }}</span>
                                    </div>
                                </div>
                            </div>

                            @php
                                $canCancelOrder = $this->canCancelBusySessionOrder;
                                $cancelOrderWindow = $this->cancelOrderAllowedMinutes;
                                $cancelOrderRemaining = $this->busyCancelOrderRemainingMinutes;
                            @endphp

                            <div class="device-control-modal-actions" style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
                                <button type="button" wire:click="closeBusyActionDetailsModal" style="border:1px solid #d1d5db;background:#fff;color:#111827;padding:8px 14px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">إلغاء</button>
                                @if ($canCancelOrder)
                                    <button type="button" wire:click="cancelBusySessionOrder" style="border:1px solid #dc2626;background:#dc2626;color:#fff;padding:8px 14px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                        إلغاء الأوردر
                                    </button>
                                @endif
                                <button type="button" wire:click="closeBusySessionInvoice" style="border:1px solid #16a34a;background:#16a34a;color:#fff;padding:8px 14px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">تقفيل الحساب</button>
                            </div>

                            @if (! $canCancelOrder && $cancelOrderWindow > 0)
                                <div style="margin-top:8px;font-size:.74rem;color:#b45309;">
                                    تم تجاوز وقت السماح لإلغاء الأوردر. وقت السماح: {{ $cancelOrderWindow }} دقيقة.
                                </div>
                            @elseif ($canCancelOrder && $cancelOrderRemaining !== null)
                                <div style="margin-top:8px;font-size:.74rem;color:#065f46;">
                                    متاح إلغاء الأوردر خلال {{ $cancelOrderRemaining }} دقيقة متبقية.
                                </div>
                            @endif

                            @if ($lastCloseInvoiceError)
                                <div class="device-control-close-error" style="margin-top:16px;padding:12px;border:1px solid #fca5a5;background:#fef2f2;border-radius:12px;display:flex;gap:10px;align-items:flex-start;">
                                    <svg style="width:18px;height:18px;color:#dc2626;flex-shrink:0;margin-top:2px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <div style="flex:1;">
                                        <div style="font-size:.82rem;font-weight:700;color:#dc2626;">
                                            ❌ فشل تقفيل الجهاز: <strong>{{ $lastCloseInvoiceDeviceName }}</strong>
                                        </div>
                                        <div style="font-size:.78rem;color:#991b1b;margin-top:4px;line-height:1.5;">
                                            {{ $lastCloseInvoiceError }}
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endif
