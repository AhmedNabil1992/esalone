@php
    $bridgeUrl = config('services.bridge.url') ?: 'ws://127.0.0.1:9000';
    $isControlActive = in_array((string) \App\Models\Tenant\SystemConfig::get('sys_control_active', '0'), ['1', 'true', 'on', 'yes'], true);
@endphp

@if ($isControlActive)
<script>
    document.addEventListener('livewire:init', () => {
        const bridgeUrl = @js($bridgeUrl);
        let bridgeWs = null;
        let reconnectTimer = null;
        let pingInterval = null;

        function updateBridgeStatusBadge(isOnline) {
            const badge = document.getElementById('bridge-status-indicator');
            if (!badge) return;

            if (isOnline) {
                badge.className = 'inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20';
                badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> الـ Bridge متصل';
            } else {
                badge.className = 'inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/20';
                badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-rose-500"></span> الـ Bridge غير متصل';
            }
        }

        function initBridgeWebSocket() {
            if (bridgeWs && (bridgeWs.readyState === WebSocket.OPEN || bridgeWs.readyState === WebSocket.CONNECTING)) {
                return;
            }

            try {
                bridgeWs = new WebSocket(bridgeUrl);

                bridgeWs.onopen = () => {
                    updateBridgeStatusBadge(true);
                    bridgeWs.send('PING_CHECK');

                    if (pingInterval) clearInterval(pingInterval);
                    pingInterval = setInterval(() => {
                        if (bridgeWs && bridgeWs.readyState === WebSocket.OPEN) {
                            bridgeWs.send('PING_CHECK');
                        }
                    }, 15000);
                };

                bridgeWs.onmessage = (event) => {
                    try {
                        const resp = JSON.parse(event.data);
                        if (resp.status === 'online') {
                            updateBridgeStatusBadge(true);
                        } else if (resp.status === 'success') {
                            console.log('✅ Bridge command success:', resp);
                        } else if (resp.status === 'failed' || resp.status === 'error') {
                            console.warn('⚠️ Bridge command failed:', resp.message);
                        }
                    } catch (e) {
                        console.log('Bridge response:', event.data);
                    }
                };

                bridgeWs.onerror = () => {
                    updateBridgeStatusBadge(false);
                };

                bridgeWs.onclose = () => {
                    updateBridgeStatusBadge(false);
                    if (pingInterval) clearInterval(pingInterval);
                    if (reconnectTimer) clearTimeout(reconnectTimer);
                    reconnectTimer = setTimeout(initBridgeWebSocket, 3000);
                };
            } catch (e) {
                updateBridgeStatusBadge(false);
                if (reconnectTimer) clearTimeout(reconnectTimer);
                reconnectTimer = setTimeout(initBridgeWebSocket, 3000);
            }
        }

        initBridgeWebSocket();

        // استقبال أمر التحكم من السيرفر وإرساله عبر الـ WebSocket المحلي
        Livewire.on('send-bridge-device-command', (payload) => {
            const data = Array.isArray(payload) ? payload[0] : payload;
            if (!data || !data.ip || !data.command) return;

            const ip = data.ip;
            const command = data.command;
            const deviceName = data.deviceName || 'Device';
            const message = `${ip},${command},${deviceName}`;

            if (bridgeWs && bridgeWs.readyState === WebSocket.OPEN) {
                bridgeWs.send(message);
                console.log('🚀 تم إرسال أمر التحكم للـ Bridge:', message);
            } else {
                console.warn('Bridge offline, trying fallback connection for message:', message);
                try {
                    const fallbackWs = new WebSocket(bridgeUrl);
                    fallbackWs.onopen = () => {
                        fallbackWs.send(message);
                        setTimeout(() => { try { fallbackWs.close(); } catch(e){} }, 2000);
                    };
                    fallbackWs.onerror = () => {
                        console.error('Failed to send command to offline bridge:', message);
                    };
                } catch (e) {
                    console.error('Failed fallback connection:', e);
                }
            }
        });
    });
</script>
@endif
