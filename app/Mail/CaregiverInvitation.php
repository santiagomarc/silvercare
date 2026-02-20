<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class CaregiverInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public $caregiverUser;
    public $elderlyUser;
    public $setPasswordUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(User $caregiverUser, User $elderlyUser)
    {
        $this->caregiverUser = $caregiverUser;
        $this->elderlyUser = $elderlyUser;

        // Generate signed URL valid for 7 days
        $this->setPasswordUrl = URL::temporarySignedRoute(
            'caregiver.password.show',
            now()->addDays(7),
            ['userId' => $caregiverUser->id]
        );
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You\'ve been invited as a Caregiver on SilverCare',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.caregiver-invitation',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
