<?php

namespace App\Console\Commands;

use App\Services\TelemetryMonitorService;
use Illuminate\Console\Command;

class MonitorIntegrationHealth extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'telemetry:monitor-integrations';

    /**
     * The console command description.
     */
    protected $description = 'Monitor health of third-party integrations (Google Fit) and alert caregivers on disconnections';

    /**
     * Execute the console command.
     */
    public function handle(TelemetryMonitorService $service): int
    {
        $this->info('Checking third-party health integration telemetry...');

        $result = $service->checkIntegrationHealth();

        $this->info("Checked {$result['monitored_count']} integration(s). Triggered {$result['alerts_triggered']} alert(s).");

        return self::SUCCESS;
    }
}
