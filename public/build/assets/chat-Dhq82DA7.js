function e(e){let t=document.createElement(`div`);return t.textContent=e??``,t.innerHTML}function t(t,n){let r=t.querySelector(`[data-chat-thread]`);if(!r)return;r.querySelector(`.t-chat-empty`)?.remove();let i=document.createElement(`div`);i.className=`t-chat-message ${n.is_me?`is-me`:``}`,i.dataset.messageId=n.id,i.innerHTML=`
        <div class="t-chat-meta">
            <span class="t-chat-author">${e(n.author)}</span>
            <span class="t-chat-time">${e(n.at)}</span>
        </div>
        <div class="t-chat-body">${e(n.body).replace(/\n/g,`<br>`)}</div>
    `,r.appendChild(i),r.scrollTop=r.scrollHeight}function n(e){let n=e.querySelector(`[data-chat-thread]`);n&&(n.scrollTop=n.scrollHeight),e._tenantAppendMessage=n=>t(e,n)}export{t as appendMessage,n as init};