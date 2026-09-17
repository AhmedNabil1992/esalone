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
                            <div class="device-control-modal-backdrop" style="position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:72;display:flex;align-items:center;justify-content:center;padding:16px;" wire:click="closeOrderItemQuantityModal">
                                <div class="device-control-modal-panel" style="width:min(360px,100%);background:#fff;border-radius:14px;box-shadow:0 20px 48px rgba(0,0,0,.28);padding:14px;" wire:click.stop>
                                    <div style="font-size:.92rem;font-weight:800;color:#111827;margin-bottom:6px;">{{ $this->selectedOrderItem->name }}</div>
                                    <div style="font-size:.78rem;color:#6b7280;margin-bottom:10px;">سعر الوحدة: {{ number_format((float) ($this->selectedOrderItem->sale_price ?? 0), 2) }}</div>

                                    <input
                                        type="number"
                                        min="1"
                                        step="1"
                                        wire:model.live="orderItemQuantity"
                                        placeholder="اكتب الكمية المطلوبة"
                                        style="width:100%;border-radius:10px;padding:8px 10px;font-size:.85rem;margin-bottom:12px;"
                                        class="bg-white dark:!bg-zinc-950 border border-slate-300 dark:!border-zinc-700 text-slate-900 dark:!text-zinc-100 placeholder-slate-400 dark:!placeholder-zinc-500"
                                    />
                                    <input
                                        type="text"
                                        wire:model.live="orderItemNotes"
                                        placeholder="ملاحظات (اختياري)"
                                        style="width:100%;border-radius:10px;padding:8px 10px;font-size:.85rem;margin-bottom:12px;"
                                        class="bg-white dark:!bg-zinc-950 border border-slate-300 dark:!border-zinc-700 text-slate-900 dark:!text-zinc-100 placeholder-slate-400 dark:!placeholder-zinc-500"
                                    />
                                    <div style="display:flex;justify-content:flex-end;gap:8px;">
                                        <button type="button" wire:click="closeOrderItemQuantityModal" style="border:1px solid #d1d5db;background:#fff;color:#111827;padding:8px 12px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                            إلغاء
                                        </button>
                                        <button type="button" wire:click="addOrderItemToBusySession" style="border:1px solid #16a34a;background:#16a34a;color:#fff;padding:8px 12px;border-radius:10px;cursor:pointer;font-size:.8rem;font-weight:700;">
                                            حفظ
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif
