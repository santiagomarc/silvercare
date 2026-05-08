<x-dashboard-layout>
    <x-slot:title>Caregiver Dashboard - SilverCare</x-slot:title>

    @push('styles')
    <style>
        /* Scrollbar hiding */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
    @endpush

    {{-- Navigation --}}
    <x-dashboard-nav
        title="Caregiver Dashboard"
        role="caregiver"
    />

    {{-- Dashboard Content --}}
    <main class="max-w-[1600px] mx-auto px-6 lg:px-12 py-5">

        <x-flash-messages />

        @if(!$elderly)
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-2xl mb-6 shadow-sm">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-lg font-[800] text-yellow-800">No Patient Assigned Yet</h3>
                        <p class="text-sm text-yellow-700 mt-1">Generate a linking PIN and share it with your patient. They can scan the QR code or enter the PIN on their dashboard to link instantly.</p>

                        <div class="mt-4">
                            <a href="{{ route('profile.edit') }}" class="inline-flex items-center justify-center rounded-xl bg-[#000080] px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-900 transition-colors">
                                Go to Profile to Generate PIN
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        @else

        @if(($elderlyPatients ?? collect())->count() > 1)
            <div class="mb-5 rounded-2xl border border-blue-100 bg-blue-50/80 p-4 shadow-sm">
                <form method="GET" action="{{ route('caregiver.dashboard') }}" class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <label for="elderly" class="text-sm font-bold text-blue-900">Viewing patient</label>
                    <select
                        id="elderly"
                        name="elderly"
                        onchange="this.form.submit()"
                        class="rounded-xl border border-blue-200 bg-white px-3 py-2 text-sm font-semibold text-gray-800"
                    >
                        @foreach(($elderlyPatients ?? collect()) as $patient)
                            <option value="{{ $patient->id }}" @selected(($selectedElderlyId ?? null) === $patient->id)>
                                {{ $patient->user?->name ?? ('Patient #' . $patient->id) }}
                            </option>
                        @endforeach
                    </select>
                    <span class="text-xs font-semibold text-blue-700 sm:ml-auto">{{ ($elderlyPatients ?? collect())->count() }} linked patients</span>
                </form>
            </div>
        @endif


        <!-- ============================================ -->
        <!-- TOP ROW: Elder Profile Card + Management Panel -->
        <!-- ============================================ -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

            <!-- ELDER PROFILE CARD (2 cols) -->
            <div class="lg:col-span-2 relative overflow-hidden rounded-[24px] bg-white/70 dark:bg-slate-900/70 backdrop-blur-2xl p-6 sm:p-8 shadow-card border border-white dark:border-slate-700/50 transition-all hover:shadow-lg flex flex-col justify-between">
                <!-- Decorative glass shapes to tint the white glass -->
                <div class="absolute -top-24 -right-24 w-[28rem] h-[28rem] bg-gradient-to-br from-sky-400/40 to-blue-400/40 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-24 -left-24 w-80 h-80 bg-gradient-to-tr from-cyan-400/30 to-sky-300/30 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col sm:flex-row items-start sm:items-center gap-6">
                    <!-- Avatar -->
                    <div class="relative w-20 h-20 sm:w-28 sm:h-28 flex-shrink-0">
                        <div class="absolute inset-0 bg-gradient-to-br from-sky-400 to-blue-500 rounded-full shadow-lg opacity-40 blur-md transform translate-y-1"></div>
                        <div class="relative w-full h-full rounded-full bg-white dark:bg-slate-800 flex items-center justify-center border-4 border-white/90 dark:border-slate-700 shadow-sm overflow-hidden z-10">
                            @if($elderly->profile_photo)
                                <img src="{{ Storage::url($elderly->profile_photo) }}" alt="{{ $elderlyUser->name ?? 'Elder' }}" class="w-full h-full object-cover">
                            @else
                                <span class="text-4xl sm:text-5xl font-black text-sky-500">{{ mb_substr($elderlyUser->name ?? $elderly->username ?? 'E', 0, 1) }}</span>
                            @endif
                        </div>
                    </div>

                    <!-- Elder Details -->
                    <div class="flex-1 w-full">
                        <div class="flex items-center justify-between mb-1">
                            <p class="text-sky-700/80 dark:text-sky-300/80 text-xs font-bold uppercase tracking-widest">Your Patient</p>
                        </div>
                        <h2 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white mb-3 tracking-tight">{{ $elderlyUser->name ?? $elderly->username ?? 'Elder' }}</h2>
                        
                        <div class="flex flex-wrap items-center gap-3 sm:gap-5 text-sm font-semibold text-slate-700 dark:text-slate-200">
                            @if($elderly->age)
                                <div class="flex items-center gap-1.5 bg-white/50 dark:bg-slate-800/50 backdrop-blur-md px-3 py-1.5 rounded-lg border border-white/60 dark:border-slate-600/50 shadow-sm">
                                    <svg class="w-4 h-4 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    <span>{{ $elderly->age }} yrs</span>
                                </div>
                            @endif
                            @if($elderly->sex)
                                <div class="flex items-center gap-1.5 bg-white/50 dark:bg-slate-800/50 backdrop-blur-md px-3 py-1.5 rounded-lg border border-white/60 dark:border-slate-600/50 shadow-sm">
                                    <svg class="w-4 h-4 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    <span>{{ $elderly->sex }}</span>
                                </div>
                            @endif
                            @if($elderly->phone_number)
                                <div class="flex items-center gap-1.5 bg-white/50 dark:bg-slate-800/50 backdrop-blur-md px-3 py-1.5 rounded-lg border border-white/60 dark:border-slate-600/50 shadow-sm">
                                    <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                    <span>{{ $elderly->phone_number }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Medical Conditions Badge -->
                @if(!empty($conditions) || !empty($medications) || !empty($allergies))
                    <div class="relative z-10 mt-6 pt-6 border-t border-sky-100/50 dark:border-slate-700/50">
                        <div class="flex flex-col gap-4">
                            @if(!empty($conditions))
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-sky-600/80 dark:text-sky-400/80 mb-2">Conditions</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($conditions as $condition)
                                        <span class="bg-white/60 dark:bg-slate-800/60 backdrop-blur-md text-sky-800 dark:text-sky-200 text-xs font-bold px-3 py-1 rounded-full border border-white/80 dark:border-slate-600/50 shadow-sm">{{ $condition }}</span>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                            <div class="flex flex-col sm:flex-row gap-4">
                                @if(!empty($medications))
                                <div class="flex-1">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-sky-600/80 dark:text-sky-400/80 mb-2">Medications</p>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($medications as $med)
                                            <span class="bg-white/60 dark:bg-slate-800/60 backdrop-blur-md text-blue-800 dark:text-blue-200 text-xs font-bold px-3 py-1 rounded-full border border-white/80 dark:border-slate-600/50 shadow-sm">💊 {{ $med }}</span>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                                @if(!empty($allergies))
                                <div class="flex-1">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-sky-600/80 dark:text-sky-400/80 mb-2">Allergies</p>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($allergies as $allergy)
                                            <span class="bg-red-50/80 dark:bg-red-500/10 backdrop-blur-md text-red-700 dark:text-red-300 text-xs font-bold px-3 py-1 rounded-full border border-red-100 dark:border-red-500/20 shadow-sm">⚠️ {{ $allergy }}</span>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- TODAY'S STATS CARD (1 col) -->
            <div class="bg-white dark:bg-slate-900 rounded-[24px] p-6 sm:p-8 shadow-card border border-slate-100 dark:border-slate-800 flex flex-col justify-between transition-all hover:shadow-lg">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white shadow-md">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    </div>
                    <h3 class="font-black text-xl text-slate-900 dark:text-white tracking-tight">Today's Summary</h3>
                </div>
                
                @if(!empty($stats))
                <div class="space-y-5 flex-1">
                    <!-- Medication Stat -->
                    <div class="group">
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-sm font-bold text-slate-600 dark:text-slate-300">Medication Adherence</span>
                            <div class="text-right">
                                <span class="text-lg font-black {{ $stats['medication_adherence'] === 100 ? 'text-emerald-500' : ($stats['medication_adherence'] >= 50 ? 'text-amber-500' : 'text-slate-400') }}">
                                    @if($stats['medication_adherence'] !== null) {{ $stats['medication_adherence'] }}% @else N/A @endif
                                </span>
                            </div>
                        </div>
                        <div class="h-3 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden shadow-inner">
                            <div class="h-full rounded-full transition-all duration-1000 ease-out {{ $stats['medication_adherence'] === 100 ? 'bg-gradient-to-r from-emerald-400 to-emerald-500' : ($stats['medication_adherence'] >= 50 ? 'bg-gradient-to-r from-amber-400 to-amber-500' : 'bg-slate-300 dark:bg-slate-600') }}" style="width: {{ $stats['medication_adherence'] ?? 0 }}%"></div>
                        </div>
                        <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 mt-1.5 uppercase tracking-wider">{{ $stats['doses_taken'] }} of {{ $stats['doses_total'] }} doses taken</p>
                    </div>
                    
                    <!-- Task Stat -->
                    <div class="group">
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-sm font-bold text-slate-600 dark:text-slate-300">Daily Tasks</span>
                            <div class="text-right">
                                <span class="text-lg font-black {{ $stats['task_completion'] === 100 ? 'text-blue-500' : ($stats['task_completion'] >= 50 ? 'text-indigo-500' : 'text-slate-400') }}">
                                    @if($stats['task_completion'] !== null) {{ $stats['task_completion'] }}% @else N/A @endif
                                </span>
                            </div>
                        </div>
                        <div class="h-3 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden shadow-inner">
                            <div class="h-full rounded-full transition-all duration-1000 ease-out {{ $stats['task_completion'] === 100 ? 'bg-gradient-to-r from-blue-400 to-blue-500' : ($stats['task_completion'] >= 50 ? 'bg-gradient-to-r from-indigo-400 to-indigo-500' : 'bg-slate-300 dark:bg-slate-600') }}" style="width: {{ $stats['task_completion'] ?? 0 }}%"></div>
                        </div>
                        <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 mt-1.5 uppercase tracking-wider">{{ $stats['tasks_completed'] }} of {{ $stats['tasks_total'] }} tasks completed</p>
                    </div>
                    
                    <!-- Vitals Stat -->
                    <div class="group">
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-sm font-bold text-slate-600 dark:text-slate-300">Vitals Recorded</span>
                            <div class="text-right">
                                <span class="text-lg font-black {{ $stats['vitals_recorded'] === $stats['vitals_total'] ? 'text-purple-500' : 'text-pink-500' }}">
                                    {{ $stats['vitals_recorded'] }}/{{ $stats['vitals_total'] }}
                                </span>
                            </div>
                        </div>
                        <div class="h-3 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden shadow-inner">
                            @php $vp = $stats['vitals_total'] > 0 ? ($stats['vitals_recorded'] / $stats['vitals_total']) * 100 : 0; @endphp
                            <div class="h-full rounded-full transition-all duration-1000 ease-out {{ $vp === 100 ? 'bg-gradient-to-r from-purple-400 to-purple-500' : 'bg-gradient-to-r from-pink-400 to-pink-500' }}" style="width: {{ $vp }}%"></div>
                        </div>
                        <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 mt-1.5 uppercase tracking-wider">Metrics logged today</p>
                    </div>
                </div>
                @else
                    <div class="flex-1 flex flex-col items-center justify-center py-8 text-center">
                        <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center mb-3">
                            <svg class="w-8 h-8 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                        </div>
                        <p class="text-sm font-bold text-slate-500 dark:text-slate-400">No stats available</p>
                        <p class="text-xs font-semibold text-slate-400 dark:text-slate-500 mt-1">Check back later today</p>
                    </div>
                @endif
            </div>

        </div>

        <!-- ============================================ -->
        <!-- CARE MANAGEMENT PANEL (Action Buttons) -->
        <!-- ============================================ -->
        @php
            $careRouteParams = $selectedElderlyId ? ['elderly' => $selectedElderlyId] : [];
        @endphp
        <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
            
            <!-- Manage Medications -->
            <a href="{{ route('caregiver.medications.index', $careRouteParams) }}" class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 shadow-lg shadow-blue-200/50 transition-all duration-300 hover:shadow-xl hover:scale-[1.02] hover:-translate-y-1 min-h-[120px]">
                <div class="absolute top-0 right-0 -mt-6 -mr-6 w-24 h-24 rounded-full bg-white/20 blur-xl"></div>
                <div class="relative p-5 flex flex-col justify-between h-full z-10">
                    <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm w-fit">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-[900] text-white leading-tight">Medications</h3>
                        <p class="text-blue-100 text-xs font-medium mt-0.5">Manage schedules</p>
                    </div>
                    <div class="absolute bottom-4 right-4 h-8 w-8 rounded-full bg-white/20 flex items-center justify-center group-hover:bg-white group-hover:text-blue-600 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                </div>
            </a>

            <!-- Manage Checklists -->
            <a href="{{ route('caregiver.checklists.index', $careRouteParams) }}" class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-green-500 to-emerald-600 shadow-lg shadow-green-200/50 transition-all duration-300 hover:shadow-xl hover:scale-[1.02] hover:-translate-y-1 min-h-[120px]">
                <div class="absolute top-0 right-0 -mt-6 -mr-6 w-24 h-24 rounded-full bg-white/20 blur-xl"></div>
                <div class="relative p-5 flex flex-col justify-between h-full z-10">
                    <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm w-fit">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-[900] text-white leading-tight">Checklists</h3>
                        <p class="text-green-100 text-xs font-medium mt-0.5">Daily tasks</p>
                    </div>
                    <div class="absolute bottom-4 right-4 h-8 w-8 rounded-full bg-white/20 flex items-center justify-center group-hover:bg-white group-hover:text-green-600 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                </div>
            </a>

            <!-- Health Analytics -->
            <a href="{{ route('caregiver.analytics', $careRouteParams) }}" class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-purple-500 to-violet-600 shadow-lg shadow-purple-200/50 transition-all duration-300 hover:shadow-xl hover:scale-[1.02] hover:-translate-y-1 min-h-[120px]">
                <div class="absolute top-0 right-0 -mt-6 -mr-6 w-24 h-24 rounded-full bg-white/20 blur-xl"></div>
                <div class="relative p-5 flex flex-col justify-between h-full z-10">
                    <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm w-fit">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-[900] text-white leading-tight">Analytics</h3>
                        <p class="text-purple-100 text-xs font-medium mt-0.5">View insights</p>
                    </div>
                    <div class="absolute bottom-4 right-4 h-8 w-8 rounded-full bg-white/20 flex items-center justify-center group-hover:bg-white group-hover:text-purple-600 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                </div>
            </a>

            <!-- Messages -->
            <a href="{{ route('caregiver.messages.index', $careRouteParams) }}" class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-500 to-sky-600 shadow-lg shadow-indigo-200/50 transition-all duration-300 hover:shadow-xl hover:scale-[1.02] hover:-translate-y-1 min-h-[120px]">
                <div class="absolute top-0 right-0 -mt-6 -mr-6 w-24 h-24 rounded-full bg-white/20 blur-xl"></div>
                <div class="relative p-5 flex flex-col justify-between h-full z-10">
                    <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm w-fit">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h8m-8 4h5m-7 6l-3-3H3a2 2 0 01-2-2V7a2 2 0 012-2h18a2 2 0 012 2v8a2 2 0 01-2 2h-8l-5 5z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-[900] text-white leading-tight">Messages</h3>
                        <p class="text-indigo-100 text-xs font-medium mt-0.5">Chat with patient</p>
                    </div>
                    <div class="absolute bottom-4 right-4 h-8 w-8 rounded-full bg-white/20 flex items-center justify-center group-hover:bg-white group-hover:text-indigo-600 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                </div>
            </a>

<!-- My Patients -->
            <a href="{{ route('caregiver.patients.index') }}" class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-teal-500 to-emerald-600 shadow-lg shadow-teal-200/50 transition-all duration-300 hover:shadow-xl hover:scale-[1.02] hover:-translate-y-1 min-h-[120px]">
                <div class="absolute top-0 right-0 -mt-6 -mr-6 w-24 h-24 rounded-full bg-white/20 blur-xl"></div>
                <div class="relative p-5 flex flex-col justify-between h-full z-10">
                    <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm w-fit">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-[900] text-white leading-tight">My Patients</h3>
                        <p class="text-teal-100 text-xs font-medium mt-0.5">Manage patients</p>
                    </div>
                    <div class="absolute bottom-4 right-4 h-8 w-8 rounded-full bg-white/20 flex items-center justify-center group-hover:bg-white group-hover:text-teal-600 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                </div>
            </a>

            <!-- My Profile -->
            <a href="{{ route('profile.edit') }}" class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-600 to-gray-800 shadow-lg shadow-gray-400/50 transition-all duration-300 hover:shadow-xl hover:scale-[1.02] hover:-translate-y-1 min-h-[120px]">
                <div class="absolute top-0 right-0 -mt-6 -mr-6 w-24 h-24 rounded-full bg-white/10 blur-xl"></div>
                <div class="relative p-5 flex flex-col justify-between h-full z-10">
                    <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm w-fit">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-[900] text-white leading-tight">My Profile</h3>
                        <p class="text-gray-300 text-xs font-medium mt-0.5">Edit your info</p>
                    </div>
                    <div class="absolute bottom-4 right-4 h-8 w-8 rounded-full bg-white/20 flex items-center justify-center group-hover:bg-white group-hover:text-gray-700 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                </div>
            </a>
        </div>

        <!-- ============================================ -->
        <!-- MAIN CONTENT: 2-Column Layout -->
        <!-- ============================================ -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- LEFT COLUMN (8/12): Mood + Vitals -->
            <div class="lg:col-span-8 space-y-6">
                
                <!-- MOOD TRACKER (Elder's Mood) -->
                <div class="bg-gradient-to-br from-amber-50 to-orange-100 rounded-2xl p-6 md:p-8 shadow-lg border border-amber-200 dark:from-slate-900 dark:to-slate-800 dark:border-slate-700 dark:shadow-[0_24px_60px_-30px_rgba(2,6,23,0.8)]">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-[800] text-lg text-gray-900 dark:text-slate-100 flex items-center gap-2">
                            <span class="text-2xl">😊</span> {{ $elderlyUser->name ?? 'Elder' }}'s Mood Today
                        </h3>
                        @if($mood)
                            <span class="text-xs text-gray-500 dark:text-slate-400 font-medium">{{ $mood->measured_at->diffForHumans() }}</span>
                        @endif
                    </div>
                    
                    @if($mood)
                        @php
                            $moodEmojis = [1 => '😢', 2 => '😕', 3 => '😐', 4 => '🙂', 5 => '😊'];
                            $moodLabels = [1 => 'Very Sad', 2 => 'Sad', 3 => 'Neutral', 4 => 'Happy', 5 => 'Very Happy'];
                            $moodColors = [1 => 'text-red-600', 2 => 'text-orange-500', 3 => 'text-gray-600', 4 => 'text-green-500', 5 => 'text-green-600'];
                            $moodValue = (int)$mood->value;
                        @endphp
                        <div class="flex items-center gap-6">
                            <div class="text-6xl">{{ $moodEmojis[$moodValue] ?? '😐' }}</div>
                            <div>
                                <p class="font-[900] text-2xl {{ $moodColors[$moodValue] ?? 'text-gray-600' }}">{{ $moodLabels[$moodValue] ?? 'Unknown' }}</p>
                                @if($mood->notes)
                                    <p class="text-gray-500 dark:text-slate-400 text-sm mt-1">{{ $mood->notes }}</p>
                                @endif
                            </div>
                        </div>
                        <!-- Mood Scale Indicator -->
                        <div class="mt-4 flex items-center space-x-2">
                            @foreach($moodEmojis as $level => $emoji)
                                <div class="flex-1 h-2.5 rounded-full {{ $moodValue >= $level ? 'bg-amber-400' : 'bg-gray-200 dark:bg-slate-700' }}"></div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <span class="text-5xl mb-2 block opacity-50">😶</span>
                            <p class="text-slate-600 dark:text-slate-300 italic font-semibold">No mood recorded today</p>
                        </div>
                    @endif
                </div>

                <!-- HEALTH VITALS GRID -->
                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-[800] text-xl text-gray-900">Health Vitals</h3>
                    <span class="text-xs font-bold text-gray-400 bg-white px-3 py-1.5 rounded-full border border-gray-200">Today's Records</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Vital Card: Heart Rate -->
                    <div class="bg-white rounded-[24px] p-6 shadow-md border border-gray-100 hover:shadow-lg transition-all h-44 flex flex-col justify-between group">
                        <div class="flex justify-between items-start">
                            <div class="w-12 h-12 bg-rose-50 rounded-2xl flex items-center justify-center text-rose-500 group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                            </div>
                            @if($vitals['heart_rate'])
                                <span class="text-[10px] font-bold {{ $vitals['heart_rate']['status']['bg'] }} {{ $vitals['heart_rate']['status']['text'] }} px-2 py-1 rounded-full">{{ $vitals['heart_rate']['status']['label'] }}</span>
                            @endif
                        </div>
                        <div>
                            <h4 class="font-[800] text-gray-500 text-sm uppercase tracking-wide mb-1">Heart Rate</h4>
                            @if($vitals['heart_rate'])
                                <div class="flex items-baseline gap-2">
                                    <span class="text-3xl font-[900] text-gray-900">{{ intval($vitals['heart_rate']['metric']->value) }}</span>
                                    <span class="text-base font-[700] text-gray-400">bpm</span>
                                </div>
                                <p class="text-sm font-[700] text-gray-400 mt-1">{{ $vitals['heart_rate']['metric']->measured_at->format('g:i A') }}</p>
                            @else
                                <span class="text-lg text-gray-300 font-medium">No record today</span>
                            @endif
                        </div>
                    </div>

                    <!-- Vital Card: Blood Pressure -->
                    <div class="bg-white rounded-[24px] p-6 shadow-md border border-gray-100 hover:shadow-lg transition-all h-44 flex flex-col justify-between group">
                        <div class="flex justify-between items-start">
                            <div class="w-12 h-12 bg-red-50 rounded-2xl flex items-center justify-center text-red-500 group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                            </div>
                            @if($vitals['blood_pressure'])
                                <span class="text-[10px] font-bold {{ $vitals['blood_pressure']['status']['bg'] }} {{ $vitals['blood_pressure']['status']['text'] }} px-2 py-1 rounded-full">{{ $vitals['blood_pressure']['status']['label'] }}</span>
                            @endif
                        </div>
                        <div>
                            <h4 class="font-[800] text-gray-500 text-sm uppercase tracking-wide mb-1">Blood Pressure</h4>
                            @if($vitals['blood_pressure'])
                                <div class="flex items-baseline gap-2">
                                    <span class="text-3xl font-[900] text-gray-900">{{ $vitals['blood_pressure']['metric']->value_text }}</span>
                                    <span class="text-base font-[700] text-gray-400">mmHg</span>
                                </div>
                                <p class="text-sm font-[700] text-gray-400 mt-1">{{ $vitals['blood_pressure']['metric']->measured_at->format('g:i A') }}</p>
                            @else
                                <span class="text-lg text-gray-300 font-medium">No record today</span>
                            @endif
                        </div>
                    </div>

                    <!-- Vital Card: Sugar Level -->
                    <div class="bg-white rounded-[24px] p-6 shadow-md border border-gray-100 hover:shadow-lg transition-all h-44 flex flex-col justify-between group">
                        <div class="flex justify-between items-start">
                            <div class="w-12 h-12 bg-pink-50 rounded-2xl flex items-center justify-center text-pink-500 group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                            </div>
                            @if($vitals['sugar_level'])
                                <span class="text-[10px] font-bold {{ $vitals['sugar_level']['status']['bg'] }} {{ $vitals['sugar_level']['status']['text'] }} px-2 py-1 rounded-full">{{ $vitals['sugar_level']['status']['label'] }}</span>
                            @endif
                        </div>
                        <div>
                            <h4 class="font-[800] text-gray-500 text-sm uppercase tracking-wide mb-1">Sugar Level</h4>
                            @if($vitals['sugar_level'])
                                <div class="flex items-baseline gap-2">
                                    <span class="text-3xl font-[900] text-gray-900">{{ intval($vitals['sugar_level']['metric']->value) }}</span>
                                    <span class="text-base font-[700] text-gray-400">mg/dL</span>
                                </div>
                                <p class="text-sm font-[700] text-gray-400 mt-1">{{ $vitals['sugar_level']['metric']->measured_at->format('g:i A') }}</p>
                            @else
                                <span class="text-lg text-gray-300 font-medium">No record today</span>
                            @endif
                        </div>
                    </div>

                    <!-- Vital Card: Temperature -->
                    <div class="bg-white rounded-[24px] p-6 shadow-md border border-gray-100 hover:shadow-lg transition-all h-44 flex flex-col justify-between group">
                        <div class="flex justify-between items-start">
                            <div class="w-12 h-12 bg-orange-50 rounded-2xl flex items-center justify-center text-orange-500 group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                            </div>
                            @if($vitals['temperature'])
                                <span class="text-[10px] font-bold {{ $vitals['temperature']['status']['bg'] }} {{ $vitals['temperature']['status']['text'] }} px-2 py-1 rounded-full">{{ $vitals['temperature']['status']['label'] }}</span>
                            @endif
                        </div>
                        <div>
                            <h4 class="font-[800] text-gray-500 text-sm uppercase tracking-wide mb-1">Temperature</h4>
                            @if($vitals['temperature'])
                                <div class="flex items-baseline gap-2">
                                    <span class="text-3xl font-[900] text-gray-900">{{ number_format($vitals['temperature']['metric']->value, 1) }}</span>
                                    <span class="text-base font-[700] text-gray-400">°C</span>
                                </div>
                                <p class="text-sm font-[700] text-gray-400 mt-1">{{ $vitals['temperature']['metric']->measured_at->format('g:i A') }}</p>
                            @else
                                <span class="text-lg text-gray-300 font-medium">No record today</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN (4/12): Recent Activity -->
            <div class="lg:col-span-4 space-y-6">
                
                <!-- Recent Activity -->
                <div class="bg-white rounded-[24px] shadow-md border border-gray-100 p-6 flex flex-col dark:bg-slate-900 dark:border-slate-800 dark:shadow-[0_24px_60px_-30px_rgba(2,6,23,0.8)]" style="height: 490px;">
                    <div class="flex items-center justify-between mb-4 flex-shrink-0">
                        <h3 class="font-[800] text-lg text-gray-900 dark:text-slate-100">Recent Activity</h3>
                        <span class="text-xs text-gray-400 dark:text-slate-400 font-bold">Last 7 days</span>
                    </div>
                    
                    @if($recentActivity->count() > 0)
                        <ul class="space-y-3 flex-1 overflow-y-auto pr-2" style="scrollbar-width: thin; scrollbar-color: #64748b transparent;">
                            @foreach($recentActivity as $activity)
                                @php
                                    // Determine border color based on severity or color
                                    $severity = $activity['severity'] ?? null;
                                    $color = $activity['color'] ?? 'gray';
                                    
                                    $borderClass = match($severity ?? $color) {
                                        'positive', 'green' => 'border-l-green-400',
                                        'warning', 'amber' => 'border-l-amber-400',
                                        'negative', 'red' => 'border-l-red-400',
                                        'reminder', 'blue' => 'border-l-blue-400',
                                        default => 'border-l-slate-300 dark:border-l-slate-600',
                                    };
                                @endphp
                                <li class="flex items-start gap-3 py-3 px-3 border-l-4 {{ $borderClass }} bg-gray-50/80 rounded-r-xl transition-colors hover:bg-gray-100 dark:bg-slate-800/70 dark:hover:bg-slate-800">
                                    <div class="text-xl mr-1 flex-shrink-0">{{ $activity['icon'] }}</div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-800 dark:text-slate-100 font-[700] truncate">{{ $activity['title'] }}</p>
                                        <p class="text-xs text-gray-500 dark:text-slate-400 font-medium">{{ $activity['subtitle'] }}</p>
                                    </div>
                                    <div class="text-[10px] text-gray-400 dark:text-slate-500 ml-2 whitespace-nowrap font-bold">
                                        {{ \Carbon\Carbon::parse($activity['timestamp'])->diffForHumans(null, true, true) }}
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-center py-8">
                            <div class="text-4xl mb-2 opacity-30">📭</div>
                            <p class="text-gray-400 dark:text-slate-400 text-sm font-medium">No recent activity</p>
                            <p class="text-gray-300 dark:text-slate-500 text-xs mt-1">Activity will appear here as it happens</p>
                        </div>
                    @endif
                </div>

                <!-- Quick Health Legend -->
                <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100 dark:bg-slate-900 dark:border-slate-800">
                    <h4 class="text-sm font-[800] text-gray-700 dark:text-slate-200 mb-3">Health Status Legend</h4>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="flex items-center">
                            <span class="w-3 h-3 rounded-full bg-green-500 mr-2"></span>
                            <span class="text-gray-600 dark:text-slate-300 font-medium">Normal</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-3 h-3 rounded-full bg-yellow-500 mr-2"></span>
                            <span class="text-gray-600 dark:text-slate-300 font-medium">Elevated</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-3 h-3 rounded-full bg-orange-500 mr-2"></span>
                            <span class="text-gray-600 dark:text-slate-300 font-medium">High/Fever</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-3 h-3 rounded-full bg-red-500 mr-2"></span>
                            <span class="text-gray-600 dark:text-slate-300 font-medium">Critical</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-3 h-3 rounded-full bg-blue-500 mr-2"></span>
                            <span class="text-gray-600 dark:text-slate-300 font-medium">Low</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        @endif
    </main>

    {{-- Caregiver AI Health Analyst Widget --}}
    <x-ai-chat-widget role="caregiver" />

</x-dashboard-layout>
