<?php

namespace App\Http\Controllers;

use App\Models\CareMessage;
use App\Models\Notification;
use App\Models\UserProfile;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CareMessageController extends Controller
{
    public function caregiverIndex(Request $request): View
    {
        $caregiver = Auth::user()?->profile;
        abort_unless($caregiver && $caregiver->isCaregiver(), 403);

        [$elderlyPatients, $selectedElderly] = $this->resolveCaregiverSelection(
            $caregiver,
            $request->integer('elderly')
        );

        if (!$selectedElderly) {
            return view('caregiver.messages.index', [
                'elderlyPatients' => $elderlyPatients,
                'selectedElderly' => null,
                'messages' => collect(),
            ]);
        }

        $this->markMessagesAsRead($caregiver->id, $selectedElderly->id, $selectedElderly->id);

        $messages = CareMessage::where('caregiver_id', $caregiver->id)
            ->where('elderly_id', $selectedElderly->id)
            ->with('sender.user')
            ->orderBy('created_at', 'asc')
            ->get();

        return view('caregiver.messages.index', [
            'elderlyPatients' => $elderlyPatients,
            'selectedElderly' => $selectedElderly,
            'messages' => $messages,
        ]);
    }

    public function caregiverStore(Request $request, NotificationService $notificationService): RedirectResponse
    {
        $caregiver = Auth::user()?->profile;
        abort_unless($caregiver && $caregiver->isCaregiver(), 403);

        $validated = $request->validate([
            'elderly_id' => ['required', 'integer', 'exists:user_profiles,id'],
            'message' => ['required', 'string', 'max:1200'],
        ]);

        $elderly = UserProfile::where('id', $validated['elderly_id'])
            ->where('caregiver_id', $caregiver->id)
            ->first();

        if (!$elderly) {
            return back()->with('error', 'Selected patient is not linked to your account.');
        }

        CareMessage::create([
            'caregiver_id' => $caregiver->id,
            'elderly_id' => $elderly->id,
            'sender_profile_id' => $caregiver->id,
            'message' => trim($validated['message']),
        ]);

        $caregiverName = $caregiver->user?->name ?? 'Caregiver';

        $notificationService->createNotification([
            'elderly_id' => $elderly->id,
            'type' => 'caregiver_message',
            'title' => 'New message from your caregiver',
            'message' => trim($validated['message']),
            'severity' => 'reminder',
            'metadata' => [
                'sender_name' => $caregiverName,
                'preview' => mb_substr(trim($validated['message']), 0, 80),
            ],
        ]);

        return redirect()->route('caregiver.messages.index', ['elderly' => $elderly->id])
            ->with('success', 'Message sent.');
    }

    public function elderlyIndex(): View
    {
        $elderly = Auth::user()?->profile;
        abort_unless($elderly && $elderly->isElderly(), 403);

        $caregiver = $elderly->caregiver()->with('user')->first();
        $unreadNotifications = Notification::where('elderly_id', $elderly->id)
            ->where('type', '!=', 'medication_refill_caregiver')
            ->where('is_read', false)
            ->count();

        if (!$caregiver) {
            return view('elderly.messages.index', [
                'caregiver' => null,
                'messages' => collect(),
                'unreadNotifications' => $unreadNotifications,
            ]);
        }

        $this->markMessagesAsRead($caregiver->id, $elderly->id, $caregiver->id);

        $messages = CareMessage::where('caregiver_id', $caregiver->id)
            ->where('elderly_id', $elderly->id)
            ->with('sender.user')
            ->orderBy('created_at', 'asc')
            ->get();

        return view('elderly.messages.index', [
            'caregiver' => $caregiver,
            'messages' => $messages,
            'unreadNotifications' => $unreadNotifications,
        ]);
    }

    public function elderlyStore(Request $request): RedirectResponse
    {
        $elderly = Auth::user()?->profile;
        abort_unless($elderly && $elderly->isElderly(), 403);

        $caregiver = $elderly->caregiver;
        if (!$caregiver) {
            return back()->with('error', 'Link a caregiver before sending messages.');
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1200'],
        ]);

        CareMessage::create([
            'caregiver_id' => $caregiver->id,
            'elderly_id' => $elderly->id,
            'sender_profile_id' => $elderly->id,
            'message' => trim($validated['message']),
        ]);

        return redirect()->route('elderly.messages.index')->with('success', 'Message sent.');
    }

    /**
     * @return array{Collection<int, UserProfile>, UserProfile|null}
     */
    private function resolveCaregiverSelection(UserProfile $caregiver, ?int $selectedElderlyId): array
    {
        $elderlyPatients = $caregiver->elderlyPatients()
            ->with('user')
            ->orderBy('id')
            ->get();

        if ($elderlyPatients->isEmpty()) {
            return [$elderlyPatients, null];
        }

        $selectedElderly = $selectedElderlyId
            ? $elderlyPatients->firstWhere('id', $selectedElderlyId)
            : null;

        return [$elderlyPatients, $selectedElderly ?? $elderlyPatients->first()];
    }

    private function markMessagesAsRead(int $caregiverId, int $elderlyId, int $senderProfileId): void
    {
        CareMessage::where('caregiver_id', $caregiverId)
            ->where('elderly_id', $elderlyId)
            ->where('sender_profile_id', $senderProfileId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
