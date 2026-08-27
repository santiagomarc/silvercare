<?php

namespace App\Services;

use App\Models\DoseInstance;
use App\Models\HealthMetric;
use App\Models\Medication;
use App\Models\OfflineSyncLog;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OfflineReconciliationService
{
    public function __construct(
        protected DoseAdministrationService $doseService,
        protected ClinicalRulesService $clinicalRulesService,
        protected CareCheckinService $checkinService,
    ) {
    }

    /**
     * Reconcile and apply a batch of offline client mutations.
     */
    public function syncBatch(UserProfile $elderly, array $mutations, ?User $actor = null): array
    {
        $actorId = $actor?->id ?? $elderly->user_id;
        $results = [];
        $appliedCount = 0;
        $conflictCount = 0;
        $failedCount = 0;

        foreach ($mutations as $mutation) {
            $mutationId = $mutation['client_mutation_id'] ?? null;
            $actionType = $mutation['action_type'] ?? null;
            $payload = $mutation['payload'] ?? [];

            if (!$mutationId || !$actionType) {
                $results[] = [
                    'client_mutation_id' => $mutationId,
                    'status' => 'failed',
                    'error_code' => 'INVALID_PAYLOAD',
                    'message' => 'Missing mutation ID or action type.',
                ];
                $failedCount++;
                continue;
            }

            // Check if this mutation ID was already processed
            $existing = OfflineSyncLog::where('client_mutation_id', $mutationId)->first();
            if ($existing) {
                $results[] = [
                    'client_mutation_id' => $mutationId,
                    'status' => $existing->status,
                    'error_code' => $existing->error_code,
                    'message' => 'Mutation was already synced previously (idempotent skipped).',
                ];
                if ($existing->status === 'applied') {
                    $appliedCount++;
                } else {
                    $conflictCount++;
                }
                continue;
            }

            try {
                $reconcileResult = $this->applySingleMutation($elderly, $actionType, $payload, $mutationId, $actorId);

                OfflineSyncLog::create([
                    'elderly_id' => $elderly->id,
                    'client_mutation_id' => $mutationId,
                    'action_type' => $actionType,
                    'payload' => $payload,
                    'status' => $reconcileResult['status'],
                    'error_code' => $reconcileResult['error_code'] ?? null,
                    'applied_at' => $reconcileResult['status'] === 'applied' ? Carbon::now() : null,
                ]);

                $results[] = [
                    'client_mutation_id' => $mutationId,
                    'action_type' => $actionType,
                    'status' => $reconcileResult['status'],
                    'error_code' => $reconcileResult['error_code'] ?? null,
                    'message' => $reconcileResult['message'] ?? 'Mutation processed.',
                ];

                if ($reconcileResult['status'] === 'applied') {
                    $appliedCount++;
                } else {
                    $conflictCount++;
                }
            } catch (\Exception $e) {
                Log::error("Offline mutation sync error [{$mutationId}]: " . $e->getMessage());

                OfflineSyncLog::create([
                    'elderly_id' => $elderly->id,
                    'client_mutation_id' => $mutationId,
                    'action_type' => $actionType,
                    'payload' => $payload,
                    'status' => 'failed',
                    'error_code' => 'SERVER_EXCEPTION',
                    'applied_at' => null,
                ]);

                $results[] = [
                    'client_mutation_id' => $mutationId,
                    'action_type' => $actionType,
                    'status' => 'failed',
                    'error_code' => 'SERVER_EXCEPTION',
                    'message' => $e->getMessage(),
                ];

                $failedCount++;
            }
        }

        return [
            'success' => true,
            'elderly_id' => $elderly->id,
            'total_mutations' => count($mutations),
            'applied_count' => $appliedCount,
            'conflict_count' => $conflictCount,
            'failed_count' => $failedCount,
            'results' => $results,
        ];
    }

    /**
     * Apply a single mutation based on its action type.
     */
    protected function applySingleMutation(
        UserProfile $elderly,
        string $actionType,
        array $payload,
        string $mutationId,
        int $actorId
    ): array {
        switch ($actionType) {
            case 'confirm_dose':
                $medicationId = (int) ($payload['medication_id'] ?? 0);
                $timeStr = $payload['scheduled_time'] ?? null;
                $medication = Medication::where('elderly_id', $elderly->id)->where('id', $medicationId)->first();

                if (!$medication || !$timeStr) {
                    return [
                        'status' => 'conflict_skipped',
                        'error_code' => 'MEDICATION_NOT_FOUND',
                        'message' => 'Medication or scheduled time not found on server.',
                    ];
                }

                $doseRes = $this->doseService->confirmDoseByMedicationAndTime(
                    $medication,
                    $elderly->id,
                    $timeStr,
                    'offline_sync',
                    $actorId,
                    $mutationId
                );

                if ($doseRes['success']) {
                    return [
                        'status' => 'applied',
                        'message' => $doseRes['message'],
                    ];
                }

                return [
                    'status' => 'conflict_skipped',
                    'error_code' => $doseRes['error_code'] ?? 'DOSE_CONFIRM_FAILED',
                    'message' => $doseRes['message'] ?? 'Could not confirm dose.',
                ];

            case 'undo_dose':
                $instanceId = (int) ($payload['dose_instance_id'] ?? 0);
                $instance = DoseInstance::where('elderly_id', $elderly->id)->where('id', $instanceId)->first();

                if (!$instance) {
                    return [
                        'status' => 'conflict_skipped',
                        'error_code' => 'INSTANCE_NOT_FOUND',
                        'message' => 'Dose instance not found.',
                    ];
                }

                $undoRes = $this->doseService->undoDose($instance, 'offline_sync', $actorId);

                if ($undoRes['success']) {
                    return [
                        'status' => 'applied',
                        'message' => $undoRes['message'],
                    ];
                }

                return [
                    'status' => 'conflict_skipped',
                    'error_code' => $undoRes['error_code'] ?? 'UNDO_FAILED',
                    'message' => $undoRes['message'] ?? 'Could not undo dose.',
                ];

            case 'record_vital':
                $type = $payload['type'] ?? null;
                if (!$type) {
                    return [
                        'status' => 'failed',
                        'error_code' => 'INVALID_VITAL_TYPE',
                        'message' => 'Missing vital metric type.',
                    ];
                }

                $measuredAt = !empty($payload['measured_at'])
                    ? Carbon::parse($payload['measured_at'])
                    : Carbon::now();

                $metric = HealthMetric::create([
                    'elderly_id' => $elderly->id,
                    'type' => $type,
                    'value' => $payload['value'] ?? null,
                    'value_text' => $payload['value_text'] ?? null,
                    'unit' => $payload['unit'] ?? '',
                    'measured_at' => $measuredAt,
                    'source' => 'offline_sync',
                    'notes' => $payload['notes'] ?? 'Synced from offline cache',
                ]);

                $this->clinicalRulesService->evaluateVitalReading($metric);

                return [
                    'status' => 'applied',
                    'message' => 'Vital metric recorded from offline sync.',
                ];

            case 'daily_checkin':
                $status = $payload['status'] ?? 'ok';
                $notes = $payload['notes'] ?? null;
                $mood = $payload['mood'] ?? null;

                $this->checkinService->recordCheckin($elderly, $status, $notes, $mood, 'offline_sync');

                return [
                    'status' => 'applied',
                    'message' => 'Daily check-in reconciled from offline sync.',
                ];

            default:
                return [
                    'status' => 'failed',
                    'error_code' => 'UNKNOWN_ACTION_TYPE',
                    'message' => "Unsupported action type: {$actionType}",
                ];
        }
    }
}
