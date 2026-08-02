\# 🏗️ NexusPlatform — Architecture



\## Request Lifecycle



Client (HTTP)

│

▼

routes/api.php ← Versioned routes (/api/v1/...)

│

▼

Middleware ← auth:sanctum, throttle

│

▼

FormRequest ← Validation

│

▼

Controller ← HTTP only, no business logic

│

▼

Service ← All business logic

│

▼

Model (Eloquent) ← Database interaction

│

▼

Resource ← JSON response transformation

│

▼

Client





\---



\## Real-Time Event Flow



Client sends message

│

▼

MessageController@store

│

▼

MessageService::sendMessage()

├── Save to DB

├── Update last\_message\_at

├── Mark as read (sender)

└── broadcast(MessageSent)

│

▼

Laravel Queue

(database driver)

│

▼

Queue Worker

(php artisan queue:work)

│

▼

Laravel Reverb

(WebSocket Server :8080)

│

▼

private-conversation.{id}

│

┌─────┴─────┐

▼ ▼

Client A Client B





\---



\## Notification Flow



MessageSent Event

│

▼

SendMessageNotificationListener (Queued)

│

▼

NotificationService::notifyNewMessage()

├── Check: user blocked?

└── Check: notifications enabled?

│

▼

User::notify(NewMessageNotification)

│

┌─────┼─────┐

▼ ▼ ▼

DB Mail Broadcast

│

▼

private-user.{id}





\---



\## WebRTC Call Flow



Ahmed clicks Call

│

▼

POST /api/v1/calls/initiate

│

CallService::initiateCall()

├── Create Call (status: ringing)

├── Add Ahmed as participant

└── broadcast(CallInitiated) → private-user.{sara\_id}

│

▼

Sara receives call.initiated

│

▼

POST /api/v1/calls/{id}/answer

│

CallService::answerCall()

├── status → ongoing

├── Add Sara as participant

└── broadcast(CallAnswered) → private-user.{ahmed\_id}

│

▼

WebRTC Signaling:

Ahmed → POST /signal (offer SDP) → Sara

Sara → POST /signal (answer SDP) → Ahmed

Both → POST /signal (ICE candidates) → each other

│

▼

P2P Connection Established

│

▼

POST /api/v1/calls/{id}/end

├── Calculate duration

├── All participants → left

└── broadcast(CallEnded) → all participants





\---



\## Domain Map



┌─────────────────────────────────────────────────┐

│ DOMAINS │

│ │

│ ┌──────────┐ ┌──────────┐ ┌──────────────┐ │

│ │ Auth │ │ User │ │ Chat │ │

│ │ Register │ │ Profile │ │Conversations │ │

│ │ Login │ │ Avatar │ │ Messages │ │

│ │ Sessions │ │ Status │ │ Reactions │ │

│ │ Password │ │ Privacy │ │ Search │ │

│ └──────────┘ │ Block │ │ Typing │ │

│ │ Mute │ └──────────────┘ │

│ └──────────┘ │

│ ┌──────────┐ ┌──────────┐ ┌──────────────┐ │

│ │ Group │ │ Media │ │Notification │ │

│ │ CRUD │ │ Images │ │ In-App │ │

│ │ Members │ │ Voice │ │ Email │ │

│ │ Roles │ │ Files │ │ Broadcast │ │

│ │ Invites │ │ WebP │ │ Preferences │ │

│ │ Requests │ │ Thumbs │ └──────────────┘ │

│ └──────────┘ └──────────┘ │

│ │

│ ┌──────────┐ ┌──────────┐ ┌──────────────┐ │

│ │ Call │ │ Presence │ │ DevConsole │ │

│ │ Voice │ │ Online │ │ Health Check │ │

│ │ Video │ │ Offline │ │ System Doctor│ │

│ │ WebRTC │ │ Away │ │ Queue Monitor│ │

│ │ History │ │ Busy │ │ Log Viewer │ │

│ └──────────┘ └──────────┘ │ API Playground│ │

│ └──────────────┘ │

└─────────────────────────────────────────────────┘





\---



\## Directory Structure



app/

├── Domains/

│ ├── Auth/

│ │ ├── Controllers/

│ │ ├── Services/ ← AuthService, TokenService

│ │ ├── Requests/ ← RegisterRequest, LoginRequest

│ │ ├── Resources/ ← AuthUserResource

│ │ └── Notifications/

│ ├── User/

│ │ ├── Controllers/

│ │ ├── Models/ ← User.php

│ │ ├── Services/ ← ProfileService, UserService

│ │ ├── Requests/

│ │ └── Resources/

│ ├── Chat/

│ │ ├── Controllers/

│ │ ├── Models/ ← Conversation, Message, ...

│ │ ├── Services/ ← ConversationService, MessageService

│ │ ├── Events/ ← MessageSent, MessageUpdated, ...

│ │ └── Resources/

│ ├── Media/

│ │ ├── Controllers/

│ │ ├── Models/

│ │ └── Services/ ← MediaService, ImageService

│ ├── Group/

│ │ ├── Controllers/

│ │ ├── Models/ ← Group, GroupMember, ...

│ │ └── Services/ ← GroupService, GroupMemberService

│ ├── Call/

│ │ ├── Controllers/

│ │ ├── Models/ ← Call, CallParticipant

│ │ ├── Services/ ← CallService

│ │ └── Events/ ← CallInitiated, CallEnded, ...

│ ├── Notification/

│ │ ├── Controllers/

│ │ ├── Services/ ← NotificationService

│ │ ├── Listeners/ ← SendMessageNotificationListener

│ │ └── Notifications/ ← NewMessageNotification, ...

│ └── DevConsole/

│ ├── Controllers/

│ └── Services/ ← DiagnosticsService

└── Infrastructure/

├── Http/

│ ├── Controllers/ ← Base Controller

│ └── Middleware/ ← DevConsoleMiddleware

└── Providers/





\---



\## Database Schema



users

├── user\_blocks

└── user\_mutes



conversations

├── conversation\_participants

└── messages

├── message\_reactions

├── message\_reads

├── message\_mentions

└── media



groups

├── group\_members

├── group\_invitations

└── group\_join\_requests



calls

└── call\_participants



notifications ← Laravel default (UUID PK)

audits ← Laravel Auditing

personal\_access\_tokens ← Sanctum

roles + permissions ← Spatie





\---



\## Key Design Decisions



\*\*Why DDD?\*\*

Grouping by business domain makes the codebase easier to navigate, scale, and eventually split into microservices.



\*\*Why Services?\*\*

Controllers handle HTTP only. Services are reusable from Controllers, Commands, Jobs, and Tests.



\*\*Why Queued Broadcasting?\*\*

HTTP response returns immediately. Broadcasting happens in background with automatic retries.

Exception: `WebRTCSignal` skips the queue for instant delivery.



\*\*Why Soft Deletes?\*\*

Preserves audit trail, enables "message deleted" placeholders, and allows data recovery.



\*\*Why `preventLazyLoading`?\*\*

Catches N+1 queries during development before they reach production.

