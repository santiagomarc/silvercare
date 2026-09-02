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
            // C3: the senior's own devices, so a dose confirmed by the AI
            // assistant or on another device updates their screen live.
            new PrivateChannel('elderly.' . $this->doseInstance->elderly_id),
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
            // Fields the senior's medication tracker needs to update in place.
            'medication_id' => $this->doseInstance->medication_id,
            'scheduled_time' => $this->doseInstance->scheduled_at_utc
                ?->copy()
                ->setTimezone($this->doseInstance->timezone ?: config('app.timezone', 'Asia/Manila'))
                ->format('H:i'),
            'taken_late' => $this->doseInstance->state === 'taken_late',
        ];
    }
}
