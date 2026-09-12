const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["assets/vendor-sortable-BL3SdUJa.js","assets/rolldown-runtime-B-UjzUfB.js"])))=>i.map(i=>d[i]);
import{t as e}from"./preload-helper-D1wEiCgG.js";import{checkImageDimensionWarning as t}from"./file-C54IwawV.js";function n(e){let i=e.querySelector(`.t-dropzone-input`),a=e.querySelector(`[data-dropzone-files]`),o=parseInt(e.dataset.expectW,10)||null,s=parseInt(e.dataset.expectH,10)||null;!i||!a||((a._objectUrls||[]).forEach(e=>URL.revokeObjectURL(e)),a._objectUrls=[],a.innerHTML=``,e.querySelectorAll(`.t-dropzone-existing`).forEach(t=>{if(t.dataset.removed===`1`)return;let i=document.createElement(`div`);i.className=`dropzone-file`,i.dataset.existingId=t.dataset.existingId,i.innerHTML=`
            <div class="dropzone-file-row">
                ${(t.dataset.existingType||`image`)===`image`?`<img class="dropzone-file-thumb" src="${t.dataset.existingUrl}" alt="">`:``}
                <div class="dropzone-file-meta">
                    <span class="dropzone-file-name">${t.dataset.existingName||``}</span>
                </div>
                <button type="button" class="dropzone-file-remove" aria-label="Remove">
                    <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
        `,i.querySelector(`.dropzone-file-remove`)?.addEventListener(`click`,()=>{t.dataset.removed=`1`,r(e,t.dataset.existingId),n(e)}),a.appendChild(i)}),Array.from(i.files||[]).forEach((r,c)=>{let l=document.createElement(`div`);l.className=`dropzone-file`;let u=r.type.startsWith(`image/`),d=``;if(u){let e=URL.createObjectURL(r);a._objectUrls.push(e),d=`<img class="dropzone-file-thumb" src="${e}" alt="">`}l.innerHTML=`
            <div class="dropzone-file-row">
                ${d}
                <div class="dropzone-file-meta">
                    <span class="dropzone-file-name">${r.name}</span>
                    <span class="dropzone-file-size">${Math.max(1,Math.round(r.size/1024))} KB</span>
                </div>
                <button type="button" class="dropzone-file-remove" aria-label="Remove ${r.name}">
                    <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
        `,l.querySelector(`.dropzone-file-remove`)?.addEventListener(`click`,()=>{let t=new DataTransfer;Array.from(i.files||[]).forEach((e,n)=>{n!==c&&t.items.add(e)}),i.files=t.files,e._filePool=Array.from(i.files),n(e)}),a.appendChild(l),u&&o&&s&&t(r,o,s).then(e=>{if(!e)return;let t=document.createElement(`p`);t.className=`dropzone-file-warning`,t.textContent=e,l.appendChild(t)})}))}function r(e,t){let n=e.dataset.removeName,r=e.closest(`form`);if(!r||!n)return;let i=r.querySelector(`input[type="hidden"][data-dropzone-remove="${CSS.escape(t)}"]`);i||(i=document.createElement(`input`),i.type=`hidden`,i.name=`${n}[]`,i.dataset.dropzoneRemove=t,i.value=t,e.appendChild(i))}function i(e){let t=e.querySelector(`[data-order-input]`);t&&(t.value=Array.from(e.querySelectorAll(`.t-dropzone-existing:not([data-removed="1"])`)).map(e=>e.dataset.existingId).join(`,`))}async function a(t){let r=t.querySelector(`.t-dropzone-input`);if(r){if(r.addEventListener(`change`,()=>{if(r.multiple){let e=t._filePool||[],n=new Set(e.map(e=>`${e.name}-${e.size}-${e.lastModified}`)),i=new DataTransfer;e.forEach(e=>i.items.add(e)),Array.from(r.files).forEach(e=>{let t=`${e.name}-${e.size}-${e.lastModified}`;n.has(t)||i.items.add(e)}),r.files=i.files,t._filePool=Array.from(r.files)}n(t)}),t.dataset.sortable===`true`){let{default:n}=await e(async()=>{let{default:e}=await import(`./vendor-sortable-BL3SdUJa.js`).then(e=>e.n);return{default:e}},__vite__mapDeps([0,1])),r=t.querySelector(`[data-dropzone-files]`);r&&n.create(r,{handle:`.dropzone-file`,animation:150,onEnd:()=>i(t)})}n(t)}}export{a as init};