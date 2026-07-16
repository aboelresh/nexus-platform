const App = {
  state: {
    me: null,
    currentConv: null,
    conversations: [],
    replyTo: null,
    editingMsg: null,
    isRecording: false,
    mediaRecorder: null,
    audioChunks: [],
    lang: 'ar',
    theme: 'dark',
  },

  init() {
    this.state.lang  = localStorage.getItem(Config.STORAGE_LANG)  || 'ar';
    this.state.theme = localStorage.getItem(Config.STORAGE_THEME) || 'dark';
    this.applyLang();
    this.applyTheme();

    const token = localStorage.getItem(Config.STORAGE_TOKEN);
    const user  = localStorage.getItem(Config.STORAGE_USER);

    if (token && user) {
      this.state.me = JSON.parse(user);
      this.launch();
    } else {
      Auth.showPage();
    }
  },

  launch() {
    document.getElementById('authPage').style.display = 'none';
    document.getElementById('app').classList.add('visible');
    this.updateMyInfo();
    Conversations.load();
    Realtime.init();
  },

  updateMyInfo() {
    const me = this.state.me;
    if (!me) return;
    const nameEl = document.getElementById('myName');
    const avatarEl = document.getElementById('myAvatar');
    if (nameEl) nameEl.textContent = me.name;
    if (avatarEl) {
      if (me.avatar) {
        avatarEl.innerHTML = `<img src="${me.avatar}" alt="${me.name}">`;
      } else {
        avatarEl.textContent = me.name[0].toUpperCase();
      }
    }
  },

  applyTheme() {
    const t = this.state.theme;
    document.documentElement.setAttribute('data-theme', t);
    const toggle = document.getElementById('themeToggle');
    if (toggle) toggle.checked = t === 'light';
  },

  toggleTheme() {
    this.state.theme = this.state.theme === 'dark' ? 'light' : 'dark';
    localStorage.setItem(Config.STORAGE_THEME, this.state.theme);
    this.applyTheme();
  },

  applyLang() {
    const lang = this.state.lang;
    document.documentElement.setAttribute('lang', lang);
    document.documentElement.setAttribute('dir', lang === 'ar' ? 'rtl' : 'ltr');
  },

  toggleLang() {
    this.state.lang = this.state.lang === 'ar' ? 'en' : 'ar';
    localStorage.setItem(Config.STORAGE_LANG, this.state.lang);
    this.applyLang();
    location.reload();
  },

  toast(msg, type = 'info', duration = 3000) {
    const el = document.getElementById('toast');
    const icons = { success: '✅', error: '❌', info: 'ℹ️' };
    el.innerHTML = `<span>${icons[type] || ''}</span><span>${msg}</span>`;
    el.className = `toast show ${type}`;
    clearTimeout(this._toastTimer);
    this._toastTimer = setTimeout(() => el.className = 'toast', duration);
  },

  esc(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  },

  formatTime(iso) {
    return new Date(iso).toLocaleTimeString(this.state.lang === 'ar' ? 'ar' : 'en', {hour:'2-digit', minute:'2-digit'});
  },

  formatDate(iso) {
    return new Date(iso).toLocaleDateString(this.state.lang === 'ar' ? 'ar' : 'en', {year:'numeric', month:'short', day:'numeric'});
  },

  formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes/1024).toFixed(1) + ' KB';
    return (bytes/1048576).toFixed(1) + ' MB';
  },

  closeModal(id) {
    document.getElementById(id)?.classList.remove('open');
  },

  openModal(id) {
    document.getElementById(id)?.classList.add('open');
  },
};