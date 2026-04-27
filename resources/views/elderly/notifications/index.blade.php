<x-dashboard-layout>
    <x-slot:title>Notifications - SilverCare</x-slot:title>

    <x-dashboard-nav
        title="Notifications"
        subtitle="Stay updated with your health reminders and activities"
        role="elderly"
        :unread-notifications="$unreadCount"
    />

    <main id="main-content" class="max-w-[1000px] mx-auto px-4 sm:px-6 lg:px-12 py-8">

        {{-- Back Navigation --}}
        <div class="mb-6">
            <a href="{{ route('dashboard') }}" class="back-nav-pill">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Back to Home
            </a>
        </div>

        {{-- Header Section --}}
        <div class="mb-8">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-3">
                    <h2 class="text-3xl font-black text-slate-900">Notifications</h2>
                    @if($unreadCount > 0)
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-bold bg-navy-100 text-navy-600 animate-pulse-gentle">
                            {{ $unreadCount }} New
                        </span>
                    @endif
                </div>
            </div>
            <p class="text-base text-slate-500 font-medium">Stay updated with your health reminders and activities</p>
        </div>

        {{-- Action Bar --}}
        <div class="bg-white rounded-2xl p-4 shadow-card mb-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="text-base font-bold text-slate-700">
                        <span id="totalCount">{{ $totalCount }}</span> Total
                    </span>
                    <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                    <span class="text-base font-bold text-navy-500">
                        <span id="unreadCount">{{ $unreadCount }}</span> Unread
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="markAllAsRead()" class="notif-action-btn bg-navy-50 hover:bg-navy-100 text-navy-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Mark All Read
                    </button>
                    <button onclick="clearAllNotifications()" class="notif-action-btn bg-rose-50 hover:bg-rose-100 text-rose-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Clear All
                    </button>
                </div>
            </div>
        </div>

        {{-- Notifications List --}}
        <div class="space-y-6" id="notificationsList">
            @forelse($groupedNotifications as $dateLabel => $notificationGroup)
                <div class="animate-fade-in">
                    {{-- Date Header --}}
                    <div class="flex items-center gap-3 mb-4">
                        <h3 class="text-sm font-extrabold text-slate-500 uppercase tracking-wider">{{ $dateLabel }}</h3>
                        <div class="flex-1 h-px bg-slate-100"></div>
                    </div>

                    {{-- Notification Cards --}}
                    <div class="space-y-3">
                        @foreach($notificationGroup as $notification)
                            @php
                                $type = $notification->type;
                                $severity = $notification->severity;

                                // Determine icon avatar colors (circular, soft-tinted)
                                if (str_contains($type, 'medication')) {
                                    $avatarClass = 'bg-indigo-50 text-indigo-500';
                                } elseif (str_contains($type, 'task') || str_contains($type, 'checklist')) {
                                    $avatarClass = 'bg-sky-50 text-sky-500';
                                } elseif (str_contains($type, 'health') || str_contains($type, 'vitals')) {
                                    $avatarClass = 'bg-rose-50 text-rose-500';
                                } elseif (str_contains($type, 'reminder')) {
                                    $avatarClass = 'bg-violet-50 text-violet-500';
                                } elseif ($severity === 'negative' || $severity === 'high') {
                                    $avatarClass = 'bg-red-50 text-red-500';
                                } elseif ($severity === 'warning' || $severity === 'medium') {
                                    $avatarClass = 'bg-amber-50 text-amber-500';
                                } elseif ($severity === 'positive') {
                                    $avatarClass = 'bg-emerald-50 text-emerald-500';
                                } else {
                                    $avatarClass = 'bg-amber-50 text-amber-500';
                                }
                            @endphp

                            <div class="notif-card {{ $notification->is_read ? 'notif-card-read' : 'notif-card-unread' }}"
                                 data-id="{{ $notification->id }}"
                                 data-read="{{ $notification->is_read ? 'true' : 'false' }}">
                                <div class="flex items-start gap-4">
                                    {{-- Circular Icon Avatar --}}
                                    <div class="notif-avatar {{ $avatarClass }}">
                                        @if(str_contains($type, 'medication'))
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                                        @elseif(str_contains($type, 'task') || str_contains($type, 'checklist'))
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                        @elseif(str_contains($type, 'health') || str_contains($type, 'vitals'))
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                                        @elseif(str_contains($type, 'reminder'))
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        @else
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                                        @endif
                                    </div>

                                    {{-- Content --}}
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between gap-3 mb-1">
                                            <h4 class="font-extrabold text-slate-800 text-lg leading-snug">{{ $notification->title }}</h4>
                                            <div class="flex items-center gap-2 flex-shrink-0">
                                                @if(!$notification->is_read)
                                                    <span class="w-2.5 h-2.5 rounded-full bg-navy-500 animate-pulse-gentle" aria-label="Unread"></span>
                                                @endif
                                                <span class="text-sm text-slate-400 font-semibold whitespace-nowrap">{{ $notification->created_at->diffForHumans() }}</span>
                                            </div>
                                        </div>
                                        <p class="text-base text-slate-600 mb-3 leading-relaxed">{{ $notification->message }}</p>

                                        {{-- Severity Badge --}}
                                        @if($notification->severity)
                                            <div class="flex items-center gap-2 mb-3">
                                                @if($notification->severity === 'negative' || $notification->severity === 'high')
                                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-bold bg-rose-50 text-rose-700 border border-rose-100">
                                                        <x-lucide-triangle-alert class="w-4 h-4" aria-hidden="true" />
                                                        Urgent
                                                    </span>
                                                @elseif($notification->severity === 'warning' || $notification->severity === 'medium')
                                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-bold bg-amber-50 text-amber-700 border border-amber-100">
                                                        <x-lucide-bolt class="w-4 h-4" aria-hidden="true" />
                                                        Important
                                                    </span>
                                                @elseif($notification->severity === 'positive')
                                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                                        <x-lucide-check-circle class="w-4 h-4" aria-hidden="true" />
                                                        Completed
                                                    </span>
                                                @elseif($notification->severity === 'reminder')
                                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-bold bg-violet-50 text-violet-700 border border-violet-100">
                                                        <x-lucide-bell class="w-4 h-4" aria-hidden="true" />
                                                        Reminder
                                                    </span>
                                                @elseif($notification->severity === 'low')
                                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-bold bg-slate-50 text-slate-600 border border-slate-100">
                                                        <x-lucide-circle class="w-4 h-4 text-emerald-500" aria-hidden="true" />
                                                        Low Priority
                                                    </span>
                                                @endif
                                            </div>
                                        @endif

                                        {{-- Actions --}}
                                        <div class="flex items-center gap-2">
                                            @if(!$notification->is_read)
                                                <button data-notification-id="{{ $notification->id }}" onclick="markAsRead(this.dataset.notificationId)" class="notif-action-btn bg-navy-50 hover:bg-navy-100 text-navy-600">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                    Mark as Read
                                                </button>
                                            @endif
                                            <button data-notification-id="{{ $notification->id }}" onclick="deleteNotification(this.dataset.notificationId)" class="notif-action-btn bg-rose-50 hover:bg-rose-100 text-rose-600">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
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
                {{-- Premium Empty State --}}
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                        <h3 class="inline-flex items-center gap-2">
                            <span>You're all caught up!</span>
                            <x-lucide-leaf class="w-5 h-5 text-emerald-600" aria-hidden="true" />
                        </h3>
                    <p>No new notifications right now. We'll let you know when something important comes up.</p>
                    <a href="{{ route('dashboard') }}" class="mt-6 inline-flex items-center gap-2 px-6 py-3 bg-navy-500 text-white font-bold rounded-xl hover:bg-navy-600 transition-colors min-h-touch shadow-glow-brand">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        Back to Dashboard
                    </a>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($notifications->hasPages())
            <div class="mt-8">
                {{ $notifications->links() }}
            </div>
        @endif

    </main>

    @push('scripts')
    <script>
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

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
                    notificationEl.classList.remove('notif-card-unread');
                    notificationEl.classList.add('notif-card-read');
                    notificationEl.dataset.read = 'true';

                    const unreadDot = notificationEl.querySelector('.animate-pulse-gentle');
                    if (unreadDot) unreadDot.remove();

                    const markButton = notificationEl.querySelector('button[onclick*="markAsRead"]');
                    if (markButton) markButton.remove();

                    updateCounts();
                    window.scToast('Marked as read', 'success', { elderly: true });
                }
            } catch (error) {
                console.error('Error:', error);
                window.scToast('Failed to mark as read', 'error', { elderly: true });
            }
        }

        async function markAllAsRead() {
            const confirmed = await window.scConfirm({
                title: 'Mark all as read?',
                text: 'All unread notifications will be marked as read.',
                icon: 'question',
                confirmButtonText: 'Yes, mark all',
                cancelButtonText: 'Cancel',
                elderly: true,
            });

            if (!confirmed) return;

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
                window.scToast('Failed to mark all as read', 'error', { elderly: true });
            }
        }

        async function deleteNotification(notificationId) {
            const confirmed = await window.scConfirm({
                title: 'Delete this notification?',
                text: 'This notification will be permanently removed.',
                icon: 'warning',
                confirmButtonText: 'Delete notification',
                cancelButtonText: 'Keep notification',
                elderly: true,
            });

            if (!confirmed) return;

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

                    window.scToast('Notification deleted', 'success', { elderly: true });
                }
            } catch (error) {
                console.error('Error:', error);
                window.scToast('Failed to delete notification', 'error', { elderly: true });
            }
        }

        async function clearAllNotifications() {
            const confirmed = await window.scConfirm({
                title: 'Clear all notifications?',
                text: 'This action cannot be undone and all notifications will be removed.',
                icon: 'warning',
                confirmButtonText: 'Clear all',
                cancelButtonText: 'Cancel',
                elderly: true,
            });

            if (!confirmed) return;

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
                window.scToast('Failed to clear notifications', 'error', { elderly: true });
            }
        }

        function updateCounts() {
            const unreadElements = document.querySelectorAll('[data-read="false"]');
            const unreadCount = unreadElements.length;
            const totalCount = document.querySelectorAll('.notif-card').length;

            document.getElementById('unreadCount').textContent = unreadCount;
            document.getElementById('totalCount').textContent = totalCount;

            const badge = document.querySelector('.animate-pulse-gentle');
            if (badge && badge.closest('.mb-8')) {
                if (unreadCount === 0) {
                    badge.remove();
                } else {
                    badge.textContent = `${unreadCount} New`;
                }
            }
        }

        function checkIfEmpty() {
            const notifications = document.querySelectorAll('.notif-card');
            if (notifications.length === 0) {
                location.reload();
            }
        }

        setInterval(async () => {
            try {
                const response = await fetch('/notifications/unread-count');
                const data = await response.json();

                const currentCount = parseInt(document.getElementById('unreadCount').textContent, 10);
                if (data.count !== currentCount) {
                    location.reload();
                }
            } catch (error) {
                console.error('Error fetching unread count:', error);
            }
        }, 30000);
    </script>
    @endpush

</x-dashboard-layout>
