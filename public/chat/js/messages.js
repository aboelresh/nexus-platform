const Messages = {
  async load(convId, page = 1) {
    const wrap = document.getElementById('messagesWrap');
    if (page === 1) wrap.innerHTML = `<div style="text-align:center;color:var(--text2);padding:20px">${t('loading')}</div>`;

    try {
      const r = await Api.messages.list(convId, page);
      const msgs = (r.data || []).reverse();
      if (page === 1) {
        this.render(msgs, wrap);
        wrap.scrollTop = wrap.scrollHeight;
      }
    } catch(e) {
      wrap.innerHTML = `<div style="text-align:center;color:var(--red);padding:20px">خطأ في تحميل الرسائل</div>`;
    }
  },

  render(msgs, wrap) {
    if (!msgs.length) {
      wrap.innerHTML = `<div class="empty-state" style="flex:1"><div class="icon">💬</div><h3>${t('no_messages')}</h3><p>كن أول من يكتب! 👋</p></div>`;
      return;
    }

    let html = '';
    let lastDate = '';

    msgs.forEach(msg => {
      const dateStr = App.formatDate(msg.created_at);
      if (dateStr !== lastDate) {
        html += `<div class="date-divider">${dateStr}</div>`;
        lastDate = dateStr;
      }
      html += this.buildMsg(msg);
    });

    wrap.innerHTML = html;
    this.bindContextMenus();
  },

  buildMsg(msg) {
    const me    = App.state.me;
    const isOut = msg.sender?.id === me?.id;
    const conv  = App.state.currentConv;
    const name  = msg.sender?.name || '؟';

    const senderName = !isOut && conv?.type !== 'direct'
      ? `<div class="sender-name">${App.esc(name)}</div>` : '';

    const replyHtml = msg.reply_to ? `
      <div class="reply-preview" onclick="scrollToMsg(${msg.reply_to.id})">
        <div class="reply-sender">${App.esc(msg.reply_to.sender?.name || '')}</div>
        <div class="reply-text">${App.esc((msg.reply_to.body || '').substring(0,60))}</div>
      </div>` : '';

    const bodyHtml = this.buildBody(msg);

    const reactions = msg.reactions?.length ? `
      <div class="reactions-row">
        ${msg.reactions.map(r => `
          <button class="reaction-pill ${r.users?.includes(me?.id) ? 'mine' : ''}"
            onclick="Messages.react(${msg.id}, '${r.emoji}')">
            ${r.emoji} <span class="reaction-count">${r.count}</span>
          </button>`).join('')}
      </div>` : '';

    const meta = `
      <div class="msg-meta">
        ${msg.is_edited ? `<span class="edit-tag">edited</span>` : ''}
        <span class="msg-time">${App.formatTime(msg.created_at)}</span>
        ${isOut ? `<span class="read-icon">${msg.reads_count > 1 ? '✓✓' : '✓'}</span>` : ''}
      </div>`;

    const avatarHtml = msg.sender?.avatar
      ? `<img src="${msg.sender.avatar}" alt="${name}">`
      : name[0].toUpperCase();

    return `
      <div class="msg-group" id="msgGroup-${msg.id}">
        ${senderName}
        <div class="msg-row ${isOut ? 'out' : 'in'}" id="msg-${msg.id}"
          data-id="${msg.id}"
          oncontextmenu="Messages.showCtx(event, ${msg.id}, ${isOut})">
          <div class="msg-avatar">${avatarHtml}</div>
          <div class="bubble ${msg.deleted_at ? 'deleted' : ''}">
            ${replyHtml}
            ${bodyHtml}
            ${meta}
          </div>
        </div>
        ${reactions}
      </div>`;
  },

  buildBody(msg) {
    if (msg.deleted_at) return `<div class="msg-text" style="opacity:.5;font-style:italic">🚫 تم حذف هذه الرسالة</div>`;

    if (msg.media && msg.media.length) {
      return msg.media.map(m => {
        if (m.type === 'image') return `<img class="msg-img" src="${m.url}" alt="image" onclick="Media.lightbox('${m.url}')">`;
        if (m.type === 'voice') return `
          <div class="voice-player">
            <button class="voice-play-btn" onclick="Media.playVoice(this, '${m.url}')">▶</button>
            <div class="voice-waveform"><div class="voice-progress" id="vp-${m.id}"></div></div>
            <span class="voice-duration">${m.duration ? Math.round(m.duration) + 's' : ''}</span>
          </div>`;
        return `
          <a class="file-preview" href="${m.url}" target="_blank">
            <span class="file-icon">${this.fileIcon(m.mime_type)}</span>
            <div class="file-info">
              <div class="file-name">${App.esc(m.original_name)}</div>
              <div class="file-size">${App.formatFileSize(m.size)}</div>
            </div>
            ⬇
          </a>`;
      }).join('');
    }

    return `<div class="msg-text">${App.esc(msg.body || '')}</div>`;
  },

  fileIcon(mime) {
    if (mime?.startsWith('image')) return '🖼️';
    if (mime?.startsWith('video')) return '🎥';
    if (mime?.startsWith('audio')) return '🎵';
    if (mime?.includes('pdf'))     return '📄';
    if (mime?.includes('zip'))     return '📦';
    if (mime?.includes('word'))    return '📝';
    return '📎';
  },

  append(msg) {
    const wrap = document.getElementById('messagesWrap');
    const div  = document.createElement('div');
    div.innerHTML = this.buildMsg(msg);
    const node = div.firstElementChild;
    const empty = wrap.querySelector('.empty-state');
    if (empty) empty.remove();
    wrap.appendChild(node);
    wrap.scrollTop = wrap.scrollHeight;
    this.bindContextMenus();
  },

  update(msg) {
    const el = document.getElementById('msg-' + msg.id);
    if (!el) return;
    const bubble = el.querySelector('.bubble');
    if (!bubble) return;
    const textEl = bubble.querySelector('.msg-text');
    if (textEl) textEl.textContent = msg.body || '';
    const editTag = bubble.querySelector('.edit-tag');
    if (!editTag) {
      const meta = bubble.querySelector('.msg-meta');
      if (meta) meta.insertAdjacentHTML('afterbegin', '<span class="edit-tag">edited</span>');
    }
  },

  remove(msgId) {
    document.getElementById('msgGroup-' + msgId)?.remove();
  },

  async send() {
    const input = document.getElementById('msgInput');
    const body  = input.value.trim();
    if (!body || !App.state.currentConv) return;

    input.value = '';
    input.style.height = 'auto';

    const data = {body};
    if (App.state.replyTo) {
      data.reply_to_id = App.state.replyTo.id;
      this.cancelReply();
    }

    try {
      const r = await Api.messages.send(App.state.currentConv.id, data);
      this.append(r.data);
      Conversations.updateLastMsg(App.state.currentConv.id, body, r.data.created_at);
      Realtime.sendTyping(false);
    } catch(e) { App.toast(e.message, 'error'); }
  },

  setReply(msgId, body, sender) {
    App.state.replyTo = {id: msgId};
    const bar = document.getElementById('replyBar');
    bar.classList.add('show');
    document.getElementById('replyBarSender').textContent = sender || '';
    document.getElementById('replyBarText').textContent   = (body || '').substring(0, 60);
    document.getElementById('msgInput').focus();
  },

  cancelReply() {
    App.state.replyTo = null;
    document.getElementById('replyBar').classList.remove('show');
  },

  startEdit(msgId, body) {
    App.state.editingMsg = msgId;
    const input = document.getElementById('msgInput');
    input.value = body;
    input.focus();
    input.style.height = 'auto';
    input.style.height = input.scrollHeight + 'px';
  },

  cancelEdit() {
    App.state.editingMsg = null;
    document.getElementById('msgInput').value = '';
  },

  async submitEdit() {
    const body = document.getElementById('msgInput').value.trim();
    if (!body || !App.state.editingMsg) return;
    try {
      const r = await Api.messages.edit(App.state.currentConv.id, App.state.editingMsg, {body});
      this.update(r.data);
      this.cancelEdit();
    } catch(e) { App.toast(e.message, 'error'); }
  },

  async deleteMsg(msgId) {
    if (!confirm('هل تريد حذف هذه الرسالة؟')) return;
    try {
      await Api.messages.delete(App.state.currentConv.id, msgId);
      this.remove(msgId);
      App.toast(t('msg_deleted'), 'success');
    } catch(e) { App.toast(e.message, 'error'); }
  },

  async react(msgId, emoji) {
    try {
      await Api.messages.react(App.state.currentConv.id, msgId, emoji);
    } catch(e) {}
  },

  showCtx(e, msgId, isOwn) {
    e.preventDefault();
    const menu = document.getElementById('msgCtxMenu');
    menu.innerHTML = `
      <button class="ctx-item" onclick="Messages.replyFromCtx(${msgId})"><span class="ctx-icon">↩️</span>${t('reply')}</button>
      <button class="ctx-item" onclick="Messages.copyMsg(${msgId})"><span class="ctx-icon">📋</span>${t('copy')}</button>
      <button class="ctx-item" onclick="Messages.forwardMsg(${msgId})"><span class="ctx-icon">↪️</span>${t('forward')}</button>
      ${isOwn ? `
        <button class="ctx-item" onclick="Messages.editFromCtx(${msgId})"><span class="ctx-icon">✏️</span>${t('edit')}</button>
        <button class="ctx-item danger" onclick="Messages.deleteMsg(${msgId})"><span class="ctx-icon">🗑️</span>${t('delete')}</button>
      ` : ''}
      <button class="ctx-item" onclick="Messages.pinMsg(${msgId})"><span class="ctx-icon">📌</span>${t('pin')}</button>
    `;
    menu.style.top  = e.clientY + 'px';
    menu.style.left = e.clientX + 'px';
    menu.classList.add('open');
  },

  replyFromCtx(msgId) {
    const el = document.querySelector(`#msg-${msgId} .bubble .msg-text`);
    const sender = document.querySelector(`#msg-${msgId}`)?.closest('.msg-group')?.querySelector('.sender-name')?.textContent;
    this.setReply(msgId, el?.textContent, sender);
    document.getElementById('msgCtxMenu').classList.remove('open');
  },

  copyMsg(msgId) {
    const el = document.querySelector(`#msg-${msgId} .bubble .msg-text`);
    if (el) navigator.clipboard.writeText(el.textContent);
    App.toast('تم النسخ ✓', 'success');
    document.getElementById('msgCtxMenu').classList.remove('open');
  },

  editFromCtx(msgId) {
    const el = document.querySelector(`#msg-${msgId} .bubble .msg-text`);
    if (el) this.startEdit(msgId, el.textContent);
    document.getElementById('msgCtxMenu').classList.remove('open');
  },

  async pinMsg(msgId) {
    try {
      await Api.messages.pin(App.state.currentConv.id, msgId);
      App.toast('تم التثبيت 📌', 'success');
    } catch(e) { App.toast(e.message, 'error'); }
    document.getElementById('msgCtxMenu').classList.remove('open');
  },

  forwardMsg(msgId) {
    App.state.forwardMsgId = msgId;
    App.openModal('forwardModal');
    document.getElementById('msgCtxMenu').classList.remove('open');
  },

  async confirmForward(targetConvId) {
    if (!App.state.forwardMsgId) return;
    try {
      await Api.messages.forward(App.state.currentConv.id, App.state.forwardMsgId, {target_conversation_id: targetConvId});
      App.toast('تم التحويل ↪️', 'success');
      App.closeModal('forwardModal');
    } catch(e) { App.toast(e.message, 'error'); }
  },

  bindContextMenus() {
    document.addEventListener('click', () => {
      document.getElementById('msgCtxMenu')?.classList.remove('open');
    }, {once: true});
  },

  handleKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      if (App.state.editingMsg) this.submitEdit();
      else this.send();
    }
    if (e.key === 'Escape') {
      this.cancelReply();
      this.cancelEdit();
    }
  },

  autoResize(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 120) + 'px';
  },
};

function scrollToMsg(id) {
  const el = document.getElementById('msg-' + id);
  el?.scrollIntoView({behavior: 'smooth', block: 'center'});
  el?.classList.add('highlight');
  setTimeout(() => el?.classList.remove('highlight'), 2000);
}