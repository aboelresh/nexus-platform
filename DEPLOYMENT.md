\# 🚢 NexusPlatform — Deployment Guide



\## Server Requirements



| Requirement | Minimum | Recommended |

|---|---|---|

| PHP | 8.2 | 8.3 |

| MySQL | 8.0 | 8.0+ |

| Redis | 6.0 | 7.x |

| RAM | 1 GB | 2 GB+ |

| CPU | 1 vCPU | 2 vCPU+ |

| OS | Ubuntu 22.04 | Ubuntu 22.04 LTS |



\### Required PHP Extensions

```bash

php -m | grep -E "pdo|mbstring|openssl|tokenizer|xml|ctype|json|bcmath|gd|curl|redis"

```



Install missing extensions:

```bash

sudo apt install php8.2-{mysql,mbstring,xml,curl,gd,redis,zip,bcmath}

```



\---



\## Production Environment Setup



\### 1. Clone \& Install



```bash

git clone https://github.com/yourusername/nexus-platform.git /var/www/nexus-platform

cd /var/www/nexus-platform

composer install --optimize-autoloader --no-dev

```



\### 2. Environment Configuration



```bash

cp .env.example .env

php artisan key:generate

```



Edit `.env` for production:



```env

APP\_NAME=NexusPlatform

APP\_ENV=production

APP\_DEBUG=false

APP\_URL=https://yourdomain.com



LOG\_CHANNEL=stack

LOG\_LEVEL=error



DB\_CONNECTION=mysql

DB\_HOST=127.0.0.1

DB\_PORT=3306

DB\_DATABASE=nexus\_platform

DB\_USERNAME=nexus\_user

DB\_PASSWORD=strong\_password\_here



CACHE\_STORE=redis

QUEUE\_CONNECTION=redis

SESSION\_DRIVER=redis



REDIS\_HOST=127.0.0.1

REDIS\_PASSWORD=your\_redis\_password

REDIS\_PORT=6379

REDIS\_CLIENT=predis



BROADCAST\_CONNECTION=reverb

REVERB\_APP\_ID=nexus-prod

REVERB\_APP\_KEY=generate-strong-key-here

REVERB\_APP\_SECRET=generate-strong-secret-here

REVERB\_HOST=0.0.0.0

REVERB\_PORT=8080

REVERB\_SCHEME=https



MAIL\_MAILER=smtp

MAIL\_HOST=smtp.yourdomain.com

MAIL\_PORT=587

MAIL\_USERNAME=noreply@yourdomain.com

MAIL\_PASSWORD=your\_mail\_password

MAIL\_ENCRYPTION=tls

MAIL\_FROM\_ADDRESS=noreply@yourdomain.com



APP\_CONSOLE\_TOKEN=generate-very-strong-token-here

TELESCOPE\_ENABLED=false

```



\### 3. Database Setup



```bash

php artisan migrate --force

php artisan storage:link

```



\### 4. Optimize for Production



```bash

php artisan config:cache

php artisan route:cache

php artisan view:cache

php artisan event:cache

composer dump-autoload --optimize

```



\---



\## Nginx Configuration



```nginx

server {

&#x20;   listen 80;

&#x20;   server\_name yourdomain.com;

&#x20;   return 301 https://$server\_name$request\_uri;

}



server {

&#x20;   listen 443 ssl http2;

&#x20;   server\_name yourdomain.com;



&#x20;   root /var/www/nexus-platform/public;

&#x20;   index index.php;



&#x20;   ssl\_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;

&#x20;   ssl\_certificate\_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;



&#x20;   # Security headers

&#x20;   add\_header X-Frame-Options "SAMEORIGIN";

&#x20;   add\_header X-Content-Type-Options "nosniff";

&#x20;   add\_header X-XSS-Protection "1; mode=block";

&#x20;   add\_header Strict-Transport-Security "max-age=31536000; includeSubDomains";



&#x20;   # Max upload size

&#x20;   client\_max\_body\_size 100M;



&#x20;   location / {

&#x20;       try\_files $uri $uri/ /index.php?$query\_string;

&#x20;   }



&#x20;   location = /favicon.ico { access\_log off; log\_not\_found off; }

&#x20;   location = /robots.txt  { access\_log off; log\_not\_found off; }



&#x20;   location \~ \\.php$ {

&#x20;       fastcgi\_pass unix:/var/run/php/php8.2-fpm.sock;

&#x20;       fastcgi\_param SCRIPT\_FILENAME $realpath\_root$fastcgi\_script\_name;

&#x20;       include fastcgi\_params;

&#x20;       fastcgi\_hide\_header X-Powered-By;

&#x20;   }



&#x20;   location \~ /\\.(?!well-known).\* {

&#x20;       deny all;

&#x20;   }



&#x20;   # WebSocket proxy (Reverb)

&#x20;   location /app/ {

&#x20;       proxy\_pass http://127.0.0.1:8080;

&#x20;       proxy\_http\_version 1.1;

&#x20;       proxy\_set\_header Upgrade $http\_upgrade;

&#x20;       proxy\_set\_header Connection "Upgrade";

&#x20;       proxy\_set\_header Host $host;

&#x20;       proxy\_set\_header X-Real-IP $remote\_addr;

&#x20;       proxy\_set\_header X-Forwarded-For $proxy\_add\_x\_forwarded\_for;

&#x20;       proxy\_set\_header X-Forwarded-Proto $scheme;

&#x20;       proxy\_read\_timeout 60;

&#x20;       proxy\_send\_timeout 60;

&#x20;   }

}

```



\---



\## Supervisor Configuration



Supervisor keeps Queue Worker and Reverb running automatically.



\### Install Supervisor



```bash

sudo apt install supervisor

```



\### Queue Worker Config



```bash

sudo nano /etc/supervisor/conf.d/nexus-queue.conf

```



```ini

\[program:nexus-queue]

process\_name=%(program\_name)s\_%(process\_num)02d

command=php /var/www/nexus-platform/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600

autostart=true

autorestart=true

stopasgroup=true

killasgroup=true

user=www-data

numprocs=2

redirect\_stderr=true

stdout\_logfile=/var/www/nexus-platform/storage/logs/queue.log

stopwaitsecs=3600

```



\### Reverb WebSocket Config



```bash

sudo nano /etc/supervisor/conf.d/nexus-reverb.conf

```



```ini

\[program:nexus-reverb]

command=php /var/www/nexus-platform/artisan reverb:start --host=0.0.0.0 --port=8080

autostart=true

autorestart=true

stopasgroup=true

killasgroup=true

user=www-data

redirect\_stderr=true

stdout\_logfile=/var/www/nexus-platform/storage/logs/reverb.log

```



\### Apply Config



```bash

sudo supervisorctl reread

sudo supervisorctl update

sudo supervisorctl start all

sudo supervisorctl status

```



\---



\## Redis Setup



\### Install Redis



```bash

sudo apt install redis-server

```



\### Configure Redis



```bash

sudo nano /etc/redis/redis.conf

```



Key settings:

```conf

bind 127.0.0.1

requirepass your\_redis\_password

maxmemory 256mb

maxmemory-policy allkeys-lru

```



```bash

sudo systemctl restart redis

sudo systemctl enable redis

```



\---



\## SSL Certificate (Let's Encrypt)



```bash

sudo apt install certbot python3-certbot-nginx

sudo certbot --nginx -d yourdomain.com

sudo certbot renew --dry-run

```



Auto-renewal is set up automatically by Certbot.



\---



\## Database User Setup



```sql

CREATE DATABASE nexus\_platform CHARACTER SET utf8mb4 COLLATE utf8mb4\_unicode\_ci;

CREATE USER 'nexus\_user'@'localhost' IDENTIFIED BY 'strong\_password';

GRANT ALL PRIVILEGES ON nexus\_platform.\* TO 'nexus\_user'@'localhost';

FLUSH PRIVILEGES;

```



\---



\## File Permissions



```bash

sudo chown -R www-data:www-data /var/www/nexus-platform

sudo chmod -R 755 /var/www/nexus-platform

sudo chmod -R 775 /var/www/nexus-platform/storage

sudo chmod -R 775 /var/www/nexus-platform/bootstrap/cache

```



\---



\## Deployment Checklist



\### Before Deploy

\- \[ ] `APP\_DEBUG=false`

\- \[ ] `APP\_ENV=production`

\- \[ ] Strong `APP\_KEY` generated

\- \[ ] Strong `REVERB\_APP\_SECRET` set

\- \[ ] Strong `APP\_CONSOLE\_TOKEN` set

\- \[ ] `TELESCOPE\_ENABLED=false`

\- \[ ] No secrets in git history



\### After Deploy

\- \[ ] `php artisan migrate --force`

\- \[ ] `php artisan config:cache`

\- \[ ] `php artisan route:cache`

\- \[ ] `php artisan storage:link`

\- \[ ] Supervisor running (queue + reverb)

\- \[ ] SSL certificate active

\- \[ ] Health check passing: `GET /devtools/api/health`



\---



\## Zero-Downtime Deployment



```bash

\# 1. Pull latest code

git pull origin main



\# 2. Install dependencies

composer install --optimize-autoloader --no-dev



\# 3. Run migrations

php artisan migrate --force



\# 4. Clear \& rebuild cache

php artisan optimize:clear

php artisan config:cache

php artisan route:cache

php artisan event:cache



\# 5. Restart queue workers

sudo supervisorctl restart nexus-queue:\*



\# 6. Restart Reverb

sudo supervisorctl restart nexus-reverb

```



\---



\## Monitoring



\### Check Supervisor Status

```bash

sudo supervisorctl status

```



\### Check Queue Failed Jobs

```bash

php artisan queue:failed

```



\### Check Logs

```bash

tail -f storage/logs/laravel.log

tail -f storage/logs/queue.log

tail -f storage/logs/reverb.log

```



\### Developer Console (Production)



https://yourdomain.com/devtools?token=your-console-token





> ⚠️ Consider restricting DevConsole access by IP in production.



\---



\## Firewall Rules



```bash

sudo ufw allow 22      # SSH

sudo ufw allow 80      # HTTP

sudo ufw allow 443     # HTTPS

sudo ufw deny 8080     # Block direct Reverb access (proxied via Nginx)

sudo ufw enable

```

