\# 📋 Changelog



All notable changes to NexusPlatform are documented here.



Format based on \[Keep a Changelog](https://keepachangelog.com/en/1.0.0/).



\---



\## \[1.0.0] — 2026-07-31



\### 🎉 Initial Release



\---



\### Added — Auth Domain

\- User registration with validation

\- Login with multi-device support (device\_name)

\- Logout (single device or all devices)

\- Active sessions management

\- Change password with session revocation

\- Forgot password \& reset password flow

\- Token-based auth via Laravel Sanctum (30-day expiry)



\### Added — User Domain

\- User profile (view \& update)

\- Avatar upload with automatic WebP conversion

\- Custom status \& presence status

\- Privacy settings (last\_seen, profile\_photo, bio, direct\_messages)

\- User search

\- Block \& unblock users

\- Mute \& unmute users (with duration)

\- Public profile view by username



\### Added — Chat Domain

\- Direct \& group conversations

\- Paginated message history

\- Send text messages with mentions

\- Reply to messages

\- Edit messages (with is\_edited flag)

\- Soft delete messages

\- Forward messages to any conversation

\- Pin \& unpin messages

\- Message reactions (toggle emoji)

\- Read receipts

\- Typing indicators (real-time)

\- Full-text message search



\### Added — Media Domain

\- Image upload with automatic WebP conversion

\- Thumbnail generation (300×300)

\- Voice message upload

\- Document upload (PDF, DOC, TXT, ZIP)

\- MIME-type validation (not just extension)

\- Per-type size limits

\- Conversation media gallery



\### Added — Groups Domain

\- Create public, private, and invite-only groups

\- Role system: Owner → Admin → Moderator → Member

\- Invite users with expiring tokens (7 days)

\- Accept \& decline invitations

\- Join requests with admin approval flow

\- Change member roles

\- Kick, ban, and unban members

\- Mute members (with duration)

\- Transfer group ownership

\- Update group settings

\- Search public groups



\### Added — Notifications Domain

\- In-app notifications (database)

\- Email notifications

\- Real-time broadcast notifications

\- Per-user notification preferences

\- New message notifications

\- Mention notifications

\- Group invitation notifications

\- Join request notifications (to admins)

\- Mark as read (single or all)

\- Delete notifications

\- Notification stats by type



\### Added — Calls Domain

\- Initiate voice calls

\- Initiate video calls

\- Answer calls

\- Reject calls

\- End calls (with duration tracking)

\- WebRTC signaling relay (offer/answer/ICE)

\- Toggle mute

\- Toggle camera

\- Call history

\- Active call check per conversation



\### Added — Real-Time (WebSockets)

\- Laravel Reverb as WebSocket server

\- Private conversation channels

\- Private user channels

\- Public presence channel

\- Real-time message delivery

\- Real-time typing indicators

\- Real-time call events

\- Real-time notifications

\- User presence updates



\### Added — Developer Console

\- Health check dashboard

\- System Doctor with scoring

\- Queue monitor (pending, failed, processed)

\- Redis stats (memory, keys, connections)

\- Log viewer (last 100 lines)

\- Environment info

\- Storage check

\- Reverb connectivity check

\- API Playground



\### Added — Infrastructure

\- Domain-Driven Design architecture

\- Versioned API (/api/v1/)

\- Consistent JSON response format

\- Arabic error messages

\- Exception handler (401/422/404/500)

\- Lazy loading guard (preventLazyLoading)

\- Audit logging (Laravel Auditing)

\- Role \& permission system (Spatie)

\- Full-text search (Laravel Scout)

\- Background job processing (Laravel Queue)

\- Model soft deletes



\### Added — Documentation

\- README.md

\- ARCHITECTURE.md

\- WEBSOCKETS.md

\- TESTING.md

\- DEPLOYMENT.md

\- CONTRIBUTING.md

\- CHANGELOG.md

\- LICENSE

\- .env.example

\- Postman Collection (76 requests / 200+ tests)



\---



\## Upcoming — \[1.1.0]



\### Planned

\- \[ ] Email verification flow

\- \[ ] Two-factor authentication (2FA)

\- \[ ] Message threads (Slack-style)

\- \[ ] Scheduled messages

\- \[ ] File sharing between conversations

\- \[ ] Admin dashboard

\- \[ ] Rate limiting per endpoint

\- \[ ] Push notifications (FCM)

\- \[ ] Docker setup

\- \[ ] CI/CD pipeline (GitHub Actions)

