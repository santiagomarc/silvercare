<?php

namespace App\Events;

use App\Models\CareCheckin;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CheckinReceivedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public CareCheckin $checkin,
        public int $recipientCaregiverProfileId
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('caregiver.' . $this->recipientCaregiverProfileId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'checkin.received';
    }

    public function broadcastWith(): array
    {
        return [
            'checkin_id' => $this->checkin->id,
            'status' => $this->checkin->status,
            'mood' => $this->checkin->mood,
            'notes' => $this->checkin->notes,
            'patient_id' => $this->checkin->elderly_id,
            'patient_name' => $this->checkin->elderly?->user?->name ?? 'Patient',
            'time' => $this->checkin->checked_in_at?->toISOString(),
        ];
    }
}
