@php
    $bridgeUrl = config('services.bridge.url') ?: 'ws://127.0.0.1:9000';
    $isControlActive = in_array((string) \App\Models\Tenant\SystemConfig::get('sys_control_active', '0'), ['1', 'true', 'on', 'yes'], true);
@endphp

@if ($isControlActive)
<script>
    (function() {
        const bridgeUrl = @js($bridgeUrl);

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

        function ensureBridgeWebSocket() {
            if (window.__bridgeWs && (window.__bridgeWs.readyState === WebSocket.OPEN || window.__bridgeWs.readyState === WebSocket.CONNECTING)) {
                if (window.__bridgeWs.readyState === WebSocket.OPEN) {
                    updateBridgeStatusBadge(true);
                }
                return;
            }

            try {
                window.__bridgeWs = new WebSocket(bridgeUrl);

                window.__bridgeWs.onopen = () => {
                    updateBridgeStatusBadge(true);
                    window.__bridgeWs.send('PING_CHECK');

                    if (window.__bridgePingInterval) clearInterval(window.__bridgePingInterval);
                    window.__bridgePingInterval = setInterval(() => {
                        if (window.__bridgeWs && window.__bridgeWs.readyState === WebSocket.OPEN) {
                            window.__bridgeWs.send('PING_CHECK');
                        }
                    }, 10000);
                };

                window.__bridgeWs.onmessage = (event) => {
                    try {
                        const resp = JSON.parse(event.data);
                        if (resp.status === 'online' || resp.status === 'success') {
                            updateBridgeStatusBadge(true);
                        }
                    } catch (e) {}
                };

                window.__bridgeWs.onerror = () => {
                    updateBridgeStatusBadge(false);
                };

                window.__bridgeWs.onclose = () => {
                    updateBridgeStatusBadge(false);
                    if (window.__bridgePingInterval) clearInterval(window.__bridgePingInterval);
                    if (window.__bridgeReconnectTimer) clearTimeout(window.__bridgeReconnectTimer);
                    window.__bridgeReconnectTimer = setTimeout(ensureBridgeWebSocket, 2000);
                };
            } catch (e) {
                updateBridgeStatusBadge(false);
                if (window.__bridgeReconnectTimer) clearTimeout(window.__bridgeReconnectTimer);
                window.__bridgeReconnectTimer = setTimeout(ensureBridgeWebSocket, 2000);
            }
        }

        // تشغيل الاتصال المستمر فوراً
        ensureBridgeWebSocket();

        // تسجيل مستمع أحداث Livewire مرة واحدة على مستوى النافذة لمنع التكرار مع كل تحديث
        if (!window.__bridgeCommandListenerBound) {
            window.__bridgeCommandListenerBound = true;

            document.addEventListener('livewire:init', () => {
                Livewire.on('send-bridge-device-command', (payload) => {
                    const data = Array.isArray(payload) ? payload[0] : payload;
                    if (!data || !data.ip || !data.command) return;

                    const ip = data.ip;
                    const command = data.command;
                    const deviceName = data.deviceName || 'Device';
                    const message = `${ip},${command},${deviceName}`;

                    if (window.__bridgeWs && window.__bridgeWs.readyState === WebSocket.OPEN) {
                        window.__bridgeWs.send(message);
                        console.log('🚀 تم إرسال أمر التحكم للـ Bridge فوراً:', message);
                    } else {
                        console.warn('Bridge offline, trying to connect and send:', message);
                        ensureBridgeWebSocket();
                        const checkAndSend = setInterval(() => {
                            if (window.__bridgeWs && window.__bridgeWs.readyState === WebSocket.OPEN) {
                                window.__bridgeWs.send(message);
                                clearInterval(checkAndSend);
                                console.log('🚀 تم إرسال أمر التحكم للـ Bridge بعد الاتصال:', message);
                            }
                        }, 100);
                        setTimeout(() => clearInterval(checkAndSend), 3000);
                    }
                });
            });
        }
    })();
</script>
@endif
