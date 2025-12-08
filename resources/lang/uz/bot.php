<?php

return [
    'welcome' => '👋 Xush kelibsiz, :name! Men sizning moliyaviy yordamchingizman. Valyuta kurslari, aylantirish va xabarnomalar bo\'yicha yordam bera olaman.',
    'welcome_new' => '👋 Xush kelibsiz! Davom etish uchun tilni tanlang.',
    
    'language' => [
        'changed' => 'Til :language ga o\'zgartirildi',
        'select' => 'Tilni tanlang:',
    ],
    
    'menu' => [
        'main_title' => 'Asosiy menyu',
        'rates' => 'Valyuta kurslari',
        'convert' => 'Aylantirish',
        'banks' => 'Bank kurslari',
        'alerts' => 'Xabarnomalar',
        'profile' => 'Hisob',
        'help' => 'Yordam',
    ],
    
    'buttons' => [
        'back' => 'Orqaga',
        'cancel' => 'Bekor qilish',
        'confirm' => 'Tasdiqlash',
        'save' => 'Saqlash',
        'all_rates' => 'Barcha kurslar',
        'main_menu' => 'Asosiy menyu',
    ],
    
    'rates' => [
        'title' => 'Valyuta kurslari',
        'select_currency' => 'Kursni ko\'rish uchun valyutani tanlang:',
        'current_rate' => 'Joriy kurs',
        'weekly_change' => 'Haftalik o\'zgarish',
        'updated_at' => ':time da yangilandi',
        'no_data' => 'Kurs ma\'lumotlari mavjud emas.',
    ],
    
    'convert' => [
        'title' => 'Valyuta aylantirish',
        'instructions' => 'Valyuta aylantirish\n\nMenga xabar yuboring:\n• 100 USD\n• 100 USD UZS ga\n• 100 USD -> UZS\n\nYoki quyidagi menyudan valyutalarni tanlang:',
        'examples' => 'Misollar',
        'hint' => 'Yoki shunchaki summani valyuta bilan yozing, masalan "100 USD"',
        'result' => 'Aylantirish natijasi',
        'result_title' => 'Aylantirish natijasi',
        'rate' => 'Kurs',
        'invalid_format' => '❌ Noto\'g\'ri format. Iltimos, yuboring:\n• 100 USD\n• 100 USD UZS ga',
        'select_from' => 'Aylantirish uchun valyutani tanlang:',
        'select_to' => 'Aylantirish uchun valyutani tanlang:',
        'enter_amount' => 'Aylantirish uchun miqdorni kiriting:\n\nDan: :from\nGa: :to',
    ],
    
    'banks' => [
        'title' => 'Bank kurslari - :currency',
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
        'title' => 'Kurs xabarnomalari',
        'your_alerts' => 'Sizning faol xabarnomalaringiz:',
        'no_alerts' => 'Sizda faol xabarnomalar yo\'q.\n\nValyuta kursi maqsadli narxga yetganda xabar olish uchun xabarnoma yarating.',
        'create' => 'Xabarnoma yaratish',
        'created' => 'Xabarnoma muvaffaqiyatli yaratildi!',
        'deleted' => 'Xabarnoma o\'chirildi.',
        'delete_failed' => 'Xabarnomani o\'chirishda xatolik yuz berdi.',
        'triggered' => 'Xabarnoma ishga tushdi!\n\n:currency_from/:currency_to :condition :target_rate\nJoriy kurs: :current_rate',
        'invalid_format' => 'Noto\'g\'ri xabarnoma formati. Iltimos, ishlating:\n• USD > 12500\n• EUR < 14000',
        'instructions' => 'Kurs xabarnomasini yaratish:\n\nMenga yuboring:\n• USD > 12500\n• EUR < 14000\n\nYoki quyidagi tugmani ishlating:',
        'select_currency' => 'Xabarnoma uchun valyutani tanlang:',
        'current_rate' => 'Joriy kurs',
        'select_condition' => 'Shartni tanlang:',
        'when_above' => 'Yuqoriga chiqganda',
        'when_below' => 'Pastga tushganda',
        'above' => 'Yuqorida',
        'below' => 'Pastda',
        'enter_amount' => ':currency uchun maqsadli kursni kiriting :condition:',
        'select_to_delete' => 'O\'chirish uchun xabarnomani tanlang:',
        'confirm_delete' => 'Bu xabarnomani o\'chirishni xohlaysizmi?',
    ],
    
    'profile' => [
        'title' => 'Hisob',
        'name' => 'Ism',
        'username' => 'Foydalanuvchi nomi',
        'language' => 'Til',
        'favorites' => 'Sevimli valyutalar',
        'active_alerts' => 'Faol xabarnomalar',
        'daily_digest' => 'Kunlik ma\'lumot',
        'enabled' => 'Yoqilgan',
        'disabled' => 'O\'chirilgan',
        'member_since' => 'A\'zo bo\'lgan sana',
        'change_language' => 'Tilni o\'zgartirish',
        'edit_favorites' => 'Sevimlilarni tahrirlash',
        'toggle_digest' => 'Kunlik ma\'lumotni yoqish/o\'chirish',
        'select_language' => 'Tilni tanlang:',
        'digest_enabled' => 'Kunlik ma\'lumot yoqildi!',
        'digest_disabled' => 'Kunlik ma\'lumot o\'chirildi.',
        'enable_digest' => 'Kunlik ma\'lumotni yoqish',
        'disable_digest' => 'Kunlik ma\'lumotni o\'chirish',
    ],
    
    'help' => [
        'message' => "📖 <b>Bot buyruqlari:</b>\n\n" .
            "/start - Botni ishga tushirish\n" .
            "/rate - Valyuta kurslari\n" .
            "/convert - Valyutani aylantirish\n" .
            "/banks - Bank kurslari\n" .
            "/alerts - Kurs xabarnomalarini boshqarish\n" .
            "/profile - Sizning hisobingiz\n\n" .
            "<b>Tezkor aylantirish:</b>\n" .
            "Faqat yuboring: 100 USD yoki 100 USD UZS ga\n\n" .
            "<b>Xabarnoma yaratish:</b>\n" .
            "Yuboring: USD > 12500 yoki EUR < 14000",
        'title' => 'Yordam',
    ],
    
    'errors' => [
        'currency_not_found' => 'Valyuta topilmadi.',
        'invalid_amount' => 'Noto\'g\'ri miqdor. Iltimos, to\'g\'ri raqam kiriting.',
        'conversion_failed' => 'Aylantirish muvaffaqiyatsiz. Iltimos, qayta urinib ko\'ring.',
        'api_error' => 'Xizmat vaqtincha mavjud emas. Iltimos, keyinroq urinib ko\'ring.',
    ],
    
    'favorites' => [
        'title' => 'Sevimli valyutalar',
        'select' => 'Sevimli valyutalarni tanlang:',
        'current' => 'Joriy sevimlilar',
        'saved' => 'Sevimli valyutalar saqlandi!',
    ],
    
    'digest' => [
        'title' => 'Kunlik valyuta ma\'lumoti',
        'greeting' => 'Xayrli tong! Bugungi valyuta kurslari yangilanishi:',
        'rates_title' => 'Joriy kurslar:',
        'trend_title' => 'O\'zgarishlar (24 soat):',
        'banks_title' => 'Eng yaxshi bank kurslari:',
    ],
];

