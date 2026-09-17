<div dir="rtl" class="direct-sales">
    @include('livewire.tenant.sales.direct-sales-styles')

    <div class="ds-workspace">
        <section class="ds-panel ds-catalog" aria-labelledby="direct-sales-catalog-title">
            <header class="ds-heading">
                <div>
                    <h2 id="direct-sales-catalog-title" class="ds-title">المبيعات المباشرة</h2>
                    <p class="ds-subtitle">اختر الأصناف وأضفها إلى سلة البيع</p>
                </div>
            </header>

            <div class="ds-groups" aria-label="مجموعات الأصناف">
                @forelse ($this->orderGroups as $group)
                    @php($isActive = $inventory_group_id === $group->id)
                    <button type="button" wire:click="selectOrderGroup({{ $group->id }})" class="ds-group {{ $isActive ? 'is-active' : '' }}" aria-pressed="{{ $isActive ? 'true' : 'false' }}">
                        <span class="ds-group-image">
                            <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7.5h16v10H4zM8 7.5V5h8v2.5" /></svg>
                            @if ($group->picture)
                                <img src="{{ tenant_asset($group->picture) }}" alt="" onerror="this.remove()" />
                            @endif
                        </span>
                        <span class="ds-group-name">{{ $group->name }}</span>
                    </button>
                @empty
                    <div class="ds-empty">لا توجد مجموعات أصناف مفعلة</div>
                @endforelse
            </div>

            <div class="ds-toolbar">
                <div>
                    <label class="ds-section-label" for="direct-sales-group">الفئة الحالية</label>
                    <select id="direct-sales-group" wire:model.live="inventory_group_id" class="ds-field">
                        @foreach ($this->orderGroups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="ds-section-label" for="direct-sales-search">البحث في الأصناف</label>
                    <div class="ds-search">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-4-4"/></svg>
                        <input id="direct-sales-search" type="search" wire:model.live.debounce.250ms="item_search" placeholder="ابحث عن صنف..." class="ds-field" />
                    </div>
                </div>
            </div>

            <div class="ds-products" wire:loading.class="is-loading" wire:target="inventory_group_id,item_search,selectOrderGroup">
                @forelse ($this->orderItems as $item)
                    <button type="button" wire:click="addItem({{ $item->id }})" class="ds-product">
                        <span class="ds-product-image">
                            <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.5v10.75A2.25 2.25 0 0118 21.5H6a2.25 2.25 0 01-2.25-2.25V8.5M8 7V5.75A3.75 3.75 0 0111.75 2h.5A3.75 3.75 0 0116 5.75V7"/></svg>
                            @if ($item->picture)
                                <img src="{{ tenant_asset($item->picture) }}" alt="{{ $item->name }}" loading="lazy" onerror="this.remove()" />
                            @endif
                        </span>
                        <span class="ds-product-name">{{ $item->name }}</span>
                        <span class="ds-product-footer">
                            <span class="ds-price" dir="ltr">{{ number_format((float) ($item->sale_price ?? 0), 2) }} ج.م</span>
                            <span class="ds-add"><span aria-hidden="true">＋</span> إضافة</span>
                        </span>
                    </button>
                @empty
                    <div class="ds-empty">لا توجد أصناف مطابقة للبحث في هذه الفئة</div>
                @endforelse
            </div>
        </section>

        <aside class="ds-panel ds-checkout" aria-labelledby="direct-sales-cart-title">
            <div class="ds-cart-header">
                <div>
                    <h2 id="direct-sales-cart-title" class="ds-title">سلة البيع</h2>
                    <p class="ds-subtitle">{{ count($this->selectedItems) }} صنف محدد</p>
                </div>
                @if (! empty($this->selectedItems))
                    <button type="button" wire:click="clearCart" class="ds-clear">إفراغ السلة</button>
                @endif
            </div>

            <div class="ds-cart">
                <div class="ds-cart-head"><span>الصنف</span><span>الكمية</span><span>الإجمالي</span><span></span></div>
                @forelse ($this->selectedItemRows as $row)
                    <div class="ds-cart-row" wire:key="direct-sale-{{ $row['key'] }}">
                        <div><div class="ds-cart-name">{{ $row['name'] }}</div><div class="ds-cart-unit" dir="ltr">{{ number_format($row['unit_price'], 2) }} ج.م</div></div>
                        <div class="ds-quantity">
                            <button type="button" wire:click="decrementItem('{{ $row['key'] }}')" aria-label="تقليل كمية {{ $row['name'] }}">−</button>
                            <span>{{ $row['quantity'] }}</span>
                            <button type="button" wire:click="incrementItem('{{ $row['key'] }}')" aria-label="زيادة كمية {{ $row['name'] }}">＋</button>
                        </div>
                        <div class="ds-row-total" dir="ltr">{{ number_format($row['total_amount'], 2) }}</div>
                        <button type="button" wire:click="removeItem('{{ $row['key'] }}')" class="ds-remove" aria-label="حذف {{ $row['name'] }}">×</button>
                    </div>
                @empty
                    <div class="ds-cart-empty">أضف صنفًا من القائمة لبدء فاتورة جديدة</div>
                @endforelse
            </div>

            <div class="ds-form">
                <div>
                    <label class="ds-section-label" for="direct-sales-cash-box">خزينة الدفع</label>
                    <select id="direct-sales-cash-box" wire:model="selected_cash_box_id" class="ds-field">
                        @foreach ($this->cashBoxes as $cashBox)
                            <option value="{{ $cashBox->id }}">{{ $cashBox->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="ds-section-label" for="direct-sales-customer-search">تعيين عميل</label>
                    <div class="ds-customer-fields">
                        <input id="direct-sales-customer-search" type="search" wire:model.live.debounce.250ms="customer_search" placeholder="بحث بالاسم أو الهاتف" class="ds-field" />
                        <select wire:model.live="selected_customer_id" class="ds-field" aria-label="اختيار العميل">
                            <option value="">بدون عميل</option>
                            @foreach ($this->customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}{{ $customer->phone ? ' - '.$customer->phone : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="ds-form-grid">
                    @if ($this->canApplyDiscount)
                        <div><label class="ds-section-label" for="direct-sales-discount">الخصم</label><input id="direct-sales-discount" type="number" step="0.01" min="0" wire:model.live="discount_amount" class="ds-field" placeholder="0.00" /></div>
                    @endif
                    <div><label class="ds-section-label" for="direct-sales-paid">المدفوع</label><input id="direct-sales-paid" type="number" step="0.01" min="0" wire:model.live.debounce.250ms="paid_amount" class="ds-field" placeholder="0.00" /></div>
                </div>
                @if ($selected_customer_id && $this->getRemainingAmount() > 0)
                    <label class="ds-checkbox"><input type="checkbox" wire:model.live="deposit_remainder_to_customer_balance" /><span>إضافة الباقي إلى رصيد العميل بدل إرجاعه نقدًا</span></label>
                @endif
                <div><label class="ds-section-label" for="direct-sales-whatsapp">رقم الواتساب لإرسال الفاتورة (اختياري)</label><input id="direct-sales-whatsapp" type="tel" inputmode="tel" wire:model="whatsapp_phone" class="ds-field" placeholder="مثال: 01012345678" dir="ltr" /></div>
            </div>

            <div class="ds-summary">
                <div class="ds-summary-row"><span>الإجمالي</span><strong dir="ltr">{{ number_format($this->getTotalAmount(), 2) }} ج.م</strong></div>
                <div class="ds-summary-row is-total"><span>الصافي</span><strong dir="ltr">{{ number_format($this->getFinalAmount(), 2) }} ج.م</strong></div>
                <div class="ds-summary-row"><span>الباقي</span><strong class="{{ $this->getRemainingAmount() > 0 ? 'ds-change' : '' }}" dir="ltr">{{ number_format($this->getRemainingAmount(), 2) }} ج.م</strong></div>
            </div>

            <button type="button" wire:click="saveSale" wire:loading.attr="disabled" wire:target="saveSale" @disabled(empty($this->selectedItems)) class="ds-submit">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 4h12l2 2v14H5zM8 4v6h8V4M8 20v-6h8v6"/></svg>
                <span wire:loading.remove wire:target="saveSale">حفظ الفاتورة</span><span wire:loading wire:target="saveSale">جارٍ الحفظ...</span>
            </button>
        </aside>
    </div>

    @include('livewire.tenant.sales.direct-sales-printing')
</div>
