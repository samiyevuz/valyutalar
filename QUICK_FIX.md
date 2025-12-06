# 🚀 Tezkor Yechim - Webhook URL ni To'g'rilash

## Muammo
Webhook URL noto'g'ri:
- Hozir: `https://valyutalar.e-qarz.uz/api/telegram/webhook`
- To'g'ri: `https://valyutalar.e-qarz.uz/telegram/webhook`

## Yechim

### 1. `.env` faylini to'g'rilash

`.env` faylida:

```env
TELEGRAM_WEBHOOK_URL=https://valyutalar.e-qarz.uz/telegram/webhook
```

**Eslatma**: `/api/` qismini olib tashlang!

### 2. Webhook ni qayta o'rnatish

Serverda:

```bash
php artisan config:clear
php artisan telegram:set-webhook
```

### 3. Webhook holatini tekshirish

```bash
php artisan telegram:set-webhook --info
```

Endi URL `https://valyutalar.e-qarz.uz/telegram/webhook` bo'lishi kerak.

### 4. Botga `/start` yuborish

Botga `/start` yuborib, javob kelishini tekshiring.

## Tekshirish

1. `.env` faylida `TELEGRAM_WEBHOOK_URL` to'g'ri
2. `php artisan telegram:set-webhook --info` - URL to'g'ri
3. Botga `/start` yuborilganda javob keladi

## Agar hali ham ishlamasa

Log faylni tekshiring:

```bash
tail -f storage/logs/webhook-debug.log
```

Yoki:

```bash
tail -f storage/logs/laravel.log
```

