import { isSoundEnabled, playAlert, setSoundEnabled } from './alert-sound.js';

const STYLE_ID = 'notif-bell-styles';
const POLL_MS = 60000;

const CSS = `
.nb-panel{position:absolute;top:calc(100% + 10px);inset-inline-end:0;width:360px;max-width:calc(100vw - 24px);background:var(--nb-bg,#fff);color:var(--nb-fg,#1f2937);border:1px solid var(--nb-border,rgba(0,0,0,.1));border-radius:14px;box-shadow:0 18px 48px rgba(0,0,0,.18);z-index:1200;overflow:hidden;font-size:13px;text-align:start}
.nb-panel[hidden]{display:none}
.nb-head{display:flex;align-items:center;gap:8px;padding:12px 14px;border-bottom:1px solid var(--nb-border,rgba(0,0,0,.08))}
.nb-title{font-weight:700;font-size:14px;flex:1}
.nb-link,.nb-icon-btn{background:none;border:0;padding:4px 6px;border-radius:6px;cursor:pointer;color:var(--nb-accent,#2563eb);font:inherit;font-size:12px;text-decoration:none}
.nb-link:hover,.nb-icon-btn:hover{background:var(--nb-hover,rgba(0,0,0,.05))}
.nb-icon-btn{color:var(--nb-muted,#6b7280);display:inline-flex;align-items:center}
.nb-list{max-height:380px;overflow-y:auto}
.nb-item{display:flex;gap:10px;padding:11px 14px;border-bottom:1px solid var(--nb-border,rgba(0,0,0,.06));color:inherit;text-decoration:none;position:relative}
.nb-item:hover{background:var(--nb-hover,rgba(0,0,0,.04))}
.nb-item.is-unread{background:var(--nb-unread,rgba(37,99,235,.07))}
.nb-dot{width:8px;height:8px;border-radius:50%;margin-top:6px;flex-shrink:0;background:transparent}
.nb-item.is-unread .nb-dot{background:var(--nb-accent,#2563eb)}
.nb-body{flex:1;min-width:0}
.nb-item-title{font-weight:600;line-height:1.3}
.nb-item-msg{color:var(--nb-muted,#6b7280);margin-top:2px;line-height:1.35;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.nb-meta{display:flex;gap:8px;align-items:center;margin-top:5px;font-size:11px;color:var(--nb-muted,#6b7280)}
.nb-type{text-transform:capitalize;padding:1px 7px;border-radius:999px;background:var(--nb-hover,rgba(0,0,0,.06))}
.nb-mark{margin-inline-start:auto;background:none;border:0;cursor:pointer;color:var(--nb-accent,#2563eb);font-size:11px;padding:0}
.nb-empty{padding:28px 14px;text-align:center;color:var(--nb-muted,#6b7280)}
.nb-foot{padding:10px 14px;text-align:center;border-top:1px solid var(--nb-border,rgba(0,0,0,.08))}
.nb-badge-pulse{animation:nbPulse .5s ease}
@keyframes nbPulse{0%{transform:scale(1)}50%{transform:scale(1.5)}100%{transform:scale(1)}}
@media (max-width:480px){.nb-panel{position:fixed;top:60px;inset-inline:12px;width:auto}}
`;

function injectStyles() {
    if (document.getElementById(STYLE_ID)) {
        return;
    }
    const style = document.createElement('style');
    style.id = STYLE_ID;
    style.textContent = CSS;
    document.head.appendChild(style);
}

function xsrf() {
    const m = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
    return m ? decodeURIComponent(m[1]) : '';
}

async function request(url, method = 'GET') {
    const res = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrf(),
        },
    });
    if (!res.ok) {
        throw new Error(`HTTP ${res.status}`);
    }
    return res.json();
}

const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

const SOUND_ON = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 5L6 9H2v6h4l5 4V5z"/><path d="M15.5 8.5a5 5 0 0 1 0 7M19 5a10 10 0 0 1 0 14"/></svg>';
const SOUND_OFF = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 5L6 9H2v6h4l5 4V5z"/><path d="M23 9l-6 6M17 9l6 6"/></svg>';

/**
 * Mounts the notification dropdown on `root`.
 *
 * Expected data attributes on root:
 *  data-latest-url, data-read-all-url, data-read-url (with __ID__), data-read-method,
 *  data-all-url, data-channel, data-label-*  (optional i18n strings)
 * Inside root: [data-notif-toggle] (button/link), [data-notif-count] (badge).
 *
 * @param {HTMLElement} root
 * @param {{ getEcho?: () => Promise<any>|any, onToast?: (e: object) => void }} opts
 */
export function mountNotificationBell(root, { getEcho, onToast } = {}) {
    if (!root || root.dataset.nbMounted) {
        return;
    }
    root.dataset.nbMounted = '1';
    injectStyles();

    const d = root.dataset;
    const t = (key, fallback) => d[`label${key}`] || fallback;
    const toggle = root.querySelector('[data-notif-toggle]');
    const countEl = root.querySelector('[data-notif-count]');

    root.style.position = root.style.position || 'relative';

    const panel = document.createElement('div');
    panel.className = 'nb-panel';
    panel.hidden = true;
    panel.innerHTML = `
        <div class="nb-head">
            <span class="nb-title">${esc(t('Title', 'Notifications'))}</span>
            <button type="button" class="nb-icon-btn" data-nb-sound title="${esc(t('Sound', 'Alert sound'))}"></button>
            <button type="button" class="nb-link" data-nb-read-all>${esc(t('ReadAll', 'Mark all as read'))}</button>
        </div>
        <div class="nb-list" data-nb-list></div>
        <div class="nb-foot"><a class="nb-link" href="${esc(d.allUrl || '#')}">${esc(t('ViewAll', 'View all notifications'))}</a></div>`;
    root.appendChild(panel);

    const listEl = panel.querySelector('[data-nb-list]');
    const readAllBtn = panel.querySelector('[data-nb-read-all]');
    const soundBtn = panel.querySelector('[data-nb-sound]');

    let unread = parseInt(countEl?.textContent === '99+' ? '100' : countEl?.textContent || '0', 10) || 0;
    let loaded = false;

    const renderCount = (pulse = false) => {
        if (countEl) {
            countEl.textContent = unread > 99 ? '99+' : String(unread);
            countEl.hidden = unread <= 0;
            countEl.style.display = unread > 0 ? (countEl.dataset.display || '') : 'none';
            if (pulse) {
                countEl.classList.remove('nb-badge-pulse');
                void countEl.offsetWidth;
                countEl.classList.add('nb-badge-pulse');
            }
        }
        readAllBtn.hidden = unread <= 0;
    };

    const renderSound = () => {
        soundBtn.innerHTML = isSoundEnabled() ? SOUND_ON : SOUND_OFF;
    };

    const renderItems = (items) => {
        if (!items.length) {
            listEl.innerHTML = `<div class="nb-empty">${esc(t('Empty', 'No notifications yet'))}</div>`;
            return;
        }
        listEl.innerHTML = items.map((n) => `
            <a class="nb-item ${n.is_read ? '' : 'is-unread'}" href="${esc(n.open_url)}" data-id="${n.id}">
                <span class="nb-dot"></span>
                <span class="nb-body">
                    <div class="nb-item-title">${esc(n.title)}</div>
                    <div class="nb-item-msg">${esc(n.message)}</div>
                    <div class="nb-meta">
                        <span class="nb-type">${esc(String(n.type).replace(/_/g, ' '))}</span>
                        <time datetime="${esc(n.created_at)}">${esc(n.time)}</time>
                        ${n.is_read ? '' : `<button type="button" class="nb-mark" data-nb-mark="${n.id}">${esc(t('MarkRead', 'Mark as read'))}</button>`}
                    </div>
                </span>
            </a>`).join('');
    };

    const load = async () => {
        try {
            const data = await request(d.latestUrl);
            unread = data.unread;
            renderItems(data.items);
            renderCount();
            loaded = true;
        } catch {
            if (!loaded) {
                listEl.innerHTML = `<div class="nb-empty">${esc(t('Error', 'Could not load notifications'))}</div>`;
            }
        }
    };

    const open = () => {
        panel.hidden = false;
        load();
    };
    const close = () => { panel.hidden = true; };

    toggle?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        panel.hidden ? open() : close();
    });
    document.addEventListener('click', (e) => {
        if (!panel.hidden && !root.contains(e.target)) {
            close();
        }
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            close();
        }
    });

    panel.addEventListener('click', async (e) => {
        const markBtn = e.target.closest('[data-nb-mark]');
        if (markBtn) {
            e.preventDefault();
            e.stopPropagation();
            try {
                const data = await request(d.readUrl.replace('__ID__', markBtn.dataset.nbMark), d.readMethod || 'POST');
                unread = data.unread ?? Math.max(0, unread - 1);
                markBtn.closest('.nb-item')?.classList.remove('is-unread');
                markBtn.remove();
                renderCount();
            } catch { /* ignore */ }
        }
    });

    readAllBtn.addEventListener('click', async () => {
        try {
            await request(d.readAllUrl, 'POST');
            unread = 0;
            listEl.querySelectorAll('.is-unread').forEach((el) => el.classList.remove('is-unread'));
            listEl.querySelectorAll('[data-nb-mark]').forEach((el) => el.remove());
            renderCount();
            window.dispatchEvent(new CustomEvent('notifications:read-all'));
        } catch { /* ignore */ }
    });

    soundBtn.addEventListener('click', () => {
        setSoundEnabled(!isSoundEnabled());
        renderSound();
        playAlert();
    });

    const onCreated = (e) => {
        unread += 1;
        renderCount(true);
        playAlert();
        onToast?.(e);
        if (!panel.hidden) {
            load();
        }
    };

    (async () => {
        if (!getEcho || !d.channel) {
            return;
        }
        try {
            const echo = await getEcho();
            echo?.private(d.channel).listen('.notification.created', onCreated);
        } catch { /* realtime unavailable: polling below keeps the badge fresh */ }
    })();

    // Safety net when the socket is down/blocked: refresh the count periodically.
    const refreshCount = async () => {
        if (document.hidden || !panel.hidden) {
            return;
        }
        try {
            const data = await request(d.latestUrl);
            if (data.unread > unread) {
                playAlert();
            }
            unread = data.unread;
            renderCount();
        } catch { /* ignore */ }
    };
    setInterval(refreshCount, POLL_MS);
    root.addEventListener('notif:refresh', () => request(d.latestUrl).then((data) => {
        unread = data.unread;
        renderCount();
        if (!panel.hidden) renderItems(data.items);
    }).catch(() => {}));
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refreshCount(); });

    renderSound();
    renderCount();
}
