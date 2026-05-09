<x-dashboard-layout>
    <x-slot:title>{{ $config['name'] }} - SilverCare</x-slot:title>
    <x-slot:bodyClass>bg-[#F1F5F9] h-screen overflow-hidden text-gray-800</x-slot:bodyClass>

    @push('styles')
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in { animation: fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
        
        .stagger-1 { animation-delay: 0.05s; }
        .stagger-2 { animation-delay: 0.1s; }
        .stagger-3 { animation-delay: 0.15s; }
        .stagger-4 { animation-delay: 0.2s; }
        .stagger-5 { animation-delay: 0.25s; }
    </style>
    @endpush
    
    @php
        // PHP Logic Retained: Colors for Dynamic Styling
        $colorClasses = [
            'red' => [
                'bg' => 'bg-red-50', 'text' => 'text-red-600', 'border' => 'border-red-100', 'accent' => 'border-red-500',
                'btn' => 'bg-red-600 hover:bg-red-700 shadow-red-200',
                'gradient' => 'from-red-500 to-red-600',
            ],
            'blue' => [
                'bg' => 'bg-blue-50', 'text' => 'text-blue-600', 'border' => 'border-blue-100', 'accent' => 'border-blue-500',
                'btn' => 'bg-blue-600 hover:bg-blue-700 shadow-blue-200',
                'gradient' => 'from-blue-500 to-blue-600',
            ],
            'orange' => [
                'bg' => 'bg-orange-50', 'text' => 'text-orange-600', 'border' => 'border-orange-100', 'accent' => 'border-orange-500',
                'btn' => 'bg-orange-500 hover:bg-orange-600 shadow-orange-200',
                'gradient' => 'from-orange-500 to-orange-600',
            ],
            'rose' => [
                'bg' => 'bg-rose-50', 'text' => 'text-rose-600', 'border' => 'border-rose-100', 'accent' => 'border-rose-500',
                'btn' => 'bg-rose-500 hover:bg-rose-600 shadow-rose-200',
                'gradient' => 'from-rose-500 to-rose-600',
            ],
            'green' => [
                'bg' => 'bg-green-50', 'text' => 'text-green-600', 'border' => 'border-green-100', 'accent' => 'border-green-500',
                'btn' => 'bg-green-600 hover:bg-green-700 shadow-green-200',
                'gradient' => 'from-green-500 to-green-600',
            ],
        ];
        $colors = $colorClasses[$config['color']] ?? $colorClasses['blue'];
    @endphp

    {{-- ✅ Same nav component as schedule page --}}
    <x-dashboard-nav
        :title="$config['name']"
        :subtitle="'Track and manage your ' . strtolower($config['name']) . ' readings'"
        role="elderly"
        :unread-notifications="$unreadNotifications ?? 0"
    />

    <main class="max-w-7xl mx-auto px-6 pt-4 pb-6 flex flex-col" style="height: calc(100vh - 80px);">

        {{-- Back pill --}}
        <div class="flex justify-end mb-3">
            <a href="{{ route('dashboard', ['tab' => 'health']) }}" class="back-nav-pill !text-gray-600 !bg-white/70 hover:!bg-white">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back
            </a>
        </div>

        {{-- Session alerts --}}
        @if(session('success'))
            <div class="mb-4 fade-in bg-green-50 border-l-8 border-green-500 text-green-800 px-6 py-4 rounded-xl shadow-sm flex items-center gap-4">
                <div class="bg-green-100 p-2 rounded-full flex-shrink-0"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg></div>
                <span class="font-bold">{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 fade-in bg-red-50 border-l-8 border-red-500 text-red-800 px-6 py-4 rounded-xl shadow-sm flex items-center gap-4">
                <div class="bg-red-100 p-2 rounded-full flex-shrink-0"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg></div>
                <span class="font-bold">{{ session('error') }}</span>
            </div>
        @endif

        {{-- Two-column dashboard --}}
        <div class="flex gap-5 flex-1 min-h-0">

            {{-- ═══════════════════════════════
                LEFT COLUMN — Health snapshot
                Wider: hero content lives here
            ═══════════════════════════════ --}}
            <div class="w-96 flex-shrink-0 flex flex-col gap-4 min-h-0">

                {{-- 1. LATEST READING — HERO, most important --}}
                @if($stats['count'] > 0)
                    @php
                        // Determine status for the latest reading
                        $latestStatus = null;
                        if ($type === 'blood_pressure' && isset($stats['latest']->value_text)) {
                            $latestStatus = \App\Presenters\HealthMetricPresenter::getBloodPressureStatus($stats['latest']->value_text);
                        } elseif ($type === 'sugar_level' && isset($stats['latest']->value)) {
                            $latestStatus = \App\Presenters\HealthMetricPresenter::getSugarLevelStatus(floatval($stats['latest']->value));
                        } elseif ($type === 'temperature' && isset($stats['latest']->value)) {
                            $latestStatus = \App\Presenters\HealthMetricPresenter::getTemperatureStatus(floatval($stats['latest']->value));
                        } elseif ($type === 'heart_rate' && isset($stats['latest']->value)) {
                            $latestStatus = \App\Presenters\HealthMetricPresenter::getHeartRateStatus(floatval($stats['latest']->value));
                        }
                        $isDangerous = $latestStatus && in_array(strtolower($latestStatus['label'] ?? ''), ['low', 'high', 'critical', 'danger', 'elevated', 'very high', 'very low', 'hypertension']);
                    @endphp

                    <div class="rounded-3xl p-5 shadow-lg border-2 flex-1 min-h-0 relative overflow-hidden
                        {{ $isDangerous ? 'bg-red-50 border-red-200' : 'bg-white border-gray-100' }}">

                        {{-- Dangerous warning stripe --}}
                        @if($isDangerous)
                            <div class="flex items-center gap-2 mb-4 px-3 py-2 bg-red-100 rounded-xl border border-red-200">
                                <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path>
                                </svg>
                                <span class="text-red-700 font-[800] text-sm">Attention needed — check your reading</span>
                            </div>
                        @endif

                        <p class="text-xs uppercase tracking-widest font-[800] text-gray-500 mb-2">Latest Reading</p>

                        <div class="flex items-baseline gap-2 mb-1">
                            <span class="text-5xl font-[900] tracking-tight {{ $isDangerous ? 'text-red-600' : 'text-gray-900' }}">
                                @if($type === 'blood_pressure') {{ $stats['latest']->value_text ?? '-' }}
                                @elseif($type === 'temperature') {{ number_format($stats['latest']->value, 1) }}
                                @else {{ intval($stats['latest']->value) }}
                                @endif
                            </span>
                            <span class="text-xl font-[700] text-gray-500">{{ $config['unit'] }}</span>
                        </div>

                        {{-- Status badge — large and unmissable --}}
                        @if($latestStatus)
                            <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-sm font-[900] uppercase tracking-wide {{ $latestStatus['bg'] }} {{ $latestStatus['text'] }} mb-3">
                                @if($isDangerous)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 9v2m0 4h.01"></path></svg>
                                @endif
                                {{ $latestStatus['label'] }}
                            </span>
                        @endif

                        <p class="text-sm font-[600] text-gray-500 mb-5">
                            Measured {{ $stats['latest']->measured_at->diffForHumans() }}
                        </p>

                        {{-- AVG / MIN / MAX --}}
                        @if($type !== 'blood_pressure')
                        <div class="grid grid-cols-3 gap-2 pt-4 border-t {{ $isDangerous ? 'border-red-200' : 'border-gray-100' }}">
                            <div class="text-center">
                                <p class="text-[10px] font-[800] uppercase tracking-wider text-gray-500 mb-1">Avg</p>
                                <p class="text-xl font-[900] text-gray-800">{{ $stats['avg'] ?? '-' }}</p>
                            </div>
                            <div class="text-center border-x {{ $isDangerous ? 'border-red-200' : 'border-gray-100' }}">
                                <p class="text-[10px] font-[800] uppercase tracking-wider text-gray-500 mb-1">Min</p>
                                <p class="text-xl font-[900] text-gray-800">{{ number_format($stats['min'], $type === 'temperature' ? 1 : 0) }}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-[10px] font-[800] uppercase tracking-wider text-gray-500 mb-1">Max</p>
                                <p class="text-xl font-[900] text-gray-800">{{ number_format($stats['max'], $type === 'temperature' ? 1 : 0) }}</p>
                            </div>
                        </div>
                        @else
                        <div class="pt-4 border-t border-gray-100 text-center">
                            <p class="text-[10px] font-[800] uppercase tracking-wider text-gray-500 mb-1">Total Entries (30 days)</p>
                            <p class="text-3xl font-[900] text-gray-800">{{ $stats['count'] }}</p>
                        </div>
                        @endif
                    </div>

                @else
                    {{-- No readings yet — still clearly shows the space --}}
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex-1 min-h-0 text-center flex flex-col items-center justify-center">
                        <div class="text-5xl mb-3 grayscale opacity-30">{{ $config['icon'] }}</div>
                        <p class="font-[900] text-gray-400 text-lg">No readings yet</p>
                        <p class="text-sm text-gray-400 mt-1">Add your first reading below</p>
                    </div>
                @endif

                {{-- 2. ADD READING + source indicator — combined, elderly-friendly --}}
                <button onclick="openRecordModal()"
                    class="w-full bg-gradient-to-br {{ $colors['gradient'] }} text-white rounded-3xl px-6 py-4 shadow-lg relative overflow-hidden group transition-all hover:shadow-xl hover:scale-[1.02] active:scale-[0.98] flex-shrink-0 border-2 border-white">
                    <div class="absolute -bottom-4 -left-4 w-20 h-20 bg-white/5 rounded-full"></div>
                    <div class="relative flex items-center gap-4">
                        <div class="w-16 h-16 bg-white/25 backdrop-blur-sm rounded-2xl flex items-center justify-center shadow-md group-hover:scale-110 transition-transform flex-shrink-0 border border-white/30">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </div>
                        <div class="text-left flex-1 min-w-0">
                            <p class="text-white font-[900] text-xl leading-tight">Record {{ $config['name'] }}</p>
                            <p class="text-white/80 font-[600] text-sm mt-1">Tap here to add a new reading</p>
                            {{-- Source indicator merged here --}}
                            @if($supportsGoogleFit)
                                @if($googleFitConnected)
                                    <div class="flex items-center gap-1.5 mt-2">
                                        <span class="w-2 h-2 bg-green-300 rounded-full animate-pulse flex-shrink-0"></span>
                                        <span class="text-white/70 text-xs font-[700]">Google Fit connected</span>
                                    </div>
                                @else
                                    <div class="flex items-center gap-1.5 mt-2">
                                        <span class="w-2 h-2 bg-white/40 rounded-full flex-shrink-0"></span>
                                        <span class="text-white/70 text-xs font-[700]">Google Fit not connected</span>
                                    </div>
                                @endif
                            @else
                                <div class="flex items-center gap-1.5 mt-2">
                                    <svg class="w-3.5 h-3.5 text-white/60 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                    </svg>
                                    <span class="text-white/70 text-xs font-[700]">Manual entry only</span>
                                </div>
                            @endif
                        </div>
                        <div class="bg-white/20 border border-white/30 rounded-xl p-3 flex-shrink-0 group-hover:bg-white/30 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>
                </button>

                {{-- Google Fit sync controls — only shown when supported --}}
                @if($supportsGoogleFit)
                <div class="bg-white rounded-3xl px-5 py-4 shadow-sm border border-gray-100 flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 bg-gray-50 border border-gray-200 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                                <path d="M22.5 12.06c0-.82-.07-1.6-.2-2.36H12v4.49h5.88a5.03 5.03 0 0 1-2.18 3.3v2.74h3.53c2.06-1.9 3.25-4.7 3.25-7.17z" fill="#4285F4"/>
                                <path d="M12 22.5c2.95 0 5.43-.98 7.24-2.66l-3.53-2.74c-.98.66-2.23 1.05-3.71 1.05-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.08 7.7 22.5 12 22.5z" fill="#34A853"/>
                                <path d="M5.84 13.62a6.52 6.52 0 0 1-.43-2.62c0-.91.16-1.78.43-2.62V5.54H2.18A10.49 10.49 0 0 0 0 12c0 1.68.4 3.29 1.18 4.76l3.66-2.84V13.62z" fill="#FBBC05"/>
                                <path d="M12 5.03c1.61 0 3.05.55 4.19 1.64l3.15-3.15C17.43 1.63 14.95.5 12 .5 7.7.5 3.99 2.92 2.18 6.54l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-[800] text-sm text-gray-800">Google Fit</p>
                            @if($googleFitConnected)
                                <p class="text-xs font-[700] text-green-600 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse flex-shrink-0"></span>
                                    Connected
                                </p>
                            @else
                                <p class="text-xs font-[700] text-gray-500">Not connected</p>
                            @endif
                        </div>
                        @if($googleFitConnected)
                            <button onclick="syncGoogleFit()" id="syncBtn"
                                class="px-3 py-2 bg-blue-50 text-blue-700 font-[800] text-xs rounded-xl hover:bg-blue-100 transition-all flex items-center gap-1.5 flex-shrink-0 disabled:opacity-50">
                                <svg id="syncBtnIcon" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                <span id="syncBtnLabel">Sync</span>
                            </button>
                        @else
                            <a href="{{ route('elderly.googlefit.connect') }}"
                                class="px-3 py-2 bg-gray-900 text-white font-[800] text-xs rounded-xl hover:bg-gray-800 transition-all flex-shrink-0">
                                Connect
                            </a>
                        @endif
                    </div>
                    @if($googleFitConnected)
                        <div id="autoSyncStatus" class="mt-3 text-xs font-[700] text-gray-400 flex items-center gap-1.5">
                            <div class="w-1.5 h-1.5 bg-gray-300 rounded-full flex-shrink-0"></div>
                            <span>Idle</span>
                        </div>
                        <form action="{{ route('elderly.googlefit.disconnect') }}" method="POST" class="mt-2">
                            @csrf
                            <button type="submit" class="text-xs font-[700] text-gray-400 hover:text-red-500 transition-colors">
                                Unlink Google Fit
                            </button>
                        </form>
                    @endif
                </div>
                @endif
            </div>

            {{-- ═══════════════════════════════
                RIGHT COLUMN — Recent History
                Secondary but useful reference
            ═══════════════════════════════ --}}
            <div class="flex-1 min-w-0 bg-white rounded-3xl shadow-sm border border-gray-100 flex flex-col overflow-hidden">

                <div class="px-6 py-5 border-b border-gray-100 flex-shrink-0">
                    <h2 class="font-[900] text-xl text-gray-900">Recent History</h2>
                    <p class="text-sm font-[700] text-gray-500 mt-0.5">Your logs for the past 30 days</p>
                </div>

                <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
                    @forelse($metrics as $metric)
                        @php
                            $recordStatus = null;
                            if ($type === 'blood_pressure' && $metric->value_text) {
                                $recordStatus = \App\Presenters\HealthMetricPresenter::getBloodPressureStatus($metric->value_text);
                            } elseif ($type === 'sugar_level' && $metric->value) {
                                $recordStatus = \App\Presenters\HealthMetricPresenter::getSugarLevelStatus(floatval($metric->value));
                            } elseif ($type === 'temperature' && $metric->value) {
                                $recordStatus = \App\Presenters\HealthMetricPresenter::getTemperatureStatus(floatval($metric->value));
                            } elseif ($type === 'heart_rate' && $metric->value) {
                                $recordStatus = \App\Presenters\HealthMetricPresenter::getHeartRateStatus(floatval($metric->value));
                            }
                            $rowDangerous = $recordStatus && in_array(strtolower($recordStatus['label'] ?? ''), ['low', 'high', 'critical', 'danger', 'elevated', 'very high', 'very low', 'hypertension']);
                        @endphp

                        <div class="group px-6 py-4 transition-colors flex items-center gap-4
                            {{ $rowDangerous ? 'bg-red-50/60 hover:bg-red-50' : 'hover:bg-gray-50' }}">

                            <div class="w-11 h-11 {{ $rowDangerous ? 'bg-red-100 border-red-200' : $colors['bg'].' '.$colors['border'] }} rounded-2xl flex items-center justify-center text-xl flex-shrink-0 border">
                                {{ $config['icon'] }}
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-2xl font-[900] {{ $rowDangerous ? 'text-red-600' : 'text-gray-900' }}">
                                        @if($type === 'blood_pressure') {{ $metric->value_text }}
                                        @elseif($type === 'temperature') {{ number_format($metric->value, 1) }}
                                        @else {{ intval($metric->value) }}
                                        @endif
                                    </span>
                                    <span class="text-sm font-[700] text-gray-500">{{ $config['unit'] }}</span>
                                    @if($recordStatus)
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-[900] uppercase tracking-wide {{ $recordStatus['bg'] }} {{ $recordStatus['text'] }}">
                                            @if($rowDangerous)
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 9v2m0 4h.01"></path></svg>
                                            @endif
                                            {{ $recordStatus['label'] }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs font-[700] text-gray-500 mt-0.5">
                                    {{ $metric->measured_at->format('M j, Y') }} • {{ $metric->measured_at->format('g:i A') }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2 flex-shrink-0">
                                @if($metric->source === 'google_fit')
                                    <span class="px-2 py-1 bg-gray-50 border border-gray-200 rounded-lg text-[10px] font-[800] text-gray-600 uppercase tracking-wide">G Fit</span>
                                @else
                                    <span class="px-2 py-1 bg-gray-100 border border-gray-200 rounded-lg text-[10px] font-[800] text-gray-600 uppercase tracking-wide">Manual</span>
                                @endif
                                <button onclick="deleteRecord({{ $metric->id }})"
                                    class="p-2 rounded-lg text-gray-300 hover:text-red-500 hover:bg-red-50 transition-all opacity-0 group-hover:opacity-100">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                    @empty
                        <div class="flex flex-col items-center justify-center h-full py-16 text-center px-6">
                            <div class="text-5xl mb-4 grayscale opacity-25">{{ $config['icon'] }}</div>
                            <p class="font-[900] text-gray-300 text-lg">No Records Yet</p>
                            <p class="text-sm font-[600] text-gray-400 mt-1 max-w-xs">Use the button on the left to add your first reading.</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </main>

    {{-- ─────────────────────────────────────────────
         RECORD MODAL
    ───────────────────────────────────────────── --}}
    <div id="recordModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm z-[60] hidden items-center justify-center p-4 transition-all duration-300 opacity-0" style="transition: opacity 0.3s;">
        <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden transform scale-95 transition-transform duration-300" id="recordModalContent">
            <div class="px-8 py-6 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                <div>
                    <h3 class="font-[900] text-xl text-gray-900">Add New Reading</h3>
                    <p class="text-sm text-gray-500 font-medium">Enter your {{ strtolower($config['name']) }} data</p>
                </div>
                <button type="button" onclick="closeRecordModal()" class="w-10 h-10 rounded-full bg-white border border-gray-200 text-gray-400 hover:text-gray-800 hover:border-gray-400 flex items-center justify-center transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form id="recordForm" onsubmit="submitRecord(event)" class="p-8">
                <div class="mb-8 text-center">
                    <label class="block text-sm font-[800] text-gray-400 uppercase tracking-widest mb-4">
                        ENTER VALUE ({{ $config['unit'] }})
                    </label>
                    
                    @if($type === 'blood_pressure')
                        <div class="flex gap-4 items-center justify-center">
                            <div class="w-1/3">
                                <input type="number" id="systolicValue" name="systolic" placeholder="120" min="60" max="250" 
                                    class="w-full px-4 py-4 text-4xl font-[900] text-center text-gray-900 border-2 border-gray-200 rounded-2xl focus:border-{{ $config['color'] }}-500 focus:ring-0 outline-none transition-colors placeholder-gray-300" required>
                                <p class="text-xs font-bold text-gray-400 mt-2 uppercase">Systolic</p>
                            </div>
                            <span class="text-4xl text-gray-300 font-[200]">/</span>
                            <div class="w-1/3">
                                <input type="number" id="diastolicValue" name="diastolic" placeholder="80" min="40" max="150" 
                                    class="w-full px-4 py-4 text-4xl font-[900] text-center text-gray-900 border-2 border-gray-200 rounded-2xl focus:border-{{ $config['color'] }}-500 focus:ring-0 outline-none transition-colors placeholder-gray-300" required>
                                <p class="text-xs font-bold text-gray-400 mt-2 uppercase">Diastolic</p>
                            </div>
                        </div>
                    @else
                        <div class="relative max-w-[200px] mx-auto">
                            <input 
                                type="number" id="valueInput" name="value"
                                placeholder="{{ $type === 'sugar_level' ? '100' : ($type === 'temperature' ? '36.5' : '72') }}"
                                step="{{ $type === 'temperature' ? '0.1' : '1' }}"
                                class="w-full px-4 py-5 text-5xl font-[900] text-center text-gray-900 border-2 border-gray-200 rounded-2xl focus:border-{{ $config['color'] }}-500 focus:ring-0 outline-none transition-colors placeholder-gray-300"
                                required>
                            
                            @if($type === 'temperature')
                                <div class="absolute -right-16 top-1/2 -translate-y-1/2">
                                    <button type="button" id="unitToggle" onclick="toggleTempUnit()" class="h-10 w-10 rounded-xl bg-gray-100 text-gray-600 font-bold hover:bg-gray-200 transition-colors border border-gray-200">
                                        °C
                                    </button>
                                </div>
                                <input type="hidden" id="tempUnit" name="temp_unit" value="C">
                            @endif
                        </div>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1.5 ml-1" for="dateInput">Date</label>
                        {{-- M2 FIX: py-4 + min-h-[52px] ensures WCAG 2.5.8 ≥44px touch target --}}
                        <input type="date" id="dateInput" name="date" value="{{ now()->format('Y-m-d') }}"
                            class="w-full px-4 py-4 min-h-[52px] bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-{{ $config['color'] }}-500 focus:ring-0 font-semibold text-gray-900 cursor-pointer text-base">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1.5 ml-1" for="timeInput">Time</label>
                        <input type="time" id="timeInput" name="time" value="{{ now()->format('H:i') }}"
                            class="w-full px-4 py-4 min-h-[52px] bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-{{ $config['color'] }}-500 focus:ring-0 font-semibold text-gray-900 cursor-pointer text-base">
                    </div>
                </div>

                <div class="mb-8">
                    <label class="block text-xs font-bold text-gray-500 mb-1.5 ml-1">Notes (Optional)</label>
                    <textarea id="notesInput" name="notes" placeholder="Add any details about how you felt..." rows="2"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-{{ $config['color'] }}-500 focus:ring-0 text-sm resize-none"></textarea>
                </div>

                <button type="submit" id="submitBtn" disabled
                    class="w-full py-4 text-lg text-white font-[800] rounded-xl transition-all flex items-center justify-center gap-2 bg-gray-300 cursor-not-allowed opacity-60"
                    data-active-class="{{ $colors['btn'] }}"
                    data-inactive-class="bg-gray-300 cursor-not-allowed opacity-60">
                    Save Record <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                </button>
            </form>
        </div>
    </div>

    {{-- ─────────────────────────────────────────────
         H4 FIX: Delete Confirmation Modal
            Replaces native confirmation dialogs with a large, senior-friendly modal.
    ───────────────────────────────────────────── --}}
    <div id="deleteConfirmModal"
         class="fixed inset-0 bg-gray-900/70 backdrop-blur-sm z-[60] hidden items-center justify-center p-6"
         style="transition: opacity 0.25s;"
         role="dialog"
         aria-modal="true"
         aria-labelledby="deleteModalTitle">
        <div class="bg-white rounded-3xl shadow-2xl max-w-sm w-full p-8 text-center transform transition-transform duration-300 scale-95" id="deleteConfirmContent">
            <div class="flex items-center justify-center w-20 h-20 mx-auto mb-5 rounded-full bg-red-50 border-4 border-red-100">
                <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>
            <h2 id="deleteModalTitle" class="text-2xl font-[900] text-gray-900 mb-2">Delete This Record?</h2>
            <p class="text-base text-gray-500 font-medium mb-8">This action cannot be undone.</p>
            <div class="flex gap-4">
                <button
                    id="deleteCancelBtn"
                    onclick="closeDeleteModal()"
                    class="flex-1 py-4 text-lg font-bold text-gray-700 bg-gray-100 rounded-2xl hover:bg-gray-200 transition-colors min-h-[56px] focus:outline-none focus:ring-4 focus:ring-gray-300">
                    Cancel
                </button>
                <button
                    id="deleteConfirmBtn"
                    onclick="confirmDelete()"
                    class="flex-1 py-4 text-lg font-bold text-white bg-red-600 rounded-2xl hover:bg-red-700 transition-colors min-h-[56px] shadow-lg shadow-red-200 focus:outline-none focus:ring-4 focus:ring-red-400">
                    Yes, Delete
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;
        // --- Save button enable/disable logic ---
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

            const activeClass  = btn.dataset.activeClass;
            const inactiveClass = btn.dataset.inactiveClass;

            if (filled) {
                btn.disabled = false;
                btn.className = `w-full py-4 text-lg text-white font-[800] rounded-xl transition-all transform hover:scale-[1.02] active:scale-[0.98] shadow-lg flex items-center justify-center gap-2 ${activeClass}`;
            } else {
                btn.disabled = true;
                btn.className = `w-full py-4 text-lg text-white font-[800] rounded-xl transition-all flex items-center justify-center gap-2 ${inactiveClass}`;
            }
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
        
        document.addEventListener('DOMContentLoaded', function() {
            if (GOOGLE_FIT_CONNECTED && SUPPORTS_GOOGLE_FIT) {
                const statusEl = document.getElementById('autoSyncStatus');
                
                if (!sessionStorage.getItem(SYNC_SESSION_KEY)) {
                    if(statusEl) statusEl.innerHTML = '<div class="w-2.5 h-2.5 bg-blue-500 rounded-full animate-ping"></div><span class="text-blue-600">Syncing...</span>';
                    sessionStorage.setItem(SYNC_SESSION_KEY, 'true');
                    autoSyncGoogleFit();
                } else {
                    if(statusEl) statusEl.innerHTML = '<div class="w-2.5 h-2.5 bg-green-500 rounded-full"></div><span class="text-green-600">Up to date</span>';
                }
            }
        });

        async function autoSyncGoogleFit() {
            const statusEl = document.getElementById('autoSyncStatus');
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

                if (statusEl) {
                    if (data.success && data.synced && Object.keys(data.synced).length > 0) {
                        statusEl.innerHTML = '<div class="w-2.5 h-2.5 bg-green-500 rounded-full"></div><span class="text-green-600 font-bold">New data synced!</span>';
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        statusEl.innerHTML = '<div class="w-2.5 h-2.5 bg-green-500 rounded-full"></div><span class="text-green-600">Up to date</span>';
                    }
                }
            } catch (error) {
                if(statusEl) statusEl.innerHTML = '<div class="w-2.5 h-2.5 bg-orange-400 rounded-full"></div><span class="text-orange-500">Sync idle</span>';
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
            if (unitToggle) unitToggle.textContent = '°' + currentTempUnit;
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