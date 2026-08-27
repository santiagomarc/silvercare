<?php

namespace App\Http\Controllers;

use App\Models\CareCheckin;
use App\Models\UserProfile;
use App\Services\CareCheckinService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CareCheckinController extends Controller
{
    public function __construct(
        protected CareCheckinService $checkinService,
    ) {
    }

    /**
     * Senior records their daily check-in (e.g. "I'm OK" button).
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile || !$profile->isElderly()) {
            return response()->json([
                'success' => false,
                'message' => 'Only elderly patients can record check-ins.',
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'nullable|in:ok,need_help',
            'notes' => 'nullable|string|max:500',
            'mood' => 'nullable|string|max:50',
            'source' => 'nullable|string|max:50',
        ]);

        $status = $validated['status'] ?? 'ok';
        $notes = $validated['notes'] ?? null;
        $mood = $validated['mood'] ?? null;
        $source = $validated['source'] ?? 'web_button';

        $checkin = $this->checkinService->recordCheckin($profile, $status, $notes, $mood, $source);

        $msg = ($status === 'need_help')
            ? 'Help request sent to your caregiver.'
            : 'You are checked in for today! Great job.';

        return response()->json([
            'success' => true,
            'message' => $msg,
            'checkin' => [
                'id' => $checkin->id,
                'checkin_date' => $checkin->checkin_date->toDateString(),
                'status' => $checkin->status,
                'notes' => $checkin->notes,
                'mood' => $checkin->mood,
                'checked_in_at' => $checkin->checked_in_at?->toISOString(),
            ],
        ]);
    }

    /**
     * Caregiver views patient's check-in history.
     */
    public function history(Request $request, UserProfile $patient): JsonResponse
    {
        $caregiver = Auth::user()->profile;

        if (!$caregiver || !$caregiver->isCaregiver() || $patient->caregiver_id !== $caregiver->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $days = (int) $request->query('days', 30);
        $startDate = Carbon::today()->subDays($days)->toDateString();

        $checkins = CareCheckin::where('elderly_id', $patient->id)
            ->where('checkin_date', '>=', $startDate)
            ->orderBy('checkin_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'patient_id' => $patient->id,
            'checkins' => $checkins,
        ]);
    }
}
