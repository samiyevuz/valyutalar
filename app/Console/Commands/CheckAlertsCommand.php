<?php

namespace App\Console\Commands;

use App\Services\AlertService;
use Illuminate\Console\Command;

class CheckAlertsCommand extends Command
{
    protected $signature = 'telegram:check-alerts';

    protected $description = 'Check all active price alerts and send notifications';

    public function __construct(
        private AlertService $alertService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Checking price alerts...');

        $triggered = $this->alertService->checkAllAlerts();

        $this->info("✅ Done! Triggered alerts: {$triggered}");

        return self::SUCCESS;
    }
}

