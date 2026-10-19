import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { reverbConfig } from './reverbConfig';

window.Pusher = Pusher;

// The admin panel lives on the central domain under /admin and authorizes
// private channels via the 'admin' guard on the default /broadcasting/auth
// route. Everything else runs on a tenant subdomain (Stancl Tenancy routes
// registered separately from the central router), so it needs its own
// tenancy-aware auth endpoint — see routes/tenant.php.
const authEndpoint = window.location.pathname.startsWith('/admin')
    ? '/broadcasting/auth'
    : '/tenant/broadcasting/auth';

window.Echo = new Echo({
    ...reverbConfig(),
    authEndpoint,
});
