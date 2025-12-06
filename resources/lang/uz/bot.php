<?php

return [
    'welcome' => '👋 Xush kelibsiz, :name! Men sizning moliyaviy yordamchingizman. Valyuta kurslari, konvertatsiya va ogohlantirishlar bo\'yicha yordam bera olaman.',
    'welcome_new' => '👋 Xush kelibsiz! Davom etish uchun tilni tanlang.',
    
    'language' => [
        'changed' => 'Til :language ga o\'zgartirildi',
        'select' => 'Tilni tanlang:',
    ],
    
    'menu' => [
        'main_title' => 'Asosiy menyu',
        'rates' => '💱 Valyuta kurslari',
        'convert' => '💱 Konvertatsiya',
        'banks' => '🏦 Bank kurslari',
        'history' => '📊 Kurslar tarixi',
        'alerts' => '🔔 Ogohlantirishlar',
        'profile' => '👤 Profil',
        'help' => '❓ Yordam',
    ],
    
    'buttons' => [
        'back' => 'Orqaga',
        'cancel' => 'Bekor qilish',
        'save' => 'Saqlash',
        'all_rates' => 'Barcha kurslar',
        'main_menu' => 'Asosiy menyu',
    ],
    
    'rates' => [
        'title' => '💱 Valyuta kurslari',
        'select_currency' => 'Kursni ko\'rish uchun valyutani tanlang:',
        'current_rate' => 'Joriy kurs',
        'weekly_change' => 'Haftalik o\'zgarish',
        'updated_at' => ':time da yangilandi',
        'no_data' => 'Kurs ma\'lumotlari mavjud emas.',
    ],
    
    'convert' => [
        'instructions' => '💱 Valyuta konvertori\n\nMenga xabar yuboring:\n• 100 USD\n• 100 USD UZS ga\n• 100 USD -> UZS\n\nYoki quyidagi menyudan valyutalarni tanlang:',
        'result' => 'Konvertatsiya natijasi',
        'rate' => 'Kurs',
        'invalid_format' => '❌ Noto\'g\'ri format. Iltimos, yuboring:\n• 100 USD\n• 100 USD UZS ga',
        'select_from' => 'Konvertatsiya qilish uchun valyutani tanlang:',
        'select_to' => 'Konvertatsiya qilish uchun valyutani tanlang:',
        'enter_amount' => 'Konvertatsiya qilish uchun miqdorni kiriting:\n\nDan: :from\nGa: :to',
    ],
    
    'history' => [
        'select_currency' => 'Tarixni ko\'rish uchun valyutani tanlang:',
        'select_period' => ':currency uchun davrni tanlang:',
        'days' => 'kun',
        'start' => 'Boshlanish',
        'end' => 'Tugash',
        'change' => 'O\'zgarish',
        'no_data' => 'Tarixiy ma\'lumotlar mavjud emas.',
        'trend_up' => 'Trend: Yuqoriga',
        'trend_down' => 'Trend: Pastga',
        'trend_stable' => 'Trend: Barqaror',
        'period_7d' => '7 kun',
        'period_30d' => '30 kun',
        'period_1y' => '1 yil',
    ],
    
    'banks' => [
        'title' => '🏦 Bank kurslari - :currency',
        'select_currency' => 'Bank kurslarini ko\'rish uchun valyutani tanlang:',
        'bank' => 'Bank',
        'buy' => 'Sotib olish',
        'sell' => 'Sotish',
        'best_buy' => 'Eng yaxshi sotib olish: :bank - :rate so\'m',
        'best_sell' => 'Eng yaxshi sotish: :bank - :rate so\'m',
        'no_data' => 'Bank kurslari hozircha mavjud emas.',
        'updated_at' => ':time da yangilandi',
    ],
    
    'alerts' => [
        'title' => '🔔 Kurs ogohlantirishlari',
        'your_alerts' => 'Sizning faol ogohlantirishlaringiz:',
        'no_alerts' => 'Sizda faol ogohlantirishlar yo\'q.\n\nValyuta kursi maqsadli narxga yetganda xabar olish uchun ogohlantirish yarating.',
        'create' => 'Ogohlantirish yaratish',
        'created' => 'Ogohlantirish muvaffaqiyatli yaratildi!',
        'deleted' => 'Ogohlantirish o\'chirildi.',
        'delete_failed' => 'Ogohlantirishni o\'chirishda xatolik yuz berdi.',
        'triggered' => 'Ogohlantirish ishga tushdi!\n\n:currency_from/:currency_to :condition :target_rate\nJoriy kurs: :current_rate',
        'invalid_format' => 'Noto\'g\'ri ogohlantirish formati. Iltimos, ishlating:\n• USD > 12500\n• EUR < 14000',
        'instructions' => 'Kurs ogohlantirishini yaratish:\n\nMenga yuboring:\n• USD > 12500\n• EUR < 14000\n\nYoki quyidagi tugmani ishlating:',
        'select_currency' => 'Ogohlantirish uchun valyutani tanlang:',
        'current_rate' => 'Joriy kurs',
        'select_condition' => 'Shartni tanlang:',
        'above' => 'Yuqorida',
        'below' => 'Pastda',
        'enter_amount' => ':currency uchun maqsadli kursni kiriting :condition:',
        'select_to_delete' => 'O\'chirish uchun ogohlantirishni tanlang:',
        'confirm_delete' => 'Bu ogohlantirishni o\'chirishni xohlaysizmi?',
    ],
    
    'profile' => [
        'title' => 'Profil',
        'name' => 'Ism',
        'username' => 'Username',
        'language' => 'Til',
        'favorites' => 'Sevimli valyutalar',
        'active_alerts' => 'Faol ogohlantirishlar',
        'daily_digest' => 'Kunlik xulosa',
        'enabled' => 'Yoqilgan',
        'disabled' => 'O\'chirilgan',
        'member_since' => 'A\'zo bo\'lgan sana',
        'change_language' => 'Tilni o\'zgartirish',
        'edit_favorites' => 'Sevimlilarni tahrirlash',
        'toggle_digest' => 'Kunlik xulosani yoqish/o\'chirish',
        'select_language' => 'Tilni tanlang:',
        'digest_enabled' => 'Kunlik xulosa yoqildi!',
        'digest_disabled' => 'Kunlik xulosa o\'chirildi.',
        'enable_digest' => 'Kunlik xulosani yoqish',
        'disable_digest' => 'Kunlik xulosani o\'chirish',
    ],
    
    'help' => [
        'message' => "📖 <b>Bot buyruqlari:</b>\n\n" .
            "/start - Botni ishga tushirish\n" .
            "/rate - Valyuta kurslari\n" .
            "/convert - Valyutani konvertatsiya qilish\n" .
            "/history - Kurslar tarixi\n" .
            "/banks - Bank kurslari\n" .
            "/alerts - Kurs ogohlantirishlarini boshqarish\n" .
            "/profile - Sizning profilingiz\n\n" .
            "<b>Tezkor konvertatsiya:</b>\n" .
            "Faqat yuboring: 100 USD yoki 100 USD UZS ga\n\n" .
            "<b>Ogohlantirish yaratish:</b>\n" .
            "Yuboring: USD > 12500 yoki EUR < 14000",
        'title' => 'Yordam',
    ],
    
    'errors' => [
        'currency_not_found' => 'Valyuta topilmadi.',
        'invalid_amount' => 'Noto\'g\'ri miqdor. Iltimos, to\'g\'ri raqam kiriting.',
        'conversion_failed' => 'Konvertatsiya muvaffaqiyatsiz. Iltimos, qayta urinib ko\'ring.',
        'api_error' => 'Xizmat vaqtincha mavjud emas. Iltimos, keyinroq urinib ko\'ring.',
    ],
    
    'favorites' => [
        'title' => 'Sevimli valyutalar',
        'select' => 'Sevimli valyutalarni tanlang:',
        'current' => 'Joriy sevimlilar',
        'saved' => 'Sevimli valyutalar saqlandi!',
    ],
    
    'digest' => [
        'title' => '📊 Kunlik valyuta xulosasi',
        'greeting' => 'Xayrli tong! Bugungi valyuta kurslari yangilanishi:',
        'rates_title' => 'Joriy kurslar:',
        'trend_title' => 'Trendlar (24 soat):',
        'banks_title' => 'Eng yaxshi bank kurslari:',
    ],
];

