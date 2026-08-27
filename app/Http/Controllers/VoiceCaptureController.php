<?php

namespace App\Http\Controllers;

use App\Models\HealthMetric;
use App\Services\CareCheckinService;
use App\Services\ClinicalRulesService;
use App\Services\DoseAdministrationService;
use App\Services\VoiceVitalParserService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoiceCaptureController extends Controller
{
    public function __construct(
        protected VoiceVitalParserService $parserService,
        protected ClinicalRulesService $clinicalRulesService,
        protected DoseAdministrationService $doseService,
        protected CareCheckinService $checkinService,
    ) {
    }

    /**
     * Parse raw voice speech transcript into structured candidate entities.
     */
    public function parse(Request $request): JsonResponse
    {
        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile || !$profile->isElderly()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'transcript' => 'required|string|max:1000',
        ]);

        $result = $this->parserService->parse($validated['transcript'], $profile);

        return response()->json([
            'success' => true,
            'parsed' => $result,
        ]);
    }

    /**
     * Confirm candidate parsed voice entity and commit to database.
     */
    public function confirm(Request $request): JsonResponse
    {
        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile || !$profile->isElderly()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'intent' => 'required|string|in:vital_reading,medication_taken,checkin',
            'payload' => 'required|array',
        ]);

        $intent = $validated['intent'];
        $payload = $validated['payload'];

        if ($intent === 'vital_reading') {
            $type = $payload['type'];
            $metric = HealthMetric::create([
                'elderly_id' => $profile->id,
                'type' => $type,
                'value' => $payload['value'] ?? null,
                'value_text' => $payload['value_text'] ?? null,
                'unit' => $payload['unit'] ?? '',
                'measured_at' => Carbon::now(),
                'source' => 'voice_capture',
                'notes' => 'Recorded via senior voice assistant',
            ]);

            $alert = $this->clinicalRulesService->evaluateVitalReading($metric);

            return response()->json([
                'success' => true,
                'type' => 'vital',
                'message' => 'Vital reading recorded successfully from voice command.',
                'metric' => $metric,
                'alert_triggered' => ($alert !== null),
            ]);
        }

        if ($intent === 'medication_taken') {
            $medicationId = (int) $payload['medication_id'];
            $time = Carbon::now($profile->timezone ?? 'Asia/Manila')->format('H:i:s');

            $result = $this->doseService->confirmDoseByMedicationAndTime(
                $profile,
                $medicationId,
                $time,
                'voice_capture',
                $user->id
            );

            return response()->json($result);
        }

        if ($intent === 'checkin') {
            $status = $payload['status'] ?? 'ok';
            $notes = $payload['notes'] ?? null;
            $checkin = $this->checkinService->recordCheckin($profile, $status, $notes, null, 'voice');

            return response()->json([
                'success' => true,
                'type' => 'checkin',
                'message' => 'Daily check-in recorded successfully via voice.',
                'checkin' => $checkin,
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Invalid intent.'], 422);
    }
}
