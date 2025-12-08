<?php

namespace App\Console\Commands;

use App\Services\CurrencyService;
use Illuminate\Console\Command;

class FetchCurrencyRatesCommand extends Command
{
    protected $signature = 'telegram:fetch-rates';

    protected $description = 'Fetch latest currency rates from CBU';

    public function __construct(
        private CurrencyService $currencyService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('🔄 Fetching currency rates from CBU...');
        $this->info('⏰ Time: ' . now('Asia/Tashkent')->format('Y-m-d H:i:s'));

        try {
            // Clear cache to force fresh fetch
            $this->currencyService->refreshRates();

            $rates = $this->currencyService->getLiveRates();

            if (empty($rates)) {
                $this->error('❌ No rates fetched!');
                return self::FAILURE;
            }

            $this->info("✅ Successfully fetched " . count($rates) . " currency rates");

            // Show main currencies
            $mainCurrencies = ['USD', 'EUR', 'RUB', 'GBP'];

            $data = [];
            foreach ($rates as $rate) {
                if (in_array($rate->currencyCode, $mainCurrencies)) {
                    $data[] = [
                        $rate->currencyCode,
                        number_format($rate->rate, 2, '.', ' '),
                        $rate->date->format('Y-m-d'),
                    ];
                }
            }

            if (!empty($data)) {
                $this->table(['Currency', 'Rate (UZS)', 'Date'], $data);
            }

            $this->info('✅ Currency rates updated successfully!');
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error fetching rates: ' . $e->getMessage());
            \Log::error('FetchCurrencyRatesCommand error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return self::FAILURE;
        }
    }
}

