# PS Print + TCP Extension (Chrome/Edge)

This extension sends receipt pages to a local bridge for silent printing and can send TCP payloads to local network devices.

## Why this architecture

- Browser extensions cannot do true silent printing by themselves in normal mode.
- Browser extensions also cannot open arbitrary raw TCP sockets directly in MV3.
- So we use a local bridge service on your machine.

## 1) Run the local bridge

Open terminal in `tools/print-tcp-bridge`:

```bash
npm install
npx playwright install chromium
set BRIDGE_TOKEN=your-strong-token
npm start
```

Or from project root:

```bash
npm run bridge:setup
npm run bridge:start
```

Bridge health test:

`http://127.0.0.1:17325/health`

## 2) Load extension

1. Open Chrome/Edge extensions page.
2. Enable Developer Mode.
3. Load unpacked extension from `tools/print-tcp-extension`.
4. Open extension popup and set:

- Bridge URL: `http://127.0.0.1:17325`
- Token: same as `BRIDGE_TOKEN`

## 3) Behavior

- On invoice print page URLs (`/tenant/invoices/{id}/print`), extension sends HTML to bridge and bridge prints silently using default printer.
- Use popup TCP section to send test TCP payloads to local IP:port.

## 4) IoT UDP for ESP8266-01

The bridge now exposes a UDP endpoint compatible with your old desktop logic:

- Endpoint: `POST /iot/udp-send`
- Body:

```json
{
    "token": "your-strong-token",
    "ipAddress": "192.168.1.80",
    "deviceName": "Room-01",
    "commandName": "11",
    "udpPort": 4210,
    "ping": true
}
```

It performs a ping first, then sends ASCII command over UDP to port 4210.

## Notes

- Keep bridge running on cashier machine.
- Use firewall rules to block external access if needed.
- This is intended for trusted internal network only.
