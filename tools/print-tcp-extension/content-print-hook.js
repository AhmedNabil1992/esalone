(function () {
    // Runs only on invoice print pages per manifest match pattern.
    const html = document.documentElement.outerHTML;
    const title = document.title || "Receipt";
    const url = window.location.href;
    const params = new URLSearchParams(window.location.search);
    const silentMode = params.get("silent") === "1";
    const root = document.documentElement;

    function fallbackToBrowserPrint() {
        if (silentMode) {
            return;
        }

        try {
            window.print();
        } catch (error) {
            console.warn("Browser print fallback failed:", error);
        }
    }

    root.setAttribute("data-bridge-print", "pending");

    chrome.runtime.sendMessage(
        {
            type: "PRINT_RECEIPT",
            html,
            title,
            url,
        },
        (response) => {
            if (chrome.runtime.lastError) {
                console.warn(
                    "Bridge print message failed:",
                    chrome.runtime.lastError.message,
                );
                root.setAttribute("data-bridge-print", "failed");
                fallbackToBrowserPrint();
                return;
            }

            if (!response?.ok) {
                console.warn(
                    "Bridge print response error:",
                    response?.error || response,
                );
                root.setAttribute("data-bridge-print", "failed");
                fallbackToBrowserPrint();
                return;
            }

            if (response?.skipped) {
                root.setAttribute("data-bridge-print", "skipped");
                fallbackToBrowserPrint();
                return;
            }

            root.setAttribute("data-bridge-print", "success");

            if (silentMode) {
                if (window.top !== window.self && window.frameElement) {
                    // Remove hidden iframe host when silent print succeeds.
                    window.frameElement.remove();
                } else {
                    // Close auto-opened print tab after successful bridge print.
                    window.close();
                }
            }
        },
    );
})();
