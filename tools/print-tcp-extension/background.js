const DEFAULTS = {
    bridgeUrl: "http://127.0.0.1:17325",
    token: "change-me",
    printEnabled: true,
    tcpEnabled: true,
};

async function getSettings() {
    const saved = await chrome.storage.sync.get(DEFAULTS);
    return { ...DEFAULTS, ...saved };
}

async function postJson(url, body) {
    const response = await fetch(url, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify(body),
    });

    if (!response.ok) {
        const text = await response.text();
        throw new Error(`Bridge error ${response.status}: ${text}`);
    }

    return response.json().catch(() => ({}));
}

chrome.runtime.onMessage.addListener((message, _sender, sendResponse) => {
    (async () => {
        const settings = await getSettings();

        if (message?.type === "PRINT_RECEIPT") {
            if (!settings.printEnabled) {
                sendResponse({
                    ok: true,
                    skipped: true,
                    reason: "print disabled",
                });
                return;
            }

            const endpoint = `${settings.bridgeUrl}/print`;
            const result = await postJson(endpoint, {
                token: settings.token,
                title: message.title || "Receipt",
                url: message.url || "",
                html: message.html || "",
            });

            sendResponse({ ok: true, result });
            return;
        }

        if (message?.type === "SEND_TCP") {
            if (!settings.tcpEnabled) {
                sendResponse({
                    ok: true,
                    skipped: true,
                    reason: "tcp disabled",
                });
                return;
            }

            const endpoint = `${settings.bridgeUrl}/tcp-send`;
            const result = await postJson(endpoint, {
                token: settings.token,
                host: message.host,
                port: Number(message.port),
                payload: message.payload || "",
                encoding: message.encoding || "utf8",
            });

            sendResponse({ ok: true, result });
            return;
        }

        sendResponse({ ok: false, error: "Unknown message type" });
    })().catch((error) => {
        sendResponse({ ok: false, error: String(error?.message || error) });
    });

    return true;
});
