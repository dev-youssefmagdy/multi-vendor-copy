import './shell/layout.js';
import { boot } from './core/index.js';

document.addEventListener('DOMContentLoaded', () => {
    boot();
});

window.Tenant = {
    version: '1.0.0',
};
