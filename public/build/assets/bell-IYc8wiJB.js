var e=`notif_sound_enabled`,t=null,n=!1;function r(){try{return localStorage.getItem(e)}catch{return null}}function i(){return r()!==`0`}function a(t){try{localStorage.setItem(e,t?`1`:`0`)}catch{}}function o(){if(!t){let e=window.AudioContext||window.webkitAudioContext;if(!e)return null;t=new e}return t}function s(){if(n)return;let e=o();e&&e.resume?.().then(()=>{n=e.state===`running`}).catch(()=>{})}[`pointerdown`,`keydown`,`touchstart`].forEach(e=>{document.addEventListener(e,s,{passive:!0})});function c(e,t,n,r,i){let a=e.createOscillator(),o=e.createGain();a.type=`sine`,a.frequency.value=t,o.gain.setValueAtTime(1e-4,n),o.gain.exponentialRampToValueAtTime(i,n+.02),o.gain.exponentialRampToValueAtTime(1e-4,n+r),a.connect(o).connect(e.destination),a.start(n),a.stop(n+r+.05)}function l(){if(!i())return;let e=o();if(!e||e.state!==`running`)return;let t=e.currentTime;c(e,880,t,.28,.25),c(e,1318.5,t+.16,.45,.25)}var u=`notif-bell-styles`,d=6e4,f=`
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
`;function p(){if(document.getElementById(u))return;let e=document.createElement(`style`);e.id=u,e.textContent=f,document.head.appendChild(e)}function m(){let e=document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);return e?decodeURIComponent(e[1]):``}async function h(e,t=`GET`){let n=await fetch(e,{method:t,credentials:`same-origin`,headers:{Accept:`application/json`,"X-Requested-With":`XMLHttpRequest`,"X-XSRF-TOKEN":m()}});if(!n.ok)throw Error(`HTTP ${n.status}`);return n.json()}var g=e=>String(e??``).replace(/[&<>"']/g,e=>({"&":`&amp;`,"<":`&lt;`,">":`&gt;`,'"':`&quot;`,"'":`&#39;`})[e]),_=`<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 5L6 9H2v6h4l5 4V5z"/><path d="M15.5 8.5a5 5 0 0 1 0 7M19 5a10 10 0 0 1 0 14"/></svg>`,v=`<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 5L6 9H2v6h4l5 4V5z"/><path d="M23 9l-6 6M17 9l6 6"/></svg>`;function y(e,{getEcho:t,onToast:n}={}){if(!e||e.dataset.nbMounted)return;e.dataset.nbMounted=`1`,p();let r=e.dataset,o=(e,t)=>r[`label${e}`]||t,s=e.querySelector(`[data-notif-toggle]`),c=e.querySelector(`[data-notif-count]`);e.style.position=e.style.position||`relative`;let u=document.createElement(`div`);u.className=`nb-panel`,u.hidden=!0,u.innerHTML=`
        <div class="nb-head">
            <span class="nb-title">${g(o(`Title`,`Notifications`))}</span>
            <button type="button" class="nb-icon-btn" data-nb-sound title="${g(o(`Sound`,`Alert sound`))}"></button>
            <button type="button" class="nb-link" data-nb-read-all>${g(o(`ReadAll`,`Mark all as read`))}</button>
        </div>
        <div class="nb-list" data-nb-list></div>
        <div class="nb-foot"><a class="nb-link" href="${g(r.allUrl||`#`)}">${g(o(`ViewAll`,`View all notifications`))}</a></div>`,e.appendChild(u);let f=u.querySelector(`[data-nb-list]`),m=u.querySelector(`[data-nb-read-all]`),y=u.querySelector(`[data-nb-sound]`),b=parseInt(c?.textContent===`99+`?`100`:c?.textContent||`0`,10)||0,x=!1,S=(e=!1)=>{c&&(c.textContent=b>99?`99+`:String(b),c.hidden=b<=0,c.style.display=b>0?c.dataset.display||``:`none`,e&&(c.classList.remove(`nb-badge-pulse`),c.offsetWidth,c.classList.add(`nb-badge-pulse`))),m.hidden=b<=0},C=()=>{y.innerHTML=i()?_:v},w=e=>{e.length?f.innerHTML=e.map(e=>`
            <a class="nb-item ${e.is_read?``:`is-unread`}" href="${g(e.open_url)}" data-id="${e.id}">
                <span class="nb-dot"></span>
                <span class="nb-body">
                    <div class="nb-item-title">${g(e.title)}</div>
                    <div class="nb-item-msg">${g(e.message)}</div>
                    <div class="nb-meta">
                        <span class="nb-type">${g(String(e.type).replace(/_/g,` `))}</span>
                        <time datetime="${g(e.created_at)}">${g(e.time)}</time>
                        ${e.is_read?``:`<button type="button" class="nb-mark" data-nb-mark="${e.id}">${g(o(`MarkRead`,`Mark as read`))}</button>`}
                    </div>
                </span>
            </a>`).join(``):f.innerHTML=`<div class="nb-empty">${g(o(`Empty`,`No notifications yet`))}</div>`},T=async()=>{try{let e=await h(r.latestUrl);b=e.unread,w(e.items),S(),x=!0}catch{x||(f.innerHTML=`<div class="nb-empty">${g(o(`Error`,`Could not load notifications`))}</div>`)}},E=()=>{u.hidden=!1,T()},D=()=>{u.hidden=!0};s?.addEventListener(`click`,e=>{e.preventDefault(),e.stopPropagation(),u.hidden?E():D()}),document.addEventListener(`click`,t=>{!u.hidden&&!e.contains(t.target)&&D()}),document.addEventListener(`keydown`,e=>{e.key===`Escape`&&D()}),u.addEventListener(`click`,async e=>{let t=e.target.closest(`[data-nb-mark]`);if(t){e.preventDefault(),e.stopPropagation();try{b=(await h(r.readUrl.replace(`__ID__`,t.dataset.nbMark),r.readMethod||`POST`)).unread??Math.max(0,b-1),t.closest(`.nb-item`)?.classList.remove(`is-unread`),t.remove(),S()}catch{}}}),m.addEventListener(`click`,async()=>{try{await h(r.readAllUrl,`POST`),b=0,f.querySelectorAll(`.is-unread`).forEach(e=>e.classList.remove(`is-unread`)),f.querySelectorAll(`[data-nb-mark]`).forEach(e=>e.remove()),S(),window.dispatchEvent(new CustomEvent(`notifications:read-all`))}catch{}}),y.addEventListener(`click`,()=>{a(!i()),C(),l()});let O=e=>{b+=1,S(!0),l(),n?.(e),u.hidden||T()};(async()=>{if(t&&r.channel)try{(await t())?.private(r.channel).listen(`.notification.created`,O)}catch{}})();let k=async()=>{if(!document.hidden&&u.hidden)try{let e=await h(r.latestUrl);e.unread>b&&l(),b=e.unread,S()}catch{}};setInterval(k,d),e.addEventListener(`notif:refresh`,()=>h(r.latestUrl).then(e=>{b=e.unread,S(),u.hidden||w(e.items)}).catch(()=>{})),document.addEventListener(`visibilitychange`,()=>{document.hidden||k()}),C(),S()}export{y as t};