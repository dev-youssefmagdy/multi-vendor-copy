function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

export function appendMessage(el, message) {
    const thread = el.querySelector('[data-chat-thread]');
    if (!thread) {
        return;
    }

    const empty = thread.querySelector('.t-chat-empty');
    empty?.remove();

    const wrapper = document.createElement('div');
    wrapper.className = `t-chat-message ${message.is_me ? 'is-me' : ''}`;
    wrapper.dataset.messageId = message.id;
    wrapper.innerHTML = `
        <div class="t-chat-meta">
            <span class="t-chat-author">${escapeHtml(message.author)}</span>
            <span class="t-chat-time">${escapeHtml(message.at)}</span>
        </div>
        <div class="t-chat-body">${escapeHtml(message.body).replace(/\n/g, '<br>')}</div>
    `;

    thread.appendChild(wrapper);
    thread.scrollTop = thread.scrollHeight;
}

export function init(el) {
    const thread = el.querySelector('[data-chat-thread]');
    if (thread) {
        thread.scrollTop = thread.scrollHeight;
    }
    el._tenantAppendMessage = (message) => appendMessage(el, message);
}
