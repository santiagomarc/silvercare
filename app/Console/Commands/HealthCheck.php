<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Models\AlertDelivery;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * M4 — operational telemetry.
 *
 * Two failures in this system were invisible for weeks because nothing watched
 * for them: the scheduler could stop and no one would know, and alert_deliveries
 * accumulated `failed` rows that nothing ever read. This reports both, plus the
 * numbers that say whether the alert pipeline is actually working.
 *
 * Run every 15 minutes. Exits non-zero when something needs attention, so it
 * can be wired to an external monitor.
 */
class HealthCheck extends Command
{
    protected $signature = 'silvercare:health-check
                            {--json : Emit machine-readable JSON instead of a table}';

    protected $description = 'Report scheduler, alert delivery, and integration health';

    /** Cache key the scheduler heartbeat writes to. */
    public const HEARTBEAT_KEY = 'silvercare:scheduler:last-run';

    public function handle(): int
    {
        $report = [
            'generated_at' => now()->toISOString(),
            'scheduler' => $this->schedulerHeartbeat(),
            'alert_deliveries' => $this->deliveryHealth(),
            'acknowledgement' => $this->acknowledgementLatency(),
            'overdue_alerts' => $this->overdueAlerts(),
            'queue' => $this->queueHealth(),
            'captures' => $this->captureRetention(),
        ];

        $problems = $this->collectProblems($report);
        $report['status'] = $problems === [] ? 'ok' : 'attention';
        $report['problems'] = $problems;

        if ($this->option('json')) {
            $this->line((string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $problems === [] ? self::SUCCESS : self::FAILURE;
        }

        $this->renderTable($report, $problems);

        return $problems === [] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * The scheduler writes a timestamp on every run. If it stops, everything
     * time-based stops with it — missed-dose detection, escalation, check-ins —
     * and nothing else would notice.
     */
    private function schedulerHeartbeat(): array
    {
        $last = Cache::get(self::HEARTBEAT_KEY);
        $lastRun = $last ? Carbon::parse($last) : null;
        $minutesAgo = $lastRun ? (int) $lastRun->diffInMinutes(now()) : null;

        return [
            'last_run' => $lastRun?->toISOString(),
            'minutes_ago' => $minutesAgo,
            // The heartbeat fires every 5 minutes; twice that is the tolerance.
            'healthy' => $minutesAgo !== null && $minutesAgo <= 10,
        ];
    }

    private function deliveryHealth(): array
    {
        $since = now()->subDay();

        $rows = AlertDelivery::where('created_at', '>=', $since)
            ->select('channel', 'state', DB::raw('count(*) as total'))
            ->groupBy('channel', 'state')
            ->get();

        $failed = (int) $rows->where('state', 'failed')->sum('total');
        $total = (int) $rows->sum('total');

        return [
            'window' => '24h',
            'total' => $total,
            'failed' => $failed,
            'by_channel' => $rows->groupBy('channel')->map(
                fn ($group) => $group->pluck('total', 'state')->all()
            )->all(),
            'healthy' => $failed === 0,
        ];
    }

    /**
     * How long a caregiver takes to acknowledge. A rising P95 means alerts are
     * arriving but not being seen.
     */
    private function acknowledgementLatency(): array
    {
        $acknowledged = Alert::whereNotNull('acknowledged_at')
            ->where('created_at', '>=', now()->subDays(7))
            ->get(['created_at', 'acknowledged_at']);

        if ($acknowledged->isEmpty()) {
            return ['count' => 0, 'average_minutes' => null, 'p95_minutes' => null, 'healthy' => true];
        }

        $latencies = $acknowledged
            ->map(fn ($a) => (int) $a->created_at->diffInMinutes($a->acknowledged_at))
            ->sort()
            ->values();

        $p95Index = (int) floor(0.95 * ($latencies->count() - 1));

        return [
            'count' => $latencies->count(),
            'average_minutes' => (int) round($latencies->avg()),
            'p95_minutes' => (int) $latencies[$p95Index],
            'healthy' => true,
        ];
    }

    /**
     * Alerts past their escalation deadline and still unacknowledged. These are
     * the ones a caregiver has not seen.
     */
    private function overdueAlerts(): array
    {
        $overdue = Alert::where('state', 'open')
            ->whereNotNull('escalate_at')
            ->where('escalate_at', '<=', now())
            ->get(['id', 'severity', 'elderly_id', 'escalate_at']);

        return [
            'count' => $overdue->count(),
            'critical_or_worse' => $overdue->whereIn('severity', ['critical', 'emergency'])->count(),
            'ids' => $overdue->pluck('id')->take(10)->all(),
            'healthy' => $overdue->whereIn('severity', ['critical', 'emergency'])->isEmpty(),
        ];
    }

    /**
     * Alert email is queued (H7). Without a worker running, those jobs sit
     * unsent and the highest-severity channel silently stops.
     */
    private function queueHealth(): array
    {
        if (! Schema::hasTable('jobs')) {
            return ['pending' => null, 'failed' => null, 'healthy' => true, 'note' => 'not using the database queue'];
        }

        $pending = (int) DB::table('jobs')->count();
        $failed = Schema::hasTable('failed_jobs') ? (int) DB::table('failed_jobs')->count() : 0;

        $oldest = DB::table('jobs')->min('available_at');
        $oldestAgeMinutes = $oldest ? (int) Carbon::createFromTimestamp($oldest)->diffInMinutes(now()) : null;

        return [
            'pending' => $pending,
            'failed' => $failed,
            'oldest_pending_minutes' => $oldestAgeMinutes,
            // A backlog is fine; a backlog that is not draining is not.
            'healthy' => $failed === 0 && ($oldestAgeMinutes === null || $oldestAgeMinutes <= 15),
        ];
    }

    private function captureRetention(): array
    {
        if (! Schema::hasTable('capture_sessions')) {
            return ['expired_pending_purge' => 0, 'healthy' => true];
        }

        $expired = (int) DB::table('capture_sessions')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->count();

        return [
            'expired_pending_purge' => $expired,
            // PHI kept past its retention window is a compliance problem, not
            // just untidiness.
            'healthy' => $expired === 0,
        ];
    }

    /** @return list<string> */
    private function collectProblems(array $report): array
    {
        $problems = [];

        if (! $report['scheduler']['healthy']) {
            $problems[] = $report['scheduler']['last_run'] === null
                ? 'Scheduler has never run. Time-based features (missed doses, escalation, check-ins) are all inactive.'
                : "Scheduler last ran {$report['scheduler']['minutes_ago']} minutes ago — it may have stopped.";
        }

        if (! $report['alert_deliveries']['healthy']) {
            $problems[] = "{$report['alert_deliveries']['failed']} alert delivery failure(s) in the last 24h. Run `alerts:redeliver-failed`.";
        }

        if (! $report['overdue_alerts']['healthy']) {
            $problems[] = "{$report['overdue_alerts']['critical_or_worse']} critical/emergency alert(s) past their escalation deadline and still unacknowledged.";
        }

        if (! $report['queue']['healthy']) {
            $problems[] = $report['queue']['failed'] > 0
                ? "{$report['queue']['failed']} failed queue job(s) — alert emails may not be sending."
                : "Queue backlog is {$report['queue']['oldest_pending_minutes']} minutes old. Is `queue:work` running?";
        }

        if (! $report['captures']['healthy']) {
            $problems[] = "{$report['captures']['expired_pending_purge']} capture session(s) past their PHI retention window. Run `captures:purge-expired`.";
        }

        return $problems;
    }

    private function renderTable(array $report, array $problems): void
    {
        $this->newLine();
        $this->line('<options=bold>SilverCare health check</> — ' . $report['generated_at']);
        $this->newLine();

        $this->table(
            ['Check', 'Status', 'Detail'],
            [
                [
                    'Scheduler heartbeat',
                    $this->badge($report['scheduler']['healthy']),
                    $report['scheduler']['last_run']
                        ? "last run {$report['scheduler']['minutes_ago']}m ago"
                        : 'never run',
                ],
                [
                    'Alert deliveries (24h)',
                    $this->badge($report['alert_deliveries']['healthy']),
                    "{$report['alert_deliveries']['failed']} failed of {$report['alert_deliveries']['total']}",
                ],
                [
                    'Acknowledgement latency (7d)',
                    'INFO',
                    $report['acknowledgement']['count'] === 0
                        ? 'no acknowledged alerts'
                        : "avg {$report['acknowledgement']['average_minutes']}m, p95 {$report['acknowledgement']['p95_minutes']}m",
                ],
                [
                    'Overdue alerts',
                    $this->badge($report['overdue_alerts']['healthy']),
                    "{$report['overdue_alerts']['count']} open past deadline",
                ],
                [
                    'Queue',
                    $this->badge($report['queue']['healthy']),
                    "{$report['queue']['pending']} pending, {$report['queue']['failed']} failed",
                ],
                [
                    'Capture retention',
                    $this->badge($report['captures']['healthy']),
                    "{$report['captures']['expired_pending_purge']} past retention",
                ],
            ]
        );

        if ($problems === []) {
            $this->info('All checks healthy.');

            return;
        }

        $this->newLine();
        $this->warn('Needs attention:');
        foreach ($problems as $problem) {
            $this->line('  • ' . $problem);
        }
    }

    private function badge(bool $healthy): string
    {
        return $healthy ? '<fg=green>OK</>' : '<fg=red>FAIL</>';
    }
}
