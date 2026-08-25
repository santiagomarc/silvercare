<?php

namespace App\Services;

use App\Events\CriticalAlertFired;
use App\Models\Alert;
use App\Models\AlertDelivery;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AlertDeliveryService
{
    public function __construct(
        protected NotificationService $notificationService,
    ) {
    }

    /**
     * Dispatch an alert across configured channels (in-app, reverb, email).
     */
    public function deliver(Alert $alert): void
    {
        $elderly = $alert->elderly;
        if (!$elderly) {
            return;
        }

        $caregiver = $elderly->caregiver;

        // 1. In-app notification for patient
        $this->deliverInApp($alert, $elderly->id);

        if (!$caregiver) {
            Log::info("Alert #{$alert->id} created for patient #{$elderly->id} without linked caregiver.");
            return;
        }

        // 2. In-app notification for caregiver
        $this->deliverInApp($alert, $caregiver->id);

        // 3. Realtime Reverb Broadcast
        $this->deliverReverb($alert, $caregiver->id);

        // 4. Email delivery for critical/emergency alerts
        if (in_array($alert->severity, ['critical', 'emergency'], true)) {
            $this->deliverEmail($alert, $caregiver);
        }
    }

    /**
     * Re-escalate unacknowledged alert.
     */
    public function reEscalate(Alert $alert): void
    {
        if ($alert->isAcknowledged() || $alert->isResolved()) {
            return;
        }

        $elderly = $alert->elderly;
        $caregiver = $elderly?->caregiver;

        if ($caregiver) {
            $this->deliverReverb($alert, $caregiver->id);
            $this->deliverEmail($alert, $caregiver);
        }

        $escalateMinutes = config("alerts.escalation_minutes.{$alert->severity}", 15);
        $alert->update([
            'escalate_at' => Carbon::now()->addMinutes($escalateMinutes),
        ]);

        Log::warning("Alert #{$alert->id} re-escalated for patient #{$alert->elderly_id}");
    }

    protected function deliverInApp(Alert $alert, int $profileId): void
    {
        try {
            $severity = match ($alert->severity) {
                'emergency', 'critical' => 'critical',
                'warning' => 'warning',
                default => 'reminder',
            };

            $notifType = ($alert->source_type === 'sos') ? 'sos_alert' : 'alert_' . $alert->source_type;

            $this->notificationService->createNotification([
                'elderly_id' => $profileId,
                'type' => $notifType,
                'title' => $alert->title,
                'message' => $alert->message,
                'severity' => $severity,
                'metadata' => [
                    'alert_id' => $alert->id,
                    'severity' => $alert->severity,
                    'source_type' => $alert->source_type,
                ],
            ]);

            AlertDelivery::create([
                'alert_id' => $alert->id,
                'recipient_profile_id' => $profileId,
                'channel' => 'in_app',
                'state' => 'delivered',
                'sent_at' => Carbon::now(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed in-app delivery for alert #{$alert->id}: " . $e->getMessage());
            AlertDelivery::create([
                'alert_id' => $alert->id,
                'recipient_profile_id' => $profileId,
                'channel' => 'in_app',
                'state' => 'failed',
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function deliverReverb(Alert $alert, int $caregiverProfileId): void
    {
        try {
            event(new CriticalAlertFired($alert, $caregiverProfileId));

            AlertDelivery::create([
                'alert_id' => $alert->id,
                'recipient_profile_id' => $caregiverProfileId,
                'channel' => 'reverb',
                'state' => 'delivered',
                'sent_at' => Carbon::now(),
            ]);
        } catch (\Exception $e) {
            Log::warning("Reverb broadcast delivery failed for alert #{$alert->id}: " . $e->getMessage());
            AlertDelivery::create([
                'alert_id' => $alert->id,
                'recipient_profile_id' => $caregiverProfileId,
                'channel' => 'reverb',
                'state' => 'failed',
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function deliverEmail(Alert $alert, UserProfile $caregiver): void
    {
        $caregiverUser = $caregiver->user;
        if (!$caregiverUser || !$caregiverUser->email) {
            return;
        }

        try {
            $patientName = $alert->elderly?->user?->name ?? 'Your patient';
            $disclaimer = config('alerts.emergency_disclaimer');

            Mail::raw(
                "🚨 {$alert->title}\n\nPatient: {$patientName}\nSeverity: " . strtoupper($alert->severity) . "\nMessage: {$alert->message}\nTime: " . now()->format('Y-m-d H:i:s') . "\n\n{$disclaimer}\n\nPlease open SilverCare dashboard immediately to review and acknowledge.",
                function ($message) use ($caregiverUser, $alert) {
                    $message->to($caregiverUser->email)
                        ->subject("🚨 [SilverCare " . strtoupper($alert->severity) . "] " . $alert->title);
                }
            );

            AlertDelivery::create([
                'alert_id' => $alert->id,
                'recipient_profile_id' => $caregiver->id,
                'channel' => 'email',
                'state' => 'sent',
                'sent_at' => Carbon::now(),
            ]);
        } catch (\Exception $e) {
            Log::error("Email delivery failed for alert #{$alert->id}: " . $e->getMessage());
            AlertDelivery::create([
                'alert_id' => $alert->id,
                'recipient_profile_id' => $caregiver->id,
                'channel' => 'email',
                'state' => 'failed',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
