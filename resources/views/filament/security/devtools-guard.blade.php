<script>
(() => {
    if (window.__psWebDevtoolsGuardLoaded) {
        return;
    }

    window.__psWebDevtoolsGuardLoaded = true;

    const DEVTOOLS_BLOCK_CDN = 'https://cdn.jsdelivr.net/gh/fastuptime/devtools-block@main/script.js';

    const blockShortcuts = (event) => {
        const key = String(event.key || '').toLowerCase();
        const ctrlOrMeta = event.ctrlKey || event.metaKey;

        const blocked =
            key === 'f12' ||
            (ctrlOrMeta && event.shiftKey && ['i', 'j', 'c', 'k'].includes(key)) ||
            (ctrlOrMeta && ['u'].includes(key));

        if (blocked) {
            event.preventDefault();
            event.stopPropagation();
        }
    };

    const lockInterface = () => {
        if (document.getElementById('ps-web-security-overlay')) {
            return;
        }

        const overlay = document.createElement('div');
        overlay.id = 'ps-web-security-overlay';
        overlay.style.position = 'fixed';
        overlay.style.inset = '0';
        overlay.style.zIndex = '2147483647';
        overlay.style.background = '#0b1220';
        overlay.style.color = '#ffffff';
        overlay.style.display = 'flex';
        overlay.style.alignItems = 'center';
        overlay.style.justifyContent = 'center';
        overlay.style.textAlign = 'center';
        overlay.style.padding = '24px';
        overlay.innerHTML = '<div><h2 style="margin:0 0 10px;font-size:22px;">تم إيقاف الواجهة لأسباب أمنية</h2><p style="margin:0;font-size:14px;opacity:.9;">يرجى إغلاق أدوات المطور وإعادة تحميل الصفحة.</p></div>';

        document.body.appendChild(overlay);
        document.body.style.pointerEvents = 'none';
        overlay.style.pointerEvents = 'auto';
    };

    document.addEventListener('keydown', blockShortcuts, true);
    document.addEventListener('contextmenu', (event) => {
        event.preventDefault();
    }, true);

    const startFallbackDetector = () => {
        setInterval(() => {
            const widthGap = window.outerWidth - window.innerWidth;
            const heightGap = window.outerHeight - window.innerHeight;
            const devtoolsLikelyOpen = widthGap > 160 || heightGap > 160;

            if (devtoolsLikelyOpen) {
                lockInterface();
            }
        }, 1000);
    };

    const startDevtoolsBlock = () => {
        if (typeof window.DisableDevtool !== 'function') {
            startFallbackDetector();
            return;
        }

        try {
            const blocker = new window.DisableDevtool({
                interval: 700,
                disableMenu: false,
                disableSelect: false,
                clearLog: true,
                seoBot: true,
                redirectUrl: 'about:blank',
                rewriteHTML: '<h1>🚫 Access Denied</h1><p>Developer tools are not allowed!</p>',

                onDevToolOpen: () => {
                    lockInterface();
                },
                detectors: ['regToString', 'defineId', 'windowSize', 'debugger'],
            });

            blocker.start();
        } catch (error) {
            startFallbackDetector();
        }
    };

    const script = document.createElement('script');
    script.src = DEVTOOLS_BLOCK_CDN;
    script.async = true;
    script.onload = startDevtoolsBlock;
    script.onerror = startFallbackDetector;
    document.head.appendChild(script);
})();
</script>
