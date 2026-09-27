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

            @php
                $isControlActive = in_array((string) \App\Models\Tenant\SystemConfig::get('sys_control_active', '0'), ['1', 'true', 'on', 'yes'], true);
                $usesMqtt = \App\Models\Tenant\SystemConfig::get('sys_iot_connection_type', 'bridge') === 'mqtt';
            @endphp
            @if ($isControlActive)
                <div id="bridge-status-indicator" @if (! $usesMqtt) wire:ignore @endif class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full {{ $usesMqtt && ! $this->bridgeUnavailable ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : ($usesMqtt ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : 'bg-amber-500/10 text-amber-500 border border-amber-500/20') }}">
                    <span class="w-2 h-2 rounded-full {{ $usesMqtt && ! $this->bridgeUnavailable ? 'bg-emerald-500 animate-pulse' : ($usesMqtt ? 'bg-rose-500' : 'bg-amber-500 animate-pulse') }}"></span>
                    {{ $usesMqtt ? ($this->bridgeUnavailable ? 'MQTT غير متصل' : 'MQTT متصل') : 'جاري فحص الـ Bridge...' }}
                </div>
            @endif
        </div>
        @endif
