<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Caregiver Dashboard - SilverCare</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/icons/silvercare.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/icons/silvercare.png') }}">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Montserrat', sans-serif; }
        
        /* Scrollbar hiding */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>
<body class="bg-[#EBEBEB] min-h-screen">

    <!-- NAV BAR -->
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-[1600px] mx-auto px-6 lg:px-12 h-16 flex justify-between items-center">
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('assets/icons/silvercare.png') }}" alt="SilverCare" class="w-9 h-9 object-contain">
                    <h1 class="text-xl font-[900] tracking-tight text-gray-900 hidden sm:block">SILVER<span class="text-[#000080]">CARE</span></h1>
                </div>
                <div class="h-6 w-[1px] bg-gray-200 hidden md:block"></div>
                <div class="hidden md:block">
                    <h2 class="text-lg font-[800] text-gray-900">Caregiver Dashboard</h2>
                    <p class="text-xs text-gray-500 font-medium -mt-0.5">{{ now()->format('l, F j, Y') }}</p>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <!-- PROFILE LINK -->
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 group hover:bg-gray-50 rounded-xl py-1.5 px-2 transition-all cursor-pointer" title="Manage Profile">
                    <div class="relative">
                        <div class="w-9 h-9 rounded-full bg-purple-100 flex items-center justify-center text-purple-700 font-[900] text-base group-hover:bg-purple-600 group-hover:text-white transition-colors overflow-hidden">
                            @if(Auth::user()->profile && Auth::user()->profile->profile_photo)
                                <img src="{{ Storage::url(Auth::user()->profile->profile_photo) }}" alt="{{ Auth::user()->name }}" class="w-full h-full object-cover">
                            @else
                                {{ substr(Auth::user()->name, 0, 1) }}
                            @endif
                        </div>
                    </div>
                    <div class="hidden sm:block">
                        <p class="text-sm font-bold text-gray-900 leading-tight group-hover:text-purple-600 transition-colors">{{ Auth::user()->name }}</p>
                        <p class="text-[10px] text-gray-500 font-medium">Caregiver</p>
                    </div>
                </a>

                <!-- Logout Button -->
                <form method="POST" action="{{ route('logout') }}" class="ml-1">
                    @csrf
                    <button type="submit" class="flex items-center gap-1.5 px-3 py-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl font-bold text-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        <span class="hidden sm:inline">Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <!-- DASHBOARD CONTENT -->
    <main class="max-w-[1600px] mx-auto px-6 lg:px-12 py-5">
        
        @if(session('success'))
            <div class="mb-4 bg-green-50 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded-lg shadow-sm flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-lg shadow-sm flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                {{ session('error') }}
            </div>
        @endif

        @if(!$elderly)
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-2xl mb-6 shadow-sm">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-[800] text-yellow-800">No Elderly Assigned</h3>
                        <p class="text-sm text-yellow-700 mt-1">No elderly profile is currently associated with your account. Please contact support or complete the setup.</p>
                    </div>
                </div>
            </div>
        @else

        <!-- ============================================ -->
        <!-- TOP ROW: Elder Profile Card + Management Panel -->
        <!-- ============================================ -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

            <!-- ELDER PROFILE CARD (2 cols) -->
            <div class="lg:col-span-2 relative overflow-hidden rounded-[24px] bg-gradient-to-br from-teal-500 to-emerald-600 p-6 text-white shadow-xl shadow-teal-900/20">
                <div class="absolute top-0 right-0 -mr-10 -mt-10 h-40 w-40 rounded-full bg-white/10 blur-xl"></div>
                <div class="absolute bottom-0 left-0 -ml-8 -mb-8 h-32 w-32 rounded-full bg-black/10 blur-xl"></div>

                <div class="relative z-10 flex flex-col sm:flex-row items-start sm:items-center gap-5">
                    <!-- Avatar -->
                    <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center border-4 border-white/30 flex-shrink-0 overflow-hidden">
                        @if($elderly->profile_photo)
                            <img src="{{ Storage::url($elderly->profile_photo) }}" alt="{{ $elderlyUser->name ?? 'Elder' }}" class="w-full h-full object-cover">
                        @else
                            <span class="text-4xl sm:text-5xl font-[900] text-white/90">{{ substr($elderlyUser->name ?? $elderly->username ?? 'E', 0, 1) }}</span>
                        @endif
                    </div>

                    <!-- Elder Details -->
                    <div class="flex-1">
                        <p class="text-teal-100 text-xs font-bold uppercase tracking-wider mb-1">Your Patient</p>
                        <h2 class="text-2xl sm:text-3xl font-[900] mb-2 leading-tight">{{ $elderlyUser->name ?? $elderly->username ?? 'Elder' }}</h2>
                        <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-teal-50/90 font-medium">
                            @if($elderly->age)
                                <span class="flex items-center gap-1.5"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>{{ $elderly->age }} years old</span>
                            @endif
                            @if($elderly->sex)
                                <span class="flex items-center gap-1.5"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>{{ $elderly->sex }}</span>
                            @endif
                            @if($elderly->phone_number)
                                <span class="flex items-center gap-1.5"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>{{ $elderly->phone_number }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Medical Conditions Badge -->
                @php
                    // Check dedicated columns first, then fall back to legacy medical_info field
                    $conditions = $elderly->medical_conditions ?? [];
                    if (is_string($conditions)) { $conditions = json_decode($conditions, true) ?? []; }
                    
                    $medications = $elderly->medications ?? [];
                    if (is_string($medications)) { $medications = json_decode($medications, true) ?? []; }
                    
                    $allergies = $elderly->allergies ?? [];
                    if (is_string($allergies)) { $allergies = json_decode($allergies, true) ?? []; }
                    
                    // Fallback to legacy medical_info field if dedicated columns are empty
                    $medicalInfo = $elderly->medical_info ?? [];
                    if (is_string($medicalInfo)) { $medicalInfo = json_decode($medicalInfo, true) ?? []; }
                    
                    if (empty($conditions) && !empty($medicalInfo['conditions'])) {
                        $conditions = $medicalInfo['conditions'];
                    }
                    if (empty($medications) && !empty($medicalInfo['medications'])) {
                        $medications = $medicalInfo['medications'];
                    }
                    if (empty($allergies) && !empty($medicalInfo['allergies'])) {
                        $allergies = $medicalInfo['allergies'];
                    }
                @endphp
                @if(!empty($conditions) || !empty($medications) || !empty($allergies))
                    <div class="relative z-10 mt-5 pt-5 border-t border-white/20">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            @if(!empty($conditions))
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-teal-100 mb-2.5">Known Conditions</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($conditions as $condition)
                                        <span class="bg-white/20 backdrop-blur-sm text-white text-sm font-bold px-3 py-1.5 rounded-full">{{ $condition }}</span>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                            @if(!empty($medications))
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-blue-100 mb-2.5">💊 Medications</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($medications as $med)
                                        <span class="bg-blue-500/30 backdrop-blur-sm text-white text-sm font-bold px-3 py-1.5 rounded-full">{{ $med }}</span>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                            @if(!empty($allergies))
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-amber-200 mb-2.5">⚠️ Allergies</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($allergies as $allergy)
                                        <span class="bg-amber-500/30 backdrop-blur-sm text-white text-sm font-bold px-3 py-1.5 rounded-full">{{ $allergy }}</span>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- TODAY'S STATS CARD (1 col) -->
            <div class="bg-white rounded-[24px] p-6 shadow-md border border-gray-100 flex flex-col">
                <h3 class="font-[800] text-lg text-gray-900 mb-4 flex items-center gap-2">
                    <span class="text-xl">📊</span> Today's Summary
                </h3>
                @if(!empty($stats))
                <div class="space-y-4 flex-1">
                    <!-- Medication Stat -->
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm text-gray-600 font-medium">Medication</span>
                            <span class="text-sm font-bold {{ $stats['medication_adherence'] === 100 ? 'text-green-600' : ($stats['medication_adherence'] >= 50 ? 'text-yellow-600' : 'text-gray-500') }}">
                                @if($stats['medication_adherence'] !== null) {{ $stats['medication_adherence'] }}% @else N/A @endif
                            </span>
                        </div>
                        <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all {{ $stats['medication_adherence'] === 100 ? 'bg-green-500' : ($stats['medication_adherence'] >= 50 ? 'bg-yellow-400' : 'bg-gray-300') }}" style="width: {{ $stats['medication_adherence'] ?? 0 }}%"></div>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">{{ $stats['doses_taken'] }}/{{ $stats['doses_total'] }} doses taken</p>
                    </div>
                    <!-- Task Stat -->
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm text-gray-600 font-medium">Tasks</span>
                            <span class="text-sm font-bold {{ $stats['task_completion'] === 100 ? 'text-green-600' : ($stats['task_completion'] >= 50 ? 'text-yellow-600' : 'text-gray-500') }}">
                                @if($stats['task_completion'] !== null) {{ $stats['task_completion'] }}% @else N/A @endif
                            </span>
                        </div>
                        <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all {{ $stats['task_completion'] === 100 ? 'bg-green-500' : ($stats['task_completion'] >= 50 ? 'bg-yellow-400' : 'bg-gray-300') }}" style="width: {{ $stats['task_completion'] ?? 0 }}%"></div>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">{{ $stats['tasks_completed'] }}/{{ $stats['tasks_total'] }} completed</p>
                    </div>
                    <!-- Vitals Stat -->
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm text-gray-600 font-medium">Vitals Recorded</span>
                            <span class="text-sm font-bold {{ $stats['vitals_recorded'] === $stats['vitals_total'] ? 'text-green-600' : 'text-blue-600' }}">{{ $stats['vitals_recorded'] }}/{{ $stats['vitals_total'] }}</span>
                        </div>
                        <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                            @php $vp = $stats['vitals_total'] > 0 ? ($stats['vitals_recorded'] / $stats['vitals_total']) * 100 : 0; @endphp
                            <div class="h-full bg-blue-500 rounded-full transition-all" style="width: {{ $vp }}%"></div>
                        </div>
                    </div>
                </div>
                @else
                    <p class="text-gray-400 text-sm">No stats available.</p>
                @endif
            </div>

        </div>

        <!-- ============================================ -->
        <!-- CARE MANAGEMENT PANEL (Action Buttons) -->
        <!-- ============================================ -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            
            <!-- Manage Medications -->
            <a href="{{ route('caregiver.medications.index') }}" class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 shadow-lg shadow-blue-200/50 transition-all duration-300 hover:shadow-xl hover:scale-[1.02] hover:-translate-y-1 min-h-[120px]">
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
            <a href="{{ route('caregiver.checklists.index') }}" class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-green-500 to-emerald-600 shadow-lg shadow-green-200/50 transition-all duration-300 hover:shadow-xl hover:scale-[1.02] hover:-translate-y-1 min-h-[120px]">
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
            <a href="{{ route('caregiver.analytics') }}" class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-purple-500 to-violet-600 shadow-lg shadow-purple-200/50 transition-all duration-300 hover:shadow-xl hover:scale-[1.02] hover:-translate-y-1 min-h-[120px]">
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
                <div class="bg-gradient-to-br from-amber-50 to-orange-100 rounded-2xl p-6 md:p-8 shadow-lg border border-amber-200">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-[800] text-lg text-gray-900 flex items-center gap-2">
                            <span class="text-2xl">😊</span> {{ $elderlyUser->name ?? 'Elder' }}'s Mood Today
                        </h3>
                        @if($mood)
                            <span class="text-xs text-gray-500 font-medium">{{ $mood->measured_at->diffForHumans() }}</span>
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
                                    <p class="text-gray-500 text-sm mt-1">{{ $mood->notes }}</p>
                                @endif
                            </div>
                        </div>
                        <!-- Mood Scale Indicator -->
                        <div class="mt-4 flex items-center space-x-2">
                            @foreach($moodEmojis as $level => $emoji)
                                <div class="flex-1 h-2.5 rounded-full {{ $moodValue >= $level ? 'bg-amber-400' : 'bg-gray-200' }}"></div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <span class="text-5xl mb-2 block opacity-50">😶</span>
                            <p class="text-gray-400 italic font-medium">No mood recorded today</p>
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
                <div class="bg-white rounded-[24px] shadow-md border border-gray-100 p-6 flex flex-col" style="height: 490px;">
                    <div class="flex items-center justify-between mb-4 flex-shrink-0">
                        <h3 class="font-[800] text-lg text-gray-900">Recent Activity</h3>
                        <span class="text-xs text-gray-400 font-bold">Last 7 days</span>
                    </div>
                    
                    @if($recentActivity->count() > 0)
                        <ul class="space-y-3 flex-1 overflow-y-auto pr-2" style="scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent;">
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
                                        default => 'border-l-gray-200',
                                    };
                                @endphp
                                <li class="flex items-start py-2 pl-3 border-l-4 {{ $borderClass }} bg-gray-50/50 rounded-r-lg">
                                    <div class="text-xl mr-3 flex-shrink-0">{{ $activity['icon'] }}</div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-800 font-[700] truncate">{{ $activity['title'] }}</p>
                                        <p class="text-xs text-gray-500 font-medium">{{ $activity['subtitle'] }}</p>
                                    </div>
                                    <div class="text-[10px] text-gray-400 ml-2 whitespace-nowrap font-bold">
                                        {{ \Carbon\Carbon::parse($activity['timestamp'])->diffForHumans(null, true, true) }}
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-center py-8">
                            <div class="text-4xl mb-2 opacity-30">📭</div>
                            <p class="text-gray-400 text-sm font-medium">No recent activity</p>
                            <p class="text-gray-300 text-xs mt-1">Activity will appear here as it happens</p>
                        </div>
                    @endif
                </div>

                <!-- Quick Health Legend -->
                <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100">
                    <h4 class="text-sm font-[800] text-gray-700 mb-3">Health Status Legend</h4>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="flex items-center">
                            <span class="w-3 h-3 rounded-full bg-green-500 mr-2"></span>
                            <span class="text-gray-600 font-medium">Normal</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-3 h-3 rounded-full bg-yellow-500 mr-2"></span>
                            <span class="text-gray-600 font-medium">Elevated</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-3 h-3 rounded-full bg-orange-500 mr-2"></span>
                            <span class="text-gray-600 font-medium">High/Fever</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-3 h-3 rounded-full bg-red-500 mr-2"></span>
                            <span class="text-gray-600 font-medium">Critical</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-3 h-3 rounded-full bg-blue-500 mr-2"></span>
                            <span class="text-gray-600 font-medium">Low</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        @endif
    </main>

</body>
</html>
