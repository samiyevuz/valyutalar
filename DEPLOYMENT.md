# 🚀 Deployment Guide

Bu qo'llanma production muhitida Telegram Financial Bot ni deploy qilish uchun to'liq ko'rsatmalar beradi.

## 📋 Pre-Deployment Checklist

- [ ] Laravel 11 va PHP 8.2+ o'rnatilgan
- [ ] MySQL/MariaDB database tayyor
- [ ] Telegram Bot Token olingan ([@BotFather](https://t.me/BotFather))
- [ ] SSL sertifikat o'rnatilgan (HTTPS kerak)
- [ ] Domain nomi sozlangan
- [ ] Server IP manzili ma'lum

## 🔧 Step 1: Server Setup

### PHP Requirements

```bash
php -v  # PHP 8.2+ kerak
composer --version
```

### Install Dependencies

```bash
cd /path/to/project
composer install --optimize-autoloader --no-dev
npm install && npm run build
```

## 🔐 Step 2: Environment Configuration

`.env` faylini production uchun sozlang:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=valyutalar
DB_USERNAME=your_db_user
DB_PASSWORD=strong_password

# Telegram
TELEGRAM_BOT_TOKEN=your_bot_token
TELEGRAM_BOT_USERNAME=your_bot_username
TELEGRAM_WEBHOOK_URL=https://yourdomain.com/telegram/webhook
TELEGRAM_SECRET_TOKEN=generate_random_string_here

# Queue
QUEUE_CONNECTION=database

# Cache
CACHE_STORE=redis  # yoki database
```

### Generate Secret Token

```bash
php artisan key:generate
# yoki
openssl rand -hex 32
```

## 🗄️ Step 3: Database Setup

```bash
php artisan migrate --force
php artisan db:seed  # ixtiyoriy
```

## 🔗 Step 4: Set Webhook

```bash
php artisan telegram:set-webhook
```

Webhook holatini tekshiring:

```bash
php artisan telegram:set-webhook --info
```

## ⚙️ Step 5: Queue Worker

### Option 1: Supervisor (Tavsiya etiladi)

`/etc/supervisor/conf.d/telegram-bot-worker.conf`:

```ini
[program:telegram-bot-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/project/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/project/storage/logs/worker.log
stopwaitsecs=3600
```

Supervisor ni qayta yuklang:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start telegram-bot-worker:*
```

### Option 2: Systemd

`/etc/systemd/system/telegram-bot-worker.service`:

```ini
[Unit]
Description=Telegram Bot Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /path/to/project/artisan queue:work database --sleep=3 --tries=3

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable telegram-bot-worker
sudo systemctl start telegram-bot-worker
```

## ⏰ Step 6: Cron Jobs

Crontab ga qo'shing:

```bash
crontab -e
```

```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

Yoki systemd timer:

`/etc/systemd/system/telegram-bot-schedule.timer`:

```ini
[Unit]
Description=Telegram Bot Schedule Timer

[Timer]
OnCalendar=*:0/1
Persistent=true

[Install]
WantedBy=timers.target
```

## 🔒 Step 7: Security

### Nginx Configuration

```nginx
server {
    listen 443 ssl http2;
    server_name yourdomain.com;
    root /path/to/project/public;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Apache Configuration

`.htaccess` faylida:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

## 📊 Step 8: Monitoring

### Log Files

```bash
tail -f storage/logs/laravel.log
tail -f storage/logs/worker.log
```

### Queue Status

```bash
php artisan queue:monitor
```

### Webhook Status

```bash
php artisan telegram:set-webhook --info
```

## 🔄 Step 9: Updates

Production da yangilash:

```bash
git pull origin main
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

## 🐛 Troubleshooting

### Webhook ishlamayapti

1. Webhook URL ni tekshiring:
```bash
php artisan telegram:set-webhook --info
```

2. SSL sertifikat to'g'ri o'rnatilganligini tekshiring
3. Firewall sozlamalarini tekshiring
4. Log fayllarni ko'rib chiqing

### Queue ishlamayapti

1. Worker ishlayotganini tekshiring:
```bash
ps aux | grep queue:work
```

2. Supervisor holatini tekshiring:
```bash
sudo supervisorctl status
```

3. Database connection ni tekshiring

### Rate limiting

`.env` da:

```env
TELEGRAM_RATE_LIMIT_MAX_REQUESTS=30
TELEGRAM_RATE_LIMIT_PER_MINUTES=1
```

## 📈 Performance Optimization

1. **Cache Configuration**:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

2. **OPcache** ni yoqing (php.ini):
```ini
opcache.enable=1
opcache.memory_consumption=256
```

3. **Redis** ishlatish (cache va queue uchun):
```env
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## 🔐 Security Best Practices

1. `.env` faylini hech qachon commit qilmang
2. `APP_KEY` ni production da o'zgartiring
3. Database parolini kuchli qiling
4. Secret token ni random qiling
5. IP whitelisting ni yoqing (production da)
6. Rate limiting ni sozlang
7. Regular backups oling

## 📞 Support

Muammo bo'lsa:
- Log fayllarni tekshiring
- GitHub Issues oching
- Telegram: [@your_support_bot]

---

**Happy Deploying! 🚀**

