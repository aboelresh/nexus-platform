const Profile = {
  async openModal() {
    App.openModal('profileModal');
    try {
      const r = await Api.users.profile();
      const u = r.data;
      document.getElementById('profileName').value     = u.name     || '';
      document.getElementById('profileUsername').value = u.username || '';
      document.getElementById('profileBio').value      = u.bio      || '';
      document.getElementById('profilePhone').value    = u.phone    || '';
      document.getElementById('profileStatus').value   = u.custom_status || '';

      const avatarEl = document.getElementById('profileAvatarPreview');
      if (u.avatar) {
        avatarEl.innerHTML = `<img src="${u.avatar}" style="width:100%;height:100%;object-fit:cover;border-radius:50%">`;
      } else {
        avatarEl.textContent = u.name[0].toUpperCase();
      }
    } catch(e) { App.toast(e.message, 'error'); }
  },

  async save() {
    const data = {
      name:          document.getElementById('profileName').value.trim(),
      bio:           document.getElementById('profileBio').value.trim(),
      phone:         document.getElementById('profilePhone').value.trim(),
      custom_status: document.getElementById('profileStatus').value.trim(),
    };

    try {
      const r = await Api.users.update(data);
      App.state.me = {...App.state.me, ...data};
      localStorage.setItem(Config.STORAGE_USER, JSON.stringify(App.state.me));
      App.updateMyInfo();
      App.toast(t('profile_updated'), 'success');
      App.closeModal('profileModal');
    } catch(e) { App.toast(e.message, 'error'); }
  },

  triggerAvatarUpload() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = async (e) => {
      const file = e.target.files[0];
      if (!file) return;

      const form = new FormData();
      form.append('avatar', file);

      try {
        App.toast('جاري الرفع...', 'info');
        const r = await Api.users.avatar(form);
        const url = r.data?.avatar;
        if (url) {
          document.getElementById('profileAvatarPreview').innerHTML = `<img src="${url}" style="width:100%;height:100%;object-fit:cover;border-radius:50%">`;
          App.state.me.avatar = url;
          localStorage.setItem(Config.STORAGE_USER, JSON.stringify(App.state.me));
          App.updateMyInfo();
        }
        App.toast('تم تحديث الصورة ✅', 'success');
      } catch(e) { App.toast(e.message, 'error'); }
    };
    input.click();
  },

  async deleteAvatar() {
    if (!confirm('هل تريد حذف صورتك الشخصية؟')) return;
    try {
      await Api.users.delAvatar();
      App.state.me.avatar = null;
      localStorage.setItem(Config.STORAGE_USER, JSON.stringify(App.state.me));
      App.updateMyInfo();
      document.getElementById('profileAvatarPreview').textContent = App.state.me.name[0].toUpperCase();
      App.toast('تم الحذف', 'success');
    } catch(e) { App.toast(e.message, 'error'); }
  },

  async changePassword() {
    const current = document.getElementById('currentPassword').value;
    const newPass  = document.getElementById('newPassword').value;
    const confirm  = document.getElementById('confirmPassword').value;

    if (!current || !newPass || !confirm) { App.toast(t('err_fill'), 'error'); return; }
    if (newPass !== confirm) { App.toast('كلمتا المرور غير متطابقتين', 'error'); return; }
    if (newPass.length < 8)  { App.toast('كلمة المرور يجب أن تكون 8 أحرف على الأقل', 'error'); return; }

    try {
      await Api.post('/auth/change-password', {current_password: current, password: newPass, password_confirmation: confirm});
      App.toast('تم تغيير كلمة المرور ✅', 'success');
      document.getElementById('currentPassword').value = '';
      document.getElementById('newPassword').value     = '';
      document.getElementById('confirmPassword').value = '';
    } catch(e) { App.toast(e.message, 'error'); }
  },

  async updatePrivacy(setting, value) {
    try {
      await Api.users.privacy({[setting]: value});
      App.toast('تم الحفظ ✅', 'success');
    } catch(e) { App.toast(e.message, 'error'); }
  },
};