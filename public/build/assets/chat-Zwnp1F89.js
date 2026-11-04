function e(e){let t=document.createElement(`div`);return t.textContent=e??``,t.innerHTML}function t(t){return!Array.isArray(t)||!t.length?``:`<div class="t-chat-attachments">${t.map(t=>{let n=e(t.url),r=e(t.name);return t.image?`<a href="${n}" target="_blank" rel="noopener" title="${r}" class="t-chat-attachment-img"><img src="${n}" alt="${r}" loading="lazy"></a>`:`<a href="${n}" target="_blank" class="t-chat-attachment">${r}</a>`}).join(``)}</div>`}function n(n,r){let i=n.querySelector(`[data-chat-thread]`);if(!i)return;i.querySelector(`.t-chat-empty`)?.remove();let a=document.createElement(`div`);a.className=`t-chat-message ${r.is_me?`is-me`:``}`,a.dataset.messageId=r.id,a.innerHTML=`
        <div class="t-chat-meta">
            <span class="t-chat-author">${e(r.author)}</span>
            <span class="t-chat-time">${e(r.at)}</span>
        </div>
        <div class="t-chat-body">${e(r.body).replace(/\n/g,`<br>`)}</div>
        ${t(r.attachments)}
    `,i.appendChild(a),i.scrollTop=i.scrollHeight}function r(e){let t=e.querySelector(`[data-chat-thread]`);t&&(t.scrollTop=t.scrollHeight),e._tenantAppendMessage=t=>n(e,t)}export{n as appendMessage,r as init};