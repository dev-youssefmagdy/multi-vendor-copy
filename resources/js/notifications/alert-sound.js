const STORAGE_KEY = 'notif_sound_enabled';

let ctx = null;
let unlocked = false;

function safeGet() {
    try {
        return localStorage.getItem(STORAGE_KEY);
    } catch {
        return null;
    }
}

export function isSoundEnabled() {
    return safeGet() !== '0';
}

export function setSoundEnabled(on) {
    try {
        localStorage.setItem(STORAGE_KEY, on ? '1' : '0');
    } catch {
        /* storage unavailable */
    }
}

function context() {
    if (!ctx) {
        const Ctor = window.AudioContext || window.webkitAudioContext;
        if (!Ctor) {
            return null;
        }
        ctx = new Ctor();
    }
    return ctx;
}

/**
 * Browsers block audio until the user has interacted with the page, so the
 * context is created/resumed on the first gesture. Alerts arriving before
 * any interaction are silent (the toast + badge still show).
 */
function unlock() {
    if (unlocked) {
        return;
    }
    const c = context();
    if (!c) {
        return;
    }
    c.resume?.().then(() => {
        unlocked = c.state === 'running';
    }).catch(() => {});
}

['pointerdown', 'keydown', 'touchstart'].forEach((evt) => {
    document.addEventListener(evt, unlock, { passive: true });
});

function tone(c, freq, start, duration, volume) {
    const osc = c.createOscillator();
    const gain = c.createGain();
    osc.type = 'sine';
    osc.frequency.value = freq;
    gain.gain.setValueAtTime(0.0001, start);
    gain.gain.exponentialRampToValueAtTime(volume, start + 0.02);
    gain.gain.exponentialRampToValueAtTime(0.0001, start + duration);
    osc.connect(gain).connect(c.destination);
    osc.start(start);
    osc.stop(start + duration + 0.05);
}

/** Two-note "ding-dong" alert. */
export function playAlert() {
    if (!isSoundEnabled()) {
        return;
    }
    const c = context();
    if (!c || c.state !== 'running') {
        return;
    }
    const now = c.currentTime;
    tone(c, 880, now, 0.28, 0.25);
    tone(c, 1318.5, now + 0.16, 0.45, 0.25);
}
