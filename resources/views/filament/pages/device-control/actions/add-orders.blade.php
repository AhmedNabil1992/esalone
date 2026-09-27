                        @php
                            $currentDeviceOrders = $this->selectedBusyDeviceOrders;
                            $ordersCount = $currentDeviceOrders->count();
                            $ordersTotal = $currentDeviceOrders->sum('total');
                        @endphp

                        <!-- الهيدر الخاص بشاشة إضافة طلبات مع زرار الإغلاق وأزرار التحكم -->
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                @if ($this->selectedBusyDevice)
                                    <span style="font-size: .85rem; font-weight: 700; color: #4b5563;">
                                        الجهاز: <strong style="color: #111827;" class="text-slate-900 dark:!text-zinc-100">{{ $this->selectedBusyDevice->name }}</strong>
                                    </span>
                                @endif
                            </div> 
                            
                            <div style="display: flex; align-items: center; gap: 8px; margin-top: -30px; margin-left: -10px;">
                                <!-- زرار عرض الطلبات الحالية للجهاز -->
                                <button 
                                    type="button" 
                                    @if ($ordersCount > 0)
                                        wire:click="openCurrentDeviceOrdersModal" 
                                    @endif
                                    @disabled($ordersCount === 0)
                                    style="display: flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 8px; font-size: .8rem; font-weight: 700; border: 1px solid {{ $ordersCount > 0 ? '#3b82f6' : '#e5e7eb' }}; background: {{ $ordersCount > 0 ? '#eff6ff' : '#f9fafb' }}; color: {{ $ordersCount > 0 ? '#1d4ed8' : '#9ca3af' }}; cursor: {{ $ordersCount > 0 ? 'pointer' : 'not-allowed' }}; transition: all .15s ease;"
                                    title="{{ $ordersCount > 0 ? 'عرض الطلبات المطلوبة على هذا الجهاز' : 'لا توجد طلبات مسجلة على هذا الجهاز' }}"
                                >
                                    <svg style="width: 17px; height: 17px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119.993z" />
                                    </svg>
                                    <span>طلبات الجهاز</span>
                                    @if ($ordersCount > 0)
                                        <span style="background: #2563eb; color: #fff; font-size: .72rem; padding: 1px 7px; border-radius: 9999px; font-weight: 800;">
                                            {{ $ordersCount }}
                                        </span>
                                    @endif
                                </button>

                                <!-- زرار الإغلاق -->
                                <button 
                                    type="button" 
                                    wire:click="closeBusyActionDetailsModal" 
                                    style="background:transparent; border:none; padding:6px; cursor:pointer; color:#9ca3af; display:flex; align-items:center; justify-content:center; border-radius:6px;"
                                    onmouseover="this.style.backgroundColor='#f3f4f6'; this.style.color='#ef4444'" 
                                    onmouseout="this.style.backgroundColor='transparent'; this.style.color='#9ca3af'"
                                    aria-label="إغلاق"
                                >
                                    <svg style="width:24px; height:24px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                            <div style="display:grid;gap:12px;">
                                <div>
                                    <div style="font-size:.8rem;font-weight:700;color:#374151;margin-bottom:6px;">مجموعات الأصناف</div>
                                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(96px,1fr));gap:8px;">
                                        @forelse ($this->orderGroups as $group)
                                        <button
                                            type="button"
                                            wire:click="selectOrderGroup({{ $group->id }})"
                                            style="border:1px solid {{ $selectedOrderGroupId === $group->id ? '#16a34a' : '#d1d5db' }};background:{{ $selectedOrderGroupId === $group->id ? '#f0fdf4' : '#fff' }};border-radius:12px;padding:8px;display:flex;flex-direction:column;align-items:center;gap:6px;cursor:pointer;"
                                        >
                                            <div style="width:56px;height:56px;border-radius:10px;overflow:hidden;background:#f3f4f6;display:flex;align-items:center;justify-content:center;">
                                                @if ($group->picture)
                                                    <img src="{{ tenant_asset($group->picture) }}" alt="{{ $group->name }}" style="width:56px;height:56px;object-fit:contain;display:block;" />
                                                @else
                                                    <svg style="width:24px;height:24px;color:#9ca3af;" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5A1.5 1.5 0 014.5 6h15A1.5 1.5 0 0121 7.5v9A1.5 1.5 0 0119.5 18h-15A1.5 1.5 0 013 16.5v-9z"/>
                                                    </svg>
                                                @endif
                                            </div>
                                            <div style="font-size:.74rem;font-weight:700;color:#111827;text-align:center;line-height:1.2;word-break:break-word;">{{ $group->name }}</div>
                                        </button>
                                    @empty
                                        <div style="grid-column:1/-1;text-align:center;color:#6b7280;font-size:.78rem;padding:8px;">لا توجد مجموعات أصناف مفعلة</div>
                                    @endforelse
                                </div>
                            </div>

                            <div>
                                <div class="device-control-order-toolbar">
                                    <div style="font-size:.8rem;font-weight:700;color:#374151;">الأصناف</div>
                                    <label class="device-control-order-search">
                                        <svg aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <circle cx="11" cy="11" r="7" />
                                            <path stroke-linecap="round" d="m20 20-4-4" />
                                        </svg>
                                        <input
                                            type="search"
                                            wire:model.live.debounce.250ms="orderItemSearch"
                                            placeholder="ابحث عن صنف..."
                                            aria-label="البحث عن صنف"
                                        />
                                    </label>
                                </div>
                                <div class="device-control-order-items-grid">
                                    @forelse ($this->orderItems as $item)
                                        <button
                                            type="button"
                                            wire:click="openOrderItemQuantityModal({{ $item->id }})"
                                            style="border:1px solid #d1d5db;background:#fff;border-radius:12px;padding:8px;display:flex;flex-direction:column;align-items:center;gap:6px;cursor:pointer;text-align:center;"
                                        >
                                            <div style="width:60px;height:60px;border-radius:10px;overflow:hidden;background:#f3f4f6;display:flex;align-items:center;justify-content:center;">
                                                @if ($item->picture)
                                                    <img src="{{ tenant_asset($item->picture) }}" alt="{{ $item->name }}" style="width:60px;height:60px;object-fit:contain;display:block;" />
                                                @else
                                                    <svg style="width:24px;height:24px;color:#9ca3af;" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.11 1.5 2.076v8.662a2.25 2.25 0 01-2.25 2.25H4.5a2.25 2.25 0 01-2.25-2.25v-8.662c0-.966.616-1.792 1.5-2.076"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v10.5m0 0l3-3m-3 3l-3-3"/>
                                                    </svg>
                                                @endif
                                            </div>
                                            <div style="font-size:.75rem;font-weight:700;color:#111827;line-height:1.2;word-break:break-word;">{{ $item->name }}</div>
                                            <div style="font-size:.74rem;font-weight:700;color:#16a34a;">{{ number_format((float) ($item->sale_price ?? 0), 2) }}</div>
                                        </button>
                                    @empty
                                        <div style="grid-column:1/-1;text-align:center;color:#6b7280;font-size:.78rem;padding:10px;">
                                            {{ trim($orderItemSearch) !== '' ? 'لا توجد أصناف مطابقة للبحث' : 'اختر مجموعة لإظهار الأصناف' }}
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        @if ($selectedCatalogItemId && $this->selectedOrderItem)
                            <div
                                class="device-control-modal-backdrop"
                                style="position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:72;display:flex;align-items:center;justify-content:center;padding:16px;"
                                wire:click="closeOrderItemQuantityModal"
                                @keydown.escape.window="$wire.closeOrderItemQuantityModal()"
                            >
                                <form
                                    wire:submit="addOrderItemToBusySession"
                                    class="device-control-modal-panel"
                                    style="width:min(360px,100%);background:#fff;border-radius:14px;box-shadow:0 20px 48px rgba(0,0,0,.28);padding:14px;margin:0;"
                                    wire:click.stop
                                >
                                    <div style="font-size:.92rem;font-weight:800;color:#111827;margin-bottom:6px;">{{ $this->selectedOrderItem->name }}</div>
                                    <div style="font-size:.78rem;color:#6b7280;margin-bottom:10px;">سعر الوحدة: {{ number_format((float) ($this->selectedOrderItem->sale_price ?? 0), 2) }}</div>

                                    <input
                                        x-init="$nextTick(() => { setTimeout(() => { $el.focus(); $el.select(); }, 50) })"
                                        type="number"
                                        min="1"
                                        step="1"
                                        wire:model="orderItemQuantity"
                                        placeholder="اكتب الكمية المطلوبة"
                                        style="width:100%;border-radius:10px;padding:8px 10px;font-size:.85rem;margin-bottom:12px;"
                                        class="bg-white dark:!bg-zinc-950 border border-slate-300 dark:!border-zinc-700 text-slate-900 dark:!text-zinc-100 placeholder-slate-400 dark:!placeholder-zinc-500"
                                    />
                                    <input
                                        type="text"
                                        wire:model="orderItemNotes"
                                        placeholder="ملاحظات (اختياري)"
                                        style="width:100%;border-radius:10px;padding:8px 10px;font-size:.85rem;margin-bottom:12px;"
                                        class="bg-white dark:!bg-zinc-950 border border-slate-300 dark:!border-zinc-700 text-slate-900 dark:!text-zinc-100 placeholder-slate-400 dark:!placeholder-zinc-500"
                                    />
                                    <div style="display:flex;justify-content:flex-end;gap:8px;">
                                        <button type="button" wire:click="closeOrderItemQuantityModal" style="border:1px solid #d1d5db;background:#fff;color:#111827;padding:8px 12px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                            إلغاء
                                        </button>
                                        <button type="submit" wire:loading.attr="disabled" style="border:1px solid #16a34a;background:#16a34a;color:#fff;padding:8px 12px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                            حفظ
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endif

                        @if ($showCurrentDeviceOrdersModal)
                            <div
                                class="device-control-modal-backdrop"
                                style="position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:73;display:flex;align-items:center;justify-content:center;padding:16px;"
                                wire:click="closeCurrentDeviceOrdersModal"
                                @keydown.escape.window="$wire.closeCurrentDeviceOrdersModal()"
                            >
                                <div
                                    class="device-control-modal-panel bg-white dark:!bg-zinc-900 border border-slate-200 dark:!border-zinc-800 text-slate-900 dark:!text-zinc-100"
                                    style="width:min(640px,100%);max-height:85vh;overflow-y:auto;border-radius:16px;box-shadow:0 24px 64px rgba(0,0,0,.35);padding:18px 20px;display:flex;flex-direction:column;gap:14px;"
                                    wire:click.stop
                                >
                                    {{-- الهيدر --}}
                                    <div style="display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #e5e7eb;padding-bottom:12px;" class="border-slate-200 dark:!border-zinc-800">
                                        <div style="display:flex;align-items:center;gap:10px;">
                                            <div style="width:38px;height:38px;border-radius:10px;background:#eff6ff;color:#2563eb;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                <svg style="width:22px;height:22px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119.993z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div style="font-size:1rem;font-weight:800;" class="text-slate-900 dark:!text-zinc-100">
                                                    طلبات جهاز: {{ $this->selectedBusyDevice?->name }}
                                                </div>
                                                <div style="font-size:.76rem;color:#6b7280;">
                                                    إجمالي الأصناف المسجلة: <strong>{{ $ordersCount }}</strong>
                                                </div>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            wire:click="closeCurrentDeviceOrdersModal"
                                            style="background:transparent;border:none;padding:6px;cursor:pointer;color:#9ca3af;display:flex;align-items:center;justify-content:center;border-radius:6px;"
                                            onmouseover="this.style.backgroundColor='#f3f4f6';this.style.color='#ef4444'"
                                            onmouseout="this.style.backgroundColor='transparent';this.style.color='#9ca3af'"
                                            aria-label="إغلاق"
                                        >
                                            <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>

                                    {{-- جدول الطلبات --}}
                                    <div style="max-height:360px;overflow-y:auto;border:1px solid #e5e7eb;border-radius:12px;" class="border-slate-200 dark:!border-zinc-800">
                                        <table style="width:100%;border-collapse:collapse;font-size:.8rem;text-align:right;">
                                            <thead style="background:#f9fafb;color:#374151;position:sticky;top:0;z-index:1;" class="bg-slate-50 dark:!bg-zinc-800 text-slate-700 dark:!text-zinc-300">
                                                <tr>
                                                    <th style="padding:10px 12px;border-bottom:1px solid #e5e7eb;">الصنف</th>
                                                    <th style="padding:10px 12px;border-bottom:1px solid #e5e7eb;text-align:center;">الكمية</th>
                                                    <th style="padding:10px 12px;border-bottom:1px solid #e5e7eb;text-align:center;">السعر</th>
                                                    <th style="padding:10px 12px;border-bottom:1px solid #e5e7eb;text-align:center;">الإجمالي</th>
                                                    <th style="padding:10px 12px;border-bottom:1px solid #e5e7eb;text-align:center;">ملاحظات</th>
                                                    @if ($this->canUpdateDeviceControlOrders || $this->canDeleteDeviceControlOrders)
                                                        <th style="padding:10px 12px;border-bottom:1px solid #e5e7eb;text-align:center;">إجراء</th>
                                                    @endif
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($currentDeviceOrders as $order)
                                                    <tr style="border-bottom:1px solid #f3f4f6;" class="border-slate-100 dark:!border-zinc-800/50 hover:bg-slate-50 dark:hover:!bg-zinc-800/40">
                                                        <td style="padding:10px 12px;font-weight:700;" class="text-slate-900 dark:!text-zinc-100">
                                                            {{ $order->catalogItem?->name ?? '—' }}
                                                        </td>
                                                        <td style="padding:10px 12px;text-align:center;">
                                                            @if ($this->canUpdateDeviceControlOrders)
                                                                <div style="display:inline-flex;align-items:center;gap:6px;direction:ltr;">
                                                                    <button
                                                                        type="button"
                                                                        wire:click="updateBusySessionOrderQuantity({{ $order->id }}, {{ max(1, (int) $order->quantity - 1) }})"
                                                                        @disabled((int) $order->quantity <= 1)
                                                                        aria-label="تقليل الكمية"
                                                                        style="width:26px;height:26px;border:1px solid #d1d5db;border-radius:6px;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-weight:700;"
                                                                        class="bg-white dark:!bg-zinc-800 border-slate-300 dark:!border-zinc-600 text-slate-800 dark:!text-zinc-200"
                                                                    >-</button>
                                                                    <strong style="min-width:20px;text-align:center;">{{ (int) $order->quantity }}</strong>
                                                                    <button
                                                                        type="button"
                                                                        wire:click="updateBusySessionOrderQuantity({{ $order->id }}, {{ (int) $order->quantity + 1 }})"
                                                                        aria-label="زيادة الكمية"
                                                                        style="width:26px;height:26px;border:1px solid #d1d5db;border-radius:6px;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-weight:700;"
                                                                        class="bg-white dark:!bg-zinc-800 border-slate-300 dark:!border-zinc-600 text-slate-800 dark:!text-zinc-200"
                                                                    >+</button>
                                                                </div>
                                                            @else
                                                                <strong>{{ (int) $order->quantity }}</strong>
                                                            @endif
                                                        </td>
                                                        <td style="padding:10px 12px;text-align:center;direction:ltr;unicode-bidi:isolate;">
                                                            {{ number_format((float) ($order->price ?? 0), 2) }}
                                                        </td>
                                                        <td style="padding:10px 12px;text-align:center;color:#16a34a;font-weight:800;direction:ltr;unicode-bidi:isolate;">
                                                            {{ number_format((float) ($order->total ?? 0), 2) }}
                                                        </td>
                                                        <td style="padding:10px 12px;text-align:center;color:#6b7280;font-size:.74rem;">
                                                            {{ $order->notes ? $order->notes : '—' }}
                                                        </td>
                                                        @if ($this->canUpdateDeviceControlOrders || $this->canDeleteDeviceControlOrders)
                                                            <td style="padding:10px 12px;text-align:center;">
                                                                @if ($this->canDeleteDeviceControlOrders)
                                                                    <button
                                                                        type="button"
                                                                        wire:click="deleteBusySessionOrder({{ $order->id }})"
                                                                        wire:confirm="هل أنت متأكد من حذف هذا الطلب؟"
                                                                        style="border:1px solid #fecaca;background:#fef2f2;color:#dc2626;padding:4px 8px;border-radius:6px;cursor:pointer;font-size:.74rem;font-weight:700;"
                                                                        class="dark:!bg-red-950/40 dark:!border-red-900/60 dark:!text-red-400"
                                                                    >
                                                                        حذف
                                                                    </button>
                                                                @endif
                                                            </td>
                                                        @endif
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="{{ ($this->canUpdateDeviceControlOrders || $this->canDeleteDeviceControlOrders) ? 6 : 5 }}" style="padding:24px;text-align:center;color:#6b7280;">
                                                            لا توجد طلبات مسجلة على هذه الجلسة.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>

                                    {{-- الفوتر: الإجمالي وزر الإغلاق --}}
                                    <div style="display:flex;align-items:center;justify-content:space-between;padding-top:4px;">
                                        <div style="display:flex;align-items:center;gap:6px;font-size:.9rem;">
                                            <span style="font-weight:700;color:#374151;" class="text-slate-700 dark:!text-zinc-300">إجمالي الطلبات:</span>
                                            <span style="font-weight:800;color:#16a34a;font-size:1.05rem;direction:ltr;unicode-bidi:isolate;">
                                                {{ number_format((float) $ordersTotal, 2) }} ج.م
                                            </span>
                                        </div>
                                        <button
                                            type="button"
                                            wire:click="closeCurrentDeviceOrdersModal"
                                            style="border:1px solid #d1d5db;background:#fff;color:#111827;padding:8px 18px;border-radius:10px;cursor:pointer;font-size:.82rem;font-weight:700;"
                                            class="bg-white dark:!bg-zinc-800 border-slate-300 dark:!border-zinc-700 text-slate-800 dark:!text-zinc-200"
                                        >
                                            إغلاق
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif
