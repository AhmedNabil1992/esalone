import Echo from "laravel-echo";

import Pusher from "pusher-js";
window.Pusher = Pusher;

const reverbHost =
    import.meta.env.VITE_REVERB_HOST &&
    import.meta.env.VITE_REVERB_HOST !== "127.0.0.1" &&
    import.meta.env.VITE_REVERB_HOST !== "localhost"
        ? import.meta.env.VITE_REVERB_HOST
        : window.location.hostname;
const reverbScheme =
    window.location.protocol === "https:"
        ? "https"
        : (import.meta.env.VITE_REVERB_SCHEME ?? "http");
const websocketPort =
    window.location.protocol === "https:"
        ? 443
        : Number(import.meta.env.VITE_REVERB_PORT ?? 8080);

// window.Echo = new Echo({
//     broadcaster: "reverb",
//     key: import.meta.env.VITE_REVERB_APP_KEY,
//     wsHost: reverbHost,
//     wsPort: websocketPort,
//     wssPort: websocketPort,
//     forceTLS: reverbScheme === "https",
//     encrypted: reverbScheme === "https",
//     enabledTransports: ["ws", "wss"],
// });

window.Echo = new Echo({
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: reverbHost,
    wsPort: websocketPort,
    wssPort: websocketPort,
    forceTLS: reverbScheme === "https",
    encrypted: reverbScheme === "https",
    enabledTransports: ["ws", "wss"],
    // 💥 الإضافة السحرية: إرفاق التوكن والـ Credentials للسماح بالمصادقة
    auth: {
        headers: {
            "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content"),
        },
    },
    withCredentials: true,
});
