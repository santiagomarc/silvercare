<?php

namespace App\Services;

use App\Events\CriticalAlertFired;
use App\Mail\ClinicalAlertMail;
use App\Models\Alert;
use App\Models\AlertDelivery;
use App\Models\PushSubscription;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

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

        // 5. Browser push — reaches a caregiver who is not looking at the
        //    dashboard and has not opened their email.
        if (in_array($alert->severity, (array) config('webpush.push_severities', []), true)) {
            $this->deliverPush($alert, $caregiver);
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

            if (in_array($alert->severity, (array) config('webpush.push_severities', []), true)) {
                $this->deliverPush($alert, $caregiver);
            }
        }

        $escalateMinutes = config("alerts.escalation_minutes.{$alert->severity}", 15);
        $alert->update([
            'escalate_at' => Carbon::now()->addMinutes($escalateMinutes),
        ]);

        Log::warning("Alert #{$alert->id} re-escalated for patient #{$alert->elderly_id}");
    }

    /**
     * Retry the channels that failed for an alert.
     *
     * Used by `alerts:redeliver-failed` to recover deliveries lost while the
     * stale notifications_severity_check rejected every 'critical' write (C1).
     * A failed row is left in place — "failed at T1, delivered at T2" is the
     * honest audit trail — and a successful retry adds a new row alongside it.
     *
     * @return array{retried: int, recovered: int, skipped: int}
     */
    public function retryFailedDeliveries(Alert $alert): array
    {
        $retried = 0;
        $recovered = 0;
        $skipped = 0;

        $failures = AlertDelivery::where('alert_id', $alert->id)
            ->where('state', 'failed')
            ->orderBy('id')
            ->get();

        foreach ($failures as $failure) {
            // A later attempt on the same channel and recipient may already
            // have landed — don't send the caregiver a duplicate.
            $alreadyDelivered = AlertDelivery::where('alert_id', $alert->id)
                ->where('channel', $failure->channel)
                ->where('recipient_profile_id', $failure->recipient_profile_id)
                ->whereIn('state', ['sent', 'delivered'])
                ->exists();

            if ($alreadyDelivered) {
                $skipped++;
                continue;
            }

            $retried++;

            match ($failure->channel) {
                'in_app' => $this->deliverInApp($alert, $failure->recipient_profile_id),
                'reverb' => $this->deliverReverb($alert, $failure->recipient_profile_id),
                'email' => $this->retryEmail($alert, $failure->recipient_profile_id),
                'browser_push' => $this->retryPush($alert, $failure->recipient_profile_id),
                default => null,
            };

            $nowDelivered = AlertDelivery::where('alert_id', $alert->id)
                ->where('channel', $failure->channel)
                ->where('recipient_profile_id', $failure->recipient_profile_id)
                ->whereIn('state', ['sent', 'delivered'])
                ->exists();

            if ($nowDelivered) {
                $recovered++;
            }
        }

        return ['retried' => $retried, 'recovered' => $recovered, 'skipped' => $skipped];
    }

    /**
     * H8 — browser push to every device the caregiver has enabled.
     *
     * Skipped silently when VAPID keys are not configured or the caregiver has
     * no subscriptions: push is an additional channel, never the only one, so
     * its absence must not affect in-app or email delivery.
     */
    protected function deliverPush(Alert $alert, UserProfile $caregiver): void
    {
        $publicKey = config('webpush.vapid.public_key');
        $privateKey = config('webpush.vapid.private_key');

        if (empty($publicKey) || empty($privateKey)) {
            return;
        }

        $subscriptions = PushSubscription::where('profile_id', $caregiver->id)->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $patientName = $alert->elderly?->user?->name ?? 'Your patient';

        $payload = json_encode([
            'title' => $alert->title,
            'body' => $alert->message,
            'severity' => $alert->severity,
            'alert_id' => $alert->id,
            'patient' => $patientName,
            'url' => route('caregiver.dashboard'),
        ], JSON_UNESCAPED_UNICODE);

        try {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => config('webpush.vapid.subject'),
                    'publicKey' => $publicKey,
                    'privateKey' => $privateKey,
                ],
            ], [
                'TTL' => (int) config('webpush.ttl', 14400),
                'urgency' => (string) config('webpush.urgency', 'high'),
            ]);

            foreach ($subscriptions as $subscription) {
                $webPush->queueNotification(
                    Subscription::create($subscription->toWebPushArray()),
                    $payload
                );
            }

            $sent = 0;
            $failures = [];

            foreach ($webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    $sent++;
                    continue;
                }

                $failures[] = $report->getReason();

                // 404/410 means the browser revoked this subscription. Keeping
                // it would fail on every future alert.
                if ($report->isSubscriptionExpired() && config('webpush.prune_expired', true)) {
                    PushSubscription::where(
                        'endpoint_hash',
                        PushSubscription::hashEndpoint($report->getEndpoint())
                    )->delete();
                }
            }

            AlertDelivery::create([
                'alert_id' => $alert->id,
                'recipient_profile_id' => $caregiver->id,
                'channel' => 'browser_push',
                'state' => $sent > 0 ? 'sent' : 'failed',
                'sent_at' => $sent > 0 ? Carbon::now() : null,
                'error' => $failures === [] ? null : implode(' | ', array_slice($failures, 0, 3)),
            ]);
        } catch (\Throwable $e) {
            Log::error("Push delivery failed for alert #{$alert->id}: " . $e->getMessage());

            AlertDelivery::create([
                'alert_id' => $alert->id,
                'recipient_profile_id' => $caregiver->id,
                'channel' => 'browser_push',
                'state' => 'failed',
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function retryPush(Alert $alert, int $profileId): void
    {
        $recipient = UserProfile::find($profileId);

        if ($recipient) {
            $this->deliverPush($alert, $recipient);
        }
    }

    protected function retryEmail(Alert $alert, int $profileId): void
    {
        $recipient = UserProfile::find($profileId);

        if ($recipient) {
            $this->deliverEmail($alert, $recipient);
        }
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

            // C3: 'sent', not 'delivered'. Dispatching to Reverb proves the
            // server handed the event off, not that a browser received it —
            // and a clinical audit trail must not claim otherwise.
            AlertDelivery::create([
                'alert_id' => $alert->id,
                'recipient_profile_id' => $caregiverProfileId,
                'channel' => 'reverb',
                'state' => 'sent',
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

            // H7: queued, not inline. An SOS tap must not block on an SMTP
            // round-trip, and a slow mail server must not become a failed
            // delivery on the highest-severity path in the system.
            Mail::to($caregiverUser->email)->queue(
                new ClinicalAlertMail($alert, $patientName)
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
