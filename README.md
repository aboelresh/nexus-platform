<div align="center">

# ⚡ NexusPlatform

**Enterprise Real-Time Communication Platform**

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![Redis](https://img.shields.io/badge/Redis-7.x-DC382D?style=for-the-badge&logo=redis&logoColor=white)](https://redis.io)
[![WebSocket](https://img.shields.io/badge/WebSocket-Reverb-6C47FF?style=for-the-badge)](https://laravel.com/docs/reverb)
[![License](https://img.shields.io/badge/License-MIT-22C55E?style=for-the-badge)](LICENSE)

*A production-ready, feature-rich backend for enterprise communication — built with Domain-Driven Design.*

</div>

---

## 📖 Overview

NexusPlatform is a **full-featured enterprise communication backend** built with Laravel 12, following Domain-Driven Design principles. It powers real-time messaging, voice/video calls, group management, and notifications — all through a clean, versioned REST API.

---

## ✨ Features

### 💬 Messaging
- Real-time direct & group messaging via WebSockets
- Message reactions, replies, forwarding, pinning
- Read receipts & typing indicators
- Soft delete with real-time propagation
- Full-text message search

### 👥 Groups
- Role-based access: Owner → Admin → Moderator → Member
- Invitation system with expiring tokens
- Join requests with admin approval flow
- Per-group mute, ban, and kick controls
- Configurable group settings & permissions

### 📁 Media
- Image upload with automatic WebP conversion & thumbnails
- Voice messages, documents, and video support
- MIME-type validation (not just extension)
- Per-type size limits

### 📞 Calls
- Voice & video call initiation
- WebRTC signaling relay (Offer / Answer / ICE)
- Call history with duration tracking
- Reject / miss / end states

### 🔔 Notifications
- In-app, email, and broadcast (WebSocket) channels
- Per-user notification preferences
- Mention, invitation, and join-request alerts

### 🔐 Security
- Token-based auth via Laravel Sanctum
- Multi-device session management
- Block & mute users
- Privacy settings (last seen, profile photo, DMs)

### 🛠️ Developer Console
- Built-in diagnostics dashboard at `/devtools`
- Health checks: DB, Redis, Queue, Reverb, Storage
- System Doctor with auto-fix suggestions
- Queue monitor, Redis stats, Log viewer, API Playground

---

## 🧰 Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 12 |
| Language | PHP 8.2+ |
| Database | MySQL / MariaDB |
| Cache & Queue | Redis (Predis) |
| WebSocket | Laravel Reverb |
| Auth | Laravel Sanctum |
| Roles | Spatie Permission |
| Images | Intervention Image |
| Audit | Laravel Auditing |
| Debugging | Laravel Telescope |

---

## 📋 Requirements

- PHP >= 8.2 with extensions: `pdo`, `mbstring`, `openssl`, `gd`, `curl`
- MySQL >= 8.0 or MariaDB >= 10.4
- Redis >= 6.0
- Composer >= 2.x

---

## 🚀 Installation

**1. Clone & install:**
```bash
git clone https://github.com/aboelresh/nexus-platform.git
cd nexus-platform
composer install
```

**2. Environment:**
```bash
cp .env.example .env
php artisan key:generate
```

**3. Configure `.env`:**
```env
DB_DATABASE=nexus_platform
DB_USERNAME=root
DB_PASSWORD=

CACHE_STORE=redis
QUEUE_CONNECTION=database
REDIS_CLIENT=predis

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080

APP_CONSOLE_TOKEN=your-secure-token
```

**4. Database:**
```bash
php artisan migrate
php artisan storage:link
```

---

## ▶️ Running

Open **3 terminals**:

```bash
# Terminal 1 — API Server
php artisan serve

# Terminal 2 — Queue Worker
php artisan queue:work --tries=3

# Terminal 3 — WebSocket Server
php artisan reverb:start --debug
```

API available at: `http://localhost:8000/api/v1`

---

## 🧪 Testing

Import `NexusPlatform.postman_collection.json` into Postman.

**Start with:**
1. `Login Ahmed` → token saves automatically
2. `Login Sara` → token_sara saves automatically
3. Run requests in order — all variables auto-save

**Results: 76 requests / 200+ tests — all passing ✅**

---

## 🛠️ Developer Console

http://localhost:8000/devtools?token=your-console-token


---

## 📚 Documentation

| File | Contents |
|---|---|
| [ARCHITECTURE.md](ARCHITECTURE.md) | System design, flows, domain map |
| [WEBSOCKETS.md](WEBSOCKETS.md) | Reverb setup, channels, events |
| [DEPLOYMENT.md](DEPLOYMENT.md) | Production setup, Nginx, Supervisor |
| [TESTING.md](TESTING.md) | Postman collection, test guide |
| [CONTRIBUTING.md](CONTRIBUTING.md) | How to contribute |
| [CHANGELOG.md](CHANGELOG.md) | Version history |

---

## 📄 License

MIT License — see [LICENSE](LICENSE)

---

<div align="center">
Built by Eng\Ahmed Saad using Laravel 12 • PHP 8.2 • Redis • WebSockets
</div>