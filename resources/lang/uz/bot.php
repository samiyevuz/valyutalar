<?php

return [
    'welcome' => '👋 Xush kelibsiz, :name! Men sizning moliyaviy yordamchingizman. Valyuta kurslari, konvertatsiya va ogohlantirishlar bo\'yicha yordam bera olaman.',
    'welcome_new' => '👋 Xush kelibsiz! Davom etish uchun tilni tanlang.',
    
    'menu' => [
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
    ],
    
    'history' => [
        'select_currency' => 'Tarixni ko\'rish uchun valyutani tanlang:',
        'days' => 'kun',
        'start' => 'Boshlanish',
        'end' => 'Tugash',
        'change' => 'O\'zgarish',
        'no_data' => 'Tarixiy ma\'lumotlar mavjud emas.',
        'select_period' => 'Davrni tanlang:',
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
    ],
    
    'alerts' => [
        'title' => '🔔 Kurs ogohlantirishlari',
        'your_alerts' => 'Sizning faol ogohlantirishlaringiz:',
        'no_alerts' => 'Sizda faol ogohlantirishlar yo\'q.\n\nValyuta kursi maqsadli narxga yetganda xabar olish uchun ogohlantirish yarating.',
        'create' => 'Ogohlantirish yaratish',
        'created' => 'Ogohlantirish muvaffaqiyatli yaratildi!',
        'deleted' => 'Ogohlantirish o\'chirildi.',
        'triggered' => 'Ogohlantirish ishga tushdi!\n\n:currency_from/:currency_to :condition :target_rate\nJoriy kurs: :current_rate',
        'invalid_format' => 'Noto\'g\'ri ogohlantirish formati. Iltimos, ishlating:\n• USD > 12500\n• EUR < 14000',
        'instructions' => 'Kurs ogohlantirishini yaratish:\n\nMenga yuboring:\n• USD > 12500\n• EUR < 14000\n\nYoki quyidagi tugmani ishlating:',
    ],
    
    'profile' => [
        'title' => 'Profil',
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
    ],
    
    'help' => [
        'message' => '📖 <b>Bot buyruqlari:</b>\n\n' .
            '/start - Botni ishga tushirish\n' .
            '/rate - Valyuta kurslari\n' .
            '/convert - Valyutani konvertatsiya qilish\n' .
            '/history - Kurslar tarixi\n' .
            '/banks - Bank kurslari\n' .
            '/alerts - Kurs ogohlantirishlarini boshqarish\n' .
            '/profile - Sizning profilingiz\n\n' .
            '<b>Tezkor konvertatsiya:</b>\n' .
            'Faqat yuboring: 100 USD yoki 100 USD UZS ga\n\n' .
            '<b>Ogohlantirish yaratish:</b>\n' .
            'Yuboring: USD > 12500 yoki EUR < 14000',
    ],
    
    'errors' => [
        'currency_not_found' => 'Valyuta topilmadi.',
        'invalid_amount' => 'Noto\'g\'ri miqdor. Iltimos, to\'g\'ri raqam kiriting.',
        'conversion_failed' => 'Konvertatsiya muvaffaqiyatsiz. Iltimos, qayta urinib ko\'ring.',
        'api_error' => 'Xizmat vaqtincha mavjud emas. Iltimos, keyinroq urinib ko\'ring.',
    ],
    
    'digest' => [
        'title' => '📊 Kunlik valyuta xulosasi',
        'greeting' => 'Xayrli tong! Bugungi valyuta kurslari yangilanishi:',
        'rates_title' => 'Joriy kurslar:',
        'trend_title' => 'Trendlar (24 soat):',
        'banks_title' => 'Eng yaxshi bank kurslari:',
    ],
];

