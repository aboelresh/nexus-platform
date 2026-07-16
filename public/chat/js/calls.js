const Calls = {
  localStream: null,
  peerConnection: null,
  callType: null,
  isInCall: false,

  async startCall(type) {
    if (!App.state.currentConv) return;
    this.callType = type;

    const constraints = { audio: true, video: type === 'video' };
    try {
      this.localStream = await navigator.mediaDevices.getUserMedia(constraints);
      this.showCallUI(type, false);

      if (type === 'video') {
        document.getElementById('localVideo').srcObject = this.localStream;
      }

      App.toast(type === 'video' ? '📹 جاري بدء مكالمة الفيديو...' : '📞 جاري الاتصال...', 'info');
    } catch(e) {
      App.toast('لا يمكن الوصول للكاميرا/الميكروفون', 'error');
    }
  },

  showCallUI(type, incoming) {
    const overlay = document.getElementById('callOverlay');
    const name    = App.state.currentConv?.name || t('direct');

    overlay.innerHTML = `
      <div style="background:var(--bg2);border-radius:20px;padding:40px;text-align:center;min-width:300px;box-shadow:var(--shadow)">
        ${type === 'video' ? `
          <div style="position:relative;width:300px;height:200px;background:#000;border-radius:12px;margin:0 auto 20px;overflow:hidden">
            <video id="remoteVideo" autoplay playsinline style="width:100%;height:100%;object-fit:cover"></video>
            <video id="localVideo"  autoplay muted playsinline style="position:absolute;bottom:8px;inset-inline-end:8px;width:80px;height:60px;object-fit:cover;border-radius:8px;border:2px solid var(--bg2)"></video>
          </div>
        ` : `
          <div class="avatar" style="width:80px;height:80px;font-size:30px;margin:0 auto 16px;background:var(--accent)">${name[0].toUpperCase()}</div>
        `}
        <div style="font-size:20px;font-weight:700;margin-bottom:8px">${App.esc(name)}</div>
        <div style="color:var(--text2);font-size:14px;margin-bottom:28px" id="callStatus">${incoming ? 'مكالمة واردة...' : 'جاري الاتصال...'}</div>
        <div style="display:flex;justify-content:center;gap:16px">
          ${incoming ? `<button onclick="Calls.accept()" style="width:56px;height:56px;border-radius:50%;background:var(--green);border:none;font-size:22px;cursor:pointer">📞</button>` : ''}
          <button onclick="Calls.toggleMute()" id="muteBtn" style="width:56px;height:56px;border-radius:50%;background:var(--bg4);border:none;font-size:22px;cursor:pointer">🎤</button>
          ${type === 'video' ? `<button onclick="Calls.toggleCamera()" id="cameraBtn" style="width:56px;height:56px;border-radius:50%;background:var(--bg4);border:none;font-size:22px;cursor:pointer">📷</button>` : ''}
          <button onclick="Calls.endCall()" style="width:56px;height:56px;border-radius:50%;background:var(--red);border:none;font-size:22px;cursor:pointer">📵</button>
        </div>
      </div>
    `;
    overlay.style.display = 'flex';
    this.isInCall = true;
    this.startTimer();
  },

  startTimer() {
    let seconds = 0;
    this.callTimer = setInterval(() => {
      seconds++;
      const m = Math.floor(seconds / 60).toString().padStart(2,'0');
      const s = (seconds % 60).toString().padStart(2,'0');
      const el = document.getElementById('callStatus');
      if (el) el.textContent = `${m}:${s}`;
    }, 1000);
  },

  toggleMute() {
    if (!this.localStream) return;
    const track = this.localStream.getAudioTracks()[0];
    if (track) {
      track.enabled = !track.enabled;
      const btn = document.getElementById('muteBtn');
      if (btn) btn.textContent = track.enabled ? '🎤' : '🔇';
    }
  },

  toggleCamera() {
    if (!this.localStream) return;
    const track = this.localStream.getVideoTracks()[0];
    if (track) {
      track.enabled = !track.enabled;
      const btn = document.getElementById('cameraBtn');
      if (btn) btn.textContent = track.enabled ? '📷' : '📷';
    }
  },

  accept() {
    const el = document.getElementById('callStatus');
    if (el) el.textContent = '00:00';
    App.toast('تم قبول المكالمة', 'success');
  },

  endCall() {
    clearInterval(this.callTimer);
    this.localStream?.getTracks().forEach(t => t.stop());
    this.peerConnection?.close();
    this.localStream    = null;
    this.peerConnection = null;
    this.isInCall       = false;
    document.getElementById('callOverlay').style.display = 'none';
    App.toast('انتهت المكالمة', 'info');
  },
};