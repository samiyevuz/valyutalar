# 🧪 Botni Test Qilish

## Command autoload muammosi

`telegram:test` command autoload qilinmagan. Quyidagilarni bajaring:

### 1. Autoload ni yangilash

Serverda:

```bash
composer dump-autoload
```

### 2. Keyin test qilish

```bash
php artisan telegram:test
```

## Yoki mavjud command ishlatish

Agar `telegram:test` ishlamasa, quyidagilarni ishlating:

### Webhook holatini tekshirish:

```bash
php artisan telegram:set-webhook --info
```

### Botga `/start` yuborish va log tekshirish:

1. Botga `/start` yuboring
2. Serverda log faylni tekshiring:

```bash
tail -50 storage/logs/webhook-debug.log
```

Yoki:

```bash
tail -50 storage/logs/laravel.log
```

## Webhook endpoint ni test qilish

```bash
curl https://valyutalar.e-qarz.uz/telegram/test
```

Agar javob kelsa, endpoint ishlayapti.

## Bot ishlamayotgan sabablar

1. **Webhook endpoint ishlamayapti** - Test endpoint ni tekshiring
2. **Log faylga yozilmayapti** - File permissions ni tekshiring
3. **Database muammosi** - `php artisan db:test` ni ishlating
4. **Bot token noto'g'ri** - `.env` faylida `TELEGRAM_BOT_TOKEN` ni tekshiring

## Tekshirish

1. `composer dump-autoload` - muvaffaqiyatli
2. `php artisan telegram:test` - bot ma'lumotlari ko'rinadi
3. Botga `/start` yuborilganda javob keladi
4. Log faylga yoziladi


