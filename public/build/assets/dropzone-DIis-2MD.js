const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["assets/vendor-sortable-BG9McofS.js","assets/rolldown-runtime-DrUyviEM.js"])))=>i.map(i=>d[i]);
import{t as e}from"./preload-helper-Cdi5alSk.js";import{t}from"./i18n-DGKVOdfd.js";import{checkImageDimensionWarning as n}from"./file-fJtRgVoc.js";function r(e){let a=e.querySelector(`.t-dropzone-input`),o=e.querySelector(`[data-dropzone-files]`),s=parseInt(e.dataset.expectW,10)||null,c=parseInt(e.dataset.expectH,10)||null;!a||!o||((o._objectUrls||[]).forEach(e=>URL.revokeObjectURL(e)),o._objectUrls=[],o.innerHTML=``,e.querySelectorAll(`.t-dropzone-existing`).forEach(n=>{if(n.dataset.removed===`1`)return;let a=document.createElement(`div`);a.className=`dropzone-file`,a.dataset.existingId=n.dataset.existingId,a.innerHTML=`
            <div class="dropzone-file-row">
                ${(n.dataset.existingType||`image`)===`image`?`<img class="dropzone-file-thumb" src="${n.dataset.existingUrl}" alt="">`:``}
                <div class="dropzone-file-meta">
                    <span class="dropzone-file-name">${n.dataset.existingName||``}</span>
                </div>
                <button type="button" class="dropzone-file-remove" aria-label="${t(`Remove`)}">
                    <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
        `,a.querySelector(`.dropzone-file-remove`)?.addEventListener(`click`,()=>{n.dataset.removed=`1`,i(e,n.dataset.existingId),r(e)}),o.appendChild(a)}),Array.from(a.files||[]).forEach((i,l)=>{let u=document.createElement(`div`);u.className=`dropzone-file`;let d=i.type.startsWith(`image/`),f=``;if(d){let e=URL.createObjectURL(i);o._objectUrls.push(e),f=`<img class="dropzone-file-thumb" src="${e}" alt="">`}u.innerHTML=`
            <div class="dropzone-file-row">
                ${f}
                <div class="dropzone-file-meta">
                    <span class="dropzone-file-name">${i.name}</span>
                    <span class="dropzone-file-size">${Math.max(1,Math.round(i.size/1024))} KB</span>
                </div>
                <button type="button" class="dropzone-file-remove" aria-label="${t(`Remove :name`,{name:i.name})}">
                    <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
        `,u.querySelector(`.dropzone-file-remove`)?.addEventListener(`click`,()=>{let t=new DataTransfer;Array.from(a.files||[]).forEach((e,n)=>{n!==l&&t.items.add(e)}),a.files=t.files,e._filePool=Array.from(a.files),r(e)}),o.appendChild(u),d&&s&&c&&n(i,s,c).then(e=>{if(!e)return;let t=document.createElement(`p`);t.className=`dropzone-file-warning`,t.textContent=e,u.appendChild(t)})}))}function i(e,t){let n=e.dataset.removeName,r=e.closest(`form`);if(!r||!n)return;let i=r.querySelector(`input[type="hidden"][data-dropzone-remove="${CSS.escape(t)}"]`);i||(i=document.createElement(`input`),i.type=`hidden`,i.name=`${n}[]`,i.dataset.dropzoneRemove=t,i.value=t,e.appendChild(i))}function a(e){let t=e.querySelector(`[data-order-input]`);t&&(t.value=Array.from(e.querySelectorAll(`.t-dropzone-existing:not([data-removed="1"])`)).map(e=>e.dataset.existingId).join(`,`))}async function o(t){let n=t.querySelector(`.t-dropzone-input`);if(n){if(n.addEventListener(`change`,()=>{if(n.multiple){let e=t._filePool||[],r=new Set(e.map(e=>`${e.name}-${e.size}-${e.lastModified}`)),i=new DataTransfer;e.forEach(e=>i.items.add(e)),Array.from(n.files).forEach(e=>{let t=`${e.name}-${e.size}-${e.lastModified}`;r.has(t)||i.items.add(e)}),n.files=i.files,t._filePool=Array.from(n.files)}r(t)}),t.dataset.sortable===`true`){let{default:n}=await e(async()=>{let{default:e}=await import(`./vendor-sortable-BG9McofS.js`).then(e=>e.n);return{default:e}},__vite__mapDeps([0,1])),r=t.querySelector(`[data-dropzone-files]`);r&&n.create(r,{handle:`.dropzone-file`,animation:150,onEnd:()=>a(t)})}r(t)}}export{o as init};