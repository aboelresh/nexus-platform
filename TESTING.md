\# 🧪 NexusPlatform — Testing Guide



\## Overview



NexusPlatform uses a \*\*Postman Collection\*\* with 76 requests and 200+ automated tests covering all API endpoints.



\---



\## Prerequisites



Before running tests, ensure all 3 services are running:



```bash

\# Terminal 1

php artisan serve



\# Terminal 2

php artisan queue:work --tries=3



\# Terminal 3

php artisan reverb:start --debug

```



Also ensure the database has the two test users:



```bash

php artisan tinker

```



```php

// Create Ahmed (if not exists)

\\App\\Domains\\User\\Models\\User::firstOrCreate(

&#x20;   \['email' => 'ahmed@test.com'],

&#x20;   \[

&#x20;       'name'     => 'Ahmed Developer',

&#x20;       'username' => 'ahmed\_test',

&#x20;       'password' => bcrypt('password123'),

&#x20;       'is\_active'=> true,

&#x20;   ]

);



// Create Sara (if not exists)

\\App\\Domains\\User\\Models\\User::firstOrCreate(

&#x20;   \['email' => 'sara@test.com'],

&#x20;   \[

&#x20;       'name'     => 'Sara Test',

&#x20;       'username' => 'sara\_test',

&#x20;       'password' => bcrypt('password123'),

&#x20;       'is\_active'=> true,

&#x20;   ]

);

```



```bash

exit

```



\---



\## Postman Collection Setup



\### Import



1\. Open Postman

2\. Click \*\*Import\*\*

3\. Select `NexusPlatform.postman\_collection.json`

4\. Collection appears as \*\*"NexusPlatform API - Complete"\*\*



\### Collection Variables



Variables auto-save between requests — no manual setup needed:



| Variable | Set By | Used By |

|---|---|---|

| `base\_url` | Pre-set | All requests |

| `token` | Login Ahmed | All Ahmed requests |

| `token\_sara` | Login Sara | Sara requests |

| `conversation\_id` | Start Direct Conv | Message requests |

| `message\_id` | Send Message | Edit, Delete, React |

| `group\_id` | Create Group | Group member requests |

| `call\_id` | Initiate Call | Answer, End, Signal |

| `media\_id` | Upload Image | Send with Image |

| `invite\_token` | Invite User | Accept Invitation |



\---



\## Running Tests



\### Option 1: Run Collection (All at once)



1\. Right-click the collection → \*\*Run collection\*\*

2\. Click \*\*Run NexusPlatform API\*\*

3\. Watch all 76 requests execute in order



\### Option 2: Run Folder by Folder



Run each folder separately to isolate issues:

\- `01 - Auth` → `02 - User Profile` → `03 - Conversations` → ...



\### Option 3: Run Individual Request



Click any request → \*\*Send\*\* → check Tests tab for results.



\---



\## Test Results



\### Expected Results



| Module | Requests | Status |

|---|---|---|

| 🔐 Auth | 9 | ✅ All pass |

| 👤 User Profile | 8 | ✅ All pass |

| 💬 Conversations | 5 | ✅ All pass |

| 📨 Messages | 13 | ✅ All pass |

| 📁 Media | 7 | ✅ All pass |

| 👥 Groups | 10 | ✅ All pass |

| 🔔 Notifications | 6 | ✅ All pass |

| 📞 Calls | 10 | ✅ All pass |

| 🏥 Health \& Security | 6 | ✅ All pass |

| 🚪 Logout | 2 | ✅ All pass |

| \*\*Total\*\* | \*\*76\*\* | \*\*✅ All pass\*\* |



\---



\## Media Tests (Manual Step Required)



Media upload requests require selecting a file manually in Postman:



| Request | File to Select |

|---|---|

| Upload Image | Any `.jpg`, `.png`, or `.webp` |

| Upload Voice | Any `.mp3`, `.m4a`, or `.wav` |

| Upload Document | Any `.pdf`, `.doc`, or `.txt` |

| Reject Invalid File | Any `.php` or `.exe` file |



\*\*Steps:\*\*

1\. Open the request

2\. Go to \*\*Body\*\* tab → \*\*form-data\*\*

3\. Click the `file` field → select your file

4\. Click \*\*Send\*\*



\---



\## Calls Tests (Important Notes)



Before running Calls tests:



```bash

php artisan tinker

```



```php

// Clear any stuck active calls

\\App\\Domains\\Call\\Models\\Call::whereIn('status', \['ringing', 'ongoing'])

&#x20;   ->update(\['status' => 'ended', 'ended\_at' => now(), 'duration' => 0]);

```



```bash

exit

```



Run requests in this exact order:

1\. `01. Initiate Voice Call` → saves `call\_id`

2\. `02. Sara Answers Call` → uses `token\_sara`

3\. `03. WebRTC Signal - Offer` → uses `call\_id`

4\. `04. WebRTC Signal - ICE` → uses `call\_id`

5\. `05. Toggle Mute` → uses `call\_id`

6\. `06. Toggle Unmute` → uses `call\_id`

7\. `07. End Call` → uses `call\_id`

8\. `08. Initiate Video Call` → saves new `call\_id`

9\. `09. Reject Call (Sara)` → uses `token\_sara`

10\. `10. Call History` → verifies all calls recorded



\---



\## Manual cURL Tests



\### Health Check

```bash

curl http://localhost:8000/api/ping

```



\### Register

```bash

curl -X POST http://localhost:8000/api/v1/auth/register \\

&#x20; -H "Content-Type: application/json" \\

&#x20; -d '{

&#x20;   "name": "Test User",

&#x20;   "username": "test\_user",

&#x20;   "email": "test@test.com",

&#x20;   "password": "password123",

&#x20;   "password\_confirmation": "password123",

&#x20;   "device\_name": "curl"

&#x20; }'

```



\### Login

```bash

curl -X POST http://localhost:8000/api/v1/auth/login \\

&#x20; -H "Content-Type: application/json" \\

&#x20; -d '{"email":"ahmed@test.com","password":"password123","device\_name":"curl"}'

```



\### Send Message

```bash

curl -X POST http://localhost:8000/api/v1/conversations/1/messages \\

&#x20; -H "Content-Type: application/json" \\

&#x20; -H "Authorization: Bearer YOUR\_TOKEN" \\

&#x20; -d '{"body":"Hello from cURL!"}'

```



\### Upload Image

```bash

curl -X POST http://localhost:8000/api/v1/media/upload \\

&#x20; -H "Authorization: Bearer YOUR\_TOKEN" \\

&#x20; -F "file=@/path/to/image.jpg" \\

&#x20; -F "type=image"

```



\---



\## Developer Console Tests



The DevConsole has a built-in API Playground at:



http://localhost:8000/devtools?token=your-console-token





Use it to test any endpoint interactively without Postman.



\---



\## Validation Tests



The collection includes negative tests to verify the API correctly rejects bad input:



| Test | Expected |

|---|---|

| Register with short password | `422` with Arabic error message |

| Access protected route without token | `401 Unauthenticated` |

| Upload `.php` file as image | `422` MIME type rejected |

| Use token after logout | `401 Unauthenticated` |

| Start call when one is active | `422` with Arabic error |



\---



\## Real-Time Verification



To verify WebSocket events work:



1\. Open `http://localhost:8000/devtools?token=your-console-token`

2\. Go to \*\*API Playground\*\* tab

3\. Or use the WebSocket test page at `http://localhost:8000/ws-test.html`



Then send a message via Postman and watch the event appear in real-time.



\---



\## Troubleshooting



| Issue | Fix |

|---|---|

| Variables not saving | Run Login requests first |

| `401` on all requests | Token expired — run Login again |

| Call tests failing | Clear active calls via tinker |

| Notification tests failing | Ensure `queue:work` is running |

| Media upload failing | Manually select file in Body tab |

