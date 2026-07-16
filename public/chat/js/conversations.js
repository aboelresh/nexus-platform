const Conversations = {
  async load() {
    try {
      const r = await Api.conversations.list();
      App.state.conversations = r.data || [];
      this.render(App.state.conversations);
    } catch(e) {
      App.toast(e.message, 'error');
    }
  },

  render(list) {
    const el = document.getElementById('convList');
    if (!list.length) {
      el.innerHTML = `<div style="text-align:center;padding:40px;color:var(--text2);font-size:13px">لا توجد محادثات بعد<br><small>ابدأ محادثة جديدة! 👋</small></div>`;
      return;
    }

    el.innerHTML = list.map(c => this.renderItem(c)).join('');
  },

  renderItem(c) {
    const name    = App.esc(c.name || t('direct'));
    const letter  = (c.name || '؟')[0].toUpperCase();
    const last    = c.last_message?.body ? App.esc(c.last_message.body.substring(0,40)) : `<em style="color:var(--text3)">${t('no_messages')}</em>`;
    const time    = c.last_message_at ? App.formatTime(c.last_message_at) : '';
    const unread  = c.unread_count > 0 ? `<span class="badge">${c.unread_count}</span>` : '';
    const active  = App.state.currentConv?.id === c.id ? 'active' : '';
    const avatarHtml = c.avatar ? `<img src="${c.avatar}" style="width:100%;height:100%;object-fit:cover;border-radius:50%">` : letter;
    const isGroup = c.type === 'group';

    return `
      <div class="conv-item ${active}" onclick="Conversations.open(${c.id})" id="conv-item-${c.id}">
        <div class="conv-avatar">
          <div class="avatar" style="width:46px;height:46px;font-size:17px;background:${this.colorFor(c.name || '')}">${avatarHtml}</div>
          ${!isGroup ? `<div class="online-dot" id="dot-${c.id}" style="display:none"></div>` : ''}
        </div>
        <div class="conv-info">
          <div class="conv-name">${name}${isGroup ? ' 👥' : ''}</div>
          <div class="conv-last">${last}</div>
        </div>
        <div class="conv-meta">
          <span class="conv-time">${time}</span>
          ${unread}
        </div>
      </div>
    `;
  },

  colorFor(name) {
    const colors = ['#5b67f8','#22c55e','#f59e0b','#ef4444','#a855f7','#f97316','#06b6d4','#ec4899'];
    let hash = 0;
    for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
    return colors[Math.abs(hash) % colors.length];
  },

  async open(id) {
    const conv = App.state.conversations.find(c => c.id === id) || {id};
    App.state.currentConv = conv;

    document.querySelectorAll('.conv-item').forEach(el => el.classList.remove('active'));
    document.getElementById('conv-item-' + id)?.classList.add('active');

    document.getElementById('emptyState').style.display  = 'none';
    document.getElementById('chatWindow').style.display  = 'flex';
    document.getElementById('chatWindow').style.flexDirection = 'column';
    document.getElementById('chatWindow').style.height   = '100%';

    const name = conv.name || t('direct');
    document.getElementById('chatName').textContent   = name;
    document.getElementById('chatAvatar').textContent = name[0].toUpperCase();
    document.getElementById('chatSub').textContent    = conv.type === 'group' ? t('group') : t('direct');

    Messages.load(id);
    Realtime.subscribeConversation(id);

    try { await Api.conversations.read(id); } catch(e) {}

    const item = document.getElementById('conv-item-' + id);
    if (item) item.querySelector('.badge')?.remove();

    if (window.innerWidth <= 700) {
      document.getElementById('sidebar').classList.remove('mobile-open');
    }
  },

  updateLastMsg(convId, body, time) {
    const item = document.getElementById('conv-item-' + convId);
    if (!item) return;
    const lastEl = item.querySelector('.conv-last');
    const timeEl = item.querySelector('.conv-time');
    if (lastEl) lastEl.textContent = (body || '').substring(0, 40);
    if (timeEl && time) timeEl.textContent = App.formatTime(time);
    const list = document.getElementById('convList');
    if (list.firstChild !== item) list.prepend(item);
  },

  filter(q) {
    if (!q) { this.render(App.state.conversations); return; }
    this.render(App.state.conversations.filter(c => (c.name || '').toLowerCase().includes(q.toLowerCase())));
  },

  async openNewModal() {
    App.openModal('newConvModal');
    document.getElementById('newConvTab1').click();
    document.getElementById('userSearchInput').value = '';
    document.getElementById('userSearchResults').innerHTML = '';
    setTimeout(() => document.getElementById('userSearchInput').focus(), 100);
  },

  async searchUsers(q) {
    const el = document.getElementById('userSearchResults');
    if (q.length < 2) { el.innerHTML = ''; return; }
    try {
      const r = await Api.users.search(q);
      const users = r.data || [];
      el.innerHTML = users.length
        ? users.map(u => `
          <div class="user-result" onclick="Conversations.startDirect(${u.id})">
            <div class="avatar" style="width:38px;height:38px;font-size:14px;background:${this.colorFor(u.name)}">${u.name[0].toUpperCase()}</div>
            <div class="info">
              <div class="name">${App.esc(u.name)}</div>
              <div class="username">@${App.esc(u.username)}</div>
            </div>
            <span style="font-size:11px;color:var(--${u.presence_status==='online'?'green':'text3'})">${u.presence_status==='online'?t('online'):''}</span>
          </div>
        `).join('')
        : `<div style="text-align:center;padding:16px;color:var(--text2);font-size:13px">لا نتائج</div>`;
    } catch(e) {}
  },

  async startDirect(userId) {
    App.closeModal('newConvModal');
    try {
      const r = await Api.conversations.create({type: 'direct', user_id: userId});
      await this.load();
      this.open(r.data.id);
      App.toast(t('msg_sent'), 'success');
    } catch(e) { App.toast(e.message, 'error'); }
  },
};