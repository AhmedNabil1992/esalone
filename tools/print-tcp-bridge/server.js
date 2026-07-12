import express from "express";
import dgram from "node:dgram";
import net from "node:net";
import os from "node:os";
import path from "node:path";
import fs from "node:fs/promises";
import { execFile } from "node:child_process";
import { promisify } from "node:util";
import { chromium } from "playwright";
import pdfToPrinter from "pdf-to-printer";

const { print: printPdf } = pdfToPrinter;

const app = express();
app.use(express.json({ limit: "20mb" }));

const PORT = process.env.BRIDGE_PORT || 17325;
const TOKEN = process.env.BRIDGE_TOKEN || "change-me";
const PAPER_WIDTH_MM = Number(process.env.PRINT_PAPER_WIDTH_MM || 75);
const PAPER_MAX_HEIGHT_MM = Number(process.env.PRINT_MAX_HEIGHT_MM || 420);
const execFileAsync = promisify(execFile);

async function pingHost(host) {
    const isWindows = process.platform === "win32";
    const args = isWindows
        ? ["-n", "1", "-w", "1200", host]
        : ["-c", "1", "-W", "1", host];

    try {
        await execFileAsync("ping", args);
        return true;
    } catch {
        return false;
    }
}

function assertToken(req, res) {
    if ((req.body?.token || "") !== TOKEN) {
        res.status(401).json({ ok: false, error: "invalid token" });
        return false;
    }
    return true;
}

app.get("/health", (_req, res) => {
    res.json({ ok: true, service: "print-tcp-bridge" });
});

app.post("/print", async (req, res) => {
    if (!assertToken(req, res)) {
        return;
    }

    const html = String(req.body?.html || "");
    const title = String(req.body?.title || "receipt");

    if (!html) {
        res.status(400).json({ ok: false, error: "html is required" });
        return;
    }

    const tmpDir = os.tmpdir();
    const stamp = Date.now();
    const safeTitle =
        title.replace(/[^a-zA-Z0-9-_]/g, "_").slice(0, 40) || "receipt";
    const pdfPath = path.join(tmpDir, `${safeTitle}_${stamp}.pdf`);

    let browser;
    try {
        browser = await chromium.launch({ headless: true });
        const page = await browser.newPage({
            viewport: { width: 1200, height: 2000 },
        });
        await page.setContent(html, { waitUntil: "networkidle" });

        const contentHeightPx = await page.evaluate(() => {
            const body = document.body;
            const doc = document.documentElement;

            return Math.max(
                body?.scrollHeight || 0,
                body?.offsetHeight || 0,
                doc?.scrollHeight || 0,
                doc?.offsetHeight || 0,
            );
        });

        const pxToMm = 25.4 / 96;
        const contentHeightMm = Math.ceil(contentHeightPx * pxToMm);
        const pdfHeightMm = Math.max(
            80,
            Math.min(PAPER_MAX_HEIGHT_MM, contentHeightMm + 6),
        );

        await page.pdf({
            path: pdfPath,
            width: `${PAPER_WIDTH_MM}mm`,
            height: `${pdfHeightMm}mm`,
            printBackground: true,
            preferCSSPageSize: false,
            margin: { top: "0mm", right: "0mm", bottom: "0mm", left: "0mm" },
        });

        await printPdf(pdfPath, {
            orientation: "portrait",
            scale: "noscale",
        });

        res.json({ ok: true, printed: true, file: pdfPath });
    } catch (error) {
        res.status(500).json({
            ok: false,
            error: String(error?.message || error),
        });
    } finally {
        if (browser) {
            await browser.close().catch(() => {});
        }
        // Keep small delay before delete to avoid spooler race conditions.
        setTimeout(() => {
            fs.unlink(pdfPath).catch(() => {});
        }, 7000);
    }
});

app.post("/screenshot", async (req, res) => {
    if (!assertToken(req, res)) {
        return;
    }

    const html = String(req.body?.html || "");

    if (!html) {
        res.status(400).json({ ok: false, error: "html is required" });
        return;
    }

    let browser;
    try {
        browser = await chromium.launch({ headless: true });
        const page = await browser.newPage({
            viewport: { width: 380, height: 800 },
        });
        await page.setContent(html, { waitUntil: "networkidle" });
        await page.waitForTimeout(300);

        const contentHeightPx = await page.evaluate(() => {
            return Math.max(
                document.body?.scrollHeight || 0,
                document.documentElement?.scrollHeight || 0,
                document.body?.offsetHeight || 0
            );
        });

        await page.setViewportSize({ width: 380, height: contentHeightPx + 10 });

        const screenshotBuffer = await page.screenshot({
            type: "png",
            fullPage: true,
        });

        const base64 = screenshotBuffer.toString("base64");

        res.json({ ok: true, screenshot: base64 });
    } catch (error) {
        res.status(500).json({
            ok: false,
            error: String(error?.message || error),
        });
    } finally {
        if (browser) {
            await browser.close().catch(() => {});
        }
    }
});

app.post("/tcp-send", async (req, res) => {
    if (!assertToken(req, res)) {
        return;
    }

    const host = String(req.body?.host || "").trim();
    const port = Number(req.body?.port || 0);
    const payload = String(req.body?.payload || "");
    const encoding = String(req.body?.encoding || "utf8");

    if (!host || !Number.isInteger(port) || port <= 0 || port > 65535) {
        res.status(400).json({ ok: false, error: "host/port invalid" });
        return;
    }

    const socket = new net.Socket();

    const done = (ok, error = "") => {
        if (!socket.destroyed) {
            socket.destroy();
        }
        if (ok) {
            res.json({ ok: true, sent: true });
        } else {
            res.status(500).json({ ok: false, error });
        }
    };

    socket.setTimeout(4000);

    socket.on("connect", () => {
        socket.write(payload, encoding, () => {
            done(true);
        });
    });

    socket.on("timeout", () => done(false, "tcp timeout"));
    socket.on("error", (err) => done(false, String(err?.message || err)));

    socket.connect(port, host);
});

app.post("/iot/udp-send", async (req, res) => {
    if (!assertToken(req, res)) {
        return;
    }

    const ipAddress = String(req.body?.ipAddress || "").trim();
    const deviceName = String(req.body?.deviceName || "").trim();
    const commandName = String(req.body?.commandName || "").trim();
    const udpPort = Number(req.body?.udpPort || 4210);
    const shouldPing = Boolean(req.body?.ping ?? true);

    if (!ipAddress) {
        res.status(400).json({ ok: false, error: "ipAddress is required" });
        return;
    }

    if (!commandName) {
        res.status(400).json({ ok: false, error: "commandName is required" });
        return;
    }

    if (!Number.isInteger(udpPort) || udpPort <= 0 || udpPort > 65535) {
        res.status(400).json({ ok: false, error: "udpPort invalid" });
        return;
    }

    if (shouldPing) {
        const pingOk = await pingHost(ipAddress);
        if (!pingOk) {
            res.status(503).json({
                ok: false,
                error: `يرجي التأكد من توصيل جهاز ${deviceName || ipAddress}`,
            });
            return;
        }
    }

    const message = Buffer.from(commandName, "ascii");
    const socket = dgram.createSocket("udp4");

    socket.send(message, udpPort, ipAddress, (error) => {
        socket.close();

        if (error) {
            res.status(500).json({
                ok: false,
                error: String(error?.message || error),
            });
            return;
        }

        res.json({
            ok: true,
            sent: true,
            protocol: "udp",
            ipAddress,
            udpPort,
            commandName,
        });
    });
});

app.listen(PORT, () => {
    console.log(`print-tcp-bridge listening on http://127.0.0.1:${PORT}`);
});
