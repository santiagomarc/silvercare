<?php

namespace App\Events;

use App\Models\DoseInstance;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DoseConfirmedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public DoseInstance $doseInstance,
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
        return 'dose.confirmed';
    }

    public function broadcastWith(): array
    {
        return [
            'dose_instance_id' => $this->doseInstance->id,
            'medication_name' => $this->doseInstance->medication?->name ?? 'Medication',
            'state' => $this->doseInstance->state,
            'scheduled_at' => $this->doseInstance->scheduled_at_utc?->toISOString(),
            'taken_at' => $this->doseInstance->taken_at?->toISOString(),
            'patient_id' => $this->doseInstance->elderly_id,
            'patient_name' => $this->doseInstance->elderly?->user?->name ?? 'Patient',
        ];
    }
}
