<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Notifications - SilverCare</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/icons/silvercare.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/icons/silvercare.png') }}">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Montserrat', sans-serif; }
        
        .notification-item {
            transition: all 0.3s ease;
        }
        .notification-item:hover {
            transform: translateX(4px);
        }
        .notification-unread {
            background: linear-gradient(to right, rgba(59, 130, 246, 0.05), transparent);
            border-left: 4px solid #3B82F6;
        }
        .notification-read {
            opacity: 0.7;
        }
        .badge-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .slide-in {
            animation: slideIn 0.3s ease-out forwards;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="bg-[#EBEBEB] min-h-screen">

    <!-- NAV BAR -->
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-12 h-20 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 hover:opacity-80 transition-opacity">
                    <img src="{{ asset('assets/icons/silvercare.png') }}" alt="SilverCare" class="w-10 h-10 object-contain">
                    <h1 class="text-2xl font-[900] tracking-tight text-gray-900">SILVER<span class="text-[#000080]">CARE</span></h1>
                </a>
            </div>
            
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-[#000080] font-[900] text-lg overflow-hidden">
                        @if(Auth::user()->profile && Auth::user()->profile->profile_photo)
                            <img src="{{ Storage::url(Auth::user()->profile->profile_photo) }}" alt="{{ Auth::user()->name }}" class="w-full h-full object-cover">
                        @else
                            {{ substr(Auth::user()->name, 0, 1) }}
                        @endif
                    </div>
                    <div class="hidden sm:block">
                        <p class="text-sm font-bold text-gray-900 leading-tight">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-gray-500 font-medium">Patient</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="ml-2">
                    @csrf
                    <button type="submit" class="flex items-center gap-2 px-4 py-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl font-bold text-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        <span class="hidden sm:inline">Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-12 py-8">
        
        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-3">
                    <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                    <h2 class="text-3xl font-[900] text-gray-900">Notifications</h2>
                    @if($unreadCount > 0)
                        <span class="badge-pulse inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-blue-500 text-white">
                            {{ $unreadCount }} New
                        </span>
                    @endif
                </div>
            </div>
            <p class="text-gray-500 text-sm">Stay updated with your health reminders and activities</p>
        </div>

        <!-- Action Bar -->
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 mb-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="text-sm font-bold text-gray-700">
                        <span id="totalCount">{{ $totalCount }}</span> Total Notifications
                    </span>
                    <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                    <span class="text-sm font-bold text-blue-600">
                        <span id="unreadCount">{{ $unreadCount }}</span> Unread
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="markAllAsRead()" class="px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-xl font-bold text-sm transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Mark All Read
                    </button>
                    <button onclick="clearAllNotifications()" class="px-4 py-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl font-bold text-sm transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Clear All
                    </button>
                </div>
            </div>
        </div>

        <!-- Notifications List -->
        <div class="space-y-6" id="notificationsList">
            @forelse($groupedNotifications as $dateLabel => $notificationGroup)
                <div class="slide-in">
                    <!-- Date Header -->
                    <div class="flex items-center gap-3 mb-4">
                        <h3 class="text-sm font-[800] text-gray-900 uppercase tracking-wide">{{ $dateLabel }}</h3>
                        <div class="flex-1 h-px bg-gray-200"></div>
                    </div>

                    <!-- Notification Cards -->
                    <div class="space-y-3">
                        @foreach($notificationGroup as $notification)
                            <div class="notification-item {{ $notification->is_read ? 'notification-read' : 'notification-unread' }} bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md" 
                                 data-id="{{ $notification->id }}"
                                 data-read="{{ $notification->is_read ? 'true' : 'false' }}">
                                <div class="flex items-start gap-4">
                                    <!-- Icon -->
                                    @php
                                        $iconClass = 'bg-gray-100 text-gray-600';
                                        $type = $notification->type;
                                        $severity = $notification->severity;
                                        
                                        if (str_contains($type, 'medication')) {
                                            $iconClass = $severity === 'positive' ? 'bg-green-100 text-green-600' : 
                                                        ($severity === 'warning' || $severity === 'negative' ? 'bg-amber-100 text-amber-600' : 'bg-green-100 text-green-600');
                                        } elseif (str_contains($type, 'task') || str_contains($type, 'checklist')) {
                                            $iconClass = $severity === 'positive' ? 'bg-blue-100 text-blue-600' : 'bg-blue-100 text-blue-600';
                                        } elseif (str_contains($type, 'health') || str_contains($type, 'vitals')) {
                                            $iconClass = 'bg-rose-100 text-rose-600';
                                        } elseif (str_contains($type, 'reminder')) {
                                            $iconClass = 'bg-purple-100 text-purple-600';
                                        } elseif ($severity === 'negative') {
                                            $iconClass = 'bg-red-100 text-red-600';
                                        } elseif ($severity === 'warning') {
                                            $iconClass = 'bg-amber-100 text-amber-600';
                                        } elseif ($severity === 'positive') {
                                            $iconClass = 'bg-green-100 text-green-600';
                                        }
                                    @endphp
                                    <div class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center {{ $iconClass }}">
                                        @if(str_contains($notification->type, 'medication'))
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                                        @elseif(str_contains($notification->type, 'task') || str_contains($notification->type, 'checklist'))
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                        @elseif(str_contains($notification->type, 'health') || str_contains($notification->type, 'vitals'))
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                                        @elseif(str_contains($notification->type, 'reminder'))
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        @else
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                                        @endif
                                    </div>

                                    <!-- Content -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between gap-3 mb-1">
                                            <h4 class="font-[800] text-gray-900 text-base">{{ $notification->title }}</h4>
                                            <div class="flex items-center gap-2 flex-shrink-0">
                                                @if(!$notification->is_read)
                                                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                                @endif
                                                <span class="text-xs text-gray-500 font-medium">{{ $notification->created_at->diffForHumans() }}</span>
                                            </div>
                                        </div>
                                        <p class="text-sm text-gray-600 mb-3">{{ $notification->message }}</p>

                                        <!-- Priority Badge -->
                                        @if($notification->severity)
                                            <div class="flex items-center gap-2 mb-3">
                                                @if($notification->severity === 'negative')
                                                    <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-bold bg-red-100 text-red-700">⚠️ Urgent</span>
                                                @elseif($notification->severity === 'warning')
                                                    <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-bold bg-amber-100 text-amber-700">⚡ Important</span>
                                                @elseif($notification->severity === 'positive')
                                                    <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-bold bg-green-100 text-green-700">✓ Completed</span>
                                                @elseif($notification->severity === 'reminder')
                                                    <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-bold bg-blue-100 text-blue-700">🔔 Reminder</span>
                                                @elseif($notification->severity === 'high')
                                                    <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-bold bg-red-100 text-red-700">🔴 High Priority</span>
                                                @elseif($notification->severity === 'medium')
                                                    <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-bold bg-yellow-100 text-yellow-700">🟡 Medium Priority</span>
                                                @elseif($notification->severity === 'low')
                                                    <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-bold bg-gray-100 text-gray-600">🟢 Low Priority</span>
                                                @endif
                                            </div>
                                        @endif

                                        <!-- Actions -->
                                        <div class="flex items-center gap-2">
                                            @if(!$notification->is_read)
                                                <button onclick="markAsRead({{ $notification->id }})" class="text-xs font-bold text-blue-600 hover:text-blue-700 flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                    Mark as Read
                                                </button>
                                            @endif
                                            <button onclick="deleteNotification({{ $notification->id }})" class="text-xs font-bold text-red-600 hover:text-red-700 flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <!-- Empty State -->
                <div class="text-center py-20">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-[900] text-gray-900 mb-2">No Notifications</h3>
                    <p class="text-gray-500 mb-6">You're all caught up! We'll notify you when something important happens.</p>
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-[#000080] text-white font-bold rounded-xl hover:bg-blue-900 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        Back to Dashboard
                    </a>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($notifications->hasPages())
            <div class="mt-8">
                {{ $notifications->links() }}
            </div>
        @endif

    </main>

    <!-- JavaScript -->
    <script>
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

        // Mark single notification as read
        async function markAsRead(notificationId) {
            try {
                const response = await fetch(`/notifications/${notificationId}/read`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();
                
                if (data.success) {
                    const notificationEl = document.querySelector(`[data-id="${notificationId}"]`);
                    notificationEl.classList.remove('notification-unread');
                    notificationEl.classList.add('notification-read');
                    notificationEl.dataset.read = 'true';
                    
                    // Remove unread indicator
                    const unreadDot = notificationEl.querySelector('.bg-blue-500');
                    if (unreadDot) unreadDot.remove();
                    
                    // Remove mark as read button
                    const markButton = notificationEl.querySelector('button[onclick*="markAsRead"]');
                    if (markButton) markButton.remove();
                    
                    updateCounts();
                    showToast('✓ Marked as read', 'success');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('❌ Failed to mark as read', 'error');
            }
        }

        // Mark all as read
        async function markAllAsRead() {
            if (!confirm('Mark all notifications as read?')) return;

            try {
                const response = await fetch('/notifications/mark-all-read', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();
                
                if (data.success) {
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('❌ Failed to mark all as read', 'error');
            }
        }

        // Delete notification
        async function deleteNotification(notificationId) {
            if (!confirm('Delete this notification?')) return;

            try {
                const response = await fetch(`/notifications/${notificationId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();
                
                if (data.success) {
                    const notificationEl = document.querySelector(`[data-id="${notificationId}"]`);
                    notificationEl.style.transform = 'translateX(100%)';
                    notificationEl.style.opacity = '0';
                    
                    setTimeout(() => {
                        notificationEl.remove();
                        updateCounts();
                        checkIfEmpty();
                    }, 300);
                    
                    showToast('✓ Notification deleted', 'success');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('❌ Failed to delete notification', 'error');
            }
        }

        // Clear all notifications
        async function clearAllNotifications() {
            if (!confirm('Clear all notifications? This action cannot be undone.')) return;

            try {
                const response = await fetch('/notifications/clear-all', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();
                
                if (data.success) {
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('❌ Failed to clear notifications', 'error');
            }
        }

        // Update counts
        function updateCounts() {
            const unreadElements = document.querySelectorAll('[data-read="false"]');
            const unreadCount = unreadElements.length;
            const totalCount = document.querySelectorAll('.notification-item').length;
            
            document.getElementById('unreadCount').textContent = unreadCount;
            document.getElementById('totalCount').textContent = totalCount;
            
            // Update badge
            const badge = document.querySelector('.badge-pulse');
            if (badge) {
                if (unreadCount === 0) {
                    badge.remove();
                } else {
                    badge.textContent = `${unreadCount} New`;
                }
            }
        }

        // Check if list is empty
        function checkIfEmpty() {
            const notifications = document.querySelectorAll('.notification-item');
            if (notifications.length === 0) {
                location.reload();
            }
        }

        // Toast notification
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                info: 'bg-blue-500'
            };
            
            toast.className = `fixed bottom-6 right-6 ${colors[type]} text-white px-6 py-3 rounded-xl shadow-lg font-bold text-sm z-50 transform translate-y-20 opacity-0 transition-all duration-300`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.transform = 'translateY(0)';
                toast.style.opacity = '1';
            }, 10);
            
            setTimeout(() => {
                toast.style.transform = 'translateY(20px)';
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Auto-refresh unread count every 30 seconds
        setInterval(async () => {
            try {
                const response = await fetch('/notifications/unread-count');
                const data = await response.json();
                
                const currentCount = parseInt(document.getElementById('unreadCount').textContent);
                if (data.count !== currentCount) {
                    // New notifications arrived, reload page
                    location.reload();
                }
            } catch (error) {
                console.error('Error fetching unread count:', error);
            }
        }, 30000); // Check every 30 seconds
    </script>

</body>
</html>
