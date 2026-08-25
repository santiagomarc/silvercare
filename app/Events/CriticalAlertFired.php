<?php

namespace App\Events;

use App\Models\Alert;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CriticalAlertFired implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Alert $alert,
        public int $recipientCaregiverProfileId
    ) {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('caregiver.' . $this->recipientCaregiverProfileId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'critical.alert.fired';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'alert_id' => $this->alert->id,
            'severity' => $this->alert->severity,
            'source_type' => $this->alert->source_type,
            'title' => $this->alert->title,
            'message' => $this->alert->message,
            'created_at' => $this->alert->created_at?->toISOString(),
            'patient_id' => $this->alert->elderly_id,
            'patient_name' => $this->alert->elderly?->user?->name ?? 'Patient',
        ];
    }
}
