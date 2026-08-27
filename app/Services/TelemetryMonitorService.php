<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\GoogleFitToken;
use App\Models\SyncTelemetryLog;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TelemetryMonitorService
{
    public function __construct(
        protected AlertDeliveryService $alertDeliveryService,
    ) {
    }

    /**
     * Log a telemetry sync event.
     */
    public function recordSync(
        UserProfile $elderly,
        string $serviceName,
        string $status,
        int $recordsSynced = 0,
        ?int $durationMs = null,
        ?string $error = null
    ): SyncTelemetryLog {
        return SyncTelemetryLog::create([
            'elderly_id' => $elderly->id,
            'service_name' => $serviceName,
            'status' => $status,
            'records_synced' => $recordsSynced,
            'response_time_ms' => $durationMs,
            'error_details' => $error,
            'synced_at' => Carbon::now(),
        ]);
    }

    /**
     * Check integration health across all connected devices and alert caregivers if disconnected or stale.
     */
    public function checkIntegrationHealth(): array
    {
        $tokens = GoogleFitToken::all();
        $monitoredCount = $tokens->count();
        $alertsTriggered = 0;

        foreach ($tokens as $token) {
            $elderly = $token->user?->profile ?? UserProfile::where('user_id', $token->user_id)->first();
            if (!$elderly) {
                continue;
            }

            $patientName = $elderly->user?->name ?? 'Patient';

            // Check if token is expired or flagged as revoked
            $isExpired = $token->expires_at && Carbon::parse($token->expires_at)->isPast();
            $latestLog = SyncTelemetryLog::where('elderly_id', $elderly->id)
                ->where('service_name', 'google_fit')
                ->latest('synced_at')
                ->first();

            $isStale = $latestLog && $latestLog->synced_at->diffInHours(Carbon::now()) > 72;
            $hasRecentFailure = $latestLog && in_array($latestLog->status, ['token_expired', 'failed'], true);

            if (($isExpired || $hasRecentFailure || $isStale) && $elderly->caregiver_id) {
                // Ensure we don't spam duplicate open alerts
                $existingOpenAlert = Alert::where('elderly_id', $elderly->id)
                    ->where('source_type', 'integration_telemetry')
                    ->where('state', 'open')
                    ->exists();

                if (!$existingOpenAlert) {
                    $alert = Alert::create([
                        'elderly_id' => $elderly->id,
                        'severity' => 'warning',
                        'source_type' => 'integration_telemetry',
                        'title' => "⚠️ Google Fit Disconnected: {$patientName}",
                        'message' => "Health sync with Google Fit for {$patientName} has been interrupted. Please re-authenticate the connection.",
                        'metadata' => [
                            'service' => 'google_fit',
                            'is_expired' => $isExpired,
                            'is_stale' => $isStale,
                            'last_sync' => $latestLog?->synced_at?->toISOString(),
                        ],
                        'state' => 'open',
                        'escalate_at' => Carbon::now()->addHours(24),
                    ]);

                    $this->alertDeliveryService->deliver($alert);
                    $alertsTriggered++;
                }
            }
        }

        return [
            'monitored_count' => $monitoredCount,
            'alerts_triggered' => $alertsTriggered,
        ];
    }
}
