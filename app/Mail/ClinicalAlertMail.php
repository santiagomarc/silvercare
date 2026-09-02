<?php

namespace App\Mail;

use App\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * H7 — Alert email, queued.
 *
 * This was previously a synchronous Mail::raw() inside AlertDeliveryService,
 * so an SOS tap blocked on the SMTP round-trip before the senior saw any
 * response, and a mail-server timeout became a failed delivery on the highest
 * severity path in the system.
 *
 * Implementing ShouldQueue moves it onto the queue with retries. The delivery
 * record is written when the job succeeds, not when it is queued — see
 * AlertDeliveryService::deliverEmail().
 */
class ClinicalAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Retry a failing send three times with widening gaps, then let it land in
     * failed_jobs where the health check can surface it.
     */
    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120, 300];

    public function __construct(
        public Alert $alert,
        public string $patientName,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[SilverCare ' . strtoupper($this->alert->severity) . '] ' . $this->alert->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.clinical-alert',
            with: [
                'alert' => $this->alert,
                'patientName' => $this->patientName,
                'disclaimer' => config('alerts.emergency_disclaimer'),
                'dashboardUrl' => route('caregiver.dashboard'),
            ],
        );
    }
}
