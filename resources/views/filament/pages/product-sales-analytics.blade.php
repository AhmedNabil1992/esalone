<x-filament-panels::page>
    @php
        $stats = $this->getProductSalesStats();
        $unsold = $this->getUnsoldProducts();
        
        $totalQty = array_sum(array_column($stats, 'quantity_sold'));
        $totalRev = array_sum(array_column($stats, 'total_revenue'));
        $activeProductsCount = \App\Models\Tenant\CatalogItem::where('is_active', true)->count();
        
        // Prepare chart datasets for Top 10 items
        $topItems = array_slice($stats, 0, 10);
        $labels = [];
        $qtyData = [];
        $revData = [];
        
        foreach ($topItems as $item) {
            $labels[] = $item['name'];
            $qtyData[] = $item['quantity_sold'];
            $revData[] = $item['total_revenue'];
        }
    @endphp

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <div style="display: flex; flex-direction: column; gap: 20px;">
        
        <!-- Filters Card -->
        <div style="background: #fff; padding: 16px 20px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
            <div style="font-size: .88rem; font-weight: 700; color: #111827;">تصفية بالفترة الزمنية:</div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 6px;">
                    <span style="font-size: .78rem; color: #4b5563;">من تاريخ</span>
                    <input type="date" wire:model.live="from" style="border: 1px solid #d1d5db; border-radius: 8px; padding: 6px 12px; font-size: .82rem; color: #111827; background: #fff;" />
                </div>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <span style="font-size: .78rem; color: #4b5563;">إلى تاريخ</span>
                    <input type="date" wire:model.live="until" style="border: 1px solid #d1d5db; border-radius: 8px; padding: 6px 12px; font-size: .82rem; color: #111827; background: #fff;" />
                </div>
            </div>
        </div>

        <!-- KPI Cards Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
            <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 16px;">
                <div style="background: #faf5ff; color: #7c3aed; border-radius: 10px; padding: 12px; font-size: 1.5rem; display: flex; align-items: center; justify-content: center;">☕</div>
                <div>
                    <div style="font-size: .78rem; color: #6b7280;">إجمالي مبيعات البوفيه والكافيه</div>
                    <div style="font-size: 1.3rem; font-weight: 800; color: #7c3aed; margin-top: 4px;">{{ number_format($totalRev, 2) }} ج.م</div>
                </div>
            </div>

            <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 16px;">
                <div style="background: #eff6ff; color: #2563eb; border-radius: 10px; padding: 12px; font-size: 1.5rem; display: flex; align-items: center; justify-content: center;">📦</div>
                <div>
                    <div style="font-size: .78rem; color: #6b7280;">إجمالي عدد السلع المباعة</div>
                    <div style="font-size: 1.3rem; font-weight: 800; color: #111827; margin-top: 4px;">{{ $totalQty }} قطعة</div>
                </div>
            </div>

            <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 16px;">
                <div style="background: #f0fdf4; color: #16a34a; border-radius: 10px; padding: 12px; font-size: 1.5rem; display: flex; align-items: center; justify-content: center;">📋</div>
                <div>
                    <div style="font-size: .78rem; color: #6b7280;">عدد الأصناف الفعالة بالمنيو</div>
                    <div style="font-size: 1.3rem; font-weight: 800; color: #16a34a; margin-top: 4px;">{{ $activeProductsCount }} صنف</div>
                </div>
            </div>
        </div>

        <!-- Chart Section -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px;">
            
            <!-- Quantity Chart -->
            <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); padding: 20px;">
                <h3 style="font-size: .94rem; font-weight: 700; color: #111827; margin-bottom: 16px;">أكثر الأصناف مبيعاً بالكمية (قطعة)</h3>
                <div wire:ignore x-data="{
                    chart: null,
                    init() {
                        const ctx = this.$refs.canvas.getContext('2d');
                        this.chart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: {{ json_encode($labels) }},
                                datasets: [{
                                    label: 'الكمية المباعة',
                                    data: {{ json_encode($qtyData) }},
                                    backgroundColor: 'rgba(59, 130, 246, 0.75)',
                                    borderColor: 'rgb(59, 130, 246)',
                                    borderWidth: 1,
                                    borderRadius: 6
                                }]
                            },
                            options: {
                                indexAxis: 'y',
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    x: { beginAtZero: true }
                                }
                            }
                        });

                        this.$watch('$wire.chartDataSerialized', value => {
                            if (value) {
                                const data = JSON.parse(value);
                                this.chart.data.labels = data.labels || [];
                                this.chart.data.datasets[0].data = data.qty || [];
                                this.chart.update();
                            }
                        });
                    }
                }" style="width: 100%; height: 320px;">
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>

            <!-- Revenue Chart -->
            <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); padding: 20px;">
                <h3 style="font-size: .94rem; font-weight: 700; color: #111827; margin-bottom: 16px;">إيرادات الأصناف الأكثر مبيعاً (ج.م)</h3>
                <div wire:ignore x-data="{
                    chart: null,
                    init() {
                        const ctx = this.$refs.canvas.getContext('2d');
                        this.chart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: {{ json_encode($labels) }},
                                datasets: [{
                                    label: 'إجمالي الإيراد',
                                    data: {{ json_encode($revData) }},
                                    backgroundColor: 'rgba(245, 158, 11, 0.75)',
                                    borderColor: 'rgb(245, 158, 11)',
                                    borderWidth: 1,
                                    borderRadius: 6
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: { beginAtZero: true }
                                }
                            }
                        });

                        this.$watch('$wire.chartDataSerialized', value => {
                            if (value) {
                                const data = JSON.parse(value);
                                this.chart.data.labels = data.labels || [];
                                this.chart.data.datasets[0].data = data.rev || [];
                                this.chart.update();
                            }
                        });
                    }
                }" style="width: 100%; height: 320px;">
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>

        </div>

        <!-- Detailed Breakdown & Unsold Items -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px;">
            
            <!-- Table 1: Sales breakdown -->
            <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; display: flex; flex-direction: column;">
                <div style="padding: 16px 20px; font-size: .94rem; font-weight: 700; color: #111827; border-bottom: 1px solid #e5e7eb; background: #fafafa; display: flex; justify-content: space-between;">
                    <span>تفاصيل مبيعات المنتجات</span>
                    <span style="color: #7c3aed;">إجمالي المبيعات: {{ number_format($totalRev, 2) }} ج.م</span>
                </div>
                <div style="overflow-x: auto; flex: 1;">
                    <table style="width: 100%; text-align: right; border-collapse: collapse; font-size: .82rem;">
                        <thead>
                            <tr style="background: #f3f4f6; border-bottom: 1px solid #e5e7eb; color: #374151;">
                                <th style="padding: 12px 16px; font-weight: 700;">اسم المنتج</th>
                                <th style="padding: 12px 16px; font-weight: 700; text-align: center;">سعر البيع</th>
                                <th style="padding: 12px 16px; font-weight: 700; text-align: center;">الكمية المباعة</th>
                                <th style="padding: 12px 16px; font-weight: 700; text-align: center;">إجمالي الإيراد</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stats as $index => $item)
                                <tr style="border-bottom: 1px solid #e5e7eb; background: #fff;">
                                    <td style="padding: 12px 16px; font-weight: 700; color: #111827;">
                                        <span style="color:#9ca3af; font-size:.74rem; font-weight:700; margin-left:6px;">#{{ $index + 1 }}</span>
                                        {{ $item['name'] }}
                                    </td>
                                    <td style="padding: 12px 16px; text-align: center;">{{ number_format($item['unit_price'], 2) }} ج.م</td>
                                    <td style="padding: 12px 16px; text-align: center; font-weight: 700; color: #2563eb;">{{ $item['quantity_sold'] }}</td>
                                    <td style="padding: 12px 16px; text-align: center; font-weight: 700; color: #16a34a;">{{ number_format($item['total_revenue'], 2) }} ج.m</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" style="padding: 24px; text-align: center; color: #6b7280;">لا توجد مبيعات مسجلة في هذه الفترة.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Table 2: Unsold Items -->
            <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; display: flex; flex-direction: column;">
                <div style="padding: 16px 20px; font-size: .94rem; font-weight: 700; color: #111827; border-bottom: 1px solid #e5e7eb; background: #fff5f5; color: #991b1b; display: flex; justify-content: space-between;">
                    <span>⚠️ أصناف راكدة (لم تبع نهائياً)</span>
                    <span>عدد الأصناف: {{ count($unsold) }}</span>
                </div>
                <div style="overflow-x: auto; flex: 1;">
                    <table style="width: 100%; text-align: right; border-collapse: collapse; font-size: .82rem;">
                        <thead>
                            <tr style="background: #fff2f2; border-bottom: 1px solid #e5e7eb; color: #991b1b;">
                                <th style="padding: 12px 16px; font-weight: 700;">اسم المنتج</th>
                                <th style="padding: 12px 16px; font-weight: 700; text-align: center;">سعر البيع الافتراضي</th>
                                <th style="padding: 12px 16px; font-weight: 700; text-align: center;">النشاط</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($unsold as $item)
                                <tr style="border-bottom: 1px solid #e5e7eb; background: #fff;">
                                    <td style="padding: 12px 16px; font-weight: 600; color: #374151;">{{ $item['name'] }}</td>
                                    <td style="padding: 12px 16px; text-align: center; color: #4b5563;">{{ number_format($item['price'], 2) }} ج.م</td>
                                    <td style="padding: 12px 16px; text-align: center;">
                                        <span style="background: #fef2f2; color: #991b1b; font-size: .68rem; padding: 2px 8px; border-radius: 6px; font-weight: 700;">غير مباع</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" style="padding: 24px; text-align: center; color: #16a34a; font-weight: 700;">🎉 رائع! جميع الأصناف تم البيع منها.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
</x-filament-panels::page>
