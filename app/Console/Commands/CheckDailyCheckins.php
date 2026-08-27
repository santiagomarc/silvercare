<?php

namespace App\Console\Commands;

use App\Services\CareCheckinService;
use Illuminate\Console\Command;

class CheckDailyCheckins extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'checkins:check-missed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Identify missing daily check-ins for active patients and alert caregivers';

    /**
     * Execute the console command.
     */
    public function handle(CareCheckinService $checkinService): int
    {
        $this->info('Evaluating patient daily check-in statuses...');

        $missedCount = $checkinService->checkMissedCheckins();

        $this->info("Completed daily check-in evaluation. Recorded and alerted on {$missedCount} missing check-in(s).");

        return Command::SUCCESS;
    }
}
