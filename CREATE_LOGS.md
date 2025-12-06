# 📝 Log Fayllarni Yaratish

## Muammo
Log fayllar mavjud emas: `storage/logs/webhook-debug.log` va `storage/logs/laravel.log`

## Yechim

### 1. Log papkasini yaratish

Serverda:

```bash
mkdir -p storage/logs
chmod -R 775 storage/logs
chown -R www-data:www-data storage/logs
```

### 2. Log fayllarni yaratish

```bash
touch storage/logs/webhook-debug.log
touch storage/logs/laravel.log
chmod 666 storage/logs/webhook-debug.log
chmod 666 storage/logs/laravel.log
```

### 3. Test endpoint ni ishlatish

Brauzer yoki curl orqali:

```bash
curl https://valyutalar.e-qarz.uz/telegram/test
```

Bu log faylni yaratadi va test yozuv qo'shadi.

### 4. Log faylni tekshirish

```bash
cat storage/logs/webhook-debug.log
```

### 5. Botga `/start` yuborish

Botga `/start` yuborib, keyin log faylni tekshiring:

```bash
tail -50 storage/logs/webhook-debug.log
```

## Tekshirish

1. `storage/logs` papkasi mavjud
2. `webhook-debug.log` fayli mavjud va yoziladi
3. Botga `/start` yuborilganda log yoziladi

