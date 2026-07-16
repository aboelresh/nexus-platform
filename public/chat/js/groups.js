const Groups = {
  selectedMembers: [],

  openCreateModal() {
    App.openModal('createGroupModal');
    this.selectedMembers = [];
    document.getElementById('groupName').value = '';
    document.getElementById('groupDesc').value = '';
    document.getElementById('groupMemberSearch').value = '';
    document.getElementById('groupMemberResults').innerHTML = '';
    document.getElementById('selectedMembersList').innerHTML = '';
  },

  async searchMembers(q) {
    if (q.length < 2) { document.getElementById('groupMemberResults').innerHTML = ''; return; }
    try {
      const r = await Api.users.search(q);
      const users = r.data || [];
      document.getElementById('groupMemberResults').innerHTML = users
        .filter(u => !this.selectedMembers.find(m => m.id === u.id))
        .map(u => `
          <div class="user-result" onclick="Groups.addMember(${u.id}, '${App.esc(u.name)}', '${App.esc(u.username)}')">
            <div class="avatar" style="width:34px;height:34px;font-size:13px">${u.name[0].toUpperCase()}</div>
            <div class="info">
              <div class="name">${App.esc(u.name)}</div>
              <div class="username">@${App.esc(u.username)}</div>
            </div>
            <button style="background:var(--accent);color:#fff;border:none;border-radius:6px;padding:3px 10px;font-size:12px">+</button>
          </div>
        `).join('');
    } catch(e) {}
  },

  addMember(id, name, username) {
    if (this.selectedMembers.find(m => m.id === id)) return;
    this.selectedMembers.push({id, name, username});
    this.renderSelectedMembers();
    document.getElementById('groupMemberResults').innerHTML = '';
    document.getElementById('groupMemberSearch').value = '';
  },

  removeMember(id) {
    this.selectedMembers = this.selectedMembers.filter(m => m.id !== id);
    this.renderSelectedMembers();
  },

  renderSelectedMembers() {
    document.getElementById('selectedMembersList').innerHTML = this.selectedMembers.map(m => `
      <div style="display:inline-flex;align-items:center;gap:6px;background:var(--bg4);border-radius:20px;padding:4px 10px;font-size:12px;margin:3px">
        <span>${App.esc(m.name)}</span>
        <button onclick="Groups.removeMember(${m.id})" style="background:none;border:none;color:var(--text2);cursor:pointer;font-size:14px;padding:0">✕</button>
      </div>
    `).join('');
  },

  async create() {
    const name = document.getElementById('groupName').value.trim();
    const desc = document.getElementById('groupDesc').value.trim();

    if (!name) { App.toast('اسم المجموعة مطلوب', 'error'); return; }
    if (this.selectedMembers.length === 0) { App.toast('أضف عضواً واحداً على الأقل', 'error'); return; }

    try {
      const r = await Api.conversations.create({
        type: 'group',
        name,
        description: desc,
        members: this.selectedMembers.map(m => m.id),
      });

      await Conversations.load();
      Conversations.open(r.data.id);
      App.closeModal('createGroupModal');
      App.toast('تم إنشاء المجموعة 🎉', 'success');
    } catch(e) { App.toast(e.message, 'error'); }
  },
};