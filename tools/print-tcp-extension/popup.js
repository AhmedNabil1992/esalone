const DEFAULTS = {
    bridgeUrl: "http://127.0.0.1:17325",
    token: "change-me",
    printEnabled: true,
    tcpEnabled: true,
    tcpHost: "127.0.0.1",
    tcpPort: 9100,
    tcpPayload: "",
};

const $ = (id) => document.getElementById(id);

function setStatus(text) {
    $("status").textContent = text;
}

async function loadSettings() {
    const settings = await chrome.storage.sync.get(DEFAULTS);
    $("bridgeUrl").value = settings.bridgeUrl;
    $("token").value = settings.token;
    $("printEnabled").checked = Boolean(settings.printEnabled);
    $("tcpEnabled").checked = Boolean(settings.tcpEnabled);
    $("tcpHost").value = settings.tcpHost;
    $("tcpPort").value = settings.tcpPort;
    $("tcpPayload").value = settings.tcpPayload;
}

async function saveSettings() {
    const settings = {
        bridgeUrl: $("bridgeUrl").value.trim(),
        token: $("token").value.trim(),
        printEnabled: $("printEnabled").checked,
        tcpEnabled: $("tcpEnabled").checked,
        tcpHost: $("tcpHost").value.trim(),
        tcpPort: Number($("tcpPort").value || 0),
        tcpPayload: $("tcpPayload").value,
    };

    await chrome.storage.sync.set(settings);
    setStatus("Saved.");
}

async function sendTcpTest() {
    await saveSettings();

    const response = await chrome.runtime.sendMessage({
        type: "SEND_TCP",
        host: $("tcpHost").value.trim(),
        port: Number($("tcpPort").value || 0),
        payload: $("tcpPayload").value,
        encoding: "utf8",
    });

    if (!response?.ok) {
        setStatus(`TCP failed: ${response?.error || "unknown"}`);
        return;
    }

    setStatus("TCP sent successfully.");
}

$("saveBtn").addEventListener("click", () => {
    saveSettings().catch((error) => setStatus(String(error)));
});

$("sendTcpBtn").addEventListener("click", () => {
    sendTcpTest().catch((error) => setStatus(String(error)));
});

loadSettings().catch((error) => setStatus(String(error)));
