import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
const configuredHost = String(import.meta.env.VITE_REVERB_HOST ?? '').replace(/^['"]|['"]$/g, '').trim();
const configuredPort = Number(import.meta.env.VITE_REVERB_PORT ?? 0);
const browserHost = window.location.hostname;
const browserScheme = window.location.protocol === 'https:' ? 'https' : 'http';
const configuredScheme = String(import.meta.env.VITE_REVERB_SCHEME ?? browserScheme).replace(/^['"]|['"]$/g, '').trim() || browserScheme;
const isLocalHost = (value) => ['localhost', '127.0.0.1', '::1', '[::1]'].includes(String(value || '').toLowerCase());
const resolvedHost = (() => {
    if (!configuredHost) {
        return browserHost;
    }

    if (browserHost && !isLocalHost(browserHost) && isLocalHost(configuredHost)) {
        return browserHost;
    }

    return configuredHost;
})();
const useTls = configuredScheme === 'https';
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

if (reverbKey) {
    window.Pusher = Pusher;

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: resolvedHost,
        wsPort: configuredPort || 80,
        wssPort: configuredPort || 443,
        forceTLS: useTls,
        enabledTransports: ['ws', 'wss'],
        withCredentials: true,
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        },
    });
} else {
    window.Echo = null;
}
