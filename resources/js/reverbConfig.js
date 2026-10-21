const LOCAL_HOSTS = ['localhost', '127.0.0.1', '[::1]', '0.0.0.0', ''];

/**
 * Builds the Echo/Reverb connection options so the WebSocket always targets a
 * host the visitor's browser can reach (desktop and mobile alike).
 *
 * A build-time host that points at the developer machine (localhost, 127.0.0.1)
 * would make the visitor's browser connect to *their own device*, which triggers
 * Chrome's "access other apps and services on this device" prompt on phones.
 * In that case, and whenever the page is served over https, we connect through
 * the page's own origin (wss on 443, proxied to Reverb by the web server).
 */
export function reverbConfig() {
    const env = import.meta.env;
    const page = window.location;
    const pageIsLocal = LOCAL_HOSTS.includes(page.hostname) || /\.(loc|local|test|localhost)$/.test(page.hostname) || /^(10|192\.168|172\.(1[6-9]|2\d|3[01]))\./.test(page.hostname);

    const envHost = (env.VITE_REVERB_HOST || '').trim();
    const envHostIsLocal = LOCAL_HOSTS.includes(envHost);
    const useOrigin = !envHost || (envHostIsLocal && !pageIsLocal);

    const host = useOrigin ? page.hostname : envHost;
    // https pages cannot open ws:// (mixed content is blocked on mobile browsers).
    const tls = page.protocol === 'https:' || (env.VITE_REVERB_SCHEME ?? 'http') === 'https';
    const originPort = Number(page.port) || (page.protocol === 'https:' ? 443 : 80);
    const port = useOrigin ? originPort : Number(env.VITE_REVERB_PORT) || (tls ? 443 : 8080);

    return {
        broadcaster: 'reverb',
        key: env.VITE_REVERB_APP_KEY,
        wsHost: host,
        wsPort: port,
        wssPort: port,
        forceTLS: tls,
        enabledTransports: ['ws', 'wss'],
        disableStats: true,
    };
}
