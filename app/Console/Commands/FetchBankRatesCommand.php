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
        $this->info('🔄 Fetching bank rates...');
        $this->info('⏰ Time: ' . now('Asia/Tashkent')->format('Y-m-d H:i:s'));

        try {
            $fetched = $this->bankRatesService->fetchAllBankRates();

            if ($fetched <= 0) {
                $this->error('❌ No bank rates fetched!');
                return self::FAILURE;
            }

            $this->info("✅ Successfully fetched rates from {$fetched} banks");
            $this->info('✅ Bank rates updated successfully!');
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error fetching bank rates: ' . $e->getMessage());
            \Log::error('FetchBankRatesCommand error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return self::FAILURE;
        }
    }
}

