<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteIncompleteProfiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'profiles:delete-incomplete {--hours=24 : Number of hours old to consider for deletion}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete users with incomplete profiles older than the specified time period';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $threshold = now()->subHours($hours);

        try {
            $deletedCount = User::whereHas('profile', function ($query) {
                $query->where('profile_completed', false);
            })
            ->where('created_at', '<', $threshold)
            ->delete();

            $this->info("Deleted {$deletedCount} user(s) with incomplete profiles older than {$hours} hours.");
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Error deleting incomplete profiles: ' . $e->getMessage());
            return 1;
        }
    }
}
