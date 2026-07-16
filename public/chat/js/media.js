const Media = {
  currentAudio: null,
  currentVoiceBtn: null,

  async uploadAndSend(file, type) {
    if (!App.state.currentConv) return;

    const maxSize = type === 'image' ? Config.MAX_IMAGE_SIZE : Config.MAX_FILE_SIZE;
    if (file.size > maxSize) {
      App.toast(`حجم الملف كبير جداً (الحد: ${App.formatFileSize(maxSize)})`, 'error');
      return;
    }

    const form = new FormData();
    form.append('file', file);
    form.append('type', type);

    try {
      App.toast('جاري الرفع...', 'info');
      const mediaRes = await Api.media.upload(form);
      const media = mediaRes.data;

      const msgRes = await Api.messages.send(App.state.currentConv.id, {
        type,
        body: null,
        media_ids: [media.id],
      });

      Messages.append(msgRes.data);
      Conversations.updateLastMsg(App.state.currentConv.id, `📎 ${type}`, msgRes.data.created_at);
      App.toast('تم الرفع ✅', 'success');
    } catch(e) {
      App.toast(e.message, 'error');
    }
  },

  triggerImagePicker() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = (e) => {
      const file = e.target.files[0];
      if (file) this.uploadAndSend(file, 'image');
    };
    input.click();
  },

  triggerFilePicker() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.pdf,.doc,.docx,.zip,.txt,.xlsx,.pptx';
    input.onchange = (e) => {
      const file = e.target.files[0];
      if (file) this.uploadAndSend(file, 'document');
    };
    input.click();
  },

  async startVoiceRecording() {
    if (App.state.isRecording) { this.stopVoiceRecording(); return; }

    try {
      const stream = await navigator.mediaDevices.getUserMedia({audio: true});
      App.state.mediaRecorder = new MediaRecorder(stream);
      App.state.audioChunks   = [];
      App.state.isRecording   = true;

      const btn = document.getElementById('voiceBtn');
      if (btn) { btn.textContent = '⏹'; btn.style.color = 'var(--red)'; }

      App.state.mediaRecorder.ondataavailable = (e) => App.state.audioChunks.push(e.data);
      App.state.mediaRecorder.start();

      App.toast('يتم التسجيل... اضغط مجدداً للإيقاف 🔴', 'info', 10000);
    } catch(e) {
      App.toast('لا يمكن الوصول للميكروفون', 'error');
    }
  },

  stopVoiceRecording() {
    if (!App.state.mediaRecorder) return;

    App.state.mediaRecorder.onstop = () => {
      const blob = new Blob(App.state.audioChunks, {type: 'audio/webm'});
      const file = new File([blob], 'voice-' + Date.now() + '.webm', {type: 'audio/webm'});
      this.uploadAndSend(file, 'voice');
    };

    App.state.mediaRecorder.stop();
    App.state.mediaRecorder.stream.getTracks().forEach(t => t.stop());
    App.state.isRecording = false;

    const btn = document.getElementById('voiceBtn');
    if (btn) { btn.textContent = '🎤'; btn.style.color = ''; }
  },

  playVoice(btn, url) {
    if (this.currentAudio && !this.currentAudio.paused) {
      this.currentAudio.pause();
      if (this.currentVoiceBtn) this.currentVoiceBtn.textContent = '▶';
      if (this.currentVoiceBtn === btn) { this.currentAudio = null; return; }
    }

    const audio = new Audio(url);
    this.currentAudio    = audio;
    this.currentVoiceBtn = btn;
    btn.textContent = '⏸';

    audio.play();
    audio.onended = () => { btn.textContent = '▶'; };
    audio.onerror = () => { App.toast('خطأ في تشغيل الصوت', 'error'); btn.textContent = '▶'; };
  },

  lightbox(url) {
    const lb = document.getElementById('lightbox');
    const img = lb.querySelector('img');
    img.src = url;
    lb.classList.add('open');
  },

  closeLightbox() {
    document.getElementById('lightbox').classList.remove('open');
  },

  handlePaste(e) {
    const items = e.clipboardData?.items;
    if (!items) return;
    for (const item of items) {
      if (item.type.startsWith('image/')) {
        const file = item.getAsFile();
        if (file) { e.preventDefault(); this.uploadAndSend(file, 'image'); }
      }
    }
  },
};