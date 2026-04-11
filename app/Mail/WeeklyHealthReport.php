<?php

namespace App\Mail;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyHealthReport extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $caregiverUser,
        public UserProfile $elderlyProfile,
        public string $pdfBinary,
        public string $filename,
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $elderName = $this->elderlyProfile->user?->name ?? 'Patient';

        return new Envelope(
            subject: "Weekly Health Report - {$elderName}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.weekly-health-report',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfBinary, $this->filename)
                ->withMime('application/pdf'),
        ];
    }
}
