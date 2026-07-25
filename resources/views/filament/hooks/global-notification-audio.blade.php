<script>
    document.addEventListener('DOMContentLoaded', () => {
        // 1. Request Browser Notification permissions on load if default
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }

        let previousUnreadCount = null;

        // 2. Audio Alert Synthesizer (Beep Tone)
        window.playGlobalAlertSound = function () {
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) return;

                const audioCtx = new AudioContext();
                if (audioCtx.state === 'suspended') {
                    audioCtx.resume();
                }

                const now = audioCtx.currentTime;

                // High-pitched warning double beep
                const playBeep = (freq, startTime, duration) => {
                    const osc = audioCtx.createOscillator();
                    const gain = audioCtx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(freq, startTime);
                    gain.gain.setValueAtTime(0.5, startTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, startTime + duration);
                    osc.connect(gain);
                    gain.connect(audioCtx.destination);
                    osc.start(startTime);
                    osc.stop(startTime + duration);
                };

                playBeep(880, now, 0.18);       // A5 tone 1
                playBeep(1046.50, now + 0.22, 0.25); // C6 tone 2
            } catch (err) {
                console.warn('Global alert audio playback failed:', err);
            }
        };

        // 3. Speech Alert Synthesizer (Text-To-Speech for alerts)
        window.speakGlobalAlert = function (text) {
            if ('speechSynthesis' in window && text) {
                try {
                    window.speechSynthesis.cancel();
                    const cleanText = text.replace(/[\*\_]/g, '');
                    const utterance = new SpeechSynthesisUtterance(cleanText);
                    utterance.lang = 'ar-SA';
                    utterance.rate = 0.95;
                    window.speechSynthesis.speak(utterance);
                } catch (e) {
                    console.warn('Speech alert failed:', e);
                }
            }
        };

        // 4. Function to check unread notification count badge in Filament
        function checkUnreadNotifications() {
            const badge = document.querySelector('.fi-topbar-database-notifications-btn .fi-icon-btn-badge, [data-unread-notifications-count]');
            if (badge) {
                const currentCount = parseInt(badge.textContent.trim()) || 0;
                if (previousUnreadCount !== null && currentCount > previousUnreadCount) {
                    window.playGlobalAlertSound();

                    // Read out recent notification text if available
                    const firstNotificationBody = document.querySelector('.fi-dropdown-list-item-description, .fi-no-notification-body');
                    if (firstNotificationBody && firstNotificationBody.textContent) {
                        window.speakGlobalAlert(firstNotificationBody.textContent.trim());
                    }
                }
                previousUnreadCount = currentCount;
            } else if (previousUnreadCount === null) {
                const initialCount = badge ? (parseInt(badge.textContent.trim()) || 0) : 0;
                previousUnreadCount = initialCount;
            }
        }

        // 5. Listen for Livewire commit/update events (Filament Database Notifications Polling)
        if (window.Livewire) {
            Livewire.hook('commit', ({ component, succeed }) => {
                succeed(() => {
                    if (component.name === 'filament.core.database-notifications' || component.name?.includes('database-notifications')) {
                        setTimeout(checkUnreadNotifications, 200);
                    }
                });
            });
        }

        // 6. Listen for Filament Notification Events
        window.addEventListener('filament-notification-sent', (event) => {
            window.playGlobalAlertSound();
            if (event?.detail?.title || event?.detail?.body) {
                const text = (event.detail.title || '') + ' ' + (event.detail.body || '');
                window.speakGlobalAlert(text);
            }
        });

        window.addEventListener('database-notifications.updated', () => {
            setTimeout(checkUnreadNotifications, 200);
        });

        // 7. MutationObserver as a reliable fallback for header notification badge updates
        const observer = new MutationObserver(() => {
            checkUnreadNotifications();
        });

        observer.observe(document.body, { childList: true, subtree: true });
    });
</script>
