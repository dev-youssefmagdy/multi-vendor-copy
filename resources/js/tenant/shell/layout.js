import { emit } from '../core/events.js';

let sbCollapsed = window.localStorage.getItem('tenant-sb-collapsed') === '1';
let isDark = true;
let eventsBound = false;

const byId = (id) => document.getElementById(id);
const isMobile = () => window.innerWidth < 1024;

export function setDateLabel() {
    const dateLabel = byId('dt');

    if (!dateLabel) {
        return;
    }

    dateLabel.textContent = new Date().toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

export function applyTheme(theme) {
    isDark = theme !== 'light';
    document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');

    byId('dOpt')?.classList.toggle('ta', isDark);
    byId('lOpt')?.classList.toggle('ta', !isDark);
}

export function toggleTheme() {
    applyTheme(isDark ? 'light' : 'dark');
    const value = isDark ? 'dark' : 'light';
    window.localStorage.setItem('nexus-theme', value);
    document.cookie = `tenant_theme=${value}; path=/; max-age=${365 * 24 * 60 * 60}; SameSite=Lax`;
    emit('tenant:theme-changed', { theme: value });
}

export function openMobileSidebar() {
    byId('sb')?.classList.add('sb-open');
    byId('ov')?.classList.add('on');
    document.body.style.overflow = 'hidden';
}

export function closeMobileSidebar() {
    byId('sb')?.classList.remove('sb-open');
    byId('ov')?.classList.remove('on');
    document.body.style.overflow = '';
}

export function toggleDesktopSidebar() {
    sbCollapsed = !sbCollapsed;
    window.localStorage.setItem('tenant-sb-collapsed', sbCollapsed ? '1' : '0');
    byId('sb')?.classList.toggle('sb-hide', sbCollapsed);
    byId('nav')?.classList.toggle('nav-full', sbCollapsed);
    byId('nav2')?.classList.toggle('nav-full', sbCollapsed);
    byId('mn')?.classList.toggle('mn-full', sbCollapsed);
}

export function handleHamburger() {
    if (isMobile()) {
        openMobileSidebar();
        return;
    }

    toggleDesktopSidebar();
}

export function syncSidebarLayout() {
    const sidebar = byId('sb');
    const nav = byId('nav');
    const main = byId('mn');

    if (!sidebar || !nav || !main) {
        return;
    }

    if (isMobile()) {
        closeMobileSidebar();
        return;
    }

    sidebar.classList.toggle('sb-hide', sbCollapsed);
    nav.classList.toggle('nav-full', sbCollapsed);
    byId('nav2')?.classList.toggle('nav-full', sbCollapsed);
    main.classList.toggle('mn-full', sbCollapsed);
}

export function setActiveNav(target) {
    document.querySelectorAll('#sb .ni').forEach((item) => {
        item.classList.remove('act');
        item.querySelector('.act-bar')?.remove();
    });

    target.classList.add('act');
    const activeBar = document.createElement('div');
    activeBar.className = 'act-bar';
    target.prepend(activeBar);

    if (isMobile()) {
        closeMobileSidebar();
    }
}

export function setActiveSubNav(target) {
    document.querySelectorAll('#sb .nk').forEach((item) => item.classList.remove('nk-act'));
    target.classList.add('nk-act');

    if (isMobile()) {
        closeMobileSidebar();
    }
}

export function toggleGroup(trigger) {
    const wasOpen = trigger.classList.contains('op');

    document.querySelectorAll('#sb .ng-trigger').forEach((item) => {
        item.classList.remove('op');
        item.nextElementSibling?.classList.remove('op');
    });

    if (!wasOpen) {
        trigger.classList.add('op');
        trigger.nextElementSibling?.classList.add('op');
    }
}

function handleShellClick(event) {
    const actionTarget = event.target.closest('[data-action]');

    if (!actionTarget) {
        return;
    }

    switch (actionTarget.dataset.action) {
        case 'close-mobile':
            closeMobileSidebar();
            break;
        case 'handle-ham':
            handleHamburger();
            break;
        case 'toggle-theme':
            toggleTheme();
            break;
        case 'set-active':
            setActiveNav(actionTarget);
            break;
        case 'set-sub-active':
            setActiveSubNav(actionTarget);
            break;
        case 'toggle-group':
            toggleGroup(actionTarget);
            break;
        default:
            break;
    }
}

function bindEvents() {
    if (eventsBound) {
        return;
    }

    document.addEventListener('click', handleShellClick);
    window.addEventListener('resize', syncSidebarLayout);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMobileSidebar();
        }
    });
    eventsBound = true;
}

function initShell() {
    bindEvents();

    const preferredTheme = window.localStorage.getItem('nexus-theme');
    applyTheme(
        preferredTheme === 'light' || preferredTheme === 'dark'
            ? preferredTheme
            : document.documentElement.dataset.theme || 'dark',
    );
    setDateLabel();
    syncSidebarLayout();
}

document.addEventListener('DOMContentLoaded', initShell);
