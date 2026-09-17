<x-filament-panels::page>
    <div style="background:#fff; border-radius:16px; box-shadow:0 4px 20px rgba(0,0,0,.05); border:1px solid #e5e7eb; overflow:hidden; padding:20px;">
        
        {{-- شريط التحكم والتبديل العلوي --}}
        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:20px; border-bottom:1px solid #f3f4f6; padding-bottom:16px;">
            
            {{-- أزرار التنقل --}}
            <div style="display:flex; align-items:center; gap:8px;">
                <button 
                    type="button" 
                    wire:click="prev"
                    style="display:flex; align-items:center; gap:4px; border:1px solid #d1d5db; background:#fff; border-radius:10px; padding:8px 12px; font-weight:700; font-size:.8rem; cursor:pointer; color:#374151;"
                >
                    <x-filament::icon icon="heroicon-o-chevron-right" style="width:16px; height:16px; color:#4b5563;" />
                    السابق
                </button>

                <button 
                    type="button" 
                    wire:click="today"
                    style="border:1px solid #d1d5db; background:#fff; border-radius:10px; padding:8px 14px; font-weight:700; font-size:.8rem; cursor:pointer; color:#374151;"
                >
                    اليوم
                </button>

                <button 
                    type="button" 
                    wire:click="next"
                    style="display:flex; align-items:center; gap:4px; border:1px solid #d1d5db; background:#fff; border-radius:10px; padding:8px 12px; font-weight:700; font-size:.8rem; cursor:pointer; color:#374151;"
                >
                    التالي
                    <x-filament::icon icon="heroicon-o-chevron-left" style="width:16px; height:16px; color:#4b5563;" />
                </button>
            </div>

            {{-- عنوان المخطط الحالي --}}
            <div style="font-size:1.25rem; font-weight:900; color:#111827; text-align:center;">
                {{ $this->getHeaderTitle() }}
            </div>

            {{-- مبدل طرق العرض --}}
            <div style="display:flex; gap:6px; background:#f3f4f6; border-radius:10px; padding:4px;">
                <button 
                    type="button" 
                    wire:click="changeView('day')" 
                    style="border:none; padding:6px 14px; font-weight:700; font-size:.8rem; border-radius:8px; cursor:pointer; background:{{ $viewMode === 'day' ? '#fff' : 'transparent' }}; color:{{ $viewMode === 'day' ? '#111827' : '#6b7280' }}; box-shadow:{{ $viewMode === 'day' ? '0 1px 3px rgba(0,0,0,.08)' : 'none' }}; transition:all .15s;"
                >
                    اليوم
                </button>
                <button 
                    type="button" 
                    wire:click="changeView('week')" 
                    style="border:none; padding:6px 14px; font-weight:700; font-size:.8rem; border-radius:8px; cursor:pointer; background:{{ $viewMode === 'week' ? '#fff' : 'transparent' }}; color:{{ $viewMode === 'week' ? '#111827' : '#6b7280' }}; box-shadow:{{ $viewMode === 'week' ? '0 1px 3px rgba(0,0,0,.08)' : 'none' }}; transition:all .15s;"
                >
                    الأسبوع
                </button>
                <button 
                    type="button" 
                    wire:click="changeView('month')" 
                    style="border:none; padding:6px 14px; font-weight:700; font-size:.8rem; border-radius:8px; cursor:pointer; background:{{ $viewMode === 'month' ? '#fff' : 'transparent' }}; color:{{ $viewMode === 'month' ? '#111827' : '#6b7280' }}; box-shadow:{{ $viewMode === 'month' ? '0 1px 3px rgba(0,0,0,.08)' : 'none' }}; transition:all .15s;"
                >
                    الشهر
                </button>
            </div>

        </div>

        {{-- محتوى العرض الحالي --}}
        <div>
            
            {{-- 1. عرض اليوم (الأجهزة كأعمدة) --}}
            @if($viewMode === 'day')
                @php 
                    $dayData = $this->getDayViewData(); 
                    $isPast = \Carbon\Carbon::parse($date)->isBefore(today());
                @endphp
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:16px;">
                    @forelse($dayData['devices'] as $device)
                        @php
                            $deviceRes = $dayData['reservations']->get($device->id, collect());
                        @endphp
                        <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:12px; padding:12px; display:flex; flex-direction:column; justify-content:space-between; min-height:220px;">
                            
                            {{-- رأس الكارت --}}
                            <div style="border-bottom:1px dashed #d1d5db; padding-bottom:8px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
                                <span style="font-size:.9rem; font-weight:800; color:#111827;">{{ $device->name }}</span>
                                <span style="font-size:.7rem; font-weight:700; color:#6b7280; background:#fff; border:1px solid #e5e7eb; border-radius:6px; padding:2px 6px;">{{ $device->type?->name }}</span>
                            </div>

                            {{-- قائمة الحجوزات لهذا الجهاز اليوم --}}
                            <div style="display:grid; gap:8px; flex-grow:1; align-content:start;">
                                @forelse($deviceRes as $res)
                                    @php
                                        $badgeBg = match($res->status) {
                                            'active' => '#f0fdf4',
                                            'completed' => '#eff6ff',
                                            'cancelled' => '#fef2f2',
                                            default => '#f9fafb'
                                        };
                                        $badgeBorder = match($res->status) {
                                            'active' => '#bbf7d0',
                                            'completed' => '#bfdbfe',
                                            'cancelled' => '#fecaca',
                                            default => '#e5e7eb'
                                        };
                                        $badgeText = match($res->status) {
                                            'active' => '#15803d',
                                            'completed' => '#1d4ed8',
                                            'cancelled' => '#b91c1c',
                                            default => '#374151'
                                        };
                                        $startTimeFormatted = $res->start_time ? app(\App\Support\Tenant\TenantClock::class)->utcToLocal($res->start_time)->format('h:i A') : '';
                                        $endTimeFormatted = $res->end_time ? app(\App\Support\Tenant\TenantClock::class)->utcToLocal($res->end_time)->format('h:i A') : '';
                                    @endphp
                                    <a 
                                        href="{{ route('filament.admin.resources.tenant.reservations.edit', ['record' => $res->id]) }}"
                                        style="display:flex; flex-direction:column; gap:2px; text-decoration:none; padding:6px 8px; border-radius:8px; background:{{ $badgeBg }}; border:1px solid {{ $badgeBorder }}; color:{{ $badgeText }}; transition:all .15s;"
                                        onmouseover="this.style.transform='translateY(-1px)';"
                                        onmouseout="this.style.transform='none';"
                                    >
                                        <div style="display:flex; align-items:center; gap:4px; direction:ltr; font-size:.68rem; font-weight:800; color:inherit;">
                                            <span>{{ $startTimeFormatted }}</span>
                                            <span style="opacity:.6;">-</span>
                                            <span>{{ $endTimeFormatted }}</span>
                                        </div>
                                        <div style="font-size:.65rem; font-weight:700; margin-top:2px; display:flex; justify-content:space-between; color:inherit;">
                                            <span>{{ $res->customer?->name ?? 'بدون عميل' }}</span>
                                            <span>({{ $res->playPrice?->playType?->name ?? 'فردي' }})</span>
                                        </div>
                                    </a>
                                @empty
                                    <div style="font-size:.74rem; color:#9ca3af; text-align:center; padding:20px 0; font-weight:500;">لا توجد حجوزات اليوم</div>
                                @endforelse
                            </div>

                            {{-- إضافة حجز جديد سريع --}}
                            @if(!$isPast)
                                <a 
                                    href="{{ route('filament.admin.resources.tenant.reservations.create', ['device_id' => $device->id, 'start_time' => $date . 'T12:00']) }}"
                                    style="display:flex; align-items:center; justify-content:center; gap:6px; border:1px dashed #cbd5e1; background:#fff; border-radius:10px; padding:8px; font-weight:700; font-size:.78rem; text-decoration:none; color:#4b5563; margin-top:12px; transition:all .15s;"
                                    onmouseover="this.style.borderStyle='solid'; this.style.borderColor='#16a34a'; this.style.color='#16a34a';"
                                    onmouseout="this.style.borderStyle='dashed'; this.style.borderColor='#cbd5e1'; this.style.color='#4b5563';"
                                >
                                    + إضافة حجز للجهاز
                                </a>
                            @endif

                        </div>
                    @empty
                        <div style="grid-column:1/-1; text-align:center; padding:40px 0; color:#6b7280; font-weight:700;">لا توجد أجهزة معرفة في النظام</div>
                    @endforelse
                </div>

            {{-- 2. عرض الأسبوع (7 أعمدة للأيام) --}}
            @elseif($viewMode === 'week')
                @php $weekDays = $this->getWeekViewData(); @endphp
                <div style="display:grid; grid-template-columns:repeat(7, 1fr); gap:1px; background:#e5e7eb; border-radius:12px; overflow:hidden; border:1px solid #e5e7eb;">
                    @foreach($weekDays as $day)
                        @php $isDayPast = \Carbon\Carbon::parse($day['date'])->isBefore(today()); @endphp
                        <div style="background:#fff; min-height:300px; padding:10px; display:flex; flex-direction:column; justify-content:space-between;">
                            
                            {{-- رأس اليوم --}}
                            <div style="border-bottom:1px solid #f3f4f6; padding-bottom:6px; margin-bottom:8px; display:flex; justify-content:space-between; align-items:center;">
                                <div style="display:flex; flex-direction:column;">
                                    <span style="font-size:.85rem; font-weight:800; color:#111827;">{{ $day['name'] }}</span>
                                    <span style="font-size:.68rem; color:#6b7280; font-weight:700;">{{ $day['display_date'] }}</span>
                                </div>
                                @if(!$isDayPast)
                                    <a 
                                        href="{{ route('filament.admin.resources.tenant.reservations.create', ['start_time' => $day['date'] . 'T12:00']) }}"
                                        title="إضافة حجز"
                                        style="width:20px; height:20px; border-radius:6px; background:#f3f4f6; display:flex; align-items:center; justify-content:center; text-decoration:none; color:#4b5563; font-weight:bold; font-size:.8rem; cursor:pointer;"
                                        onmouseover="this.style.background='#16a34a'; this.style.color='#fff';"
                                        onmouseout="this.style.background='#f3f4f6'; this.style.color='#4b5563';"
                                    >
                                        +
                                    </a>
                                @endif
                            </div>

                            {{-- الحجوزات اليومية --}}
                            <div style="display:grid; gap:6px; flex-grow:1; align-content:start; max-height:220px; overflow-y:auto; padding-inline-end:2px;">
                                @forelse($day['reservations'] as $res)
                                    @php
                                        $badgeBg = match($res->status) {
                                            'active' => '#f0fdf4',
                                            'completed' => '#eff6ff',
                                            'cancelled' => '#fef2f2',
                                            default => '#f9fafb'
                                        };
                                        $badgeBorder = match($res->status) {
                                            'active' => '#bbf7d0',
                                            'completed' => '#bfdbfe',
                                            'cancelled' => '#fecaca',
                                            default => '#e5e7eb'
                                        };
                                        $badgeText = match($res->status) {
                                            'active' => '#15803d',
                                            'completed' => '#1d4ed8',
                                            'cancelled' => '#b91c1c',
                                            default => '#374151'
                                        };
                                        $startTimeFormatted = $res->start_time ? app(\App\Support\Tenant\TenantClock::class)->utcToLocal($res->start_time)->format('h:i A') : '';
                                        $endTimeFormatted = $res->end_time ? app(\App\Support\Tenant\TenantClock::class)->utcToLocal($res->end_time)->format('h:i A') : '';
                                    @endphp
                                    <a 
                                        href="{{ route('filament.admin.resources.tenant.reservations.edit', ['record' => $res->id]) }}"
                                        style="display:flex; flex-direction:column; gap:2px; text-decoration:none; padding:4px 6px; border-radius:6px; background:{{ $badgeBg }}; border:1px solid {{ $badgeBorder }}; color:{{ $badgeText }}; font-size:.66rem; font-weight:700; transition:all .15s;"
                                    >
                                        <div style="display:flex; align-items:center; gap:2px; direction:ltr; font-weight:800; color:inherit;">
                                            <span>{{ $startTimeFormatted }}</span>
                                            <span>-</span>
                                            <span>{{ $endTimeFormatted }}</span>
                                        </div>
                                        <div style="color:inherit; font-size:.6rem; opacity:.85; display:flex; justify-content:space-between; margin-top:2px;">
                                            <span>{{ $res->device?->name }}</span>
                                            <span>({{ $res->playPrice?->playType?->name ?? 'فردي' }})</span>
                                        </div>
                                    </a>
                                @empty
                                    <span style="font-size:.68rem; color:#9ca3af; text-align:center; margin:auto 0; display:block;"></span>
                                @endforelse
                            </div>

                        </div>
                    @endforeach
                </div>

            {{-- 3. عرض الشهر (الجدول المعتاد) --}}
            @else
                <div style="display:grid; grid-template-columns:repeat(7, 1fr); gap:1px; background:#e5e7eb; border-radius:12px; overflow:hidden; border:1px solid #e5e7eb;">
                    
                    {{-- أسماء الأيام --}}
                    @foreach(['السبت', 'الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة'] as $dayName)
                        <div style="background:#f9fafb; padding:12px 6px; text-align:center; font-weight:800; font-size:.8rem; color:#374151; border-bottom:1px solid #e5e7eb;">
                            {{ $dayName }}
                        </div>
                    @endforeach

                    {{-- أيام الشهر --}}
                    @foreach($this->getCalendarData() as $week)
                        @foreach($week as $day)
                            @if($day)
                                @php $isMonthDayPast = \Carbon\Carbon::parse($day['date'])->isBefore(today()); @endphp
                                <div style="background:#fff; min-height:120px; padding:8px; display:flex; flex-direction:column; justify-content:space-between; position:relative;">
                                    
                                    {{-- رأس اليوم --}}
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                        <span style="font-size:.95rem; font-weight:800; color:#111827;">
                                            {{ $day['day'] }}
                                        </span>
                                        
                                        @if(!$isMonthDayPast)
                                            <a 
                                                href="{{ route('filament.admin.resources.tenant.reservations.create', ['start_time' => $day['date'] . 'T12:00']) }}"
                                                title="إضافة حجز"
                                                style="width:22px; height:22px; border-radius:6px; background:#f3f4f6; display:flex; align-items:center; justify-content:center; text-decoration:none; color:#4b5563; font-weight:bold; font-size:.85rem; cursor:pointer;"
                                                onmouseover="this.style.background='#16a34a'; this.style.color='#fff';"
                                                onmouseout="this.style.background='#f3f4f6'; this.style.color='#4b5563';"
                                            >
                                                +
                                            </a>
                                        @endif
                                    </div>

                                    {{-- قائمة الحجوزات --}}
                                    <div style="display:grid; gap:4px; max-height:85px; overflow-y:auto; padding-inline-end:2px; flex-grow:1;">
                                        @forelse($day['reservations'] as $res)
                                            @php
                                                $badgeBg = match($res->status) {
                                                    'active' => '#f0fdf4',
                                                    'completed' => '#eff6ff',
                                                    'cancelled' => '#fef2f2',
                                                    default => '#f9fafb'
                                                };
                                                $badgeBorder = match($res->status) {
                                                    'active' => '#bbf7d0',
                                                    'completed' => '#bfdbfe',
                                                    'cancelled' => '#fecaca',
                                                    default => '#e5e7eb'
                                                };
                                                $badgeText = match($res->status) {
                                                    'active' => '#15803d',
                                                    'completed' => '#1d4ed8',
                                                    'cancelled' => '#b91c1c',
                                                    default => '#374151'
                                                };
                                                $startTimeFormatted = $res->start_time ? app(\App\Support\Tenant\TenantClock::class)->utcToLocal($res->start_time)->format('h:i A') : '';
                                                $endTimeFormatted = $res->end_time ? app(\App\Support\Tenant\TenantClock::class)->utcToLocal($res->end_time)->format('h:i A') : '';
                                            @endphp
                                            <a 
                                                href="{{ route('filament.admin.resources.tenant.reservations.edit', ['record' => $res->id]) }}"
                                                style="display:flex; flex-direction:column; gap:2px; text-decoration:none; padding:4px 6px; border-radius:6px; background:{{ $badgeBg }}; border:1px solid {{ $badgeBorder }}; color:{{ $badgeText }}; font-size:.66rem; font-weight:700; transition:all .15s;"
                                                onmouseover="this.style.transform='translateY(-1px)';"
                                                onmouseout="this.style.transform='none';"
                                            >
                                                <div style="display:flex; align-items:center; gap:2px; direction:ltr; font-weight:800; color:inherit;">
                                                    <span>{{ $startTimeFormatted }}</span>
                                                    <span>-</span>
                                                    <span>{{ $endTimeFormatted }}</span>
                                                </div>
                                                <div style="color:inherit; font-size:.6rem; opacity:.85; display:flex; justify-content:space-between; margin-top:2px;">
                                                    <span>{{ $res->device?->name }}</span>
                                                    <span>({{ $res->playPrice?->playType?->name ?? 'فردي' }})</span>
                                                </div>
                                            </a>
                                        @empty
                                            <span style="font-size:.68rem; color:#9ca3af; text-align:center; margin:auto 0; display:block;"></span>
                                        @endforelse
                                    </div>

                                </div>
                            @else
                                <div style="background:#f3f4f6; min-height:120px;"></div>
                            @endif
                        @endforeach
                    @endforeach

                </div>
            @endif

        </div>

        {{-- دليل الألوان --}}
        <div style="display:flex; gap:16px; margin-top:20px; font-size:.78rem; font-weight:700; color:#4b5563; border-top:1px solid #f3f4f6; padding-top:14px;">
            <div style="display:flex; align-items:center; gap:6px;">
                <span style="width:10px; height:10px; border-radius:3px; background:#f0fdf4; border:1px solid #bbf7d0; display:inline-block;"></span>
                حجز نشط
            </div>
            <div style="display:flex; align-items:center; gap:6px;">
                <span style="width:10px; height:10px; border-radius:3px; background:#eff6ff; border:1px solid #bfdbfe; display:inline-block;"></span>
                حجز مكتمل
            </div>
            <div style="display:flex; align-items:center; gap:6px;">
                <span style="width:10px; height:10px; border-radius:3px; background:#fef2f2; border:1px solid #fecaca; display:inline-block;"></span>
                حجز ملغي
            </div>
        </div>

    </div>
</x-filament-panels::page>
