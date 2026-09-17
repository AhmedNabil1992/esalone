@php
    $invoicePrinter = \App\Models\Tenant\SystemConfig::get('sys_invoice_printer', '');
    $orderPrinter = \App\Models\Tenant\SystemConfig::get('sys_order_printer', '');
    $bridgeUrl = config('services.bridge.url') ?: 'ws://127.0.0.1:9000';
@endphp

<script>
    document.addEventListener('livewire:init', () => {
        const invoicePrinter = @js($invoicePrinter);
        const orderPrinter = @js($orderPrinter);
        const bridgeUrl = @js($bridgeUrl);

        const printFallback = (sourceUrl) => {
            let url = sourceUrl;
            try {
                const parsedUrl = new URL(url, window.location.origin);
                parsedUrl.searchParams.set('fallback', '1');
                url = parsedUrl.toString();
            } catch (error) {
                console.error('Failed to append fallback parameter:', error);
            }

            const popup = window.open(url, '_blank', 'noopener,noreferrer');
            if (! popup) {
                alert('تم حظر نافذة الطباعة. يرجى السماح بالنوافذ المنبثقة لطباعة الفاتورة.');
            }
        };

        Livewire.on('open-print-window', (payload) => {
            const eventData = Array.isArray(payload) ? payload[0] : payload;
            const url = eventData && typeof eventData.url === 'string' ? eventData.url : '';
            if (! url) {
                return;
            }

            if (! url.includes('silent=1')) {
                window.open(url, '_blank', 'noopener,noreferrer');
                return;
            }

            let targetPrinter = url.includes('order') || url.includes('prepare') ? orderPrinter : invoicePrinter;
            try {
                targetPrinter = new URL(url, window.location.origin).searchParams.get('printer') || targetPrinter;
            } catch (error) {
                console.error('Failed to parse printer parameter:', error);
            }

            if (! bridgeUrl || ! targetPrinter) {
                printFallback(url);
                return;
            }

            fetch(url)
                .then((response) => response.text())
                .then((html) => {
                    const cleanedHtml = html.replace(/window\.print\(\);?/gi, '');
                    let socket;
                    let finished = false;
                    let timeout;

                    const fallback = () => {
                        if (finished) {
                            return;
                        }

                        finished = true;
                        clearTimeout(timeout);
                        try { socket?.close(); } catch (error) {}
                        printFallback(url);
                    };

                    timeout = setTimeout(fallback, 5000);

                    try {
                        socket = new WebSocket(bridgeUrl);
                        socket.onopen = () => socket.send(`PRINT|${targetPrinter}|${cleanedHtml}`);
                        socket.onerror = fallback;
                        socket.onmessage = (event) => {
                            clearTimeout(timeout);
                            try {
                                const response = JSON.parse(event.data);
                                if (response.status === 'success' || response.status === 'printed') {
                                    finished = true;
                                } else {
                                    fallback();
                                }
                            } catch (error) {
                                fallback();
                            }
                            try { socket.close(); } catch (error) {}
                        };
                    } catch (error) {
                        fallback();
                    }
                })
                .catch(() => printFallback(url));
        });
    });
</script>
