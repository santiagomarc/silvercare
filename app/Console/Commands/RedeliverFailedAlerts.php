<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Services\AlertDeliveryService;
use Illuminate\Console\Command;

/**
 * Recovery for deliveries lost to C1.
 *
 * Between the sprint 2 deploy and the constraint repair, every in-app delivery
 * of a critical or emergency alert was rejected by the stale
 * notifications_severity_check and swallowed into an alert_deliveries row with
 * state 'failed'. Caregivers never saw those alerts.
 *
 * This walks unresolved alerts with failed deliveries and retries the failed
 * channels. Safe to re-run: a channel that has since succeeded is skipped.
 */
class RedeliverFailedAlerts extends Command
{
    protected $signature = 'alerts:redeliver-failed
                            {--dry-run : List what would be retried without sending anything}
                            {--include-resolved : Also retry alerts that have already been resolved}
                            {--days=30 : Only consider alerts created within this many days}';

    protected $description = 'Retry alert deliveries that previously failed, for alerts still needing attention';

    public function handle(AlertDeliveryService $deliveryService): int
    {
        $days = max((int) $this->option('days'), 1);
        $dryRun = (bool) $this->option('dry-run');

        $query = Alert::whereHas('deliveries', fn ($q) => $q->where('state', 'failed'))
            ->where('created_at', '>=', now()->subDays($days))
            ->with(['elderly.user', 'deliveries'])
            ->orderBy('created_at');

        if (! $this->option('include-resolved')) {
            $query->whereIn('state', ['open', 'acknowledged']);
        }

        $alerts = $query->get();

        if ($alerts->isEmpty()) {
            $this->info('No alerts with failed deliveries in the selected window.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%s %d alert(s) with failed deliveries from the last %d day(s).',
            $dryRun ? 'Would retry' : 'Retrying',
            $alerts->count(),
            $days
        ));
        $this->newLine();

        $totalRetried = 0;
        $totalRecovered = 0;

        foreach ($alerts as $alert) {
            $patient = $alert->elderly?->user?->name ?? "profile #{$alert->elderly_id}";
            $failedChannels = $alert->deliveries
                ->where('state', 'failed')
                ->pluck('channel')
                ->unique()
                ->implode(', ');

            $this->line(sprintf(
                '  #%d [%s] %s — %s (failed: %s)',
                $alert->id,
                strtoupper($alert->severity),
                $patient,
                $alert->title,
                $failedChannels
            ));

            if ($dryRun) {
                continue;
            }

            $result = $deliveryService->retryFailedDeliveries($alert);
            $totalRetried += $result['retried'];
            $totalRecovered += $result['recovered'];

            $this->line(sprintf(
                '      retried %d, recovered %d, skipped %d (already delivered)',
                $result['retried'],
                $result['recovered'],
                $result['skipped']
            ));
        }

        $this->newLine();

        if ($dryRun) {
            $this->comment('Dry run — nothing was sent. Re-run without --dry-run to deliver.');

            return self::SUCCESS;
        }

        $this->info("Done. Retried {$totalRetried} delivery attempt(s), recovered {$totalRecovered}.");

        if ($totalRecovered < $totalRetried) {
            $this->warn(
                'Some retries still failed. Check storage/logs and alert_deliveries.error — '
                . 'Reverb failures are expected when the websocket server is not running.'
            );
        }

        return self::SUCCESS;
    }
}
