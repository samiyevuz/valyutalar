# 💱 Telegram Financial Assistant Bot

Production-ready Laravel 11 Telegram bot for currency exchange rates, conversions, and financial alerts. Supports Uzbek, Russian, and English languages.

## 🌟 Features

- 💱 **Real-time Currency Rates** - Get live exchange rates from Central Bank of Uzbekistan (CBU)
- 🔄 **Currency Converter** - Convert between USD, EUR, RUB, UZS, GBP, CNY with natural language parsing
- 📊 **Historical Analytics** - View 7-day, 30-day, and 1-year rate history with charts
- 🏦 **Bank Rates** - Compare exchange rates from major Uzbek banks
- 🔔 **Price Alerts** - Set alerts for target exchange rates
- 📬 **Daily Digest** - Receive morning briefings with rate updates
- 🌍 **Multi-language** - Full support for Uzbek, Russian, and English
- 🔒 **Secure Webhook** - IP whitelisting and secret token validation
- ⚡ **Queue-based** - Asynchronous message processing
- 📈 **Trend Analysis** - Track currency trends and changes

## 📋 Requirements

- PHP 8.2+
- Laravel 11
- MySQL 5.7+ / MariaDB 10.3+
- Composer
- Telegram Bot Token (from [@BotFather](https://t.me/BotFather))

## 🚀 Installation

### 1. Clone and Install Dependencies

```bash
git clone <repository-url>
cd valyutalar
composer install
npm install
```

### 2. Environment Configuration

Copy `.env.example` to `.env`:

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and configure:

```env
# Telegram Configuration
TELEGRAM_BOT_TOKEN=your_bot_token_here
TELEGRAM_BOT_USERNAME=your_bot_username
TELEGRAM_WEBHOOK_URL=https://yourdomain.com/telegram/webhook
TELEGRAM_SECRET_TOKEN=your_secret_token_for_webhook_validation

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=valyutalar
DB_USERNAME=root
DB_PASSWORD=

# Queue
QUEUE_CONNECTION=database

# Currency API (optional)
CURRENCY_API_PROVIDER=cbu
EXCHANGERATE_API_KEY=your_api_key_if_needed
```

### 3. Database Setup

```bash
php artisan migrate
php artisan db:seed  # Optional: seed test data
```

### 4. Set Up Webhook

```bash
php artisan telegram:set-webhook
```

This command will:
- Set the webhook URL in Telegram
- Register bot commands for all languages
- Verify webhook status

### 5. Start Queue Worker

```bash
php artisan queue:work
```

Or use Laravel Horizon (if installed):

```bash
php artisan horizon
```

### 6. Configure Cron Jobs

Add to your crontab (`crontab -e`):

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

## 📖 Usage

### Bot Commands

- `/start` - Start the bot and select language
- `/help` - Show help message
- `/rate` - View exchange rates
- `/convert` - Convert currency (e.g., "100 USD" or "100 USD to UZS")
- `/history` - View rate history with charts
- `/banks` - Compare bank exchange rates
- `/alerts` - Manage price alerts
- `/profile` - View and edit your profile

### Natural Language Conversion

The bot understands natural language:

```
100 USD
100 USD to UZS
100 USD -> UZS
150000 UZS to USD
```

### Creating Alerts

```
USD > 12500
EUR < 14000
RUB > 150
```

### API Endpoints

The bot also provides REST API endpoints:

```
GET /api/v1/rates
GET /api/v1/rates/{currency}
GET /api/v1/convert?from=USD&to=UZS&amount=100
GET /api/v1/history/{currency}?days=30
GET /api/v1/banks?currency=USD
```

## 🏗️ Architecture

### Project Structure

```
app/
├── Actions/Telegram/          # Command handlers
├── Builders/Keyboard/          # Keyboard builders
├── Console/Commands/           # Artisan commands
├── DTOs/                       # Data Transfer Objects
├── Enums/                      # Language, Currency enums
├── Exceptions/                 # Custom exceptions
├── Http/
│   ├── Controllers/            # Webhook & API controllers
│   └── Middleware/             # Webhook validation
├── Jobs/                       # Queue jobs
├── Models/                     # Eloquent models
└── Services/                   # Business logic services
```

### Key Components

- **TelegramService** - Handles all Telegram API interactions
- **CurrencyService** - Fetches and caches currency rates
- **AlertService** - Manages price alerts and notifications
- **BankRatesService** - Aggregates bank exchange rates
- **ChartService** - Generates rate history charts
- **ConversionParser** - Parses natural language conversions

## 🧪 Testing

```bash
php artisan test
```

Run specific test suites:

```bash
php artisan test --filter CurrencyServiceTest
php artisan test --filter TelegramServiceTest
php artisan test --filter AlertServiceTest
```

## 📝 Scheduled Tasks

The following tasks run automatically:

- **Check Alerts** - Every 30 minutes (`telegram:check-alerts`)
- **Send Daily Digest** - Daily at 9:00 AM Tashkent time (`telegram:send-digest`)
- **Fetch Currency Rates** - Every hour (`telegram:fetch-rates`)
- **Fetch Bank Rates** - Every 2 hours (`telegram:fetch-bank-rates`)

## 🔒 Security

- **Webhook Validation** - Secret token validation
- **IP Whitelisting** - Only Telegram IP ranges allowed
- **Rate Limiting** - Prevents spam and abuse
- **Input Sanitization** - All user inputs are validated

## 🌍 Localization

All bot messages are localized in:

- `resources/lang/en/bot.php` - English
- `resources/lang/ru/bot.php` - Russian
- `resources/lang/uz/bot.php` - Uzbek

Users can switch languages via `/start` or `/profile`.

## 📊 Database Schema

### Tables

- `telegram_users` - Bot users and preferences
- `alerts` - Price alerts
- `currency_rates` - Historical currency rates
- `bank_rates` - Bank exchange rates
- `conversion_histories` - User conversion history

## 🛠️ Development

### Adding New Commands

1. Create action in `app/Actions/Telegram/`
2. Register in `config/telegram.php`
3. Add translations in language files
4. Update help message

### Adding New Languages

1. Create `resources/lang/{code}/bot.php`
2. Add language enum in `app/Enums/Language.php`
3. Update language keyboard builder

## 📦 Deployment

### Production Checklist

- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure webhook URL
- [ ] Set up SSL certificate
- [ ] Configure queue workers
- [ ] Set up cron jobs
- [ ] Enable IP whitelisting
- [ ] Set secret token
- [ ] Configure database backups
- [ ] Set up monitoring

### Webhook Setup

```bash
php artisan telegram:set-webhook
php artisan telegram:set-webhook --info  # Check status
php artisan telegram:set-webhook --delete  # Remove webhook
```

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🙏 Acknowledgments

- Central Bank of Uzbekistan (CBU) for providing free currency API
- Laravel Framework
- Telegram Bot API

## 📞 Support

For issues and questions:
- Open an issue on GitHub
- Contact: [your-email@example.com]

---

Made with ❤️ using Laravel 11
