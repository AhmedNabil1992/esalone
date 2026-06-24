import Echo from "laravel-echo";

import Pusher from "pusher-js";
window.Pusher = Pusher;

const reverbHost = import.meta.env.VITE_REVERB_HOST ?? window.location.hostname;
const reverbScheme =
    import.meta.env.VITE_REVERB_SCHEME ??
    (window.location.protocol === "https:" ? "https" : "http");
const websocketPort = Number(
    import.meta.env.VITE_REVERB_PORT ?? (reverbScheme === "https" ? 443 : 80),
);

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
