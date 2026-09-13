{{-- ============================================================
     CAREGIVER DASHBOARD
     ============================================================
     Sarah's screen answers "is he alright?" — so the order is:
     exceptions first (alerts, then the risk briefing), then who we
     are looking at and how today is going, then the detail a doctor
     would want (vitals, mood, activity), and the management links last.

     Same components as the senior dashboard, higher density. Semantic
     colour appears at icon scale (sc-mark, sc-plate) — the only tinted
     fills on the page are the severity chips on open alerts.
     ============================================================ --}}

<x-dashboard-layout sc>
    <x-slot:title>Caregiver Dashboard - SilverCare</x-slot:title>
    <x-slot:bodyClass>sc-page min-h-screen</x-slot:bodyClass>

    @php
        $dashboardNow = now()->timezone(config('app.timezone', 'Asia/Manila'));
        $patientName = $elderlyUser->name ?? $elderly?->username ?? null;
        $navSubtitle = $dashboardNow->format('l, j F Y') . ($patientName ? ' · Viewing ' . $patientName : '');
    @endphp

    {{-- The app bar owns the page's only <h1>; everything below starts at <h2>. --}}
    <x-dashboard-nav
        title="Caregiver dashboard"
        :subtitle="$navSubtitle"
        role="caregiver"
    />

    <main id="main-content"
          class="sc-ambient sc-stack relative max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-10 pt-5 pb-12">

        <x-flash-messages />

        @if(!$elderly)
            {{-- No patient yet. An empty screen is an invitation: say what goes
                 here and give the one button that puts it there. --}}
            <section class="sc-empty" aria-labelledby="no-patient-title">
                <span class="sc-plate">
                    <x-lucide-link class="sc-i w-6 h-6" aria-hidden="true" />
                </span>
                <h2 id="no-patient-title" class="sc-h3">No patient linked yet</h2>
                <p>Generate a linking PIN and share it with your patient. They can scan the QR code or enter the PIN on their dashboard to link instantly.</p>
                <a href="{{ route('profile.edit') }}" class="sc-btn sc-btn-primary">
                    Go to profile to generate a PIN
                </a>
            </section>

        @else

        @if(($elderlyPatients ?? collect())->count() > 1)
            <div class="sc-card-quiet px-5 py-4">
                <form method="GET" action="{{ route('caregiver.dashboard') }}" class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <label for="elderly" class="sc-label mb-0">Viewing patient</label>
                    <select
                        id="elderly"
                        name="elderly"
                        onchange="this.form.submit()"
                        class="sc-select sm:max-w-xs"
                    >
                        @foreach(($elderlyPatients ?? collect()) as $patient)
                            <option value="{{ $patient->id }}" @selected(($selectedElderlyId ?? null) === $patient->id)>
                                {{ $patient->user?->name ?? ('Patient #' . $patient->id) }}
                            </option>
                        @endforeach
                    </select>
                    <span class="sc-mark sc-mark-brand sm:ml-auto"><i></i><span class="sc-num">{{ ($elderlyPatients ?? collect())->count() }}</span>&nbsp;linked patients</span>
                </form>
            </div>
        @endif

        {{-- Urgent alert notifications (H8). Push is the only channel that
             reaches a caregiver whose phone is locked and whose email is unread. --}}
        <div x-data="pushToggle()" x-show="supported" x-cloak>
            <div class="sc-card px-5 py-4 flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="flex items-start gap-3 flex-1">
                    <span class="sc-plate sc-plate-sm">
                        <x-lucide-bell-ring class="sc-i w-5 h-5" aria-hidden="true" />
                    </span>
                    <div>
                        <p class="font-semibold" style="color: var(--sc-ink)">Urgent alert notifications</p>
                        <p class="text-sm mt-0.5" style="color: var(--sc-muted)">
                            <span x-show="enabled">On for this device — critical and emergency alerts will reach you even when SilverCare is closed.</span>
                            <span x-show="canEnable">Get critical and emergency alerts on this device, even when SilverCare is closed.</span>
                            <span x-show="blocked">Blocked in your browser settings. Allow notifications for this site to turn them back on.</span>
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    x-on:click="toggle()"
                    x-bind:disabled="busy"
                    aria-pressed="false"
                    x-bind:aria-pressed="enabled ? 'true' : 'false'"
                    class="sc-btn sc-btn-ghost shrink-0"
                >
                    <span x-show="busy">Working…</span>
                    <span x-show="showTurnOff">Turn off</span>
                    <span x-show="showTurnOn">Turn on</span>
                </button>
            </div>
        </div>

        @if(($activeAlerts ?? collect())->isNotEmpty())
            {{-- Exceptions first. The severity chip is the one tinted fill this
                 page allows itself: it marks the single most urgent thing. --}}
            <section id="clinical-alert-center" class="sc-stack-sm" aria-labelledby="alerts-title">
                <h2 id="alerts-title" class="sc-h3 flex items-center gap-2">
                    Active alerts
                    <span class="sc-badge sc-num">{{ $activeAlerts->count() }}</span>
                </h2>

                @foreach($activeAlerts as $alert)
                    @php
                        $alertTone = in_array($alert->severity, ['emergency', 'critical']) ? 'alert' : 'warn';
                        $alertIcon = match($alert->severity) {
                            'emergency' => 'siren',
                            'critical'  => 'triangle-alert',
                            default     => 'bell',
                        };
                    @endphp
                    <div class="sc-card p-5" id="alert-card-{{ $alert->id }}">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div class="flex items-start gap-3.5 min-w-0">
                                <span class="sc-plate sc-plate-sm sc-plate-{{ $alertTone }}">
                                    <x-dynamic-component :component="'lucide-' . $alertIcon" class="sc-i w-5 h-5" aria-hidden="true" />
                                </span>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap" data-alert-badges>
                                        <span class="sc-chip sc-chip-{{ $alertTone }}">{{ ucfirst($alert->severity) }}</span>
                                        <span class="text-sm" style="color: var(--sc-muted)">{{ $alert->created_at->diffForHumans() }}</span>
                                        @if($alert->isAcknowledged())
                                            <span data-ack-badge class="sc-mark sc-mark-ok"><i></i>Acknowledged</span>
                                        @endif
                                    </div>
                                    <h3 class="sc-h3 mt-1">{{ $alert->title }}</h3>
                                    <p class="mt-0.5" style="color: var(--sc-body)">{{ $alert->message }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2.5 flex-shrink-0 self-end sm:self-center">
                                @if($alert->isOpen())
                                    <button
                                        type="button"
                                        data-alert-action="acknowledge"
                                        onclick="acknowledgeAlert({{ $alert->id }})"
                                        class="sc-btn sc-btn-ghost sc-btn-sm"
                                    >
                                        Acknowledge
                                    </button>
                                @endif
                                @if($alert->isAcknowledged() || $alert->isOpen())
                                    <button
                                        type="button"
                                        data-alert-action="resolve"
                                        onclick="resolveAlert({{ $alert->id }})"
                                        class="sc-btn sc-btn-ghost sc-btn-sm"
                                    >
                                        <x-lucide-check class="sc-i w-4 h-4" aria-hidden="true" />
                                        Resolve
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </section>

            {{-- Acknowledge previously called window.location.reload(), which did
                 not repaint reliably — the caregiver clicked and nothing changed
                 until they refreshed by hand. Both actions now update the card in
                 place and surface failures instead of swallowing them. --}}
            <script>
                async function updateAlertState(alertId, action) {
                    const card = document.getElementById(`alert-card-${alertId}`);
                    const buttons = card ? card.querySelectorAll('button') : [];
                    buttons.forEach((b) => { b.disabled = true; });

                    try {
                        const response = await fetch(`/caregiver/alerts/${alertId}/${action}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                            },
                            credentials: 'same-origin',
                            cache: 'no-store',
                        });

                        if (response.status === 419) {
                            window.Alpine?.store('toast')?.show('Your session timed out. Please refresh and try again.', 'error');
                            return;
                        }

                        const data = await response.json().catch(() => null);

                        if (!response.ok || !data?.success) {
                            window.Alpine?.store('toast')?.show(data?.message || 'Could not update the alert.', 'error');
                            buttons.forEach((b) => { b.disabled = false; });
                            return;
                        }

                        if (action === 'resolve') {
                            card?.remove();
                            if (!document.querySelector('#clinical-alert-center [id^="alert-card-"]')) {
                                document.getElementById('clinical-alert-center')?.remove();
                            }
                        } else {
                            markCardAcknowledged(card, data.alert);
                            // Resolve stays available after an acknowledge, so it
                            // has to come back from the disabled state set above.
                            card?.querySelectorAll('button').forEach((b) => { b.disabled = false; });
                        }

                        window.Alpine?.store('toast')?.show(data.message || 'Alert updated.', 'success');
                    } catch {
                        window.Alpine?.store('toast')?.show('Could not reach the server. Please try again.', 'error');
                        buttons.forEach((b) => { b.disabled = false; });
                    }
                }

                function markCardAcknowledged(card, alert) {
                    if (!card) return;

                    // Drop the acknowledge button; leave resolve available.
                    card.querySelector('[data-alert-action="acknowledge"]')?.remove();

                    if (!card.querySelector('[data-ack-badge]')) {
                        const badge = document.createElement('span');
                        badge.setAttribute('data-ack-badge', '');
                        badge.className = 'sc-mark sc-mark-ok';
                        badge.append(document.createElement('i'), 'Acknowledged');
                        card.querySelector('[data-alert-badges]')?.appendChild(badge);
                    }

                    card.classList.add('opacity-75');
                }

                function acknowledgeAlert(alertId) {
                    return updateAlertState(alertId, 'acknowledge');
                }

                function resolveAlert(alertId) {
                    return updateAlertState(alertId, 'resolve');
                }
            </script>
        @endif

        @if(!empty($briefing))
            @php
                $riskLevel = $briefing['risk']['level'] ?? 'low';
                $riskTone = match($riskLevel) {
                    'high'     => 'alert',
                    'moderate' => 'warn',
                    default    => 'ok',
                };
                $checkin = $briefing['today_checkin'] ?? null;
            @endphp
            <section class="sc-card p-5 sm:p-6" aria-labelledby="briefing-title">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="sc-plate sc-plate-sm">
                            <x-lucide-stethoscope class="sc-i w-5 h-5" aria-hidden="true" />
                        </span>
                        <div>
                            <h2 id="briefing-title" class="sc-h3">Daily clinical briefing</h2>
                            <p class="text-sm mt-0.5" style="color: var(--sc-muted)">Automated safety summary from recorded data</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
                        <span class="sc-mark sc-mark-{{ $riskTone }}"><i></i>Risk score <span class="sc-num">{{ $briefing['risk']['score'] }}/100</span>&nbsp;· {{ ucfirst($riskLevel) }}</span>

                        <span class="sc-mark sc-mark-brand"><i></i><span class="sc-num">{{ $briefing['medication_adherence']['rate'] }}%</span>&nbsp;medication adherence</span>

                        @if($checkin)
                            <span class="sc-mark {{ $checkin->status === 'need_help' ? 'sc-mark-warn' : 'sc-mark-ok' }}"><i></i>{{ $checkin->status === 'need_help' ? 'Needs help' : 'Checked in' }} <span class="sc-num">({{ $checkin->checked_in_at?->format('g:i A') ?? 'Today' }})</span></span>
                        @else
                            <span class="sc-mark"><i></i>Check-in pending</span>
                        @endif
                    </div>
                </div>

                @if(!empty($briefing['highlights']))
                    <ul class="mt-4 pt-4 sc-hair grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-2">
                        @foreach($briefing['highlights'] as $highlight)
                            <li class="flex items-start gap-2.5" style="color: var(--sc-body)">
                                <span class="sc-dot mt-2 flex-shrink-0" aria-hidden="true"></span>
                                <span>{{ $highlight }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endif

        {{-- ============================================
             TOP ROW: patient card + today's summary
             ============================================ --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            {{-- Patient card (2 cols). One card, one avatar, no glass. The old
                 version floated two blurred sky-blue orbs behind a translucent
                 panel; identity does not need a wash. --}}
            <section class="lg:col-span-2 sc-card p-6 sm:p-8 flex flex-col justify-between" aria-labelledby="patient-name">
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6">
                    <span class="sc-avatar sc-avatar-xl">
                        @if($elderly->profile_photo)
                            <img src="{{ Storage::url($elderly->profile_photo) }}" alt="">
                        @else
                            <span aria-hidden="true">{{ mb_substr($elderlyUser->name ?? $elderly->username ?? 'E', 0, 1) }}</span>
                        @endif
                    </span>

                    <div class="flex-1 min-w-0 w-full">
                        <p class="sc-eyebrow">Your patient</p>
                        <h2 id="patient-name" class="sc-page-title mt-1">{{ $elderlyUser->name ?? $elderly->username ?? 'Elder' }}</h2>

                        <ul class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 font-medium" style="color: var(--sc-body)">
                            @if($elderly->age)
                                <li class="inline-flex items-center gap-1.5">
                                    <x-lucide-cake class="sc-i w-4 h-4" aria-hidden="true" />
                                    <span class="sc-num">{{ $elderly->age }}</span>&nbsp;yrs
                                </li>
                            @endif
                            @if($elderly->sex)
                                <li class="inline-flex items-center gap-1.5">
                                    <x-lucide-user-round class="sc-i w-4 h-4" aria-hidden="true" />
                                    {{ $elderly->sex }}
                                </li>
                            @endif
                            @if($elderly->phone_number)
                                <li class="inline-flex items-center gap-1.5">
                                    <x-lucide-phone class="sc-i w-4 h-4" aria-hidden="true" />
                                    <span class="sc-num">{{ $elderly->phone_number }}</span>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>

                @if(!empty($conditions) || !empty($medications) || !empty($allergies))
                    <div class="mt-6 pt-6 sc-hair flex flex-col gap-4">
                        @if(!empty($conditions))
                        <div>
                            <h3 class="sc-eyebrow mb-2">Conditions</h3>
                            <ul class="flex flex-wrap gap-2">
                                @foreach($conditions as $condition)
                                    <li class="sc-badge">{{ $condition }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                        <div class="flex flex-col sm:flex-row gap-4">
                            @if(!empty($medications))
                            <div class="flex-1">
                                <h3 class="sc-eyebrow mb-2">Medications</h3>
                                <ul class="flex flex-wrap gap-2">
                                    @foreach($medications as $med)
                                        <li class="sc-badge sc-badge-brand">
                                            <x-lucide-pill class="sc-i w-4 h-4" aria-hidden="true" />
                                            {{ $med }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif
                            @if(!empty($allergies))
                            <div class="flex-1">
                                <h3 class="sc-eyebrow mb-2">Allergies</h3>
                                <ul class="flex flex-wrap gap-2">
                                    @foreach($allergies as $allergy)
                                        <li class="sc-badge sc-badge-alert">
                                            <x-lucide-triangle-alert class="sc-i w-4 h-4" aria-hidden="true" />
                                            {{ $allergy }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif
                        </div>
                    </div>
                @endif
            </section>

            {{-- Today's summary (1 col). A bar always has its number beside it. --}}
            <section class="sc-card p-6 sm:p-8 flex flex-col" aria-labelledby="summary-title">
                <div class="flex items-center gap-3 mb-6">
                    <span class="sc-plate sc-plate-sm">
                        <x-lucide-chart-column class="sc-i w-5 h-5" aria-hidden="true" />
                    </span>
                    <h2 id="summary-title" class="sc-h3">Today's summary</h2>
                </div>

                @if(!empty($stats))
                @php
                    $vitalsPercent = $stats['vitals_total'] > 0 ? ($stats['vitals_recorded'] / $stats['vitals_total']) * 100 : 0;
                    $summaryRows = [
                        [
                            'label'   => 'Medication adherence',
                            'value'   => $stats['medication_adherence'] !== null ? $stats['medication_adherence'] . '%' : 'N/A',
                            'percent' => $stats['medication_adherence'] ?? 0,
                            'foot'    => $stats['doses_taken'] . ' of ' . $stats['doses_total'] . ' doses taken',
                            'done'    => $stats['medication_adherence'] === 100,
                        ],
                        [
                            'label'   => 'Daily tasks',
                            'value'   => $stats['task_completion'] !== null ? $stats['task_completion'] . '%' : 'N/A',
                            'percent' => $stats['task_completion'] ?? 0,
                            'foot'    => $stats['tasks_completed'] . ' of ' . $stats['tasks_total'] . ' tasks completed',
                            'done'    => $stats['task_completion'] === 100,
                        ],
                        [
                            'label'   => 'Vitals recorded',
                            'value'   => $stats['vitals_recorded'] . '/' . $stats['vitals_total'],
                            'percent' => $vitalsPercent,
                            'foot'    => 'Metrics logged today',
                            'done'    => $stats['vitals_recorded'] === $stats['vitals_total'],
                        ],
                    ];
                @endphp
                <div class="space-y-5 flex-1">
                    @foreach($summaryRows as $row)
                        <div>
                            <div class="flex justify-between items-baseline gap-3 mb-2">
                                <span class="font-medium" style="color: var(--sc-body)">{{ $row['label'] }}</span>
                                <span class="sc-num font-bold" style="color: var(--sc-ink)">{{ $row['value'] }}</span>
                            </div>
                            <div class="sc-progress" role="progressbar" aria-label="{{ $row['label'] }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ (int) round($row['percent']) }}">
                                <div class="sc-progress-fill {{ $row['done'] ? 'sc-progress-fill-ok' : '' }}" style="width: {{ $row['percent'] }}%"></div>
                            </div>
                            <p class="text-sm mt-1.5 sc-num" style="color: var(--sc-muted)">{{ $row['foot'] }}</p>
                        </div>
                    @endforeach
                </div>
                @else
                    <div class="sc-empty flex-1 py-8">
                        <x-lucide-chart-column class="sc-i w-8 h-8" style="color: var(--sc-muted)" aria-hidden="true" />
                        <p class="font-semibold" style="color: var(--sc-ink)">No stats available</p>
                        <p class="text-sm">Check back later today</p>
                    </div>
                @endif
            </section>

        </div>

        {{-- ============================================
             CARE MANAGEMENT — six destinations, one shape
             ============================================
             These were six full-bleed gradient tiles, each with a blurred
             white orb. They are links: one calm card each and an icon. --}}
        @php
            $careRouteParams = $selectedElderlyId ? ['elderly' => $selectedElderlyId] : [];
            $careLinks = [
                ['href' => route('caregiver.medications.index', $careRouteParams), 'icon' => 'pill',            'title' => 'Medications', 'desc' => 'Manage schedules'],
                ['href' => route('caregiver.checklists.index', $careRouteParams),  'icon' => 'clipboard-check', 'title' => 'Checklists',  'desc' => 'Daily tasks'],
                ['href' => route('caregiver.analytics', $careRouteParams),         'icon' => 'chart-column',    'title' => 'Analytics',   'desc' => 'View insights'],
                ['href' => route('caregiver.messages.index', $careRouteParams),    'icon' => 'message-square',  'title' => 'Messages',    'desc' => 'Chat with patient'],
                ['href' => route('caregiver.patients.index'),                      'icon' => 'users',           'title' => 'My patients', 'desc' => 'Manage patients'],
                ['href' => route('profile.edit'),                                  'icon' => 'user',            'title' => 'My profile',  'desc' => 'Edit your info'],
            ];
        @endphp
        <section aria-labelledby="manage-title">
            <h2 id="manage-title" class="sc-eyebrow mb-3">Manage care</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
                @foreach($careLinks as $link)
                    <a href="{{ $link['href'] }}"
                       class="sc-card sc-lift group p-5 flex flex-col gap-3 min-h-[8rem] no-underline">
                        <span class="flex items-start justify-between gap-2">
                            <span class="sc-plate sc-plate-sm">
                                <x-dynamic-component :component="'lucide-' . $link['icon']" class="sc-i w-5 h-5" aria-hidden="true" />
                            </span>
                            <x-lucide-chevron-right class="sc-i w-5 h-5 mt-2 transition-transform group-hover:translate-x-0.5" style="color: var(--sc-muted)" aria-hidden="true" />
                        </span>
                        <span class="mt-auto">
                            <span class="font-semibold block" style="color: var(--sc-ink)">{{ $link['title'] }}</span>
                            <span class="text-sm block mt-0.5" style="color: var(--sc-muted)">{{ $link['desc'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- ============================================
             MAIN CONTENT: mood + vitals | activity
             ============================================ --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

            <div class="lg:col-span-8 sc-stack">

                {{-- Mood. The face is the same Lucide set the senior's tracker
                     uses, coloured from the shared --sc-mood-N scale, so what
                     Arthur tapped is exactly what Sarah sees. --}}
                <section class="sc-card p-6" aria-labelledby="mood-title">
                    <div class="flex items-center justify-between gap-4 mb-4">
                        <h2 id="mood-title" class="sc-h3">{{ $elderlyUser->name ?? 'Elder' }}'s mood today</h2>
                        @if($mood)
                            <span class="text-sm whitespace-nowrap" style="color: var(--sc-muted)">{{ $mood->measured_at->diffForHumans() }}</span>
                        @endif
                    </div>

                    @if($mood)
                        @php
                            $moodValue = (int) $mood->value;
                            $moodLabels = [1 => 'Very sad', 2 => 'Sad', 3 => 'Neutral', 4 => 'Happy', 5 => 'Very happy'];
                            $moodIcons  = [1 => 'frown', 2 => 'frown', 3 => 'meh', 4 => 'smile', 5 => 'laugh'];
                            $moodIcon   = $moodIcons[$moodValue] ?? 'meh';
                            $moodColor  = 'var(--sc-mood-' . (($moodValue >= 1 && $moodValue <= 5) ? $moodValue : 3) . ')';
                        @endphp
                        <div class="flex items-center gap-5">
                            <span class="sc-card-quiet w-20 h-20 flex-shrink-0 flex items-center justify-center">
                                <x-dynamic-component :component="'lucide-' . $moodIcon" class="sc-i w-11 h-11" style="color: {{ $moodColor }}" aria-hidden="true" />
                            </span>
                            <div class="min-w-0">
                                <p class="sc-h3">{{ $moodLabels[$moodValue] ?? 'Unknown' }}</p>
                                @if($mood->notes)
                                    <p class="text-sm mt-1" style="color: var(--sc-muted)">{{ $mood->notes }}</p>
                                @endif
                            </div>
                        </div>
                        {{-- Scale: five segments, the number spoken alongside. --}}
                        <div class="mt-4 flex items-center gap-2" role="img" aria-label="Mood {{ $moodValue }} out of 5">
                            @foreach($moodLabels as $level => $label)
                                <div class="sc-progress flex-1">
                                    <div class="sc-progress-fill" style="width: {{ $moodValue >= $level ? 100 : 0 }}%; background: {{ $moodColor }}"></div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="sc-empty py-8">
                            <x-lucide-meh class="sc-i w-8 h-8" style="color: var(--sc-muted)" aria-hidden="true" />
                            <p class="font-semibold" style="color: var(--sc-ink)">No mood recorded today</p>
                        </div>
                    @endif
                </section>

                {{-- Vitals. Status is a word with a dot beside it, never a fill;
                     the presenter's colour is folded into one of three tones. --}}
                @php
                    $vitalTone = fn ($color) => match($color) {
                        'red'   => 'alert',
                        'green' => 'ok',
                        'gray'  => '',
                        default => 'warn',
                    };
                    $vitalTiles = [
                        ['key' => 'heart_rate',     'title' => 'Heart rate',     'icon' => 'heart-pulse', 'unit' => 'bpm',   'value' => fn ($m) => intval($m->value)],
                        ['key' => 'blood_pressure', 'title' => 'Blood pressure', 'icon' => 'activity',    'unit' => 'mmHg',  'value' => fn ($m) => $m->value_text],
                        ['key' => 'sugar_level',    'title' => 'Sugar level',    'icon' => 'droplet',     'unit' => 'mg/dL', 'value' => fn ($m) => intval($m->value)],
                        ['key' => 'temperature',    'title' => 'Temperature',    'icon' => 'thermometer', 'unit' => '°C',    'value' => fn ($m) => number_format($m->value, 1)],
                    ];
                @endphp
                <section aria-labelledby="vitals-title">
                    <div class="flex justify-between items-center gap-4 mb-3">
                        <h2 id="vitals-title" class="sc-h3">Health vitals</h2>
                        <span class="sc-mark"><i></i>Today's records</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($vitalTiles as $tile)
                            @php $reading = $vitals[$tile['key']] ?? null; @endphp
                            <div class="sc-stat flex flex-col min-h-[11rem]">
                                <div class="flex justify-between items-start gap-3">
                                    <span class="sc-plate sc-plate-sm">
                                        <x-dynamic-component :component="'lucide-' . $tile['icon']" class="sc-i w-5 h-5" aria-hidden="true" />
                                    </span>
                                    @if($reading)
                                        @php $tone = $vitalTone($reading['status']['color'] ?? 'gray'); @endphp
                                        <span class="sc-mark {{ $tone ? 'sc-mark-' . $tone : '' }}"><i></i>{{ $reading['status']['label'] }}</span>
                                    @endif
                                </div>
                                <div class="mt-auto pt-4">
                                    <h3 class="sc-stat-label">{{ $tile['title'] }}</h3>
                                    @if($reading)
                                        <div class="flex items-baseline gap-2 flex-wrap">
                                            <span class="sc-stat-value sc-num">{{ $tile['value']($reading['metric']) }}</span>
                                            <span class="font-semibold" style="color: var(--sc-muted)">{{ $tile['unit'] }}</span>
                                        </div>
                                        <p class="flex items-center gap-1.5 text-sm mt-2" style="color: var(--sc-muted)">
                                            <x-lucide-clock class="sc-i w-4 h-4" aria-hidden="true" />
                                            <span class="sc-num">{{ $reading['metric']->measured_at->format('g:i A') }}</span>
                                        </p>
                                    @else
                                        <p class="mt-2 font-medium" style="color: var(--sc-muted)">No record today</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <div class="lg:col-span-4 sc-stack">

                {{-- Recent activity. The presenter still hands over an emoji per
                     row; the view maps the notification type to a Lucide glyph
                     and its colour to a plate tone, so the list reads in one
                     weight and survives high contrast. --}}
                @php
                    $activityIcons = [
                        'notification_medication_taken'            => 'pill',
                        'notification_medication_taken_late'       => 'clock',
                        'notification_medication_missed'           => 'circle-x',
                        'notification_medication_refill'           => 'pill',
                        'notification_medication_refill_caregiver' => 'pill',
                        'notification_caregiver_unlinked'          => 'link',
                        'notification_task_completed'              => 'circle-check',
                        'notification_vitals_recorded'             => 'activity',
                        'notification_daily_reminder'              => 'bell',
                        'notification_appointment_reminder'        => 'calendar',
                        'notification_caregiver_message'           => 'message-square',
                        'notification_health_alert'                => 'triangle-alert',
                        'notification_refill_request'              => 'pill',
                    ];
                    $activityTone = fn ($severity, $color) => match($severity ?? $color) {
                        'positive', 'green'  => 'ok',
                        'warning', 'amber'   => 'warn',
                        'negative', 'red'    => 'alert',
                        default              => '',
                    };
                @endphp
                <section class="sc-card p-6 flex flex-col" aria-labelledby="activity-title">
                    <div class="flex items-center justify-between gap-4 mb-4 flex-shrink-0">
                        <h2 id="activity-title" class="sc-h3">Recent activity</h2>
                        <span class="text-sm whitespace-nowrap" style="color: var(--sc-muted)">Last 7 days</span>
                    </div>

                    @if($recentActivity->count() > 0)
                        <ul class="sc-divide flex-1 overflow-y-auto max-h-[30rem] -mx-2 px-2">
                            @foreach($recentActivity as $activity)
                                @php $tone = $activityTone($activity['severity'] ?? null, $activity['color'] ?? 'gray'); @endphp
                                <li class="flex items-start gap-3 py-3">
                                    <span class="sc-plate sc-plate-sm {{ $tone ? 'sc-plate-' . $tone : '' }} flex-shrink-0">
                                        <x-dynamic-component :component="'lucide-' . ($activityIcons[$activity['type']] ?? 'bell')" class="sc-i w-5 h-5" aria-hidden="true" />
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold" style="color: var(--sc-ink)">{{ $activity['title'] }}</p>
                                        <p class="text-sm" style="color: var(--sc-muted)">{{ $activity['subtitle'] }}</p>
                                    </div>
                                    <span class="text-sm sc-num whitespace-nowrap flex-shrink-0" style="color: var(--sc-muted)">
                                        {{ \Carbon\Carbon::parse($activity['timestamp'])->diffForHumans(null, true, true) }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="sc-empty py-8">
                            <x-lucide-bell class="sc-i w-8 h-8" style="color: var(--sc-muted)" aria-hidden="true" />
                            <p class="font-semibold" style="color: var(--sc-ink)">No recent activity</p>
                            <p class="text-sm">Activity will appear here as it happens</p>
                        </div>
                    @endif
                </section>

                {{-- Legend. Three tones, not five colours: every status mark on
                     this page already says its word, so the legend only has to
                     explain what the dot's tone adds. --}}
                <section class="sc-card-quiet p-4" aria-labelledby="legend-title">
                    <h2 id="legend-title" class="sc-stat-label mb-3">Health status legend</h2>
                    <ul class="grid grid-cols-2 gap-2">
                        <li><span class="sc-mark sc-mark-ok"><i></i>Normal</span></li>
                        <li><span class="sc-mark sc-mark-warn"><i></i>Elevated</span></li>
                        <li><span class="sc-mark sc-mark-warn"><i></i>High / Fever</span></li>
                        <li><span class="sc-mark sc-mark-alert"><i></i>Critical</span></li>
                        <li><span class="sc-mark sc-mark-warn"><i></i>Low</span></li>
                    </ul>
                </section>

            </div>
        </div>

        @endif
    </main>

    {{-- Caregiver AI Health Analyst Widget --}}
    <x-ai-chat-widget role="caregiver" />

</x-dashboard-layout>
