<x-dashboard-layout sc>
    <x-slot:title>Notifications - SilverCare</x-slot:title>

    <x-dashboard-nav
        title="Notifications"
        subtitle="Stay updated with your health reminders and activities"
        role="elderly"
        :unread-notifications="$unreadCount"
    />

    <main id="main-content" class="sc-app-main">
        <div class="max-w-4xl mx-auto px-3 sm:px-6 lg:px-12 py-6 space-y-6">
            <x-flash-messages />

            {{-- Back Navigation --}}
            <div class="flex justify-end">
                <a href="{{ route('dashboard') }}" class="sc-btn sc-btn-ghost inline-flex items-center gap-1.5 text-sm">
                    <x-lucide-arrow-left class="sc-i w-4 h-4" aria-hidden="true" />
                    <span>{{ __('Back to Home') }}</span>
                </a>
            </div>

            {{-- Header Section --}}
            <div class="mb-2">
                <div class="flex items-center justify-between mb-1">
                    <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                        <h2 class="text-2xl sm:text-3xl font-bold text-[var(--sc-ink)]">{{ __('Activity & Alerts') }}</h2>
                        @if($unreadCount > 0)
                            <span class="animate-pulse-gentle sc-mark sc-mark-brand sc-num text-sm">
                                <i></i>{{ $unreadCount }} {{ __('New') }}
                            </span>
                        @endif
                    </div>
                </div>
                <p class="text-sm font-medium text-[var(--sc-muted)]">{{ __('Stay updated with your health reminders and activities') }}</p>
            </div>

            {{-- Action Bar --}}
            <div class="sc-card p-4 sm:p-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-3 sc-num text-sm sm:text-base font-semibold text-[var(--sc-ink)]">
                        <span><span id="totalCount">{{ $totalCount }}</span> {{ __('Total') }}</span>
                        <span class="w-1 h-1 rounded-full bg-[var(--sc-border)]" aria-hidden="true"></span>
                        <span class="font-bold text-[var(--sc-ink)]"><span id="unreadCount">{{ $unreadCount }}</span> {{ __('Unread') }}</span>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <button type="button" onclick="markAllAsRead()" class="sc-btn sc-btn-ghost sc-btn-sm inline-flex items-center gap-1.5">
                            <x-lucide-check-check class="sc-i w-4 h-4" aria-hidden="true" />
                            <span>{{ __('Mark All Read') }}</span>
                        </button>
                        <button type="button" onclick="clearAllNotifications()" class="sc-btn sc-btn-ghost sc-btn-sm inline-flex items-center gap-1.5 text-[var(--sc-alert)] hover:bg-[var(--sc-alert-tint)]">
                            <x-lucide-trash-2 class="sc-i w-4 h-4 text-[var(--sc-alert)]" aria-hidden="true" />
                            <span>{{ __('Clear All') }}</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Notifications List --}}
            <div class="space-y-6" id="notificationsList">
                @forelse($groupedNotifications as $dateLabel => $notificationGroup)
                    <div class="space-y-3">
                        {{-- Date Header --}}
                        <div class="flex items-center gap-3 mb-3">
                            <h3 class="text-xs font-bold text-[var(--sc-muted)] uppercase tracking-wider sc-num">{{ $dateLabel }}</h3>
                            <div class="flex-1 h-px bg-[var(--sc-border)]"></div>
                        </div>

                        {{-- Notification Cards --}}
                        <div class="space-y-3">
                            @foreach($notificationGroup as $notification)
                                @php
                                    $type = $notification->type;
                                    $severity = $notification->severity;
                                    $cleanTitle = trim(preg_replace('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', '', $notification->title));
                                    $cleanMessage = trim(preg_replace('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', '', $notification->message));
                                @endphp

                                <div data-notification-card class="sc-card sc-lift p-3.5 sm:p-5 transition-all {{ $notification->is_read ? 'opacity-80' : 'border-l-4 border-l-[var(--sc-brand)]' }}"
                                     data-id="{{ $notification->id }}"
                                     data-read="{{ $notification->is_read ? 'true' : 'false' }}">
                                    <div class="flex items-start gap-3 sm:gap-4">
                                        {{-- Icon Plate --}}
                                        <div class="sc-plate sc-plate-sm flex-shrink-0 mt-0.5">
                                            @if(str_contains($type, 'medication'))
                                                <x-lucide-pill class="sc-i w-5 h-5 text-[var(--sc-brand)]" aria-hidden="true" />
                                            @elseif(str_contains($type, 'task') || str_contains($type, 'checklist'))
                                                <x-lucide-check-square class="sc-i w-5 h-5 text-[var(--sc-brand)]" aria-hidden="true" />
                                            @elseif(str_contains($type, 'health') || str_contains($type, 'vitals'))
                                                <x-lucide-heart-pulse class="sc-i w-5 h-5 text-[var(--sc-brand)]" aria-hidden="true" />
                                            @elseif(str_contains($type, 'reminder'))
                                                <x-lucide-clock class="sc-i w-5 h-5 text-[var(--sc-brand)]" aria-hidden="true" />
                                            @else
                                                <x-lucide-bell class="sc-i w-5 h-5 text-[var(--sc-brand)]" aria-hidden="true" />
                                            @endif
                                        </div>

                                        {{-- Content --}}
                                        <div class="flex-1 min-w-0">
                                            <div class="flex flex-wrap items-baseline justify-between gap-x-2 gap-y-1 mb-1.5">
                                                <h4 class="font-bold text-[var(--sc-ink)] text-base sm:text-lg leading-snug break-words">{{ $cleanTitle }}</h4>
                                                <div class="flex items-center gap-1.5 flex-shrink-0">
                                                    @if(!$notification->is_read)
                                                        <span class="w-2 h-2 rounded-full bg-[var(--sc-brand)] animate-pulse-gentle flex-shrink-0" aria-label="Unread"></span>
                                                    @endif
                                                    <span class="text-xs text-[var(--sc-muted)] font-semibold whitespace-nowrap sc-num">{{ $notification->created_at->diffForHumans() }}</span>
                                                </div>
                                            </div>
                                            <p class="text-sm sm:text-base text-[var(--sc-body)] mb-3 leading-relaxed break-words">{{ $cleanMessage }}</p>

                                            {{-- Severity & Actions Row --}}
                                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pt-2 border-t border-[var(--sc-border-subtle)]">
                                                {{-- Severity --}}
                                                <div>
                                                    @if($severity === 'negative' || $severity === 'high')
                                                        <span class="sc-mark sc-mark-alert"><i></i>{{ __('Urgent') }}</span>
                                                    @elseif($severity === 'warning' || $severity === 'medium')
                                                        <span class="sc-mark sc-mark-warn"><i></i>{{ __('Important') }}</span>
                                                    @elseif($severity === 'positive')
                                                        <span class="sc-mark sc-mark-ok"><i></i>{{ __('Completed') }}</span>
                                                    @elseif($severity === 'reminder')
                                                        <span class="sc-mark sc-mark-brand"><i></i>{{ __('Reminder') }}</span>
                                                    @elseif($severity === 'low')
                                                        <span class="sc-mark sc-mark-subtle"><i></i>{{ __('Low Priority') }}</span>
                                                    @endif
                                                </div>

                                                {{-- Action Buttons --}}
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    @if(!$notification->is_read)
                                                        <button
                                                            type="button"
                                                            data-notification-id="{{ $notification->id }}"
                                                            onclick="markAsRead(this.dataset.notificationId)"
                                                            class="sc-btn sc-btn-ghost sc-btn-sm inline-flex items-center gap-1.5"
                                                        >
                                                            <x-lucide-check class="sc-i w-3.5 h-3.5" aria-hidden="true" />
                                                            <span>{{ __('Mark as Read') }}</span>
                                                        </button>
                                                    @endif
                                                    <button
                                                        type="button"
                                                        data-notification-id="{{ $notification->id }}"
                                                        onclick="deleteNotification(this.dataset.notificationId)"
                                                        class="sc-btn sc-btn-ghost sc-btn-sm inline-flex items-center gap-1.5 text-[var(--sc-alert)] hover:bg-[var(--sc-alert-tint)]"
                                                        aria-label="{{ __('Delete notification') }}: {{ $cleanTitle }}"
                                                    >
                                                        <x-lucide-trash-2 class="sc-i w-3.5 h-3.5 text-[var(--sc-alert)]" aria-hidden="true" />
                                                        <span>{{ __('Delete') }}</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    {{-- Empty State --}}
                    <div class="sc-empty">
                        <div class="sc-plate mb-2">
                            <x-lucide-bell class="sc-i w-6 h-6" aria-hidden="true" />
                        </div>
                        <h3 class="sc-h3">{{ __("You're all caught up!") }}</h3>
                        <p style="color:var(--sc-muted)">{{ __('No new notifications right now. We\'ll let you know when something important comes up.') }}</p>
                        <a href="{{ route('dashboard') }}" class="sc-btn sc-btn-primary mt-3 inline-flex items-center gap-2">
                            <x-lucide-arrow-left class="sc-i w-4 h-4" aria-hidden="true" />
                            <span>{{ __('Back to Dashboard') }}</span>
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

        </div>
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
                    if (notificationEl) {
                        notificationEl.classList.remove('border-l-4', 'border-l-[var(--sc-brand)]');
                        notificationEl.classList.add('opacity-80');
                        notificationEl.dataset.read = 'true';

                        const unreadDot = notificationEl.querySelector('.animate-pulse-gentle');
                        if (unreadDot) unreadDot.remove();

                        const markButton = notificationEl.querySelector('button[onclick*="markAsRead"]');
                        if (markButton) markButton.remove();
                    }

                    updateCounts();
                    if (typeof window.scToast === 'function') {
                        window.scToast('Marked as read', 'success', { elderly: true });
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                if (typeof window.scToast === 'function') {
                    window.scToast('Failed to mark as read', 'error', { elderly: true });
                }
            }
        }

        async function markAllAsRead() {
            let confirmed = true;
            if (typeof window.scConfirm === 'function') {
                confirmed = await window.scConfirm({
                    title: 'Mark all as read?',
                    text: 'All unread notifications will be marked as read.',
                    icon: 'question',
                    confirmButtonText: 'Yes, mark all',
                    cancelButtonText: 'Cancel',
                    elderly: true,
                });
            } else {
                confirmed = confirm('Mark all notifications as read?');
            }

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
                if (typeof window.scToast === 'function') {
                    window.scToast('Failed to mark all as read', 'error', { elderly: true });
                }
            }
        }

        async function deleteNotification(notificationId) {
            let confirmed = true;
            if (typeof window.scConfirm === 'function') {
                confirmed = await window.scConfirm({
                    title: 'Delete this notification?',
                    text: 'This notification will be permanently removed.',
                    icon: 'warning',
                    confirmButtonText: 'Delete notification',
                    cancelButtonText: 'Keep notification',
                    elderly: true,
                });
            } else {
                confirmed = confirm('Delete this notification?');
            }

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
                    if (notificationEl) {
                        notificationEl.style.transform = 'translateX(100%)';
                        notificationEl.style.opacity = '0';

                        setTimeout(() => {
                            notificationEl.remove();
                            updateCounts();
                            checkIfEmpty();
                        }, 300);
                    }

                    if (typeof window.scToast === 'function') {
                        window.scToast('Notification deleted', 'success', { elderly: true });
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                if (typeof window.scToast === 'function') {
                    window.scToast('Failed to delete notification', 'error', { elderly: true });
                }
            }
        }

        async function clearAllNotifications() {
            let confirmed = true;
            if (typeof window.scConfirm === 'function') {
                confirmed = await window.scConfirm({
                    title: 'Clear all notifications?',
                    text: 'This action cannot be undone and all notifications will be removed.',
                    icon: 'warning',
                    confirmButtonText: 'Clear all',
                    cancelButtonText: 'Cancel',
                    elderly: true,
                });
            } else {
                confirmed = confirm('Clear all notifications?');
            }

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
                if (typeof window.scToast === 'function') {
                    window.scToast('Failed to clear notifications', 'error', { elderly: true });
                }
            }
        }

        function updateCounts() {
            const unreadElements = document.querySelectorAll('[data-read="false"]');
            const unreadCount = unreadElements.length;
            const totalCount = document.querySelectorAll('[data-notification-card]').length;

            const unreadCountEl = document.getElementById('unreadCount');
            const totalCountEl = document.getElementById('totalCount');
            if (unreadCountEl) unreadCountEl.textContent = unreadCount;
            if (totalCountEl) totalCountEl.textContent = totalCount;

            const badge = document.querySelector('.animate-pulse-gentle');
            if (badge) {
                if (unreadCount === 0) {
                    badge.remove();
                } else {
                    badge.innerHTML = `<i></i>${unreadCount} New`;
                }
            }
        }

        function checkIfEmpty() {
            const notifications = document.querySelectorAll('[data-notification-card]');
            if (notifications.length === 0) {
                location.reload();
            }
        }

        setInterval(async () => {
            try {
                const response = await fetch('/notifications/unread-count');
                const data = await response.json();

                const unreadCountEl = document.getElementById('unreadCount');
                if (unreadCountEl) {
                    const currentCount = parseInt(unreadCountEl.textContent, 10);
                    if (data.count !== currentCount) {
                        location.reload();
                    }
                }
            } catch (error) {
                console.error('Error fetching unread count:', error);
            }
        }, 30000);
    </script>
    @endpush

</x-dashboard-layout>
