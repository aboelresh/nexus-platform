const Realtime = {
  pusher: null,
  channels: {},
  typingTimer: null,
  isTyping: false,

  init() {
    const token = localStorage.getItem(Config.STORAGE_TOKEN);
    if (!token) return;

    this.pusher = new Pusher(Config.WS_KEY, {
      wsHost: Config.WS_HOST,
      wsPort: Config.WS_PORT,
      forceTLS: false,
      enabledTransports: ['ws'],
      cluster: 'mt1',
      channelAuthorization: {
        endpoint: Config.AUTH_ENDPOINT,
        transport: 'ajax',
        headers: {
          Authorization: 'Bearer ' + token,
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        }
      }
    });

    this.pusher.connection.bind('connected', () => {
      document.getElementById('myStatus').textContent = t('online');
      document.getElementById('myStatus').style.color = 'var(--green)';
    });

    this.pusher.connection.bind('disconnected', () => {
      document.getElementById('myStatus').textContent = t('offline');
      document.getElementById('myStatus').style.color = 'var(--text3)';
    });
  },

  subscribeConversation(convId) {
    if (!this.pusher || this.channels[convId]) return;

    const ch = this.pusher.subscribe('private-conversation.' + convId);
    this.channels[convId] = ch;

    ch.bind('message.sent', ({message}) => {
      if (message.sender?.id !== App.state.me?.id) {
        if (App.state.currentConv?.id == convId) {
          Messages.append(message);
          Api.conversations.read(convId).catch(()=>{});
        }
        Conversations.updateLastMsg(convId, message.body, message.created_at);
      }
    });

    ch.bind('message.updated', ({message}) => {
      Messages.update(message);
    });

    ch.bind('message.deleted', ({message_id}) => {
      Messages.remove(message_id);
    });

    ch.bind('user.typing', ({user_id, name, is_typing}) => {
      if (user_id === App.state.me?.id) return;
      if (App.state.currentConv?.id != convId) return;
      const ind  = document.getElementById('typingIndicator');
      const text = document.getElementById('typingText');
      if (is_typing) {
        text.textContent = `${name} ${t('is_typing')}`;
        ind.style.display = 'flex';
      } else {
        ind.style.display = 'none';
      }
    });
  },

  sendTyping(val) {
    if (!App.state.currentConv) return;
    clearTimeout(this.typingTimer);
    if (val && !this.isTyping) {
      this.isTyping = true;
      Api.conversations.typing(App.state.currentConv.id, true).catch(()=>{});
    }
    this.typingTimer = setTimeout(() => {
      this.isTyping = false;
      Api.conversations.typing(App.state.currentConv.id, false).catch(()=>{});
    }, Config.TYPING_DEBOUNCE);
  },
};