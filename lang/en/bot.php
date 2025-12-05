<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Welcome Messages
    |--------------------------------------------------------------------------
    */
    'welcome' => [
        'greeting' => '👋 Hello, <b>:name</b>!',
        'description' => 'I\'m your personal financial assistant. I can help you track currency exchange rates, convert currencies, monitor bank rates, and set up price alerts.',
        'features' => '🎯 What I can do:',
        'feature_rates' => 'Real-time exchange rates from CBU',
        'feature_convert' => 'Quick currency conversion',
        'feature_banks' => 'Compare bank buy/sell rates',
        'feature_alerts' => 'Price alerts when rates hit your target',
        'feature_history' => 'Historical charts and trends',
        'select_action' => '👇 Choose an action below:',
    ],

    /*
    |--------------------------------------------------------------------------
    | Main Menu
    |--------------------------------------------------------------------------
    */
    'menu' => [
        'main_title' => 'Main Menu',
        'rates' => 'Rates',
        'convert' => 'Convert',
        'banks' => 'Banks',
        'history' => 'History',
        'alerts' => 'Alerts',
        'profile' => 'Profile',
        'help' => 'Help',
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Rates
    |--------------------------------------------------------------------------
    */
    'rates' => [
        'title' => '💱 <b>Exchange Rates</b>',
        'select_currency' => 'Select a currency to view its rate:',
        'current_rate' => 'Current rate',
        'weekly_change' => '7-day change',
        'updated_at' => 'Updated at :time',
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Conversion
    |--------------------------------------------------------------------------
    */
    'convert' => [
        'title' => 'Currency Converter',
        'instructions' => 'Send me a message in this format to convert currency:',
        'examples' => 'Examples',
        'hint' => 'You can also just type an amount with currency, e.g. "100 USD"',
        'select_from' => 'Select source currency:',
        'select_to' => 'Select target currency for :currency:',
        'enter_amount' => 'Enter the amount in :from to convert to :to:',
        'result_title' => 'Conversion Result',
        'rate' => 'Rate',
    ],

    /*
    |--------------------------------------------------------------------------
    | Bank Rates
    |--------------------------------------------------------------------------
    */
    'banks' => [
        'title' => 'Bank Rates for :currency',
        'select_currency' => 'Select a currency to see bank rates:',
        'bank' => 'Bank',
        'buy' => 'Buy',
        'sell' => 'Sell',
        'best_buy' => 'Best rate to sell (bank buys)',
        'best_sell' => 'Best rate to buy (bank sells)',
        'no_data' => 'No bank rate data available at the moment.',
        'updated_at' => 'Updated at :time',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate History
    |--------------------------------------------------------------------------
    */
    'history' => [
        'select_currency' => 'Select a currency to view its history:',
        'select_period' => 'Select period for :currency:',
        'days' => 'days',
        'start' => 'Start',
        'end' => 'Current',
        'change' => 'Change',
        'trend_up' => 'Growing',
        'trend_down' => 'Falling',
        'trend_stable' => 'Stable',
        'no_data' => 'No historical data available.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Alerts
    |--------------------------------------------------------------------------
    */
    'alerts' => [
        'your_alerts' => 'Your Price Alerts',
        'no_alerts' => '🔔 You don\'t have any active alerts.\n\nCreate one to get notified when a currency reaches your target rate!',
        'hint' => 'Alerts are checked every 30 minutes',
        'create' => 'Create Alert',
        'delete' => 'Delete Alert',
        'select_currency' => 'Select a currency for your alert:',
        'select_condition' => 'When should I notify you?',
        'when_above' => 'When rate goes ABOVE',
        'when_below' => 'When rate goes BELOW',
        'above' => 'above',
        'below' => 'below',
        'enter_amount' => 'Enter the target rate for :currency (:condition):',
        'created' => 'Alert created successfully!',
        'select_to_delete' => 'Select an alert to delete:',
        'confirm_delete' => 'Are you sure you want to delete this alert?',
        'deleted' => 'Alert deleted successfully!',
        'delete_failed' => 'Failed to delete alert.',
        'current_rate' => 'Current rate',
        'triggered_title' => 'Price Alert Triggered!',
        'triggered_note' => 'This alert has been deactivated.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */
    'profile' => [
        'title' => 'Your Profile',
        'name' => 'Name',
        'username' => 'Username',
        'language' => 'Language',
        'favorites' => 'Favorite currencies',
        'active_alerts' => 'Active alerts',
        'daily_digest' => 'Daily digest',
        'member_since' => 'Member since',
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
        'change_language' => 'Change Language',
        'edit_favorites' => 'Edit Favorites',
        'enable_digest' => 'Enable Daily Digest',
        'disable_digest' => 'Disable Daily Digest',
        'select_language' => 'Select your preferred language:',
        'digest_enabled' => 'Daily digest enabled! You\'ll receive morning updates at 9:00 AM.',
        'digest_disabled' => 'Daily digest disabled.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Favorites
    |--------------------------------------------------------------------------
    */
    'favorites' => [
        'title' => 'Edit Favorite Currencies',
        'instructions' => 'Tap currencies to toggle them. Selected currencies will appear in your rates view.',
        'saved' => 'Favorites saved successfully!',
    ],

    /*
    |--------------------------------------------------------------------------
    | Language
    |--------------------------------------------------------------------------
    */
    'language' => [
        'changed' => 'Language changed to :language',
    ],

    /*
    |--------------------------------------------------------------------------
    | Help
    |--------------------------------------------------------------------------
    */
    'help' => [
        'title' => 'Help & Commands',
        'commands_title' => '📋 Available Commands',
        'cmd_start' => 'Start the bot / Main menu',
        'cmd_rate' => 'View exchange rates',
        'cmd_convert' => 'Convert currency',
        'cmd_banks' => 'Bank exchange rates',
        'cmd_history' => 'Rate history & charts',
        'cmd_alerts' => 'Manage price alerts',
        'cmd_profile' => 'Your settings',
        'cmd_help' => 'Show this help',
        'conversion_title' => '💱 Quick Conversion',
        'conversion_examples' => 'Just send a message like:',
        'alerts_title' => '🔔 Quick Alerts',
        'alerts_examples' => 'Or set alerts with:',
        'support' => 'Need help? Contact @YourSupportUsername',
    ],

    /*
    |--------------------------------------------------------------------------
    | Daily Digest
    |--------------------------------------------------------------------------
    */
    'digest' => [
        'title' => 'Good Morning! Your Daily Rates',
        'footer' => 'Have a great day! 🌟',
    ],

    /*
    |--------------------------------------------------------------------------
    | Errors
    |--------------------------------------------------------------------------
    */
    'errors' => [
        'currency_not_found' => 'Currency :currency not found.',
        'conversion_failed' => 'Conversion failed. Please try again.',
        'invalid_amount' => 'Please enter a valid number.',
        'something_wrong' => 'Something went wrong. Please try again later.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Buttons
    |--------------------------------------------------------------------------
    */
    'buttons' => [
        'back' => 'Back',
        'cancel' => 'Cancel',
        'confirm' => 'Confirm',
        'save' => 'Save',
        'all_rates' => 'All Rates',
    ],
];

