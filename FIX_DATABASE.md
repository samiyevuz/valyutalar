# 🔧 Database Connection Muammosini Hal Qilish

## Muammo
`.env` faylida hammasi to'g'ri, lekin log da `example_app` database nomi ko'rinmoqda.

## Yechim

### 1. Config Cache ni tozalash (MUHIM!)

```bash
cd C:\OSPanel\home\valyutalar\public
C:\OSPanel\modules\php\PHP_8.2\php.exe artisan config:clear
C:\OSPanel\modules\php\PHP_8.2\php.exe artisan cache:clear
C:\OSPanel\modules\php\PHP_8.2\php.exe artisan route:clear
C:\OSPanel\modules\php\PHP_8.2\php.exe artisan view:clear
```

### 2. Database connection ni test qilish

```bash
C:\OSPanel\modules\php\PHP_8.2\php.exe artisan db:test
```

Bu quyidagilarni ko'rsatadi:
- Database sozlamalari
- Connection holati
- Xato bo'lsa, aniq xato xabari

### 3. MySQL server ni tekshirish

OSPanel Control Panel da:
- MySQL **yashil** (ishlayapti) bo'lishi kerak
- Agar qizil bo'lsa, **Start** tugmasini bosing

### 4. Database mavjudligini tekshirish

OSPanel → phpMyAdmin ga o'ting va quyidagilarni tekshiring:

```sql
SHOW DATABASES;
```

`valyutalar` database mavjudligini tekshiring.

Agar mavjud bo'lmasa:

```sql
CREATE DATABASE valyutalar 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;
```

### 5. Migrationlarni ishga tushirish

```bash
C:\OSPanel\modules\php\PHP_8.2\php.exe artisan migrate
```

### 6. Webhook ni test qilish

Botga `/start` yuborib, `storage/logs/webhook-debug.log` faylini tekshiring:

```bash
type storage\logs\webhook-debug.log
```

## Agar hali ham muammo bo'lsa

### Variant 1: MySQL port ni tekshirish

OSPanel → Settings → MySQL → Port ni tekshiring (odatda 3306)

`.env` faylida:

```env
DB_PORT=3306  # yoki OSPanel da ko'rsatilgan port
```

### Variant 2: MySQL socket ishlatish

OSPanel da MySQL socket path ni toping va `.env` faylida:

```env
DB_SOCKET=C:/OSPanel/modules/database/MySQL-8.0/data/mysql.sock
```

### Variant 3: SQLite ishlatish (tezkor test)

`.env` faylida:

```env
DB_CONNECTION=sqlite
```

Keyin:

```bash
type nul > database\database.sqlite
C:\OSPanel\modules\php\PHP_8.2\php.exe artisan migrate
```

## Tekshirish ro'yxati

- [ ] Config cache tozalangan
- [ ] MySQL server ishlayapti (OSPanel da yashil)
- [ ] `valyutalar` database mavjud
- [ ] `php artisan db:test` muvaffaqiyatli
- [ ] `php artisan migrate` muvaffaqiyatli
- [ ] Botga `/start` yuborilganda javob keladi

