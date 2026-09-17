<x-filament-panels::page>
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
            <!-- KPI 1: Peak Hour -->
            <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 16px;">
                <div style="background: #fef2f2; color: #dc2626; border-radius: 10px; padding: 12px; display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 24px; height: 24px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z" />
                    </svg>
                </div>
                <div>
                    <div style="font-size: .78rem; color: #6b7280;">ساعة الذروة الأكثر إشغالاً</div>
                    <div style="font-size: 1.35rem; font-weight: 800; color: #111827; margin-top: 4px;">
                        @if ($this->peakHour)
                            {{ $this->peakHour['label'] }} 
                            <span style="font-size: .78rem; color: #4b5563; font-weight: 500;">({{ $this->peakHour['sessions_count'] }} جلسة)</span>
                        @else
                            —
                        @endif
                    </div>
                </div>
            </div>

            <!-- KPI 2: Total Sessions -->
            <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 16px;">
                <div style="background: #eff6ff; color: #2563eb; border-radius: 10px; padding: 12px; display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 24px; height: 24px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                </div>
                <div>
                    <div style="font-size: .78rem; color: #6b7280;">إجمالي الجلسات المحللة</div>
                    <div style="font-size: 1.35rem; font-weight: 800; color: #111827; margin-top: 4px;">
                        {{ number_format($this->totalSessions) }} جلسة
                    </div>
                </div>
            </div>

            <!-- KPI 3: Total Revenue -->
            <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 16px;">
                <div style="background: #f0fdf4; color: #16a34a; border-radius: 10px; padding: 12px; display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 24px; height: 24px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M12 16M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z" />
                    </svg>
                </div>
                <div>
                    <div style="font-size: .78rem; color: #6b7280;">إجمالي إيراد اللعب بالفترة</div>
                    <div style="font-size: 1.35rem; font-weight: 800; color: #16a34a; margin-top: 4px;">
                        {{ number_format($this->totalRevenue, 2) }} ج.م
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Section -->
        <div style="background: #fff; padding: 24px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="font-size: .94rem; font-weight: 700; color: #111827; margin-bottom: 16px;">مخطط توزيع الجلسات على مدار 24 ساعة اليومية</div>
            
            <div wire:key="rush-hour-chart-{{ md5($hourlyDataSerialized) }}" x-data="{
                chart: null,
                init() {
                    const ctx = this.$refs.canvas.getContext('2d');
                    const initial = JSON.parse(this.$wire.hourlyDataSerialized || '[]');
                    this.chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: initial.map(item => item.label),
                            datasets: [{
                                label: 'عدد الجلسات البادئة',
                                data: initial.map(item => item.sessions_count),
                                backgroundColor: 'rgba(37, 99, 235, 0.85)',
                                borderColor: 'rgb(37, 99, 235)',
                                borderWidth: 1,
                                borderRadius: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: { stepSize: 1 }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: false
                                }
                            }
                        }
                    });
                },
                destroy() {
                    if (this.chart) {
                        this.chart.destroy();
                        this.chart = null;
                    }
                }
            }" style="width: 100%; height: 350px;">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        <!-- Detailed Table Section -->
        <div style="background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 16px 20px; font-size: .94rem; font-weight: 700; color: #111827; border-bottom: 1px solid #e5e7eb; background: #fafafa;">ترتيب الساعات الأكثر إشغالاً تنازلياً</div>
            <div style="overflow-x: auto;">
                <table style="width: 100%; text-align: right; border-collapse: collapse; font-size: .84rem;">
                    <thead>
                        <tr style="background: #f3f4f6; border-bottom: 1px solid #e5e7eb; color: #374151;">
                            <th style="padding: 12px 20px; font-weight: 700;">الساعة اليومية</th>
                            <th style="padding: 12px 20px; font-weight: 700; text-align: center;">عدد الجلسات البادئة</th>
                            <th style="padding: 12px 20px; font-weight: 700; text-align: center;">إجمالي مدة اللعب</th>
                            <th style="padding: 12px 20px; font-weight: 700; text-align: center;">إجمالي الإيراد المحقق</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $sortedStats = collect($this->getHourlyStats())->sortByDesc('sessions_count')->values();
                        @endphp
                        @forelse ($sortedStats as $index => $stat)
                            @if ($stat['sessions_count'] > 0)
                                <tr style="border-bottom: 1px solid #e5e7eb; background: {{ $index < 3 ? '#fefcbf' : '#fff' }};">
                                    <td style="padding: 12px 20px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                                        @if ($index < 3)
                                            <span style="background: #dc2626; color: #fff; font-size: .68rem; padding: 2px 6px; border-radius: 4px; font-weight: 700;">ذروة</span>
                                        @endif
                                        {{ $stat['label'] }}
                                    </td>
                                    <td style="padding: 12px 20px; text-align: center; font-weight: 700; color: #2563eb;">{{ $stat['sessions_count'] }}</td>
                                    <td style="padding: 12px 20px; text-align: center; color: #4b5563;">
                                        {{ floor($stat['total_play_time'] / 60) }} س و {{ $stat['total_play_time'] % 60 }} د
                                    </td>
                                    <td style="padding: 12px 20px; text-align: center; font-weight: 700; color: #16a34a;">{{ number_format($stat['total_revenue'], 2) }} ج.م</td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="4" style="padding: 24px; text-align: center; color: #6b7280;">لا توجد جلسات مسجلة في هذه الفترة الزمنية.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-filament-panels::page>

<!-- Ensure Chart.js is loaded -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
