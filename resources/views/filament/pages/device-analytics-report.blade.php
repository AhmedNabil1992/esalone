<x-filament-panels::page>
    @php
        $stats = $this->getDeviceStats();
        $totalHours = array_sum(array_column($stats, 'total_hours'));
        $totalRevenue = array_sum(array_column($stats, 'total_revenue'));
        $totalSessions = array_sum(array_column($stats, 'total_sessions'));
        
        // Prepare chart datasets
        $labels = [];
        $hoursData = [];
        $sessionsData = [];
        $revenueData = [];
        
        foreach ($stats as $s) {
            $labels[] = $s['device_name'];
            $hoursData[] = $s['total_hours'];
            $sessionsData[] = $s['total_sessions'];
            $revenueData[] = $s['total_revenue'];
        }
    @endphp

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <div style="display: flex; flex-direction: column; gap: 20px;">
        
        <!-- Filters Card -->
        <div style="background: #fff; padding: 16px 20px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
            <div style="font-size: .88rem; font-weight: 700; color: #111827;">تصفية بالتواريخ:</div>
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
                <div style="background: #f0fdf4; color: #16a34a; border-radius: 10px; padding: 12px; font-size: 1.5rem; display: flex; align-items: center; justify-content: center;">🎮</div>
                <div>
                    <div style="font-size: .78rem; color: #6b7280;">إجمالي ساعات اللعب (لكل الأجهزة)</div>
                    <div style="font-size: 1.3rem; font-weight: 800; color: #111827; margin-top: 4px;">{{ number_format($totalHours, 1) }} ساعة</div>
                </div>
            </div>

            <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 16px;">
                <div style="background: #eff6ff; color: #2563eb; border-radius: 10px; padding: 12px; font-size: 1.5rem; display: flex; align-items: center; justify-content: center;">⏱️</div>
                <div>
                    <div style="font-size: .78rem; color: #6b7280;">إجمالي الجلسات المشغلة</div>
                    <div style="font-size: 1.3rem; font-weight: 800; color: #111827; margin-top: 4px;">{{ $totalSessions }} جلسة لعب</div>
                </div>
            </div>

            <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 16px;">
                <div style="background: #faf5ff; color: #7c3aed; border-radius: 10px; padding: 12px; font-size: 1.5rem; display: flex; align-items: center; justify-content: center;">💰</div>
                <div>
                    <div style="font-size: .78rem; color: #6b7280;">إجمالي إيرادات اللعب المتولدة</div>
                    <div style="font-size: 1.3rem; font-weight: 800; color: #7c3aed; margin-top: 4px;">{{ number_format($totalRevenue, 2) }} ج.م</div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px;">
            
            <!-- Play Hours Chart -->
            <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); padding: 20px;">
                <h3 style="font-size: .94rem; font-weight: 700; color: #111827; margin-bottom: 16px;">معدل تشغيل الأجهزة (بالساعة)</h3>
                <div wire:ignore x-data="(function() {
                    let chartInstance = null;
                    return {
                        init() {
                            const canvas = this.$refs.canvas;
                            const ctx = canvas.getContext('2d');
                            const initial = JSON.parse(this.$wire.chartDataSerialized || '{}');
                            chartInstance = new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels: initial.labels || [],
                                    datasets: [{
                                        label: 'ساعات التشغيل',
                                        data: initial.hours || [],
                                        backgroundColor: 'rgba(139, 92, 246, 0.75)',
                                        borderColor: 'rgb(139, 92, 246)',
                                        borderWidth: 1,
                                        borderRadius: 6
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    scales: { y: { beginAtZero: true } }
                                }
                            });
                            this.$wire.$watch('chartDataSerialized', value => {
                                if (value && chartInstance) {
                                    const data = JSON.parse(value);
                                    chartInstance.data.labels = data.labels || [];
                                    chartInstance.data.datasets[0].data = data.hours || [];
                                    chartInstance.update();
                                }
                            });
                        }
                    };
                })()" style="width: 100%; height: 320px;">
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>

            <!-- Revenue Chart -->
            <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); padding: 20px;">
                <h3 style="font-size: .94rem; font-weight: 700; color: #111827; margin-bottom: 16px;">العائد المالي للأجهزة (ج.م)</h3>
                <div wire:ignore x-data="(function() {
                    let chartInstance = null;
                    return {
                        init() {
                            const canvas = this.$refs.canvas;
                            const ctx = canvas.getContext('2d');
                            const initial = JSON.parse(this.$wire.chartDataSerialized || '{}');
                            chartInstance = new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels: initial.labels || [],
                                    datasets: [{
                                        label: 'الإيرادات المحققة',
                                        data: initial.revenue || [],
                                        backgroundColor: 'rgba(16, 185, 129, 0.75)',
                                        borderColor: 'rgb(16, 185, 129)',
                                        borderWidth: 1,
                                        borderRadius: 6
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    scales: { y: { beginAtZero: true } }
                                }
                            });
                            this.$wire.$watch('chartDataSerialized', value => {
                                if (value && chartInstance) {
                                    const data = JSON.parse(value);
                                    chartInstance.data.labels = data.labels || [];
                                    chartInstance.data.datasets[0].data = data.revenue || [];
                                    chartInstance.update();
                                }
                            });
                        }
                    };
                })()" style="width: 100%; height: 320px;">
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>

        </div>

        <!-- Detailed Table & Play Types Breakdown -->
        <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 16px 20px; font-size: .94rem; font-weight: 700; color: #111827; border-bottom: 1px solid #e5e7eb; background: #fafafa;">تفاصيل أداء واستخدام الأجهزة</div>
            <div style="overflow-x: auto;">
                <table style="width: 100%; text-align: right; border-collapse: collapse; font-size: .84rem;">
                    <thead>
                        <tr style="background: #f3f4f6; border-bottom: 1px solid #e5e7eb; color: #374151;">
                            <th style="padding: 12px 20px; font-weight: 700;">اسم الجهاز</th>
                            <th style="padding: 12px 20px; font-weight: 700; text-align: center;">ساعات التشغيل</th>
                            <th style="padding: 12px 20px; font-weight: 700; text-align: center;">عدد الجلسات</th>
                            <th style="padding: 12px 20px; font-weight: 700; text-align: center;">إيراد اللعب</th>
                            <th style="padding: 12px 20px; font-weight: 700; text-align: center;">معدل الربح/الساعة</th>
                            <th style="padding: 12px 20px; font-weight: 700;">تفاصيل نوع اللعب (العدد لكل نوع)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($stats as $s)
                            <tr style="border-bottom: 1px solid #e5e7eb; background: #fff;">
                                <td style="padding: 12px 20px; font-weight: 700; color: #111827;">{{ $s['device_name'] }}</td>
                                <td style="padding: 12px 20px; text-align: center; font-weight: 600;">{{ number_format($s['total_hours'], 1) }} س</td>
                                <td style="padding: 12px 20px; text-align: center; color: #4b5563;">{{ $s['total_sessions'] }} جلسة</td>
                                <td style="padding: 12px 20px; text-align: center; font-weight: 700; color: #16a34a;">{{ number_format($s['total_revenue'], 2) }} ج.م</td>
                                <td style="padding: 12px 20px; text-align: center; font-weight: 700; color: #2563eb;">
                                    {{ $s['total_hours'] > 0 ? number_format($s['total_revenue'] / $s['total_hours'], 2) : '0.00' }} ج.م
                                </td>
                                <td style="padding: 12px 20px;">
                                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                        @forelse ($s['play_types'] as $type => $count)
                                            <span style="background: #f3f4f6; color: #374151; font-size: .72rem; padding: 2px 8px; border-radius: 6px; font-weight: 600;">
                                                {{ $type }}: {{ $count }}
                                            </span>
                                        @empty
                                            <span style="color: #9ca3af; font-size: .72rem;">—</span>
                                        @endforelse
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: 24px; text-align: center; color: #6b7280;">لا توجد بيانات تشغيل مسجلة في هذه الفترة الزمنية.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-filament-panels::page>
