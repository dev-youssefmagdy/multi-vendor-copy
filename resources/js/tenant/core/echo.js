import { reverbConfig } from '../../reverbConfig';

let echoInstance = null;

export async function getEcho() {
    if (echoInstance) {
        return echoInstance;
    }

    const { default: Echo } = await import('laravel-echo');
    const { default: Pusher } = await import('pusher-js');

    window.Pusher = Pusher;

    const authEndpoint = document.querySelector('meta[name="tenant-broadcast-auth"]')?.content;

    echoInstance = new Echo({
        ...reverbConfig(),
        authEndpoint,
    });

    return echoInstance;
}
