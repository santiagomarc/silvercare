<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesElderlyPatient;
use App\Http\Requests\StoreCaregiverCareMessageRequest;
use App\Http\Requests\StoreElderlyCareMessageRequest;
use App\Models\CareMessage;
use App\Models\UserProfile;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CareMessageController extends Controller
{
    use ResolvesElderlyPatient;

    public function caregiverIndex(Request $request): View|RedirectResponse
    {
        $caregiver = Auth::user()?->profile;
        abort_unless($caregiver && $caregiver->isCaregiver(), 403);

        if (!$this->isMessagingTableReady()) {
            return redirect()->route('caregiver.dashboard')
                ->with('error', 'Messaging is temporarily unavailable. Please run database migrations.');
        }

        $elderlyPatients = $this->caregiverPatients($caregiver);
        $selectedElderly = $this->resolveSelectedPatient($elderlyPatients, $request->integer('elderly'));

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

    public function caregiverStore(StoreCaregiverCareMessageRequest $request, NotificationService $notificationService): RedirectResponse
    {
        $caregiver = Auth::user()?->profile;
        abort_unless($caregiver && $caregiver->isCaregiver(), 403);

        if (!$this->isMessagingTableReady()) {
            return back()->with('error', 'Messaging is temporarily unavailable. Please run database migrations.');
        }

        $validated = $request->validated();

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

    public function elderlyIndex(NotificationService $notificationService): View|RedirectResponse
    {
        $elderly = Auth::user()?->profile;
        abort_unless($elderly && $elderly->isElderly(), 403);

        if (!$this->isMessagingTableReady()) {
            return redirect()->route('dashboard')
                ->with('error', 'Messaging is temporarily unavailable. Please run database migrations.');
        }

        $caregiver = $elderly->caregiver()->with('user')->first();
        $unreadNotifications = $notificationService->getUnreadCount($elderly->id);

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

    public function elderlyStore(StoreElderlyCareMessageRequest $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $elderly = Auth::user()?->profile;
        abort_unless($elderly && $elderly->isElderly(), 403);

        if (!$this->isMessagingTableReady()) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Messaging is temporarily unavailable.'], 503);
            }
            return back()->with('error', 'Messaging is temporarily unavailable. Please run database migrations.');
        }

        $caregiver = $elderly->caregiver;
        if (!$caregiver) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Link a caregiver before sending messages.'], 422);
            }
            return back()->with('error', 'Link a caregiver before sending messages.');
        }

        $validated = $request->validated();

        $message = CareMessage::create([
            'caregiver_id' => $caregiver->id,
            'elderly_id' => $elderly->id,
            'sender_profile_id' => $elderly->id,
            'message' => trim($validated['message']),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message->only('id', 'message', 'created_at'),
            ]);
        }

        return redirect()->route('elderly.messages.index')->with('success', 'Message sent.');
    }

    private function markMessagesAsRead(int $caregiverId, int $elderlyId, int $senderProfileId): void
    {
        if (!$this->isMessagingTableReady()) {
            return;
        }

        CareMessage::where('caregiver_id', $caregiverId)
            ->where('elderly_id', $elderlyId)
            ->where('sender_profile_id', $senderProfileId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    private function isMessagingTableReady(): bool
    {
        return Schema::hasTable('care_messages');
    }
}
