<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Presenters\NotificationPresenter;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct(protected NotificationService $notificationService)
    {
    }

    public function index()
    {
        $this->authorize('viewAny', Notification::class);

        $user = Auth::user();
        $profile = $user->profile;

        // Get notifications for the current user
        $notifications = Notification::where('elderly_id', $profile->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Group notifications by date
        $groupedNotifications = $notifications->groupBy(function($notification) {
            $date = $notification->created_at;
            $today = now()->startOfDay();
            $yesterday = now()->subDay()->startOfDay();

            if ($date->gte($today)) {
                return 'Today';
            } elseif ($date->gte($yesterday)) {
                return 'Yesterday';
            } else {
                return $date->format('F j, Y');
            }
        });

        // Get counts
        $unreadCount = Notification::where('elderly_id', $profile->id)
            ->where('is_read', false)
            ->count();

        $totalCount = Notification::where('elderly_id', $profile->id)->count();

        return view('elderly.notifications.index', compact(
            'notifications',
            'groupedNotifications',
            'unreadCount',
            'totalCount'
        ));
    }

    public function markAsRead(Notification $notification)
    {
        $this->authorize('update', $notification);

        $notification->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    }

    public function markAllAsRead()
    {
        $this->authorize('viewAny', Notification::class);

        $user = Auth::user();
        
        Notification::where('elderly_id', $user->profile->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    }

    public function delete(Notification $notification)
    {
        $this->authorize('delete', $notification);

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted'
        ]);
    }

    public function clearAll()
    {
        $this->authorize('viewAny', Notification::class);

        $user = Auth::user();
        
        Notification::where('elderly_id', $user->profile->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'All notifications cleared'
        ]);
    }

    // API endpoint for real-time updates
    public function getUnreadCount()
    {
        $this->authorize('viewAny', Notification::class);

        $user = Auth::user();
        
        $count = Notification::where('elderly_id', $user->profile->id)
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    // API endpoint to fetch latest notifications
    public function getLatest()
    {
        $this->authorize('viewAny', Notification::class);

        $user = Auth::user();

        $notifications = $this->notificationService->getNotificationsForElderly($user->profile->id, 5);

        return response()->json([
            'notifications' => NotificationPresenter::toElderlyFeed($notifications)->values(),
        ]);
    }
}
