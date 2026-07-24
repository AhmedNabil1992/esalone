<div dir="rtl" class="p-3.5 bg-slate-50 dark:!bg-zinc-950 rounded-2xl border border-slate-200 dark:!border-zinc-800 space-y-4">
    <style>
        .sales-grid-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
            align-items: start;
        }
        @media (min-width: 1024px) {
            .sales-grid-container {
                grid-template-columns: minmax(0, 2.1fr) minmax(320px, 1fr);
            }
        }
    </style>

    <div class="sales-grid-container">
        <!-- 1. Catalog & Items Selection Section -->
        <section class="bg-white dark:!bg-zinc-900 border border-slate-200 dark:!border-zinc-800 rounded-2xl p-3.5 sm:p-5 shadow-sm text-slate-900 dark:!text-zinc-100">
            <div class="flex justify-between items-center gap-2 mb-3">
                <div>
                    <div class="text-xs text-slate-500 dark:!text-zinc-400 font-bold">المبيعات المباشرة</div>
                    <div class="text-base sm:text-lg font-black text-slate-900 dark:!text-zinc-100">اختيار الأصناف</div>
                </div>
            </div>

            <!-- Groups List -->
            <div class="mb-4">
                <div class="text-xs font-bold text-slate-700 dark:!text-zinc-300 mb-2">مجموعات الأصناف</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(90px,1fr));gap:8px;">
                    @forelse ($this->orderGroups as $group)
                        @php
                            $isActive = $inventory_group_id === $group->id;
                        @endphp
                        <button
                            type="button"
                            wire:click="selectOrderGroup({{ $group->id }})"
                            class="border rounded-xl p-2 flex flex-col items-center gap-1.5 cursor-pointer transition-all duration-150 text-center
                                {{ $isActive 
                                    ? 'border-emerald-600 dark:!border-emerald-500 bg-emerald-50 dark:!bg-emerald-950/40 text-emerald-900 dark:!text-emerald-300' 
                                    : 'border-slate-200 dark:!border-zinc-800 bg-white dark:!bg-zinc-950 text-slate-800 dark:!text-zinc-200 hover:border-emerald-500' }}"
                        >
                            <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-lg overflow-hidden bg-slate-100 dark:!bg-zinc-800 flex items-center justify-center shrink-0">
                                @if ($group->picture)
                                    <img src="{{ tenant_asset($group->picture) }}" alt="{{ $group->name }}" class="w-full h-full object-contain block" />
                                @else
                                    <svg class="w-5 h-5 text-slate-400 dark:!text-zinc-500" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5A1.5 1.5 0 014.5 6h15A1.5 1.5 0 0121 7.5v9A1.5 1.5 0 0119.5 18h-15A1.5 1.5 0 013 16.5v-9z"/>
                                    </svg>
                                @endif
                            </div>
                            <div class="text-[0.72rem] font-bold line-clamp-2 leading-tight break-words">{{ $group->name }}</div>
                        </button>
                    @empty
                        <div class="col-span-full text-center text-slate-500 dark:!text-zinc-400 text-xs p-3 border border-dashed border-slate-300 dark:!border-zinc-700 rounded-xl">لا توجد مجموعات أصناف مفعلة</div>
                    @endforelse
                </div>
            </div>

            <!-- Items Search & Grid -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2 mb-3">
                <div class="text-xs font-bold text-slate-700 dark:!text-zinc-300">الأصناف</div>
                <input
                    type="text"
                    wire:model.live.debounce.250ms="item_search"
                    placeholder="ابحث عن صنف..."
                    class="w-full sm:w-64 border border-slate-300 dark:!border-zinc-700 bg-white dark:!bg-zinc-950 text-slate-900 dark:!text-zinc-100 placeholder-slate-400 dark:!placeholder-zinc-500 rounded-xl px-3 py-1.5 text-xs focus:ring-emerald-500 focus:border-emerald-500"
                />
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:10px;max-height:480px;overflow-y:auto;padding-inline-end:2px;">
                @forelse ($this->orderItems as $item)
                    <button
                        type="button"
                        wire:click="addItem({{ $item->id }})"
                        class="border border-slate-200 dark:!border-zinc-800 bg-white dark:!bg-zinc-950 hover:border-emerald-500 dark:hover:!border-emerald-500 hover:bg-emerald-50/50 dark:hover:!bg-emerald-950/30 rounded-xl p-2.5 flex flex-col items-center gap-1.5 cursor-pointer text-center transition-all duration-150 group"
                    >
                        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-xl overflow-hidden bg-slate-100 dark:!bg-zinc-800 flex items-center justify-center shrink-0">
                            @if ($item->picture)
                                <img src="{{ tenant_asset($item->picture) }}" alt="{{ $item->name }}" class="w-full h-full object-contain block" />
                            @else
                                <svg class="w-6 h-6 text-slate-400 dark:!text-zinc-500" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.11 1.5 2.076v8.662a2.25 2.25 0 01-2.25 2.25H4.5a2.25 2.25 0 01-2.25-2.25v-8.662c0-.966.616-1.792 1.5-2.076"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v10.5m0 0l3-3m-3 3l-3-3"/>
                                </svg>
                            @endif
                        </div>
                        <div class="text-xs font-extrabold text-slate-900 dark:!text-zinc-100 line-clamp-2 leading-tight break-words">{{ $item->name }}</div>
                        <div class="text-xs font-black text-emerald-600 dark:!text-emerald-400 dir-ltr">{{ number_format((float) ($item->sale_price ?? 0), 2) }} ج.م</div>
                    </button>
                @empty
                    <div class="col-span-full text-center text-slate-500 dark:!text-zinc-400 text-xs p-4 border border-dashed border-slate-300 dark:!border-zinc-700 rounded-xl">لا توجد أصناف مطابقة</div>
                @endforelse
            </div>
        </section>

        <!-- 2. Shopping Cart & Checkout Aside Section -->
        <aside class="bg-white dark:!bg-zinc-900 border border-slate-200 dark:!border-zinc-800 rounded-2xl p-3.5 sm:p-5 lg:sticky lg:top-4 shadow-sm text-slate-900 dark:!text-zinc-100">
            <div class="flex items-center justify-between gap-2 mb-3">
                <div class="text-base font-black text-slate-900 dark:!text-zinc-100">سلة البيع</div>
                @if (! empty($this->selectedItems))
                    <button type="button" wire:click="clearCart" class="border border-rose-200 dark:!border-rose-900/60 bg-rose-50 dark:!bg-rose-950/40 text-rose-700 dark:!text-rose-400 hover:bg-rose-100 dark:hover:!bg-rose-900/60 px-2.5 py-1 rounded-lg text-xs font-extrabold cursor-pointer transition-colors">تفريغ</button>
                @endif
            </div>

            <!-- Cart Items Table -->
            <div class="max-h-72 overflow-y-auto border border-slate-200 dark:!border-zinc-800 rounded-xl">
                <table class="w-full text-xs text-right border-collapse">
                    <thead class="bg-slate-50 dark:!bg-zinc-950 text-slate-700 dark:!text-zinc-300 sticky top-0 border-b border-slate-200 dark:!border-zinc-800">
                        <tr>
                            <th class="p-2.5 font-extrabold">الصنف</th>
                            <th class="p-2.5 font-extrabold text-center">الكمية</th>
                            <th class="p-2.5 font-extrabold text-center">الإجمالي</th>
                            <th class="p-2.5"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->selectedItemRows as $row)
                            <tr class="border-b border-slate-100 dark:!border-zinc-800/60 text-slate-800 dark:!text-zinc-200">
                                <td class="p-2.5 align-top">
                                    <div class="font-extrabold text-slate-900 dark:!text-zinc-100">{{ $row['name'] }}</div>
                                    <div class="text-[0.7rem] text-slate-500 dark:!text-zinc-400 dir-ltr">{{ number_format($row['unit_price'], 2) }} ج.م</div>
                                </td>
                                <td class="p-2.5 text-center">
                                    <div class="inline-flex items-center gap-1 border border-slate-300 dark:!border-zinc-700 rounded-lg p-1 bg-slate-50 dark:!bg-zinc-950">
                                        <button type="button" wire:click="decrementItem('{{ $row['key'] }}')" class="w-6 h-6 rounded-md bg-slate-200 dark:!bg-zinc-800 text-slate-800 dark:!text-zinc-200 font-black hover:bg-slate-300 dark:hover:!bg-zinc-700 cursor-pointer flex items-center justify-center">-</button>
                                        <span class="min-w-5 font-black text-slate-900 dark:!text-zinc-100 text-xs">{{ $row['quantity'] }}</span>
                                        <button type="button" wire:click="incrementItem('{{ $row['key'] }}')" class="w-6 h-6 rounded-md bg-emerald-100 dark:!bg-emerald-950/60 text-emerald-700 dark:!text-emerald-400 font-black hover:bg-emerald-200 dark:hover:!bg-emerald-900/60 cursor-pointer flex items-center justify-center">+</button>
                                    </div>
                                </td>
                                <td class="p-2.5 text-center font-black text-emerald-600 dark:!text-emerald-400 dir-ltr">{{ number_format($row['total_amount'], 2) }}</td>
                                <td class="p-2.5 text-center">
                                    <button type="button" wire:click="removeItem('{{ $row['key'] }}')" class="text-rose-600 dark:!text-rose-400 hover:text-rose-700 font-extrabold text-xs cursor-pointer">حذف</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-5 text-center text-slate-400 dark:!text-zinc-500">لا توجد أصناف مضافة</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Form Options -->
            <div class="mt-3.5 space-y-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:!text-zinc-300 mb-1">خزنة الدفع</label>
                    <select wire:model="selected_cash_box_id" class="w-full border border-slate-300 dark:!border-zinc-700 bg-white dark:!bg-zinc-950 text-slate-900 dark:!text-zinc-100 rounded-xl p-2.5 text-xs focus:ring-emerald-500 focus:border-emerald-500">
                        @foreach ($this->cashBoxes as $cashBox)
                            <option value="{{ $cashBox->id }}">{{ $cashBox->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:!text-zinc-300 mb-1">تعيين عميل</label>
                    <input
                        type="text"
                        wire:model.live.debounce.250ms="customer_search"
                        placeholder="بحث عميل..."
                        class="w-full border border-slate-300 dark:!border-zinc-700 bg-white dark:!bg-zinc-950 text-slate-900 dark:!text-zinc-100 placeholder-slate-400 dark:!placeholder-zinc-500 rounded-xl p-2 text-xs mb-1.5 focus:ring-emerald-500 focus:border-emerald-500"
                    />
                    <select wire:model="selected_customer_id" class="w-full border border-slate-300 dark:!border-zinc-700 bg-white dark:!bg-zinc-950 text-slate-900 dark:!text-zinc-100 rounded-xl p-2.5 text-xs focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="">بدون عميل</option>
                        @foreach ($this->customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}{{ $customer->phone ? ' - ' . $customer->phone : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:!text-zinc-300 mb-1">الخصم</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            wire:model.live="discount_amount"
                            class="w-full border border-slate-300 dark:!border-zinc-700 bg-white dark:!bg-zinc-950 text-slate-900 dark:!text-zinc-100 placeholder-slate-400 dark:!placeholder-zinc-500 rounded-xl p-2.5 text-xs focus:ring-emerald-500 focus:border-emerald-500"
                            placeholder="0.00"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:!text-zinc-300 mb-1">المدفوع</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            wire:model.live.debounce.250ms="paid_amount"
                            class="w-full border border-slate-300 dark:!border-zinc-700 bg-white dark:!bg-zinc-950 text-slate-900 dark:!text-zinc-100 placeholder-slate-400 dark:!placeholder-zinc-500 rounded-xl p-2.5 text-xs focus:ring-emerald-500 focus:border-emerald-500"
                            placeholder="0.00"
                        />
                    </div>
                </div>

                @if ($selected_customer_id && $this->getRemainingAmount() > 0)
                    <label class="flex items-center gap-2 text-xs text-slate-700 dark:!text-zinc-300 font-bold cursor-pointer pt-1">
                        <input type="checkbox" wire:model.live="deposit_remainder_to_customer_balance" class="w-4 h-4 accent-emerald-600 rounded cursor-pointer" />
                        <span>إضافة الباقي إلى رصيد العميل بدل إرجاعه نقداً</span>
                    </label>
                @endif

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:!text-zinc-300 mb-1">رقم الواتساب لإرسال الفاتورة (اختياري)</label>
                    <input
                        type="text"
                        wire:model="whatsapp_phone"
                        class="w-full border border-slate-300 dark:!border-zinc-700 bg-white dark:!bg-zinc-950 text-slate-900 dark:!text-zinc-100 placeholder-slate-400 dark:!placeholder-zinc-500 rounded-xl p-2.5 text-xs focus:ring-emerald-500 focus:border-emerald-500"
                        placeholder="مثال: 01012345678"
                    />
                </div>
            </div>

            <!-- Totals Summary Card -->
            <div class="mt-4 border border-slate-200 dark:!border-zinc-800 rounded-xl p-3 bg-slate-50 dark:!bg-zinc-950/80 space-y-2">
                <div class="flex justify-between items-center text-xs text-slate-600 dark:!text-zinc-400">
                    <span>الإجمالي</span>
                    <strong class="dir-ltr text-slate-900 dark:!text-zinc-100 font-bold">{{ number_format($this->getTotalAmount(), 2) }} ج.م</strong>
                </div>
                <div class="flex justify-between items-center text-sm text-slate-900 dark:!text-zinc-100 font-black border-t border-slate-200 dark:!border-zinc-800 pt-2">
                    <span>الصافي</span>
                    <span class="text-emerald-600 dark:!text-emerald-400 dir-ltr">{{ number_format($this->getFinalAmount(), 2) }} ج.م</span>
                </div>
                <div class="flex justify-between items-center text-xs text-slate-900 dark:!text-zinc-100 font-extrabold border-t border-slate-200 dark:!border-zinc-800 pt-2">
                    <span>الباقي</span>
                    <span class="{{ $this->getRemainingAmount() > 0 ? 'text-blue-600 dark:!text-blue-400' : 'text-emerald-600 dark:!text-emerald-400' }} dir-ltr">{{ number_format($this->getRemainingAmount(), 2) }} ج.م</span>
                </div>
            </div>

            <!-- Checkout Button -->
            <button
                type="button"
                wire:click="saveSale"
                @disabled(empty($this->selectedItems))
                class="w-full mt-4 py-3 rounded-xl text-xs sm:text-sm font-black text-white transition-all shadow-sm cursor-pointer
                    {{ empty($this->selectedItems) 
                        ? 'bg-slate-300 dark:!bg-zinc-800 text-slate-500 dark:!text-zinc-500 cursor-not-allowed' 
                        : 'bg-emerald-600 hover:bg-emerald-700 dark:!bg-emerald-600 dark:hover:!bg-emerald-500' }}"
            >
                حفظ الفاتورة
            </button>
        </aside>
    </div>

    @php
        $invoicePrinter = \App\Models\Tenant\SystemConfig::get('sys_invoice_printer', '');
        $orderPrinter = \App\Models\Tenant\SystemConfig::get('sys_order_printer', '');
        $bridgeUrl = config('services.bridge.url', 'ws://127.0.0.1:9000');
    @endphp

    <script>
        document.addEventListener('livewire:init', () => {
            const silentPrintFrameId = 'silent-print-bridge-frame';
            const invoicePrinter = @js($invoicePrinter);
            const orderPrinter = @js($orderPrinter);
            const bridgeUrl = @js($bridgeUrl);

            function printFallback(url) {
                console.warn('Bridge printing failed or offline. Falling back to browser window.');
                try {
                    const urlObj = new URL(url, window.location.origin);
                    urlObj.searchParams.set('fallback', '1');
                    url = urlObj.toString();
                } catch (e) {
                    console.error('Failed to append fallback parameter:', e);
                }
                const popup = window.open(url, '_blank', 'noopener,noreferrer');
                if (!popup) {
                    alert('تم حظر فتح نافذة الطباعة من قبل المتصفح، يرجى السماح بالنوافذ المنبثقة لطباعة الفواتير.');
                }
            }

            Livewire.on('open-print-window', (payload) => {
                const eventData = Array.isArray(payload) ? payload[0] : payload;
                const url = eventData && typeof eventData.url === 'string' ? eventData.url : '';

                if (!url) {
                    return;
                }

                const isSilent = url.includes('silent=1');

                if (!isSilent) {
                    const popup = window.open(url, '_blank', 'noopener,noreferrer');
                    if (!popup) {
                        console.warn('Print popup was blocked by the browser.');
                    }
                    return;
                }

                // Silent printing via Bridge
                let targetPrinter = invoicePrinter;
                if (url.includes('order') || url.includes('prepare')) {
                    targetPrinter = orderPrinter;
                }

                try {
                    const urlObj = new URL(url, window.location.origin);
                    const printerParam = urlObj.searchParams.get('printer');
                    if (printerParam) {
                        targetPrinter = printerParam;
                    }
                } catch (e) {
                    console.error('Failed to parse printer param from URL:', e);
                }

                if (!bridgeUrl || !targetPrinter) {
                    console.warn('Bridge URL or printer not configured. Falling back to browser.');
                    printFallback(url);
                    return;
                }

                fetch(url)
                    .then(response => response.text())
                    .then(htmlContent => {
                        const cleanedHtml = htmlContent.replace(/window\.print\(\);?/gi, '');
                        
                        let socket;
                        let timeoutId;
                        
                        const handleFallback = (reason) => {
                            if (timeoutId) {
                                clearTimeout(timeoutId);
                                timeoutId = null;
                            }
                            if (socket) {
                                try {
                                    socket.close();
                                } catch (e) {}
                            }
                            console.warn('Bridge printing failed (' + reason + '). Falling back to browser.');
                            printFallback(url);
                        };

                        timeoutId = setTimeout(() => {
                            handleFallback('timeout');
                        }, 5000);

                        try {
                            socket = new WebSocket(bridgeUrl);
                            
                            socket.onopen = () => {
                                socket.send('PRINT|' + targetPrinter + '|' + cleanedHtml);
                            };

                            socket.onmessage = (event) => {
                                if (timeoutId) {
                                    clearTimeout(timeoutId);
                                    timeoutId = null;
                                }
                                try {
                                    const resp = JSON.parse(event.data);
                                    if (resp.status === 'success' || resp.status === 'printed') {
                                        console.log('Successfully printed via Bridge on ' + targetPrinter);
                                    } else {
                                        console.error('Bridge print failed:', resp.message);
                                        handleFallback('print_error: ' + resp.message);
                                    }
                                } catch (e) {
                                    console.error('Failed to parse bridge response:', e);
                                    handleFallback('parse_error');
                                }
                                try { socket.close(); } catch (e) {}
                            };

                            socket.onerror = (err) => {
                                console.error('Bridge connection error:', err);
                                handleFallback('connection_error');
                            };
                        } catch (e) {
                            console.error('Failed to establish WebSocket connection:', e);
                            handleFallback('websocket_init_error');
                        }
                    })
                    .catch(err => {
                        console.error('Failed to fetch invoice HTML, falling back to browser window:', err);
                        printFallback(url);
                    });
            });
        });
    </script>
</div>
