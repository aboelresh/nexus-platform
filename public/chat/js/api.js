const Api = {
  async request(method, url, data, isFormData = false) {
    const token = localStorage.getItem(Config.STORAGE_TOKEN);
    const headers = { 'Accept': 'application/json' };
    if (token) headers['Authorization'] = 'Bearer ' + token;
    if (!isFormData) headers['Content-Type'] = 'application/json';

    const opts = { method, headers };
    if (data) opts.body = isFormData ? data : JSON.stringify(data);

    const r = await fetch(Config.API_BASE + url, opts);
    const json = await r.json();
    if (!r.ok) throw new Error(json.message || json.errors ? Object.values(json.errors || {})[0]?.[0] : t('err_connect'));
    return json;
  },

  get:    (url)        => Api.request('GET', url),
  post:   (url, data)  => Api.request('POST', url, data),
  put:    (url, data)  => Api.request('PUT', url, data),
  delete: (url)        => Api.request('DELETE', url),
  upload: (url, form)  => Api.request('POST', url, form, true),

  auth: {
    login:    (d) => Api.post('/auth/login', d),
    register: (d) => Api.post('/auth/register', d),
    logout:   ()  => Api.post('/auth/logout'),
    me:       ()  => Api.get('/auth/me'),
  },

  conversations: {
    list:     ()     => Api.get('/conversations'),
    get:      (id)   => Api.get(`/conversations/${id}`),
    create:   (d)    => Api.post('/conversations', d),
    read:     (id)   => Api.post(`/conversations/${id}/read`),
    archive:  (id)   => Api.post(`/conversations/${id}/archive`),
    typing:   (id,v) => Api.post(`/conversations/${id}/typing`, {is_typing: v}),
  },

  messages: {
    list:    (cid, page=1) => Api.get(`/conversations/${cid}/messages?page=${page}`),
    send:    (cid, d)      => Api.post(`/conversations/${cid}/messages`, d),
    edit:    (cid, mid, d) => Api.put(`/conversations/${cid}/messages/${mid}`, d),
    delete:  (cid, mid)    => Api.delete(`/conversations/${cid}/messages/${mid}`),
    react:   (cid, mid, e) => Api.post(`/conversations/${cid}/messages/${mid}/react`, {emoji: e}),
    pin:     (cid, mid)    => Api.post(`/conversations/${cid}/messages/${mid}/pin`),
    forward: (cid, mid, d) => Api.post(`/conversations/${cid}/messages/${mid}/forward`, d),
    pinned:  (cid)         => Api.get(`/conversations/${cid}/messages/pinned`),
  },

  users: {
    search:    (q)    => Api.get(`/users/search?q=${encodeURIComponent(q)}`),
    profile:   ()     => Api.get('/users/profile'),
    update:    (d)    => Api.put('/users/profile', d),
    avatar:    (form) => Api.upload('/users/avatar', form),
    delAvatar: ()     => Api.delete('/users/avatar'),
    status:    (d)    => Api.put('/users/status', d),
    privacy:   (d)    => Api.put('/users/privacy', d),
  },

  media: {
    upload: (form) => Api.upload('/media/upload', form),
    delete: (id)   => Api.delete(`/media/${id}`),
  },
};