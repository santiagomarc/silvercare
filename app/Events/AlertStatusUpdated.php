<?php

namespace App\Events;

use App\Models\Alert;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AlertStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Alert $alert,
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
        return 'alert.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'alert_id' => $this->alert->id,
            'state' => $this->alert->state,
            'patient_id' => $this->alert->elderly_id,
            'patient_name' => $this->alert->elderly?->user?->name ?? 'Patient',
            'acknowledged_at' => $this->alert->acknowledged_at?->toISOString(),
            'resolved_at' => $this->alert->resolved_at?->toISOString(),
        ];
    }
}
