<div dir="rtl" style="--line:#e5e7eb;--ink:#111827;--muted:#6b7280;--brand:#16a34a;--brand-soft:#f0fdf4;--card:#ffffff;--bg:#f8fafc;padding:14px;background:var(--bg);border-radius:14px;">
    <div style="display:grid;grid-template-columns:1fr;gap:12px;">
        <div style="display:grid;grid-template-columns:minmax(0,2.1fr) minmax(320px,1fr);gap:12px;align-items:start;">
            <section style="background:var(--card);border:1px solid var(--line);border-radius:14px;padding:12px;box-shadow:0 6px 18px rgba(17,24,39,.04);">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:10px;">
                    <div>
                        <div style="font-size:.78rem;color:var(--muted);font-weight:700;">المبيعات المباشرة</div>
                        <div style="font-size:1rem;color:var(--ink);font-weight:900;">اختيار الأصناف</div>
                    </div>
                    {{-- <div style="font-size:.72rem;background:var(--brand-soft);color:#15803d;padding:6px 10px;border-radius:999px;font-weight:800;">
                        نفس نمط شاشة إضافة الطلبات
                    </div> --}}
                </div>

                <div style="margin-bottom:10px;">
                    <div style="font-size:.8rem;font-weight:800;color:#374151;margin-bottom:6px;">مجموعات الأصناف</div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(98px,1fr));gap:8px;">
                        @forelse ($this->orderGroups as $group)
                            <button
                                type="button"
                                wire:click="selectOrderGroup({{ $group->id }})"
                                style="border:1px solid {{ $inventory_group_id === $group->id ? '#16a34a' : '#d1d5db' }};background:{{ $inventory_group_id === $group->id ? '#f0fdf4' : '#fff' }};border-radius:12px;padding:8px;display:flex;flex-direction:column;align-items:center;gap:6px;cursor:pointer;transition:.15s;"
                            >
                                <div style="width:54px;height:54px;border-radius:10px;overflow:hidden;background:#f3f4f6;display:flex;align-items:center;justify-content:center;">
                                    @if ($group->picture)
                                        <img src="{{ tenant_asset($group->picture) }}" alt="{{ $group->name }}" style="width:54px;height:54px;object-fit:contain;display:block;" />
                                    @else
                                        <svg style="width:22px;height:22px;color:#9ca3af;" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5A1.5 1.5 0 014.5 6h15A1.5 1.5 0 0121 7.5v9A1.5 1.5 0 0119.5 18h-15A1.5 1.5 0 013 16.5v-9z"/>
                                        </svg>
                                    @endif
                                </div>
                                <div style="font-size:.72rem;font-weight:800;color:#111827;text-align:center;line-height:1.2;word-break:break-word;">{{ $group->name }}</div>
                            </button>
                        @empty
                            <div style="grid-column:1/-1;text-align:center;color:#6b7280;font-size:.8rem;padding:10px;border:1px dashed #d1d5db;border-radius:10px;">لا توجد مجموعات أصناف مفعلة</div>
                        @endforelse
                    </div>
                </div>

                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;">
                    <div style="font-size:.8rem;font-weight:800;color:#374151;">الأصناف</div>
                    <input
                        type="text"
                        wire:model.live.debounce.250ms="item_search"
                        placeholder="ابحث عن صنف..."
                        style="width:min(260px,100%);border:1px solid #d1d5db;border-radius:10px;padding:7px 10px;font-size:.78rem;"
                    />
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(128px,1fr));gap:8px;max-height:450px;overflow:auto;padding-inline-end:2px;">
                    @forelse ($this->orderItems as $item)
                        <button
                            type="button"
                            wire:click="addItem({{ $item->id }})"
                            style="border:1px solid #d1d5db;background:#fff;border-radius:12px;padding:8px;display:flex;flex-direction:column;align-items:center;gap:6px;cursor:pointer;text-align:center;transition:.15s;"
                            onmouseover="this.style.borderColor='#16a34a';this.style.background='#f0fdf4'"
                            onmouseout="this.style.borderColor='#d1d5db';this.style.background='#fff'"
                        >
                            <div style="width:58px;height:58px;border-radius:10px;overflow:hidden;background:#f3f4f6;display:flex;align-items:center;justify-content:center;">
                                @if ($item->picture)
                                    <img src="{{ tenant_asset($item->picture) }}" alt="{{ $item->name }}" style="width:58px;height:58px;object-fit:contain;display:block;" />
                                @else
                                    <svg style="width:22px;height:22px;color:#9ca3af;" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.11 1.5 2.076v8.662a2.25 2.25 0 01-2.25 2.25H4.5a2.25 2.25 0 01-2.25-2.25v-8.662c0-.966.616-1.792 1.5-2.076"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v10.5m0 0l3-3m-3 3l-3-3"/>
                                    </svg>
                                @endif
                            </div>
                            <div style="font-size:.74rem;font-weight:800;color:#111827;line-height:1.2;word-break:break-word;">{{ $item->name }}</div>
                            <div style="font-size:.74rem;font-weight:900;color:#16a34a;direction:ltr;unicode-bidi:isolate;">{{ number_format((float) ($item->sale_price ?? 0), 2) }}</div>
                        </button>
                    @empty
                        <div style="grid-column:1/-1;text-align:center;color:#6b7280;font-size:.8rem;padding:12px;border:1px dashed #d1d5db;border-radius:10px;">لا توجد أصناف مطابقة</div>
                    @endforelse
                </div>
            </section>

            <aside style="background:var(--card);border:1px solid var(--line);border-radius:14px;padding:12px;position:sticky;top:12px;box-shadow:0 6px 18px rgba(17,24,39,.04);">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:8px;">
                    <div style="font-size:.95rem;font-weight:900;color:#111827;">سلة البيع</div>
                    @if (! empty($this->selectedItems))
                        <button type="button" wire:click="clearCart" style="border:1px solid #fecaca;background:#fff1f2;color:#be123c;padding:5px 9px;border-radius:9px;font-size:.72rem;font-weight:800;cursor:pointer;">تفريغ</button>
                    @endif
                </div>

                <div style="max-height:300px;overflow:auto;border:1px solid #e5e7eb;border-radius:10px;">
                    <table style="width:100%;border-collapse:collapse;font-size:.76rem;">
                        <thead style="background:#f9fafb;color:#374151;position:sticky;top:0;">
                            <tr>
                                <th style="padding:8px;text-align:right;border-bottom:1px solid #e5e7eb;">الصنف</th>
                                <th style="padding:8px;text-align:center;border-bottom:1px solid #e5e7eb;">الكمية</th>
                                <th style="padding:8px;text-align:center;border-bottom:1px solid #e5e7eb;">الإجمالي</th>
                                <th style="padding:8px;border-bottom:1px solid #e5e7eb;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->selectedItemRows as $row)
                                <tr>
                                    <td style="padding:8px;border-bottom:1px solid #f3f4f6;vertical-align:top;">
                                        <div style="font-weight:800;color:#111827;">{{ $row['name'] }}</div>
                                        <div style="font-size:.7rem;color:#6b7280;direction:ltr;unicode-bidi:isolate;">{{ number_format($row['unit_price'], 2) }}</div>
                                    </td>
                                    <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;">
                                        <div style="display:inline-flex;align-items:center;gap:4px;border:1px solid #d1d5db;border-radius:8px;padding:2px;">
                                            <button type="button" wire:click="decrementItem('{{ $row['key'] }}')" style="width:22px;height:22px;border:none;border-radius:6px;background:#f3f4f6;color:#374151;font-weight:800;cursor:pointer;">-</button>
                                            <span style="min-width:18px;font-weight:800;">{{ $row['quantity'] }}</span>
                                            <button type="button" wire:click="incrementItem('{{ $row['key'] }}')" style="width:22px;height:22px;border:none;border-radius:6px;background:#dcfce7;color:#15803d;font-weight:800;cursor:pointer;">+</button>
                                        </div>
                                    </td>
                                    <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;font-weight:900;color:#16a34a;direction:ltr;unicode-bidi:isolate;">{{ number_format($row['total_amount'], 2) }}</td>
                                    <td style="padding:8px;border-bottom:1px solid #f3f4f6;text-align:center;">
                                        <button type="button" wire:click="removeItem('{{ $row['key'] }}')" style="border:none;background:transparent;color:#dc2626;font-size:.72rem;font-weight:800;cursor:pointer;">حذف</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" style="padding:18px;text-align:center;color:#6b7280;">لا توجد أصناف مضافة</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div style="margin-top:10px;display:grid;gap:8px;">
                    <div>
                        <div style="font-size:.76rem;font-weight:800;color:#4b5563;margin-bottom:5px;">خزنة الدفع</div>
                        <select wire:model="selected_cash_box_id" style="width:100%;border:1px solid #d1d5db;border-radius:10px;padding:8px 10px;font-size:.8rem;background:#fff;margin-bottom:10px;">
                            @foreach ($this->cashBoxes as $cashBox)
                                <option value="{{ $cashBox->id }}">{{ $cashBox->name }}</option>
                            @endforeach
                        </select>

                        <div style="font-size:.76rem;font-weight:800;color:#4b5563;margin-bottom:5px;">تعيين عميل</div>
                        <input
                            type="text"
                            wire:model.live.debounce.250ms="customer_search"
                            placeholder="بحث عميل..."
                            style="width:100%;border:1px solid #d1d5db;border-radius:10px;padding:7px 10px;font-size:.78rem;margin-bottom:6px;"
                        />
                        <select wire:model="selected_customer_id" style="width:100%;border:1px solid #d1d5db;border-radius:10px;padding:8px 10px;font-size:.8rem;background:#fff;">
                            <option value="">بدون عميل</option>
                            @foreach ($this->customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}{{ $customer->phone ? ' - ' . $customer->phone : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <div style="font-size:.76rem;font-weight:800;color:#4b5563;margin-bottom:5px;">الخصم</div>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            wire:model.live="discount_amount"
                            style="width:100%;border:1px solid #d1d5db;border-radius:10px;padding:8px 10px;font-size:.8rem;"
                            placeholder="0.00"
                        />
                    </div>
                </div>

                <div style="margin-top:10px;border:1px solid #e5e7eb;border-radius:10px;padding:10px;background:#f9fafb;display:grid;gap:6px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;font-size:.82rem;color:#4b5563;">
                        <span>الإجمالي</span>
                        <strong style="direction:ltr;unicode-bidi:isolate;">{{ number_format($this->getTotalAmount(), 2) }}</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;font-size:.95rem;color:#111827;font-weight:900;border-top:1px solid #e5e7eb;padding-top:6px;">
                        <span>الصافي</span>
                        <span style="color:#16a34a;direction:ltr;unicode-bidi:isolate;">{{ number_format($this->getFinalAmount(), 2) }}</span>
                    </div>
                </div>

                <button
                    type="button"
                    wire:click="saveSale"
                    @disabled(empty($this->selectedItems))
                    style="width:100%;margin-top:10px;border:none;background:{{ empty($this->selectedItems) ? '#9ca3af' : '#16a34a' }};color:#fff;padding:11px;border-radius:10px;font-size:.85rem;font-weight:900;cursor:{{ empty($this->selectedItems) ? 'not-allowed' : 'pointer' }};"
                >
                    حفظ الفاتورة
                </button>
            </aside>
        </div>
    </div>

    <style>
        @media (max-width: 1200px) {
            [dir="rtl"] > div > div {
                grid-template-columns: 1fr !important;
            }

            [dir="rtl"] aside {
                position: static !important;
            }
        }
    </style>
</div>
