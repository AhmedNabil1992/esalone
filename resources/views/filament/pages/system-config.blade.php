<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-start gap-3">
            <x-filament::button type="submit" size="lg" icon="heroicon-m-device-phone-mobile">
                حفظ الإعدادات بالكامل
            </x-filament::button>
        </div>
    </form>
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('bridge:get-printers', () => {
                let ws;
                try {
                    ws = new WebSocket('ws://127.0.0.1:9000');
                } catch (e) {
                    @this.handleBridgeError('تعذر فتح اتصال بالـ Bridge عبر المتصفح.');
                    return;
                }

                let isHandled = false;
                const timeoutId = setTimeout(() => {
                    if (!isHandled) {
                        isHandled = true;
                        try { ws.close(); } catch(e){}
                        @this.handleBridgeError('لم يتم استلام رد من الـ Bridge (تأكد من تشغيل برنامج BridgeTest على جهازك).');
                    }
                }, 4000);

                ws.onopen = () => {
                    ws.send('GET_PRINTERS');
                };

                ws.onmessage = (event) => {
                    if (isHandled) return;
                    isHandled = true;
                    clearTimeout(timeoutId);

                    try {
                        const data = JSON.parse(event.data);
                        if (data.status === 'printers' && Array.isArray(data.printers)) {
                            @this.savePrintersList(data.printers);
                        } else {
                            @this.handleBridgeError(data.message || 'رد غير صالح من البريدج');
                        }
                    } catch (e) {
                        @this.handleBridgeError('فشل قراءة بيانات الطابعات من البريدج.');
                    }
                    try { ws.close(); } catch(e){}
                };

                ws.onerror = () => {
                    if (isHandled) return;
                    isHandled = true;
                    clearTimeout(timeoutId);
                    @this.handleBridgeError('الـ Bridge غير متصل. يرجى التأكد من تشغيل برنامج BridgeTest على هذا الجهاز.');
                };
            });
        });
    </script>
</x-filament-panels::page>