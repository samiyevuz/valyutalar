# 🔧 Database Connection Muammosini Hal Qilish

## Muammo
```
SQLSTATE[HY000] [2002] Подключение не установлено, т.к. конечный компьютер отверг запрос на подключение
```

Bu xato MySQL server ishlamayapti yoki ulanish rad etilmoqda degan ma'noni anglatadi.

## Yechim

### 1. OSPanel da MySQL ni ishga tushirish

1. **OSPanel Control Panel** ni oching
2. **MySQL** ni toping
3. **Start** tugmasini bosing
4. Yashil rangda ko'rinishi kerak (ishlayapti)

### 2. Database yaratish

OSPanel → **phpMyAdmin** ga o'ting yoki quyidagi SQL ni ishlating:

```sql
CREATE DATABASE IF NOT EXISTS valyutalar 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;
```

### 3. `.env` faylini to'g'rilash

`.env` faylida quyidagilar bo'lishi kerak:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=valyutalar
DB_USERNAME=root
DB_PASSWORD=
```

**Eslatma**: OSPanel da odatda:
- Host: `127.0.0.1` yoki `localhost`
- Port: `3306`
- Username: `root`
- Password: **bo'sh** (yoki OSPanel sozlamalarida ko'rsatilgan)

### 4. MySQL port ni tekshirish

OSPanel da MySQL port odatda `3306`. Agar boshqa port ishlatilsa, `.env` faylida o'zgartiring:

```env
DB_PORT=3307  # yoki boshqa port
```

### 5. Config cache ni tozalash

```bash
php artisan config:clear
php artisan cache:clear
```

### 6. Database connection ni test qilish

```bash
php artisan tinker
```

Keyin:

```php
DB::connection()->getPdo();
```

Agar xato bo'lmasa, database ulangan.

### 7. Migrationlarni ishga tushirish

```bash
php artisan migrate
```

## Alternativ: SQLite ishlatish (tezkor test)

Agar MySQL muammosi bo'lsa, vaqtincha SQLite ishlatishingiz mumkin:

### `.env` faylida:

```env
DB_CONNECTION=sqlite
# DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD ni comment qiling
```

### Database faylini yaratish:

```bash
touch database/database.sqlite
```

### Migrationlarni ishga tushirish:

```bash
php artisan migrate
```

## Tekshirish

1. OSPanel da MySQL **yashil** (ishlayapti)
2. phpMyAdmin ga kirib, `valyutalar` database mavjudligini tekshiring
3. `.env` faylida database sozlamalari to'g'ri
4. `php artisan migrate` muvaffaqiyatli o'tdi

## Qo'shimcha yordam

Agar muammo hal bo'lmagan bo'lsa:

1. OSPanel → MySQL → **Restart**
2. OSPanel → **Settings** → MySQL port ni tekshiring
3. Windows Firewall MySQL port (3306) ni bloklamaganligini tekshiring

