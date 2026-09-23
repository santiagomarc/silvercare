{{-- ============================================================
     VITAL DETAIL — one vital, its latest reading, its history, and
     the one thing to do here: record a new reading.

     The page used to be a fixed-height viewport with its own inner
     scroll pane, a gradient "Record" slab in the vital's colour, and a
     status pill filled red for anything out of range. It is now a
     normal page that scrolls; the vital's identity is its icon, not a
     hue; and out-of-range readings carry a word and a chip, so the
     screen reads the same in high contrast.

     Every id, route, form field and JavaScript function is unchanged.
     ============================================================ --}}

<x-dashboard-layout>
    <x-slot:title>{{ $config['name'] }} - SilverCare</x-slot:title>
    <x-slot:bodyClass>sc-page min-h-screen</x-slot:bodyClass>

    @php
        // One icon per vital, everywhere — the same map App\View\Components\VitalCard uses.
        $vitalIcons = [
            'blood_pressure' => 'heart-pulse',
            'heart_rate'     => 'activity',
            'sugar_level'    => 'droplet',
            'temperature'    => 'thermometer',
        ];
        $vitalIcon = $vitalIcons[$type] ?? 'activity';

        // The presenter hands back a colour name; the page folds it into a
        // tone. Anything out of range is "dangerous" — it gets the chip.
        $statusFor = function ($metric) use ($type) {
            if (!$metric) return null;
            if ($type === 'blood_pressure') {
                return $metric->value_text ? \App\Presenters\HealthMetricPresenter::getBloodPressureStatus($metric->value_text) : null;
            }
            if (!$metric->value) return null;
            return match ($type) {
                'sugar_level' => \App\Presenters\HealthMetricPresenter::getSugarLevelStatus(floatval($metric->value)),
                'temperature' => \App\Presenters\HealthMetricPresenter::getTemperatureStatus(floatval($metric->value)),
                'heart_rate'  => \App\Presenters\HealthMetricPresenter::getHeartRateStatus(floatval($metric->value)),
                default       => null,
            };
        };
        $isDangerousStatus = fn ($status) => $status && in_array(strtolower($status['label'] ?? ''), ['low', 'high', 'critical', 'danger', 'elevated', 'very high', 'very low', 'hypertension']);
        $toneFor = fn ($status) => match ($status['color'] ?? 'gray') {
            'red'   => 'alert',
            'green' => 'ok',
            'gray'  => '',
            default => 'warn',
        };
        $formatValue = fn ($metric) => match ($type) {
            'blood_pressure' => $metric->value_text ?? '-',
            'temperature'    => number_format($metric->value, 1),
            default          => intval($metric->value),
        };
    @endphp

    <x-dashboard-nav
        :title="$config['name']"
        :subtitle="'Track and manage your ' . strtolower($config['name']) . ' readings'"
        role="elderly"
        :unread-notifications="$unreadNotifications ?? 0"
    />

    <main id="main-content" class="sc-app-main">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-10 space-y-6">

            {{-- Back navigation --}}
            <div class="flex justify-between items-center">
                <a href="{{ route('dashboard', ['tab' => 'health']) }}" class="sc-btn sc-btn-ghost sc-btn-sm">
                    <x-lucide-arrow-left class="sc-i w-4 h-4" aria-hidden="true" />
                    <span>Back to Dashboard</span>
                </a>
            </div>

            <x-flash-messages />

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">

                {{-- ═══════════════════════════════
                    LEFT COLUMN — snapshot and the action
                ═══════════════════════════════ --}}
                <div class="lg:col-span-5 space-y-4">

                    {{-- Latest reading --}}
                    @if($stats['count'] > 0)
                        @php
                            $latestStatus = $statusFor($stats['latest']);
                            $isDangerous = $isDangerousStatus($latestStatus);
                            $latestTone = $toneFor($latestStatus);
                        @endphp
                        <section class="sc-card p-6" aria-labelledby="latest-title">
                            <div class="flex items-center justify-between gap-3">
                                <h2 id="latest-title" class="sc-eyebrow">Latest reading</h2>
                                <span class="sc-plate sc-plate-sm {{ $isDangerous && $latestTone ? 'sc-plate-' . $latestTone : '' }}">
                                    <x-dynamic-component :component="'lucide-' . $vitalIcon" class="sc-i w-5 h-5" aria-hidden="true" />
                                </span>
                            </div>

                            {{-- The number is what this page is about, so it takes the
                                 hero scale. It is a value, not a section heading. --}}
                            <div class="flex items-baseline gap-2 flex-wrap mt-3">
                                <span class="sc-h2 sc-num font-extrabold" style="color: var(--sc-ink)">{{ $formatValue($stats['latest']) }}</span>
                                <span class="sc-h3 font-semibold" style="color: var(--sc-muted)">{{ $config['unit'] }}</span>
                            </div>

                            @if($latestStatus)
                                <div class="mt-3">
                                    @if($isDangerous && $latestTone)
                                        <span class="sc-chip sc-chip-{{ $latestTone }}">
                                            <x-lucide-triangle-alert class="sc-i w-4 h-4" aria-hidden="true" />
                                            {{ $latestStatus['label'] }}
                                        </span>
                                    @else
                                        <span class="sc-mark {{ $latestTone ? 'sc-mark-' . $latestTone : '' }}"><i></i>{{ $latestStatus['label'] }}</span>
                                    @endif
                                </div>
                            @endif

                            <p class="text-sm mt-3" style="color: var(--sc-muted)">
                                Measured {{ $stats['latest']->measured_at->diffForHumans() }}
                            </p>

                            @if($type !== 'blood_pressure')
                                <dl class="grid grid-cols-3 gap-2 mt-5 pt-5 sc-hair text-center">
                                    <div>
                                        <dt class="sc-stat-label">Avg</dt>
                                        <dd class="sc-num font-bold text-xl" style="color: var(--sc-ink)">{{ $stats['avg'] ?? '-' }}</dd>
                                    </div>
                                    <div class="border-x" style="border-color: var(--sc-line)">
                                        <dt class="sc-stat-label">Min</dt>
                                        <dd class="sc-num font-bold text-xl" style="color: var(--sc-ink)">{{ number_format($stats['min'], $type === 'temperature' ? 1 : 0) }}</dd>
                                    </div>
                                    <div>
                                        <dt class="sc-stat-label">Max</dt>
                                        <dd class="sc-num font-bold text-xl" style="color: var(--sc-ink)">{{ number_format($stats['max'], $type === 'temperature' ? 1 : 0) }}</dd>
                                    </div>
                                </dl>
                            @else
                                <dl class="mt-5 pt-5 sc-hair text-center">
                                    <dt class="sc-stat-label">Total entries (30 days)</dt>
                                    <dd class="sc-num font-bold text-2xl" style="color: var(--sc-ink)">{{ $stats['count'] }}</dd>
                                </dl>
                            @endif
                        </section>
                    @else
                        <section class="sc-empty" aria-labelledby="latest-title">
                            <span class="sc-plate">
                                <x-dynamic-component :component="'lucide-' . $vitalIcon" class="sc-i w-6 h-6" aria-hidden="true" />
                            </span>
                            <h2 id="latest-title" class="sc-h3">No readings yet</h2>
                            <p>Add your first reading below.</p>
                        </section>
                    @endif

                    {{-- The one action on the page. --}}
                    <div class="sc-card p-5">
                        <button type="button" onclick="openRecordModal()" class="sc-btn sc-btn-primary w-full">
                            <x-lucide-plus class="sc-i w-5 h-5" aria-hidden="true" />
                            Record {{ $config['name'] }}
                        </button>
                        <p class="text-sm text-center mt-3" style="color: var(--sc-muted)">Tap to add a new reading</p>

                        <div class="flex justify-center mt-3">
                            @if($supportsGoogleFit)
                                @if($googleFitConnected)
                                    <span class="sc-mark sc-mark-ok"><i></i>Google Fit connected</span>
                                @else
                                    <span class="sc-mark"><i></i>Google Fit not connected</span>
                                @endif
                            @else
                                <span class="sc-mark">
                                    <x-lucide-pencil class="sc-i w-4 h-4" aria-hidden="true" />
                                    Manual entry only
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Google Fit sync controls — only shown when supported --}}
                    @if($supportsGoogleFit)
                    <section class="sc-card p-5" aria-labelledby="googlefit-title">
                        <div class="flex items-center gap-3">
                            <span class="sc-plate sc-plate-sm">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.647,3.972-5.445,3.972c-3.332,0-6.033-2.701-6.033-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.503,2.988,15.139,2,12.545,2C7.021,2,2.543,6.477,2.543,12s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.748L12.545,10.239z"/></svg>
                            </span>
                            <div class="flex-1 min-w-0">
                                <h2 id="googlefit-title" class="font-semibold" style="color: var(--sc-ink)">Google Fit</h2>
                                @if($googleFitConnected)
                                    <span class="sc-mark sc-mark-ok"><i></i>Connected</span>
                                @else
                                    <span class="sc-mark"><i></i>Not connected</span>
                                @endif
                            </div>
                            @if($googleFitConnected)
                                <button type="button" onclick="syncGoogleFit()" id="syncBtn" class="sc-btn sc-btn-ghost sc-btn-sm flex-shrink-0">
                                    <x-lucide-refresh-cw id="syncBtnIcon" class="sc-i w-4 h-4" aria-hidden="true" />
                                    <span id="syncBtnLabel">Sync</span>
                                </button>
                            @else
                                <a href="{{ route('elderly.googlefit.connect') }}" class="sc-btn sc-btn-ghost sc-btn-sm flex-shrink-0">
                                    Connect
                                </a>
                            @endif
                        </div>
                        @if($googleFitConnected)
                            {{-- A live region that is always in the DOM and changes its text. --}}
                            <p id="autoSyncStatus" class="sc-mark mt-3" aria-live="polite"><i></i><span>Idle</span></p>
                            <form action="{{ route('elderly.googlefit.disconnect') }}" method="POST" class="mt-2">
                                @csrf
                                <button type="submit" class="sc-textlink text-sm">
                                    Unlink Google Fit
                                </button>
                            </form>
                        @endif
                    </section>
                    @endif
                </div>

                {{-- ═══════════════════════════════
                    RIGHT COLUMN — recent history
                ═══════════════════════════════ --}}
                <section class="lg:col-span-7 sc-card overflow-hidden" aria-labelledby="history-title">
                    <div class="px-6 py-5 sc-hair" style="border-top: 0; border-bottom: 1px solid var(--sc-line)">
                        <h2 id="history-title" class="sc-h3">Recent history</h2>
                        <p class="text-sm mt-0.5" style="color: var(--sc-muted)">Your logs for the past 30 days</p>
                    </div>

                    @if($metrics->isNotEmpty())
                        {{-- Thirty days can be a hundred rows. The list scrolls inside
                             its card so the snapshot on the left stays in reach. --}}
                        <ul class="sc-divide max-h-[42rem] overflow-y-auto">
                            @foreach($metrics as $metric)
                                @php
                                    $recordStatus = $statusFor($metric);
                                    $rowDangerous = $isDangerousStatus($recordStatus);
                                    $rowTone = $toneFor($recordStatus);
                                @endphp
                                <li class="px-6 py-4 flex items-center gap-4">
                                    <span class="sc-plate sc-plate-sm flex-shrink-0 {{ $rowDangerous && $rowTone ? 'sc-plate-' . $rowTone : '' }}">
                                        <x-dynamic-component :component="'lucide-' . $vitalIcon" class="sc-i w-5 h-5" aria-hidden="true" />
                                    </span>

                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-x-2 gap-y-1 flex-wrap">
                                            <span class="sc-num font-bold text-xl" style="color: var(--sc-ink)">{{ $formatValue($metric) }}</span>
                                            <span class="text-sm font-medium" style="color: var(--sc-muted)">{{ $config['unit'] }}</span>
                                            {{-- A mark, not a chip: the chip is for the one latest
                                                 reading. The tinted plate already flags the row. --}}
                                            @if($recordStatus)
                                                <span class="sc-mark {{ $rowTone ? 'sc-mark-' . $rowTone : '' }}"><i></i>{{ $recordStatus['label'] }}</span>
                                            @endif
                                        </div>
                                        <p class="text-sm mt-0.5 sc-num" style="color: var(--sc-muted)">
                                            {{ $metric->measured_at->format('M j, Y') }} · {{ $metric->measured_at->format('g:i A') }}
                                        </p>
                                    </div>

                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <span class="sc-badge hidden sm:inline-flex">{{ $metric->source === 'google_fit' ? 'Google Fit' : 'Manual' }}</span>
                                        {{-- Always visible: a control that only appears on hover
                                             does not exist on a phone. --}}
                                        <button type="button" onclick="deleteRecord({{ $metric->id }})" class="sc-icon-btn">
                                            <x-lucide-trash-2 class="sc-i w-5 h-5" aria-hidden="true" />
                                            <span class="sr-only">Delete this reading</span>
                                        </button>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="sc-empty m-6">
                            <x-dynamic-component :component="'lucide-' . $vitalIcon" class="sc-i w-8 h-8" style="color: var(--sc-muted)" aria-hidden="true" />
                            <p class="font-semibold" style="color: var(--sc-ink)">No records yet</p>
                            <p class="text-sm">Use the Record button to add your first reading.</p>
                        </div>
                    @endif
                </section>

            </div>
        </div>
    </main>

    {{-- ─────────────────────────────────────────────
         RECORD MODAL
         The wrapper is the scrim, so a click on the backdrop still lands on
         the element the existing listener compares against.
    ───────────────────────────────────────────── --}}
    <div id="recordModal"
         class="sc-scrim hidden items-center justify-center p-4 opacity-0"
         style="transition: opacity 0.3s;"
         role="dialog"
         aria-modal="true"
         aria-labelledby="recordModalTitle">
        <div class="sc-dialog max-w-lg w-full transform scale-95 transition-transform duration-300" id="recordModalContent">
            <div class="flex items-start justify-between gap-4 mb-6">
                <div>
                    <h2 id="recordModalTitle" class="sc-dialog-title">Add new reading</h2>
                    <p class="text-sm mt-1" style="color: var(--sc-muted)">Enter your {{ strtolower($config['name']) }} data</p>
                </div>
                <button type="button" onclick="closeRecordModal()" class="sc-icon-btn flex-shrink-0">
                    <x-lucide-x class="sc-i w-5 h-5" aria-hidden="true" />
                    <span class="sr-only">Close</span>
                </button>
            </div>

            <form id="recordForm" onsubmit="submitRecord(event)">
                @if($type === 'blood_pressure')
                    <fieldset class="sc-fieldset mb-6">
                        <legend class="sc-legend">Value ({{ $config['unit'] }})</legend>
                        <div class="flex gap-3 items-end justify-center">
                            <div class="sc-field flex-1 max-w-[9rem]">
                                <label for="systolicValue" class="sc-label sc-label-req">Systolic</label>
                                <input type="number" id="systolicValue" name="systolic" placeholder="120" min="60" max="250" inputmode="numeric"
                                    class="sc-input sc-num text-center" required>
                            </div>
                            <span class="sc-h2 pb-3" style="color: var(--sc-muted)" aria-hidden="true">/</span>
                            <div class="sc-field flex-1 max-w-[9rem]">
                                <label for="diastolicValue" class="sc-label sc-label-req">Diastolic</label>
                                <input type="number" id="diastolicValue" name="diastolic" placeholder="80" min="40" max="150" inputmode="numeric"
                                    class="sc-input sc-num text-center" required>
                            </div>
                        </div>
                    </fieldset>
                @else
                    <div class="sc-field mb-6">
                        <label for="valueInput" class="sc-label sc-label-req">Value ({{ $config['unit'] }})</label>
                        <div class="flex items-center justify-center gap-3">
                            <input
                                type="number" id="valueInput" name="value"
                                placeholder="{{ $type === 'sugar_level' ? '100' : ($type === 'temperature' ? '36.5' : '72') }}"
                                step="{{ $type === 'temperature' ? '0.1' : '1' }}"
                                inputmode="{{ $type === 'temperature' ? 'decimal' : 'numeric' }}"
                                class="sc-input sc-num text-center max-w-[12rem]"
                                required>
                            @if($type === 'temperature')
                                <button type="button" id="unitToggle" onclick="toggleTempUnit()" class="sc-btn sc-btn-ghost sc-btn-sm sc-num flex-shrink-0" aria-label="Switch temperature unit, currently °C">
                                    °C
                                </button>
                                <input type="hidden" id="tempUnit" name="temp_unit" value="C">
                            @endif
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div class="sc-field">
                        <label class="sc-label" for="dateInput">Date</label>
                        <input type="date" id="dateInput" name="date" value="{{ now()->format('Y-m-d') }}" class="sc-input sc-num">
                    </div>
                    <div class="sc-field">
                        <label class="sc-label" for="timeInput">Time</label>
                        <input type="time" id="timeInput" name="time" value="{{ now()->format('H:i') }}" class="sc-input sc-num">
                    </div>
                </div>

                <div class="sc-field mb-6">
                    <label class="sc-label sc-label-opt" for="notesInput">Notes</label>
                    <textarea id="notesInput" name="notes" placeholder="Add any details about how you felt..." rows="2" class="sc-textarea resize-none"></textarea>
                </div>

                <button type="submit" id="submitBtn" disabled class="sc-btn sc-btn-primary w-full">
                    <x-lucide-check class="sc-i w-5 h-5" aria-hidden="true" />
                    Save record
                </button>
            </form>
        </div>
    </div>

    {{-- ─────────────────────────────────────────────
         H4 FIX: Delete Confirmation Modal
            Replaces native confirmation dialogs with a large, senior-friendly modal.
    ───────────────────────────────────────────── --}}
    <div id="deleteConfirmModal"
         class="sc-scrim hidden items-center justify-center p-6"
         style="transition: opacity 0.25s;"
         role="dialog"
         aria-modal="true"
         aria-labelledby="deleteModalTitle">
        <div class="sc-dialog max-w-sm w-full text-center transform transition-transform duration-300 scale-95" id="deleteConfirmContent">
            <span class="sc-plate sc-plate-alert mx-auto mb-5">
                <x-lucide-trash-2 class="sc-i w-6 h-6" aria-hidden="true" />
            </span>
            <h2 id="deleteModalTitle" class="sc-dialog-title">Delete this reading?</h2>
            <p class="mt-2 mb-8" style="color: var(--sc-body)">This action cannot be undone.</p>
            <div class="flex gap-3">
                <button
                    type="button"
                    id="deleteCancelBtn"
                    onclick="closeDeleteModal()"
                    class="sc-btn sc-btn-ghost flex-1">
                    Cancel
                </button>
                <button
                    type="button"
                    id="deleteConfirmBtn"
                    onclick="confirmDelete()"
                    class="sc-btn sc-btn-danger flex-1">
                    Yes, delete
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;
        // --- Save button enable/disable logic ---
        // Only the real `disabled` attribute changes; .sc-btn:disabled carries
        // its own look, so no class swapping is needed.
        function validateForm() {
            const btn = document.getElementById('submitBtn');
            if (!btn) return;

            let filled = false;

            if (VITAL_TYPE === 'blood_pressure') {
                const sys = document.getElementById('systolicValue');
                const dia = document.getElementById('diastolicValue');
                filled = sys && dia && sys.value.trim() !== '' && dia.value.trim() !== '';
            } else {
                const val = document.getElementById('valueInput');
                filled = val && val.value.trim() !== '';
            }

            btn.disabled = !filled;
        }

        const VITAL_TYPE = '{{ $type }}';
        const GOOGLE_FIT_CONNECTED = {{ $googleFitConnected ? 'true' : 'false' }};
        const SUPPORTS_GOOGLE_FIT = {{ $supportsGoogleFit ? 'true' : 'false' }};

        // --- Animations for Modal ---
        const modal = document.getElementById('recordModal');
        const modalContent = document.getElementById('recordModalContent');

        function openRecordModal() {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            requestAnimationFrame(() => {
                modal.classList.remove('opacity-0');
                modalContent.classList.remove('scale-95');
                modalContent.classList.add('scale-100');
            });
            document.body.style.overflow = 'hidden';

            // Reset button to disabled state on every open
            validateForm();

            // Attach listeners (remove first to avoid duplicates)
            const inputs = modal.querySelectorAll('input[type="number"]');
            inputs.forEach(input => {
                input.removeEventListener('input', validateForm);
                input.addEventListener('input', validateForm);
            });

            setTimeout(() => {
                const firstInput = modal.querySelector('input[type="number"]');
                if (firstInput) firstInput.focus();
            }, 100);
        }

        function closeRecordModal() {
            modal.classList.add('opacity-0');
            modalContent.classList.remove('scale-100');
            modalContent.classList.add('scale-95');

            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = '';

                // Reset form
                document.getElementById('recordForm').reset();
                document.getElementById('dateInput').value = new Date().toISOString().split('T')[0];
                document.getElementById('timeInput').value = new Date().toTimeString().slice(0,5);

                if (VITAL_TYPE === 'temperature') {
                    currentTempUnit = 'C';
                    updateTempUnitUI();
                }
            }, 300); // Match transition duration
        }

        // --- Auto Sync Logic ---
        const SYNC_SESSION_KEY = `vitals_synced_${VITAL_TYPE}_${new Date().toDateString()}`;

        // The status line is a sc-mark: the tone lives in the dot, the text
        // carries the meaning. Same element, changed text — so it announces.
        function setSyncStatus(tone, text) {
            const statusEl = document.getElementById('autoSyncStatus');
            if (!statusEl) return;
            statusEl.className = 'sc-mark mt-3' + (tone ? ` sc-mark-${tone}` : '');
            statusEl.innerHTML = '<i></i><span></span>';
            statusEl.querySelector('span').textContent = text;
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (GOOGLE_FIT_CONNECTED && SUPPORTS_GOOGLE_FIT) {
                if (!sessionStorage.getItem(SYNC_SESSION_KEY)) {
                    setSyncStatus('brand', 'Syncing...');
                    sessionStorage.setItem(SYNC_SESSION_KEY, 'true');
                    autoSyncGoogleFit();
                } else {
                    setSyncStatus('ok', 'Up to date');
                }
            }
        });

        async function autoSyncGoogleFit() {
            const syncBtn  = document.getElementById('syncBtn');
            const syncIcon = document.getElementById('syncBtnIcon');
            const syncLbl  = document.getElementById('syncBtnLabel');

            if (syncBtn) {
                syncBtn.disabled = true;
                if (syncIcon) syncIcon.classList.add('animate-spin');
                if (syncLbl)  syncLbl.textContent = 'Syncing...';
            }

            try {
                const url = new URL('/google-fit/sync', window.location.origin);
                url.searchParams.set('vital_type', VITAL_TYPE); // only sync this metric

                const response = await fetch(url.toString(), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();

                if (data.success && data.synced && Object.keys(data.synced).length > 0) {
                    setSyncStatus('ok', 'New data synced!');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    setSyncStatus('ok', 'Up to date');
                }
            } catch (error) {
                setSyncStatus('warn', 'Sync idle');
            } finally {
                if (syncBtn) {
                    syncBtn.disabled = false;
                    if (syncIcon) syncIcon.classList.remove('animate-spin');
                    if (syncLbl)  syncLbl.textContent = 'Sync';
                }
            }
        }

        async function syncGoogleFit() {
            const syncBtn  = document.getElementById('syncBtn');
            const syncIcon = document.getElementById('syncBtnIcon');
            const syncLbl  = document.getElementById('syncBtnLabel');
            if (!syncBtn || syncBtn.disabled) return;

            syncBtn.disabled = true;
            if (syncIcon) syncIcon.classList.add('animate-spin');
            if (syncLbl)  syncLbl.textContent = 'Syncing...';

            try {
                const url = new URL('/google-fit/sync', window.location.origin);
                url.searchParams.set('vital_type', VITAL_TYPE); // scoped to current metric

                const response = await fetch(url.toString(), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();

                if (response.status === 401) {
                    window.scToast('Google Fit session expired. Please reconnect.', 'error', { elderly: true });
                    return;
                }

                if (!response.ok) throw new Error(data.message || 'Sync failed');

                if (data.synced && Object.keys(data.synced).length > 0) {
                    window.scToast('Data Synced Successfully', 'success', { elderly: true });
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    window.scToast('Already up to date', 'info', { elderly: true });
                }
            } catch (error) {
                window.scToast(error.message, 'error', { elderly: true });
            } finally {
                syncBtn.disabled = false;
                if (syncIcon) syncIcon.classList.remove('animate-spin');
                if (syncLbl)  syncLbl.textContent = 'Sync';
            }
        }

        // --- Temp Toggle Logic ---
        let currentTempUnit = 'C';
        function toggleTempUnit() {
            const valueInput = document.getElementById('valueInput');
            if(!valueInput) return;
            const currentValue = parseFloat(valueInput.value);

            if (currentTempUnit === 'C') {
                currentTempUnit = 'F';
                if (!isNaN(currentValue)) valueInput.value = ((currentValue * 9/5) + 32).toFixed(1);
                valueInput.min = 86; valueInput.max = 122; valueInput.placeholder = '98.6';
            } else {
                currentTempUnit = 'C';
                if (!isNaN(currentValue)) valueInput.value = ((currentValue - 32) * 5/9).toFixed(1);
                valueInput.min = 30; valueInput.max = 50; valueInput.placeholder = '36.5';
            }
            updateTempUnitUI();
        }

        function updateTempUnitUI() {
            const unitToggle = document.getElementById('unitToggle');
            const tempUnitInput = document.getElementById('tempUnit');
            if (unitToggle) {
                unitToggle.textContent = '°' + currentTempUnit;
                unitToggle.setAttribute('aria-label', 'Switch temperature unit, currently °' + currentTempUnit);
            }
            if (tempUnitInput) tempUnitInput.value = currentTempUnit;
        }

        // --- Submission Logic ---
        async function submitRecord(event) {
            event.preventDefault();
            const submitBtn = document.getElementById('submitBtn');
            const originalContent = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Saving...';

            try {
                const payload = {
                    type: VITAL_TYPE,
                    notes: document.getElementById('notesInput').value || null,
                    measured_at: `${document.getElementById('dateInput').value} ${document.getElementById('timeInput').value}:00`
                };

                @if($type === 'blood_pressure')
                    payload.value_text = `${document.getElementById('systolicValue').value}/${document.getElementById('diastolicValue').value}`;
                @elseif($type === 'temperature')
                    let tempValue = parseFloat(document.getElementById('valueInput').value);
                    if (currentTempUnit === 'F') tempValue = (tempValue - 32) * 5/9;
                    payload.value = tempValue.toFixed(1);
                @else
                    payload.value = parseFloat(document.getElementById('valueInput').value);
                @endif

                const response = await fetch('/my-vitals', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Failed to save');

                closeRecordModal();

                // Let users finish reading the success feedback before reloading.
                if (typeof window.scToast === 'function') {
                    await window.scToast('Record saved successfully!', 'success', {
                        elderly: true,
                        duration: 1800,
                    });
                }

                window.location.reload();

            } catch (error) {
                window.scToast(error.message, 'error', { elderly: true });
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalContent;
            }
        }

        // H4 FIX: Delete modal state
        let _pendingDeleteId = null;

        function deleteRecord(id) {
            _pendingDeleteId = id;
            openDeleteModal();
        }

        function openDeleteModal() {
            const modal = document.getElementById('deleteConfirmModal');
            const content = document.getElementById('deleteConfirmContent');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            requestAnimationFrame(() => {
                modal.style.opacity = '1';
                content.classList.remove('scale-95');
                content.classList.add('scale-100');
            });
            document.body.style.overflow = 'hidden';
            document.getElementById('deleteCancelBtn').focus();
        }

        function closeDeleteModal() {
            const modal = document.getElementById('deleteConfirmModal');
            const content = document.getElementById('deleteConfirmContent');
            modal.style.opacity = '0';
            content.classList.remove('scale-100');
            content.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = '';
                _pendingDeleteId = null;
            }, 250);
        }

        async function confirmDelete() {
            if (!_pendingDeleteId) return;
            const id = _pendingDeleteId;
            closeDeleteModal();
            try {
                const response = await fetch(`/my-vitals/${id}`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) throw new Error('Failed to delete');
                window.scToast('Record deleted successfully', 'success', { elderly: true });
                setTimeout(() => window.location.reload(), 600);
            } catch (error) {
                window.scToast(error.message, 'error', { elderly: true });
            }
        }

        // Close delete modal on backdrop click or Escape
        document.getElementById('deleteConfirmModal').addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDeleteModal();
                closeRecordModal();
            }
        });

        // Record modal triggers
        document.getElementById('recordModal').addEventListener('click', function(e) {
            if (e.target === this) closeRecordModal();
        });
    </script>
    @endpush

<x-ai-chat-widget />

</x-dashboard-layout>
