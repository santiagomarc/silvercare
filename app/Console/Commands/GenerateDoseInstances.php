<?php

namespace App\Console\Commands;

use App\Services\DoseInstanceGeneratorService;
use Illuminate\Console\Command;

class GenerateDoseInstances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'doses:generate-instances {--days=7 : Number of days ahead to generate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate pending dose instances for active medication schedules';

    /**
     * Execute the console command.
     */
    public function handle(DoseInstanceGeneratorService $generatorService): int
    {
        $days = (int) $this->option('days');
        if ($days <= 0) {
            $days = 7;
        }

        $this->info("Generating dose instances for the next {$days} days...");

        $count = $generatorService->generateForActiveMedications(null, $days);

        $this->info("Successfully generated/verified {$count} dose instances.");

        return Command::SUCCESS;
    }
}
