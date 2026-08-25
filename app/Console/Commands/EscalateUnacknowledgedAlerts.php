<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Services\AlertDeliveryService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class EscalateUnacknowledgedAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:escalate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Escalate unacknowledged open clinical alerts past their escalation deadline';

    /**
     * Execute the console command.
     */
    public function handle(AlertDeliveryService $deliveryService): int
    {
        $now = Carbon::now();

        $overdueAlerts = Alert::where('state', 'open')
            ->whereNotNull('escalate_at')
            ->where('escalate_at', '<=', $now)
            ->get();

        $count = $overdueAlerts->count();
        $this->info("Found {$count} overdue unacknowledged alert(s) to escalate.");

        foreach ($overdueAlerts as $alert) {
            $deliveryService->reEscalate($alert);
            $this->warn("  - Re-escalated alert #{$alert->id} ({$alert->severity}) for patient #{$alert->elderly_id}");
        }

        return Command::SUCCESS;
    }
}
