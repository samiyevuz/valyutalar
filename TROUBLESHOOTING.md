# 🔧 Troubleshooting Guide - Server Log Issues

## Log faylga ma'lumot saqlanmayapti

### 1. Log fayl huquqlarini tekshirish

Server da quyidagi buyruqlarni ishlating:

```bash
# Log papkasini tekshirish
ls -la storage/logs/

# Huquqlarni o'zgartirish
chmod -R 775 storage/logs/
chown -R www-data:www-data storage/logs/

# Yoki agar nginx/apache boshqa user ishlatayotgan bo'lsa:
chown -R nginx:nginx storage/logs/
# yoki
chown -R apache:apache storage/logs/
```

### 2. Storage papkasini yaratish

```bash
# Storage papkalarini yaratish
mkdir -p storage/logs
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views

# Huquqlarni berish
chmod -R 775 storage/
chown -R www-data:www-data storage/
```

### 3. Log faylini to'g'ridan-to'g'ri yozish

Bot endi `storage/logs/telegram-webhook.log` faylga to'g'ridan-to'g'ri yozadi. Bu faylni tekshiring:

```bash
tail -f storage/logs/telegram-webhook.log
```

### 4. PHP error log ni tekshirish

```bash
# PHP error log
tail -f /var/log/php-fpm/error.log
# yoki
tail -f /var/log/php8.2-fpm.log
# yoki
tail -f /var/log/apache2/error.log
```

### 5. Laravel log channel ni tekshirish

`.env` faylida:

```env
LOG_CHANNEL=single
LOG_LEVEL=debug
LOG_STACK=single
```

Keyin:

```bash
php artisan config:clear
php artisan cache:clear
```

### 6. Disk space tekshirish

```bash
df -h
du -sh storage/logs/*
```

### 7. SELinux muammosi (CentOS/RHEL)

```bash
# SELinux context ni o'zgartirish
chcon -R -t httpd_sys_rw_content_t storage/
setsebool -P httpd_can_network_connect 1
```

### 8. Test endpoint

Brauzer yoki curl orqali:

```bash
curl http://yourdomain.com/telegram/test
```

Keyin log faylni tekshiring:

```bash
tail -20 storage/logs/laravel.log
tail -20 storage/logs/telegram-webhook.log
```

### 9. Webhook endpoint ni test qilish

```bash
curl -X POST http://yourdomain.com/telegram/test-webhook \
  -H "Content-Type: application/json" \
  -d '{"test": "data"}'
```

### 10. Real-time log monitoring

```bash
# Laravel log
tail -f storage/logs/laravel.log

# Telegram webhook log
tail -f storage/logs/telegram-webhook.log

# PHP error log
tail -f /var/log/php-fpm/error.log
```

## Muammo hal bo'lmagan bo'lsa

1. **Log fayl mavjudligini tekshiring:**
   ```bash
   touch storage/logs/telegram-webhook.log
   chmod 666 storage/logs/telegram-webhook.log
   ```

2. **PHP error reporting ni yoqing:**
   `php.ini` da:
   ```ini
   display_errors = Off
   log_errors = On
   error_log = /var/log/php_errors.log
   ```

3. **Web server error log ni tekshiring:**
   - Nginx: `/var/log/nginx/error.log`
   - Apache: `/var/log/apache2/error.log`

4. **Laravel debug mode ni yoqing:**
   `.env` da:
   ```env
   APP_DEBUG=true
   ```

## Qo'shimcha log fayllar

Bot endi quyidagi fayllarga yozadi:

1. `storage/logs/laravel.log` - Laravel log
2. `storage/logs/telegram-webhook.log` - To'g'ridan-to'g'ri webhook log (yangi)

Agar `laravel.log` ishlamasa, `telegram-webhook.log` ishlaydi.

