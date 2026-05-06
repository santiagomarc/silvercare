<?php

namespace App\Console\Commands;

use App\Models\GoogleFitToken;
use App\Services\GoogleFitService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncGoogleFitCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-google-fit
                            {--dry-run : Log what would be synced without writing to the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Google Fit health data for all connected elderly users.';

    public function handle(GoogleFitService $googleFitService): int
    {
        $isDryRun = $this->option('dry-run');

        $tokens = GoogleFitToken::with('user.profile')->get();

        if ($tokens->isEmpty()) {
            $this->info('No Google Fit connections found. Nothing to sync.');
            return self::SUCCESS;
        }

        $this->info("Found {$tokens->count()} connected user(s). Starting sync...");

        $succeeded = 0;
        $failed    = 0;
        $skipped   = 0;

        foreach ($tokens as $token) {
            $user    = $token->user;
            $profile = $user?->profile;

            // Only sync elderly users who have a complete profile.
            if (! $profile || ! $profile->isElderly()) {
                $this->line("  Skipping user #{$token->user_id} — not an elderly profile.");
                $skipped++;
                continue;
            }

            $label = "user #{$token->user_id} (profile #{$profile->id})";

            if ($isDryRun) {
                $this->line("  [dry-run] Would sync {$label}.");
                $succeeded++;
                continue;
            }

            try {
                $synced = $googleFitService->syncAll($profile->id, $user->id);

                if (empty($synced)) {
                    $this->line("  ✔ {$label}: No new data (up to date).");
                } else {
                    $summary = collect($synced)
                        ->map(fn ($v, $k) => "{$k}: {$v}")
                        ->join(', ');
                    $this->line("  ✔ {$label}: {$summary}");
                    Log::info("Google Fit background sync succeeded for {$label}", $synced);
                }

                $succeeded++;
            } catch (\RuntimeException $e) {
                // Code 401 → refresh token is stale; mark the token as dead.
                if ($e->getCode() === 401) {
                    $this->warn("  ✖ {$label}: Refresh token expired. Removing stale connection.");
                    Log::warning("Google Fit stale token removed for {$label}: " . $e->getMessage());
                    $token->delete(); // disconnectUser() would also call revoke, but the token is already dead.
                } else {
                    $this->warn("  ✖ {$label}: " . $e->getMessage());
                    Log::warning("Google Fit sync failed for {$label}: " . $e->getMessage());
                }
                $failed++;
            } catch (\Exception $e) {
                // Network/API error — log and continue to next user.
                $this->warn("  ✖ {$label}: Unexpected error — " . $e->getMessage());
                Log::error("Google Fit sync unexpected error for {$label}: " . $e->getMessage(), [
                    'exception' => $e,
                ]);
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Sync complete. Succeeded: {$succeeded}, Failed: {$failed}, Skipped: {$skipped}.");

        return self::SUCCESS;
    }
}
