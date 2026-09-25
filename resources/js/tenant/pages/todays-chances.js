// Today's chances: opportunity cards (styles shared with the dashboard) with
// the auto-scrolling "best markets" flag ticker in each card.
import '@tenant-css/pages/dashboard.css';
import '@tenant-css/pages/todays-chances.css';
import { mountFlagTickers } from './dashboard/opportunities.js';

mountFlagTickers(document.querySelector('.tc-grid') || document);
