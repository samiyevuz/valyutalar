<?php

namespace App\Console\Commands;

use App\Services\BankRatesService;
use Illuminate\Console\Command;

class FetchBankRatesCommand extends Command
{
    protected $signature = 'telegram:fetch-bank-rates';

    protected $description = 'Fetch latest bank exchange rates';

    public function __construct(
        private BankRatesService $bankRatesService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Fetching bank rates...');

        $fetched = $this->bankRatesService->fetchAllBankRates();

        $this->info("✅ Done! Fetched rates from {$fetched} banks");

        return self::SUCCESS;
    }
}

