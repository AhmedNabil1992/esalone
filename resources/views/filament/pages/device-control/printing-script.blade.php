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
