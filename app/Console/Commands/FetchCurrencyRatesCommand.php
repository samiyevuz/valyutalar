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
        $this->info('Fetching currency rates from CBU...');

        $this->currencyService->refreshRates();

        $rates = $this->currencyService->getLiveRates();

        $this->info("✅ Fetched " . count($rates) . " currency rates");

        // Show main currencies
        $mainCurrencies = ['USD', 'EUR', 'RUB', 'GBP'];

        $data = [];
        foreach ($rates as $rate) {
            if (in_array($rate->currencyCode, $mainCurrencies)) {
                $data[] = [
                    $rate->currencyCode,
                    number_format($rate->rate, 2),
                    $rate->date->format('Y-m-d'),
                ];
            }
        }

        $this->table(['Currency', 'Rate (UZS)', 'Date'], $data);

        return self::SUCCESS;
    }
}

