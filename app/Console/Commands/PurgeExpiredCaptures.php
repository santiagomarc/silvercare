<?php

namespace App\Console\Commands;

use App\Models\CaptureSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * M5 — delete expired capture images and their sessions.
 *
 * Capture sessions hold photographs of prescription labels and home vitals
 * monitors. That is PHI, and it shipped with no expiry and no cleanup, so every
 * scan a patient ever took was retained indefinitely.
 *
 * The confirmed *values* live on the medication and health_metric records; the
 * image has no purpose once the patient has confirmed what it said.
 */
class PurgeExpiredCaptures extends Command
{
    protected $signature = 'captures:purge-expired
                            {--dry-run : List what would be deleted without deleting anything}';

    protected $description = 'Delete expired capture session images and rows (PHI retention)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $expired = CaptureSession::expired()->get();

        if ($expired->isEmpty()) {
            $this->info('No expired capture sessions.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%s %d expired capture session(s).',
            $dryRun ? 'Would purge' : 'Purging',
            $expired->count()
        ));

        $filesDeleted = 0;
        $rowsDeleted = 0;
        $missingFiles = 0;

        foreach ($expired as $session) {
            $this->line(sprintf(
                '  #%d %s (patient %d, expired %s)',
                $session->id,
                $session->session_type,
                $session->elderly_id,
                $session->expires_at?->diffForHumans() ?? 'unknown'
            ));

            if ($dryRun) {
                continue;
            }

            if ($session->image_path) {
                if (Storage::disk('public')->exists($session->image_path)) {
                    Storage::disk('public')->delete($session->image_path);
                    $filesDeleted++;
                } else {
                    // Already gone — still remove the row so it is not retried
                    // forever, but say so rather than reporting a clean delete.
                    $missingFiles++;
                }
            }

            $session->delete();
            $rowsDeleted++;
        }

        if ($dryRun) {
            $this->comment('Dry run — nothing was deleted.');

            return self::SUCCESS;
        }

        $this->info("Deleted {$rowsDeleted} session(s) and {$filesDeleted} image file(s).");

        if ($missingFiles > 0) {
            $this->warn("{$missingFiles} session(s) had no file on disk; rows removed anyway.");
        }

        return self::SUCCESS;
    }
}
