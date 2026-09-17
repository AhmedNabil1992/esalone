        {{-- ===== شريط الإحصائيات ===== --}}
        @php
            $busyCount   = $allDevices->where('status', 'busy')->count();
            $availCount  = $allDevices->where('status', 'active')->count();
            $total       = $allDevices->count();
        @endphp
        @if ($allDevices->isNotEmpty())
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div style="display:flex;align-items:center;gap:8px;">
                <span style="background:#22c55e;color:#fff;font-size:.72rem;font-weight:700;padding:3px 12px;border-radius:9999px;">
                    متاح {{ $availCount }}
                </span>
                <span style="background:#ef4444;color:#fff;font-size:.72rem;font-weight:700;padding:3px 12px;border-radius:9999px;">
                    مشغول {{ $busyCount }}
                </span>
                <span style="color:#6b7280;font-size:.75rem;">
                    / {{ $total }} جهاز
                </span>
            </div>

            <!-- <span style="font-size:.7rem;color:#9ca3af;display:flex;align-items:center;gap:4px;">
                <svg style="width:12px;height:12px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M4 9a8 8 0 0 1 16 0M20 15a8 8 0 0 1-16 0"/>
                </svg>
                تحديث لحظي + احتياطي كل 30 ث
            </span> -->
        </div>
        @endif

