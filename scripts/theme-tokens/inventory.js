const puppeteer = require('puppeteer-core'); const fs = require('fs');
(async () => { const [label, url, out] = process.argv.slice(2); // usage: node inventory.js <theme>-<variant> <url> <out.json>
  const b = await puppeteer.launch({ executablePath: '/usr/bin/google-chrome', headless: 'new', args: ['--no-sandbox'] });
  const p = await b.newPage(); await p.setViewport({ width: 1440, height: 900 });
  await p.goto(url, { waitUntil: 'networkidle2' }); await new Promise(r => setTimeout(r, 800));
  const data = await p.evaluate(() => {

    const names = {};
    const walk = rules => { for (const r of rules) { if (r.cssRules && !(r instanceof CSSStyleRule)) walk(r.cssRules);
      if (r instanceof CSSStyleRule && /^(:root|html|\*|:host)(\s*,|$)/.test((r.selectorText||'').trim()) || (r instanceof CSSStyleRule && /(^|,)\s*(:root|html)\s*(,|$)/.test(r.selectorText||''))) for (let i = 0; i < r.style.length; i++) { const n = r.style[i]; if (n.startsWith('--')) names[n] = r.style.getPropertyValue(n).trim(); } } };
    for (const s of document.styleSheets) { try { walk(s.cssRules); } catch (e) {} }
    const cs = getComputedStyle(document.documentElement); const out = {};
    for (const [n, raw] of Object.entries(names)) out[n] = { raw, computed: cs.getPropertyValue(n).trim() };
    return { tokens: out, bodyFont: getComputedStyle(document.body).fontFamily };
  });
  fs.writeFileSync(out, JSON.stringify({ label, ...data })); await b.close(); })();
