\# 🔌 NexusPlatform — WebSockets Guide



\## Overview



NexusPlatform uses \*\*Laravel Reverb\*\* as the WebSocket server.

Reverb is Laravel's official first-party WebSocket server, compatible with the Pusher protocol.



\---



\## Setup



\### Start the WebSocket Server



```bash

php artisan reverb:start --debug

```



Server runs on `localhost:8080` by default.



\### Environment Variables



```env

BROADCAST\_CONNECTION=reverb



REVERB\_APP\_ID=your-app-id

REVERB\_APP\_KEY=your-app-key

REVERB\_APP\_SECRET=your-app-secret

REVERB\_HOST=localhost

REVERB\_PORT=8080

REVERB\_SCHEME=http

```



\---



\## Channel Types



\### 1. Private Conversation Channel



private-conversation.{conversationId}



\- \*\*Auth:\*\* User must be a participant in the conversation

\- \*\*Used for:\*\* Messages, typing indicators, reactions



\### 2. Private User Channel



private-user.{userId}



\- \*\*Auth:\*\* User ID must match authenticated user

\- \*\*Used for:\*\* Incoming calls, call signals, personal notifications



\### 3. Public Presence Channel



presence



\- \*\*Auth:\*\* Any authenticated user

\- \*\*Used for:\*\* Online/offline status updates



\---



\## Channel Authorization



Channels are authorized in `routes/channels.php`:



```php

// Conversation channel — must be a participant

Broadcast::channel('conversation.{conversationId}', function (User $user, int $conversationId) {

&#x20;   $conversation = Conversation::find($conversationId);

&#x20;   if (!$conversation) return false;

&#x20;   return $conversation->hasParticipant($user->id);

});



// User channel — must be the same user

Broadcast::channel('user.{userId}', function (User $user, int $userId) {

&#x20;   return $user->id === $userId;

});



// Presence channel — any authenticated user

Broadcast::channel('presence', function (User $user) {

&#x20;   return \[

&#x20;       'id'       => $user->id,

&#x20;       'name'     => $user->name,

&#x20;       'username' => $user->username,

&#x20;   ];

});

```



The auth endpoint is registered in `routes/api.php`:

```php

Route::post('/broadcasting/auth', function (Request $request) {

&#x20;   return Broadcast::auth($request);

})->middleware('auth:sanctum');

```



\---



\## Events Reference



\### Conversation Events



| Event | Channel | Payload |

|---|---|---|

| `message.sent` | `private-conversation.{id}` | message object |

| `message.updated` | `private-conversation.{id}` | message object |

| `message.deleted` | `private-conversation.{id}` | message\_id, conversation\_id |

| `user.typing` | `private-conversation.{id}` | user\_id, name, is\_typing |



\### Call Events



| Event | Channel | Payload |

|---|---|---|

| `call.initiated` | `private-user.{id}` | call\_id, type, caller |

| `call.answered` | `private-user.{id}` | call\_id, started\_at |

| `call.ended` | `private-user.{id}` | call\_id, duration, status |

| `webrtc.signal` | `private-user.{id}` | call\_id, signal\_type, payload |



\### Presence Events



| Event | Channel | Payload |

|---|---|---|

| `user.presence` | `presence` | user\_id, username, presence\_status |



\---



\## Frontend Integration



\### Install Pusher JS



```html

<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>

```



\### Initialize Connection



```javascript

const pusher = new Pusher('YOUR\_REVERB\_APP\_KEY', {

&#x20;   wsHost: 'localhost',

&#x20;   wsPort: 8080,

&#x20;   forceTLS: false,

&#x20;   enabledTransports: \['ws'],

&#x20;   cluster: 'mt1',

&#x20;   channelAuthorization: {

&#x20;       endpoint: 'http://localhost:8000/api/broadcasting/auth',

&#x20;       transport: 'ajax',

&#x20;       headers: {

&#x20;           Authorization: 'Bearer ' + userToken,

&#x20;           Accept: 'application/json',

&#x20;           'X-Requested-With': 'XMLHttpRequest',

&#x20;       }

&#x20;   }

});

```



\### Subscribe to Conversation



```javascript

const channel = pusher.subscribe('private-conversation.1');



channel.bind('message.sent', (data) => {

&#x20;   console.log('New message:', data.message);

});



channel.bind('message.updated', (data) => {

&#x20;   console.log('Message edited:', data.message);

});



channel.bind('message.deleted', (data) => {

&#x20;   console.log('Message deleted:', data.message\_id);

});



channel.bind('user.typing', (data) => {

&#x20;   if (data.is\_typing) {

&#x20;       console.log(data.name + ' is typing...');

&#x20;   }

});

```



\### Subscribe to User Channel (Calls)



```javascript

const userChannel = pusher.subscribe('private-user.' + myUserId);



userChannel.bind('call.initiated', (data) => {

&#x20;   console.log('Incoming call from:', data.caller.name);

&#x20;   console.log('Call type:', data.type); // voice or video

&#x20;   console.log('Call ID:', data.call\_id);

});



userChannel.bind('call.answered', (data) => {

&#x20;   console.log('Call accepted, started at:', data.started\_at);

});



userChannel.bind('call.ended', (data) => {

&#x20;   console.log('Call ended. Duration:', data.duration, 'seconds');

});



userChannel.bind('webrtc.signal', (data) => {

&#x20;   console.log('WebRTC signal:', data.signal\_type);

&#x20;   // Handle offer, answer, or ice-candidate

});

```



\### Connection Status



```javascript

pusher.connection.bind('connected', () => {

&#x20;   console.log('WebSocket connected ✅');

});



pusher.connection.bind('disconnected', () => {

&#x20;   console.log('WebSocket disconnected ❌');

});



pusher.connection.bind('error', (err) => {

&#x20;   console.error('WebSocket error:', err);

});

```



\---



\## WebRTC Signaling



The backend acts as a \*\*signaling relay\*\* only.

It does not process SDP or ICE — it just forwards them between users.



\### Signal Types



| Type | Direction | Purpose |

|---|---|---|

| `offer` | Caller → Callee | SDP offer to initiate connection |

| `answer` | Callee → Caller | SDP answer to accept connection |

| `ice-candidate` | Both ways | ICE candidate exchange |

| `renegotiate` | Both ways | Renegotiate media after connection |



\### Send a Signal



```javascript

// After call is answered, send SDP offer

await fetch('/api/v1/calls/' + callId + '/signal', {

&#x20;   method: 'POST',

&#x20;   headers: {

&#x20;       'Authorization': 'Bearer ' + token,

&#x20;       'Content-Type': 'application/json',

&#x20;   },

&#x20;   body: JSON.stringify({

&#x20;       to\_user\_id: targetUserId,

&#x20;       signal\_type: 'offer',

&#x20;       payload: {

&#x20;           type: 'offer',

&#x20;           sdp: peerConnection.localDescription.sdp

&#x20;       }

&#x20;   })

});

```



\### Receive a Signal



```javascript

userChannel.bind('webrtc.signal', async (data) => {

&#x20;   if (data.signal\_type === 'offer') {

&#x20;       await peerConnection.setRemoteDescription(data.payload);

&#x20;       const answer = await peerConnection.createAnswer();

&#x20;       await peerConnection.setLocalDescription(answer);

&#x20;       // Send answer back

&#x20;   } else if (data.signal\_type === 'answer') {

&#x20;       await peerConnection.setRemoteDescription(data.payload);

&#x20;   } else if (data.signal\_type === 'ice-candidate') {

&#x20;       await peerConnection.addIceCandidate(data.payload);

&#x20;   }

});

```



\---



\## Typing Indicator



\### Send Typing



```javascript

// Send when user starts typing

await fetch('/api/v1/conversations/' + convId + '/typing', {

&#x20;   method: 'POST',

&#x20;   headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json' },

&#x20;   body: JSON.stringify({ is\_typing: true })

});



// Send after user stops (debounce 2 seconds)

setTimeout(() => {

&#x20;   fetch('/api/v1/conversations/' + convId + '/typing', {

&#x20;       method: 'POST',

&#x20;       headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json' },

&#x20;       body: JSON.stringify({ is\_typing: false })

&#x20;   });

}, 2000);

```



\### Receive Typing



```javascript

channel.bind('user.typing', (data) => {

&#x20;   if (data.user\_id === myUserId) return; // ignore own typing



&#x20;   if (data.is\_typing) {

&#x20;       showTypingIndicator(data.name);

&#x20;   } else {

&#x20;       hideTypingIndicator();

&#x20;   }

});

```



\---



\## Troubleshooting



| Issue | Solution |

|---|---|

| `Application does not exist` | Check `REVERB\_APP\_KEY` matches in `.env` and frontend config |

| `403 on /broadcasting/auth` | Ensure `auth:sanctum` middleware and correct token |

| `404 on /broadcasting/auth` | Check `Broadcast::routes()` registered in `routes/api.php` |

| Events not received | Ensure `php artisan queue:work` is running |

| Connection refused | Ensure `php artisan reverb:start` is running on port 8080 |

| IPv6 issues on Windows | Use `127.0.0.1` instead of `localhost` in `REVERB\_HOST` |

