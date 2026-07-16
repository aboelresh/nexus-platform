const Auth = {
  showPage() {
    document.getElementById('authPage').style.display = 'flex';
  },

  switchTab(tab) {
    document.getElementById('loginForm').style.display  = tab === 'login'    ? 'block' : 'none';
    document.getElementById('registerForm').style.display = tab === 'register' ? 'block' : 'none';
    document.getElementById('tabLogin').classList.toggle('active', tab === 'login');
    document.getElementById('tabRegister').classList.toggle('active', tab === 'register');
    document.getElementById('authErr').textContent = '';
    document.getElementById('authLogo').textContent = tab === 'login'
      ? 'مرحباً بك — سجّل دخولك للمتابعة'
      : 'أنشئ حسابك الجديد';
  },

  showErr(msg) {
    document.getElementById('authErr').textContent = msg;
  },

  async login() {
    const btn = document.getElementById('loginBtn');
    const email    = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;
    if (!email || !password) { this.showErr(t('err_fill')); return; }

    btn.disabled = true;
    btn.textContent = t('loading');
    try {
      const r = await Api.auth.login({email, password, device_name: navigator.userAgent.substring(0,50)});
      localStorage.setItem(Config.STORAGE_TOKEN, r.data.token);
      localStorage.setItem(Config.STORAGE_USER, JSON.stringify(r.data.user));
      App.state.me = r.data.user;
      App.launch();
    } catch(e) {
      this.showErr(e.message);
    } finally {
      btn.disabled = false;
      btn.textContent = t('login');
    }
  },

  async register() {
    const btn = document.getElementById('registerBtn');
    const name     = document.getElementById('regName').value.trim();
    const username = document.getElementById('regUsername').value.trim();
    const email    = document.getElementById('regEmail').value.trim();
    const password = document.getElementById('regPassword').value;
    if (!name || !username || !email || !password) { this.showErr(t('err_fill')); return; }

    btn.disabled = true;
    btn.textContent = t('loading');
    try {
      const r = await Api.auth.register({name, username, email, password, password_confirmation: password, device_name: navigator.userAgent.substring(0,50)});
      localStorage.setItem(Config.STORAGE_TOKEN, r.data.token);
      localStorage.setItem(Config.STORAGE_USER, JSON.stringify(r.data.user));
      App.state.me = r.data.user;
      App.launch();
    } catch(e) {
      this.showErr(e.message);
    } finally {
      btn.disabled = false;
      btn.textContent = t('register');
    }
  },

  async logout() {
    try { await Api.auth.logout(); } catch(e) {}
    localStorage.removeItem(Config.STORAGE_TOKEN);
    localStorage.removeItem(Config.STORAGE_USER);
    location.reload();
  },
};