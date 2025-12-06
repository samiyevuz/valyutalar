<?php

return [
    'welcome' => '👋 Welcome, :name! I\'m your financial assistant bot. I can help you with currency exchange rates, conversions, and alerts.',
    'welcome_new' => '👋 Welcome! Please select your language to continue.',
    
    'language' => [
        'changed' => 'Language changed to :language',
        'select' => 'Select your language:',
    ],
    
    'menu' => [
        'main_title' => 'Main Menu',
        'rates' => '💱 Exchange Rates',
        'convert' => '💱 Convert Currency',
        'banks' => '🏦 Bank Rates',
        'history' => '📊 Rate History',
        'alerts' => '🔔 Alerts',
        'profile' => '👤 Profile',
        'help' => '❓ Help',
    ],
    
    'buttons' => [
        'back' => 'Back',
        'cancel' => 'Cancel',
        'save' => 'Save',
        'all_rates' => 'All Rates',
        'main_menu' => 'Main Menu',
    ],
    
    'rates' => [
        'title' => '💱 Exchange Rates',
        'select_currency' => 'Select currency to view rate:',
        'current_rate' => 'Current Rate',
        'weekly_change' => 'Weekly Change',
        'updated_at' => 'Updated at :time',
        'no_data' => 'No rate data available.',
    ],
    
    'convert' => [
        'instructions' => '💱 Currency Converter\n\nSend me a message like:\n• 100 USD\n• 100 USD to UZS\n• 100 USD -> UZS\n\nOr select currencies from the menu below:',
        'result' => 'Conversion Result',
        'rate' => 'Rate',
        'invalid_format' => '❌ Invalid format. Please send:\n• 100 USD\n• 100 USD to UZS',
        'select_from' => 'Select currency to convert FROM:',
        'select_to' => 'Select currency to convert TO:',
        'enter_amount' => 'Enter amount to convert:\n\nFrom: :from\nTo: :to',
    ],
    
    'history' => [
        'select_currency' => 'Select currency to view history:',
        'select_period' => 'Select period for :currency:',
        'days' => 'days',
        'start' => 'Start',
        'end' => 'End',
        'change' => 'Change',
        'no_data' => 'No historical data available.',
        'trend_up' => 'Trend: Up',
        'trend_down' => 'Trend: Down',
        'trend_stable' => 'Trend: Stable',
        'period_7d' => '7 Days',
        'period_30d' => '30 Days',
        'period_1y' => '1 Year',
    ],
    
    'banks' => [
        'title' => '🏦 Bank Exchange Rates - :currency',
        'select_currency' => 'Select currency to view bank rates:',
        'bank' => 'Bank',
        'buy' => 'Buy',
        'sell' => 'Sell',
        'best_buy' => 'Best Buy: :bank - :rate UZS',
        'best_sell' => 'Best Sell: :bank - :rate UZS',
        'no_data' => 'Bank rates are not available at the moment.',
        'updated_at' => 'Updated at :time',
    ],
    
    'alerts' => [
        'title' => '🔔 Price Alerts',
        'your_alerts' => 'Your Active Alerts:',
        'no_alerts' => 'You don\'t have any active alerts.\n\nCreate an alert to get notified when a currency rate reaches your target price.',
        'create' => 'Create Alert',
        'created' => 'Alert created successfully!',
        'deleted' => 'Alert deleted.',
        'delete_failed' => 'Failed to delete alert.',
        'triggered' => 'Alert Triggered!\n\n:currency_from/:currency_to :condition :target_rate\nCurrent rate: :current_rate',
        'invalid_format' => 'Invalid alert format. Please use:\n• USD > 12500\n• EUR < 14000',
        'instructions' => 'Create a price alert:\n\nSend me:\n• USD > 12500\n• EUR < 14000\n\nOr use the button below:',
        'select_currency' => 'Select currency for alert:',
        'current_rate' => 'Current rate',
        'select_condition' => 'Select condition:',
        'above' => 'Above',
        'below' => 'Below',
        'enter_amount' => 'Enter target rate for :currency :condition:',
        'select_to_delete' => 'Select alert to delete:',
        'confirm_delete' => 'Are you sure you want to delete this alert?',
    ],
    
    'profile' => [
        'title' => 'Profile',
        'name' => 'Name',
        'username' => 'Username',
        'language' => 'Language',
        'favorites' => 'Favorite Currencies',
        'active_alerts' => 'Active Alerts',
        'daily_digest' => 'Daily Digest',
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
        'member_since' => 'Member Since',
        'change_language' => 'Change Language',
        'edit_favorites' => 'Edit Favorites',
        'toggle_digest' => 'Toggle Daily Digest',
        'select_language' => 'Select your language:',
        'digest_enabled' => 'Daily digest enabled!',
        'digest_disabled' => 'Daily digest disabled.',
        'enable_digest' => 'Enable Daily Digest',
        'disable_digest' => 'Disable Daily Digest',
    ],
    
    'help' => [
        'message' => "📖 <b>Bot Commands:</b>\n\n" .
            "/start - Start the bot\n" .
            "/rate - View exchange rates\n" .
            "/convert - Convert currency\n" .
            "/history - View rate history\n" .
            "/banks - Bank exchange rates\n" .
            "/alerts - Manage price alerts\n" .
            "/profile - Your profile\n\n" .
            "<b>Quick Conversion:</b>\n" .
            "Just send: 100 USD or 100 USD to UZS\n\n" .
            "<b>Create Alert:</b>\n" .
            "Send: USD > 12500 or EUR < 14000",
        'title' => '📖 Help',
    ],
    
    'errors' => [
        'currency_not_found' => 'Currency not found.',
        'invalid_amount' => 'Invalid amount. Please enter a valid number.',
        'conversion_failed' => 'Conversion failed. Please try again.',
        'api_error' => 'Service temporarily unavailable. Please try again later.',
    ],
    
    'favorites' => [
        'title' => 'Favorite Currencies',
        'select' => 'Select your favorite currencies:',
        'current' => 'Current favorites',
        'saved' => 'Favorite currencies saved!',
    ],
    
    'digest' => [
        'title' => '📊 Daily Currency Digest',
        'greeting' => 'Good morning! Here\'s today\'s currency update:',
        'rates_title' => 'Current Rates:',
        'trend_title' => 'Trends (24h):',
        'banks_title' => 'Best Bank Rates:',
    ],
];

