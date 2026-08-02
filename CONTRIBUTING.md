\# Contributing to NexusPlatform



Thank you for your interest in contributing!



\---



\## Development Setup



Follow the \[Installation Guide](README.md#-installation) to set up your local environment.



\---



\## Branching Strategy



main ← Production-ready code only

develop ← Integration branch

feature/\* ← New features (e.g. feature/voice-messages)

fix/\* ← Bug fixes (e.g. fix/lazy-load-participants)

hotfix/\* ← Critical production fixes

docs/\* ← Documentation updates





\### Workflow



```bash

\# Start a new feature

git checkout develop

git pull origin develop

git checkout -b feature/your-feature-name



\# Work on your feature

git add .

git commit -m "feat: add your feature description"



\# Push and open PR

git push origin feature/your-feature-name

```



\---



\## Commit Message Format



Follow \[Conventional Commits](https://www.conventionalcommits.org/):



type: short description



Types:

feat → New feature

fix → Bug fix

docs → Documentation only

refactor → Code change (no feature/fix)

perf → Performance improvement

test → Adding or updating tests

chore → Build process or tooling





\### Examples



feat: add voice message support in Media domain

fix: resolve lazy loading error in GroupMemberResource

docs: update WebSocket setup guide

refactor: extract notification logic to NotificationService

perf: add eager loading for conversation participants

chore: update composer dependencies





\---



\## Code Standards



\### PHP / Laravel



\- Follow PSR-12 coding standards

\- Use type hints on all methods

\- No business logic in Controllers — use Services

\- All relationships must be eager loaded (no lazy loading)

\- Use Form Requests for all validation

\- Use API Resources for all responses



\### Naming Conventions



```php

// Controllers — singular, suffixed

class MessageController {}



// Services — singular, suffixed

class MessageService {}



// Models — singular

class Message {}



// Resources — singular, suffixed

class MessageResource {}



// Events — past tense

class MessageSent {}



// Listeners — descriptive

class SendMessageNotificationListener {}

```



\### Response Format



Always return consistent responses:



```php

// Success

return response()->json(\[

&#x20;   'status'  => true,

&#x20;   'message' => 'تم بنجاح.',

&#x20;   'data'    => new MessageResource($message),

], 201);



// Error

return response()->json(\[

&#x20;   'status'  => false,

&#x20;   'message' => 'حدث خطأ.',

], 422);

```



\---



\## Adding a New Domain



Follow this structure for any new domain:



app/Domains/YourDomain/

├── Controllers/

│ └── YourDomainController.php

├── Models/

│ └── YourModel.php

├── Services/

│ └── YourDomainService.php

├── Requests/

│ └── CreateYourModelRequest.php

├── Resources/

│ └── YourModelResource.php

└── Events/ (if real-time needed)

└── YourEvent.php





Then add routes in `routes/api/v1/your-domain.php` and include in `routes/api.php`.



\---



\## Pull Request Guidelines



\- Keep PRs small and focused on one thing

\- Add a clear description of what changed and why

\- Reference any related issues

\- Ensure all existing Postman tests still pass

\- Update documentation if adding new endpoints



\### PR Title Format



feat: add group avatar upload

fix: resolve N+1 query in conversation list

docs: add WebRTC troubleshooting section





\---



\## Reporting Issues



When reporting a bug, include:



1\. PHP and Laravel version

2\. Steps to reproduce

3\. Expected behavior

4\. Actual behavior

5\. Relevant logs from `storage/logs/laravel.log`



\---



\## Code of Conduct



\- Be respectful and constructive

\- Focus on the code, not the person

\- Welcome newcomers and help them learn

