<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Welcome Messages
    |--------------------------------------------------------------------------
    */
    'welcome' => [
        'greeting' => '👋 Salom, <b>:name</b>!',
        'description' => 'Men sizning shaxsiy moliyaviy yordamchingizman. Valyuta kurslarini kuzatish, valyuta konvertatsiyasi, bank kurslarini solishtirish va narx ogohlantirishlarini sozlashda yordam beraman.',
        'features' => '🎯 Nima qila olaman:',
        'feature_rates' => 'O\'zbekiston Markaziy banki kurslari',
        'feature_convert' => 'Tezkor valyuta konvertatsiyasi',
        'feature_banks' => 'Bank kurslarini solishtirish',
        'feature_alerts' => 'Maqsadli kursga yetganda ogohlantirish',
        'feature_history' => 'Tarixiy grafiklar va trendlar',
        'select_action' => '👇 Amalni tanlang:',
    ],

    /*
    |--------------------------------------------------------------------------
    | Main Menu
    |--------------------------------------------------------------------------
    */
    'menu' => [
        'main_title' => 'Asosiy menyu',
        'rates' => 'Kurslar',
        'convert' => 'Konverter',
        'banks' => 'Banklar',
        'history' => 'Tarix',
        'alerts' => 'Ogohlantirishlar',
        'profile' => 'Profil',
        'help' => 'Yordam',
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Rates
    |--------------------------------------------------------------------------
    */
    'rates' => [
        'title' => '💱 <b>Valyuta kurslari</b>',
        'select_currency' => 'Kursni ko\'rish uchun valyutani tanlang:',
        'current_rate' => 'Joriy kurs',
        'weekly_change' => '7 kunlik o\'zgarish',
        'updated_at' => ':time da yangilangan',
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Conversion
    |--------------------------------------------------------------------------
    */
    'convert' => [
        'title' => 'Valyuta konverteri',
        'instructions' => 'Konvertatsiya qilish uchun quyidagi formatda xabar yuboring:',
        'examples' => 'Misollar',
        'hint' => 'Yoki shunchaki summani valyuta bilan yozing, masalan "100 dollar"',
        'select_from' => 'Boshlang\'ich valyutani tanlang:',
        'select_to' => ':currency uchun maqsad valyutani tanlang:',
        'enter_amount' => ':from dan :to ga konvertatsiya qilish uchun summani kiriting:',
        'result_title' => 'Konvertatsiya natijasi',
        'rate' => 'Kurs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Bank Rates
    |--------------------------------------------------------------------------
    */
    'banks' => [
        'title' => ':currency uchun bank kurslari',
        'select_currency' => 'Bank kurslarini ko\'rish uchun valyutani tanlang:',
        'bank' => 'Bank',
        'buy' => 'Olish',
        'sell' => 'Sotish',
        'best_buy' => 'Sotish uchun eng yaxshi kurs (bank oladi)',
        'best_sell' => 'Olish uchun eng yaxshi kurs (bank sotadi)',
        'no_data' => 'Bank kurslari hozircha mavjud emas.',
        'updated_at' => ':time da yangilangan',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate History
    |--------------------------------------------------------------------------
    */
    'history' => [
        'select_currency' => 'Tarixni ko\'rish uchun valyutani tanlang:',
        'select_period' => ':currency uchun davrni tanlang:',
        'days' => 'kun',
        'start' => 'Boshlanish',
        'end' => 'Hozir',
        'change' => 'O\'zgarish',
        'trend_up' => 'O\'sish',
        'trend_down' => 'Tushish',
        'trend_stable' => 'Barqaror',
        'no_data' => 'Tarixiy ma\'lumotlar mavjud emas.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Alerts
    |--------------------------------------------------------------------------
    */
    'alerts' => [
        'your_alerts' => 'Sizning ogohlantirishlaringiz',
        'no_alerts' => '🔔 Sizda faol ogohlantirishlar yo\'q.\n\nMaqsadli kursga yetganda xabar olish uchun ogohlantirish yarating!',
        'hint' => 'Ogohlantirishlar har 30 daqiqada tekshiriladi',
        'create' => 'Yaratish',
        'delete' => 'O\'chirish',
        'select_currency' => 'Ogohlantirish uchun valyutani tanlang:',
        'select_condition' => 'Qachon xabar berishim kerak?',
        'when_above' => 'Kurs YUQORI bo\'lganda',
        'when_below' => 'Kurs PAST bo\'lganda',
        'above' => 'yuqori',
        'below' => 'past',
        'enter_amount' => ':currency uchun maqsadli kursni kiriting (:condition):',
        'created' => 'Ogohlantirish yaratildi!',
        'select_to_delete' => 'O\'chirish uchun ogohlantirishni tanlang:',
        'confirm_delete' => 'Bu ogohlantirishni o\'chirishni xohlaysizmi?',
        'deleted' => 'Ogohlantirish o\'chirildi!',
        'delete_failed' => 'Ogohlantirishni o\'chirib bo\'lmadi.',
        'current_rate' => 'Joriy kurs',
        'triggered_title' => 'Ogohlantirish ishladi!',
        'triggered_note' => 'Bu ogohlantirish o\'chirildi.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */
    'profile' => [
        'title' => 'Sizning profilingiz',
        'name' => 'Ism',
        'username' => 'Foydalanuvchi nomi',
        'language' => 'Til',
        'favorites' => 'Sevimli valyutalar',
        'active_alerts' => 'Faol ogohlantirishlar',
        'daily_digest' => 'Kunlik xulosa',
        'member_since' => 'Ro\'yxatdan o\'tgan sana',
        'enabled' => 'Yoqilgan',
        'disabled' => 'O\'chirilgan',
        'change_language' => 'Tilni o\'zgartirish',
        'edit_favorites' => 'Sevimlilarni tahrirlash',
        'enable_digest' => 'Kunlik xulosani yoqish',
        'disable_digest' => 'Kunlik xulosani o\'chirish',
        'select_language' => 'Tilni tanlang:',
        'digest_enabled' => 'Kunlik xulosa yoqildi! Har kuni ertalab 9:00 da yangiliklar olasiz.',
        'digest_disabled' => 'Kunlik xulosa o\'chirildi.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Favorites
    |--------------------------------------------------------------------------
    */
    'favorites' => [
        'title' => 'Sevimli valyutalar',
        'instructions' => 'Qo\'shish yoki olib tashlash uchun valyutani bosing. Tanlangan valyutalar kurslar bo\'limida ko\'rinadi.',
        'saved' => 'Sevimlilar saqlandi!',
    ],

    /*
    |--------------------------------------------------------------------------
    | Language
    |--------------------------------------------------------------------------
    */
    'language' => [
        'changed' => 'Til :language ga o\'zgartirildi',
    ],

    /*
    |--------------------------------------------------------------------------
    | Help
    |--------------------------------------------------------------------------
    */
    'help' => [
        'title' => 'Yordam va buyruqlar',
        'commands_title' => '📋 Mavjud buyruqlar',
        'cmd_start' => 'Botni ishga tushirish / Asosiy menyu',
        'cmd_rate' => 'Valyuta kurslari',
        'cmd_convert' => 'Valyutani konvertatsiya qilish',
        'cmd_banks' => 'Bank kurslari',
        'cmd_history' => 'Tarix va grafiklar',
        'cmd_alerts' => 'Ogohlantirishlarni boshqarish',
        'cmd_profile' => 'Sizning sozlamalaringiz',
        'cmd_help' => 'Ushbu yordamni ko\'rsatish',
        'conversion_title' => '💱 Tezkor konvertatsiya',
        'conversion_examples' => 'Shunchaki quyidagi xabarni yuboring:',
        'alerts_title' => '🔔 Tezkor ogohlantirishlar',
        'alerts_examples' => 'Yoki ogohlantirish yarating:',
        'support' => 'Yordam kerakmi? @YourSupportUsername ga murojaat qiling',
    ],

    /*
    |--------------------------------------------------------------------------
    | Daily Digest
    |--------------------------------------------------------------------------
    */
    'digest' => [
        'title' => 'Xayrli tong! Bugungi kurslar',
        'footer' => 'Kuningiz xayrli bo\'lsin! 🌟',
    ],

    /*
    |--------------------------------------------------------------------------
    | Errors
    |--------------------------------------------------------------------------
    */
    'errors' => [
        'currency_not_found' => ':currency valyutasi topilmadi.',
        'conversion_failed' => 'Konvertatsiya amalga oshmadi. Qaytadan urinib ko\'ring.',
        'invalid_amount' => 'Iltimos, to\'g\'ri raqam kiriting.',
        'something_wrong' => 'Nimadir noto\'g\'ri ketdi. Keyinroq urinib ko\'ring.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Buttons
    |--------------------------------------------------------------------------
    */
    'buttons' => [
        'back' => 'Orqaga',
        'cancel' => 'Bekor qilish',
        'confirm' => 'Tasdiqlash',
        'save' => 'Saqlash',
        'all_rates' => 'Barcha kurslar',
    ],
];

