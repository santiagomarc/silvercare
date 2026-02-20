<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $config['name'] }} - SilverCare</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Montserrat', sans-serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in { animation: fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
        
        /* Staggered animation delays */
        .stagger-1 { animation-delay: 0.05s; }
        .stagger-2 { animation-delay: 0.1s; }
        .stagger-3 { animation-delay: 0.15s; }
        .stagger-4 { animation-delay: 0.2s; }
        .stagger-5 { animation-delay: 0.25s; }
    </style>
</head>
<body class="bg-[#F1F5F9] min-h-screen text-gray-800">

    <nav class="bg-white shadow-md sticky top-0 z-40 border-b border-gray-200">
        <div class="max-w-[1600px] mx-auto px-6 lg:px-12 h-20 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="{{ route('dashboard') }}" class="group flex items-center gap-2 px-4 py-2 rounded-xl bg-gray-50 hover:bg-gray-100 border border-gray-200 transition-all">
                    <svg class="w-5 h-5 text-gray-600 group-hover:text-gray-900 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    <span class="font-[700] text-gray-700 group-hover:text-gray-900">Back</span>
                </a>
            </div>
            
            <h1 class="text-2xl font-[900] text-gray-900 flex items-center gap-3 tracking-tight">
                <span class="text-3xl filter drop-shadow-sm">{{ $config['icon'] }}</span>
                {{ $config['name'] }}
            </h1>
            
            <div class="w-24"></div> </div>
    </nav>

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

    <main class="max-w-4xl mx-auto px-6 py-10 space-y-8">
        
        @if(session('success'))
            <div class="fade-in bg-green-50 border-l-8 border-green-500 text-green-800 px-6 py-4 rounded-xl shadow-sm flex items-center gap-4">
                <div class="bg-green-100 p-2 rounded-full"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg></div>
                <span class="font-bold text-lg">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="fade-in bg-red-50 border-l-8 border-red-500 text-red-800 px-6 py-4 rounded-xl shadow-sm flex items-center gap-4">
                <div class="bg-red-100 p-2 rounded-full"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg></div>
                <span class="font-[800] text-lg">{{ session('error') }}</span>
            </div>
        @endif

        @if($stats['count'] > 0)
        <!-- Integrated Stats Hero Card -->
        <div class="bg-gradient-to-r {{ $colors['gradient'] }} rounded-3xl p-6 lg:p-8 shadow-xl fade-in stagger-1 relative overflow-hidden">
            <!-- Background decoration -->
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full"></div>
            <div class="absolute -bottom-16 -left-16 w-48 h-48 bg-white/5 rounded-full"></div>
            
            <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <!-- Left: Latest Reading -->
                <div class="text-white">
                    <p class="text-sm uppercase tracking-widest font-[800] text-white/70 mb-2">LATEST READING</p>
                    <div class="flex items-baseline gap-3">
                        <span class="text-6xl lg:text-7xl font-[900] tracking-tight">
                            @if($type === 'blood_pressure')
                                {{ $stats['latest']->value_text ?? '-' }}
                            @else
                                {{ $type === 'temperature' ? number_format($stats['latest']->value, 1) : intval($stats['latest']->value) }}
                            @endif
                        </span>
                        <span class="text-2xl font-[800] text-white/80">{{ $config['unit'] }}</span>
                    </div>
                    <p class="text-base font-[700] text-white/70 mt-2">
                        Measured {{ $stats['latest']->measured_at->diffForHumans() }}
                    </p>
                </div>
                
                <!-- Right: Stats Grid -->
                @if($type !== 'blood_pressure')
                <div class="flex gap-3">
                    <!-- Average -->
                    <div class="bg-white/20 backdrop-blur-sm rounded-2xl px-6 py-5 text-center min-w-[100px] border border-white/20">
                        <p class="text-xs uppercase tracking-wider font-[800] text-white/70 mb-1">AVG</p>
                        <p class="text-3xl font-[900] text-white">{{ $stats['avg'] ?? '-' }}</p>
                    </div>
                    <!-- Min/Max Stacked -->
                    <div class="flex flex-col gap-2">
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl px-5 py-3 flex items-center gap-4 border border-white/20">
                            <span class="text-xs uppercase tracking-wider font-[800] text-white/70">MIN</span>
                            <span class="text-xl font-[900] text-white">{{ number_format($stats['min'], $type === 'temperature' ? 2 : 0) }}</span>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl px-5 py-3 flex items-center gap-4 border border-white/20">
                            <span class="text-xs uppercase tracking-wider font-[800] text-white/70">MAX</span>
                            <span class="text-xl font-[900] text-white">{{ number_format($stats['max'], $type === 'temperature' ? 2 : 0) }}</span>
                        </div>
                    </div>
                </div>
                @else
                <!-- Blood Pressure: Show total entries instead -->
                <div class="bg-white/20 backdrop-blur-sm rounded-2xl px-8 py-5 text-center border border-white/20">
                    <p class="text-xs uppercase tracking-wider font-[800] text-white/70 mb-1">TOTAL ENTRIES</p>
                    <p class="text-4xl font-[900] text-white">{{ $stats['count'] }}</p>
                    <p class="text-sm font-[700] text-white/60 mt-1">Last 30 Days</p>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- UNIFIED ACTION CARD: Manual Record + Google Fit -->
        <div class="bg-white rounded-3xl shadow-lg border border-gray-200 overflow-hidden fade-in stagger-2">
            <div class="flex flex-col md:flex-row">
                <!-- LEFT: Manual Record Button -->
                <button onclick="openRecordModal()" class="flex-1 p-6 lg:p-8 bg-gradient-to-br {{ $colors['gradient'] }} text-white hover:opacity-95 transition-all group relative overflow-hidden">
                    <!-- Background decoration -->
                    <div class="absolute -top-8 -right-8 w-32 h-32 bg-white/10 rounded-full"></div>
                    <div class="absolute -bottom-12 -left-12 w-40 h-40 bg-white/5 rounded-full"></div>
                    
                    <div class="relative flex items-center gap-5">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </div>
                        <div class="text-left">
                            <h3 class="font-[900] text-xl lg:text-2xl mb-1">Record Manually</h3>
                            <p class="text-white/80 font-[600] text-sm lg:text-base">Enter your {{ strtolower($config['name']) }} reading</p>
                        </div>
                    </div>
                </button>

                <!-- CURVED SEPARATOR -->
                <div class="hidden md:block relative w-0">
                    <svg class="absolute top-0 -left-6 h-full w-12 text-white z-10" viewBox="0 0 48 100" preserveAspectRatio="none">
                        <path d="M0,0 Q24,50 0,100 L48,100 L48,0 Z" fill="currentColor"/>
                    </svg>
                </div>
                <!-- Mobile separator -->
                <div class="md:hidden h-0.5 bg-gradient-to-r from-transparent via-gray-200 to-transparent"></div>

                <!-- RIGHT: Google Fit Section -->
                <div class="flex-1 p-6 lg:p-8 bg-white">
                    @if($supportsGoogleFit)
                        <div class="flex items-center gap-5 h-full">
                            <div class="w-16 h-16 bg-gray-50 border-2 border-gray-100 rounded-2xl shadow-sm flex items-center justify-center shrink-0">
                                <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none">
                                    <path d="M22.5 12.06c0-.82-.07-1.6-.2-2.36H12v4.49h5.88a5.03 5.03 0 0 1-2.18 3.3v2.74h3.53c2.06-1.9 3.25-4.7 3.25-7.17z" fill="#4285F4"/>
                                    <path d="M12 22.5c2.95 0 5.43-.98 7.24-2.66l-3.53-2.74c-.98.66-2.23 1.05-3.71 1.05-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.08 7.7 22.5 12 22.5z" fill="#34A853"/>
                                    <path d="M5.84 13.62a6.52 6.52 0 0 1-.43-2.62c0-.91.16-1.78.43-2.62V5.54H2.18A10.49 10.49 0 0 0 0 12c0 1.68.4 3.29 1.18 4.76l3.66-2.84V13.62z" fill="#FBBC05"/>
                                    <path d="M12 5.03c1.61 0 3.05.55 4.19 1.64l3.15-3.15C17.43 1.63 14.95.5 12 .5 7.7.5 3.99 2.92 2.18 6.54l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                                </svg>
                            </div>
                            <div class="flex-grow">
                                <h3 class="font-[900] text-xl text-gray-900 mb-1">Google Fit</h3>
                                @if($googleFitConnected)
                                    <p class="text-sm font-[600] text-green-600 flex items-center gap-1.5">
                                        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                        Connected & syncing
                                    </p>
                                @else
                                    <p class="text-sm font-[600] text-gray-500">Auto-sync your data</p>
                                @endif
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                @if($googleFitConnected)
                                    <button onclick="syncGoogleFit()" id="syncBtn" class="px-5 py-3 bg-blue-50 text-blue-700 font-[800] rounded-xl hover:bg-blue-100 hover:shadow-md transition-all flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                        Sync
                                    </button>
                                    <form action="{{ route('elderly.googlefit.disconnect') }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-sm font-[700] text-gray-400 hover:text-red-500 hover:bg-red-50 px-3 py-1.5 rounded-lg transition-all">
                                            Unlink
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('elderly.googlefit.connect') }}" class="px-5 py-3 bg-gray-900 text-white font-[800] rounded-xl hover:bg-gray-800 hover:shadow-lg transition-all flex items-center gap-2">
                                        Connect
                                    </a>
                                @endif
                            </div>
                        </div>
                        
                        @if($googleFitConnected)
                        <div id="autoSyncStatus" class="mt-4 pt-4 border-t border-gray-100 text-sm font-[700] text-gray-500 flex items-center gap-2">
                            <div class="w-2.5 h-2.5 bg-gray-300 rounded-full"></div>
                            <span>Status: Idle</span>
                        </div>
                        @endif
                    @else
                        <!-- Non-Google Fit supported vital -->
                        <div class="flex items-center gap-5 h-full">
                            <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-[900] text-xl text-gray-900 mb-1">Manual Entry Only</h3>
                                <p class="text-sm font-[600] text-gray-500">This vital requires manual recording</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-md border border-gray-200 overflow-hidden fade-in stagger-3">
            <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                <h2 class="font-[900] text-2xl text-gray-900">Recent History</h2>
                <p class="text-base font-[600] text-gray-500 mt-1">Your logs for the past 30 days</p>
            </div>
            
            <div class="divide-y divide-gray-100 max-h-[600px] overflow-y-auto custom-scrollbar">
                @forelse($metrics as $metric)
                    @php
                       // Logic for Health Status Labels (Retained from original)
                       $recordStatus = null;
                       // ... (Keep existing extensive PHP logic for $recordStatus here - implied for brevity, copying verbatim from prompt logic)
                       if ($type === 'blood_pressure' && $metric->value_text) {
                            $parts = explode('/', $metric->value_text);
                            if (count($parts) === 2) {
                                $sys = intval($parts[0]); $dia = intval($parts[1]);
                                if ($sys >= 180 || $dia >= 120) $recordStatus = ['label' => 'Critical', 'bg' => 'bg-red-100', 'text' => 'text-red-700'];
                                elseif ($sys >= 140 || $dia >= 90) $recordStatus = ['label' => 'High', 'bg' => 'bg-orange-100', 'text' => 'text-orange-700'];
                                elseif ($sys >= 130 || $dia >= 80) $recordStatus = ['label' => 'Elevated', 'bg' => 'bg-yellow-100', 'text' => 'text-yellow-700'];
                                elseif ($sys < 90 || $dia < 60) $recordStatus = ['label' => 'Low', 'bg' => 'bg-blue-100', 'text' => 'text-blue-700'];
                                else $recordStatus = ['label' => 'Normal', 'bg' => 'bg-green-100', 'text' => 'text-green-700'];
                            }
                        } elseif ($type === 'sugar_level' && $metric->value) {
                            $val = floatval($metric->value);
                            if ($val >= 250) $recordStatus = ['label' => 'Critical', 'bg' => 'bg-red-100', 'text' => 'text-red-700'];
                            elseif ($val >= 180) $recordStatus = ['label' => 'High', 'bg' => 'bg-orange-100', 'text' => 'text-orange-700'];
                            elseif ($val < 70) $recordStatus = ['label' => 'Low', 'bg' => 'bg-blue-100', 'text' => 'text-blue-700'];
                            else $recordStatus = ['label' => 'Normal', 'bg' => 'bg-green-100', 'text' => 'text-green-700'];
                        } elseif ($type === 'temperature' && $metric->value) {
                            $val = floatval($metric->value);
                            if ($val >= 38.0) $recordStatus = ['label' => 'Fever', 'bg' => 'bg-red-100', 'text' => 'text-red-700'];
                            else $recordStatus = ['label' => 'Normal', 'bg' => 'bg-green-100', 'text' => 'text-green-700'];
                        } elseif ($type === 'heart_rate' && $metric->value) {
                            $val = floatval($metric->value);
                            if ($val >= 100) $recordStatus = ['label' => 'High', 'bg' => 'bg-orange-100', 'text' => 'text-orange-700'];
                            elseif ($val < 60) $recordStatus = ['label' => 'Low', 'bg' => 'bg-blue-100', 'text' => 'text-blue-700'];
                            else $recordStatus = ['label' => 'Normal', 'bg' => 'bg-green-100', 'text' => 'text-green-700'];
                        }
                    @endphp

                    <div class="group p-5 lg:p-6 hover:bg-gray-50 transition-colors flex items-center justify-between">
                        <div class="flex items-center gap-5">
                            <div class="w-16 h-16 {{ $colors['bg'] }} rounded-2xl flex items-center justify-center text-3xl shadow-sm border {{ $colors['border'] }}">
                                {{ $config['icon'] }}
                            </div>
                            
                            <div>
                                <div class="flex items-baseline gap-3 flex-wrap">
                                    <span class="text-3xl lg:text-4xl font-[900] text-gray-900 tracking-tight">
                                        @if($type === 'blood_pressure')
                                            {{ $metric->value_text }}
                                        @elseif($type === 'temperature')
                                            {{ number_format($metric->value, 1) }}
                                        @else
                                            {{ intval($metric->value) }}
                                        @endif
                                    </span>
                                    <span class="text-base font-[700] text-gray-400">{{ $config['unit'] }}</span>
                                    
                                    @if($recordStatus)
                                        <span class="px-3 py-1 rounded-full text-xs font-[800] uppercase tracking-wide {{ $recordStatus['bg'] }} {{ $recordStatus['text'] }}">
                                            {{ $recordStatus['label'] }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-base font-[700] text-gray-500 mt-1">
                                    {{ $metric->measured_at->format('M j, Y') }} 
                                    <span class="text-gray-300 mx-1">•</span> 
                                    {{ $metric->measured_at->format('g:i A') }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            @if($metric->source === 'google_fit')
                                <div class="px-3 py-2 bg-gray-50 rounded-xl flex items-center gap-2 shadow-sm border border-gray-200" title="Synced from Google Fit">
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                                        <path d="M22.5 12.06c0-.82-.07-1.6-.2-2.36H12v4.49h5.88a5.03 5.03 0 0 1-2.18 3.3v2.74h3.53c2.06-1.9 3.25-4.7 3.25-7.17z" fill="#4285F4"/>
                                        <path d="M12 22.5c2.95 0 5.43-.98 7.24-2.66l-3.53-2.74c-.98.66-2.23 1.05-3.71 1.05-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.08 7.7 22.5 12 22.5z" fill="#34A853"/>
                                        <path d="M5.84 13.62a6.52 6.52 0 0 1-.43-2.62c0-.91.16-1.78.43-2.62V5.54H2.18A10.49 10.49 0 0 0 0 12c0 1.68.4 3.29 1.18 4.76l3.66-2.84V13.62z" fill="#FBBC05"/>
                                        <path d="M12 5.03c1.61 0 3.05.55 4.19 1.64l3.15-3.15C17.43 1.63 14.95.5 12 .5 7.7.5 3.99 2.92 2.18 6.54l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                                    </svg>
                                    <span class="text-sm font-[800] text-gray-700">Google Fit</span>
                                </div>
                            @else
                                <div class="px-3 py-2 bg-gray-200 text-gray-600 rounded-xl flex items-center gap-2 shadow-sm border border-gray-300" title="Manually Entered">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    <span class="text-sm font-[800]">MANUAL</span>
                                </div>
                            @endif

                            <div class="w-px h-8 bg-gray-200 mx-1"></div>

                            <button onclick="deleteRecord({{ $metric->id }})" class="p-2.5 rounded-lg text-gray-400 hover:text-white hover:bg-red-500 transition-all duration-200 group-hover:border-red-500" title="Delete Entry">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="py-20 text-center flex flex-col items-center justify-center">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center text-4xl mb-4 text-gray-400 grayscale opacity-50">
                            {{ $config['icon'] }}
                        </div>
                        <h3 class="font-[800] text-xl text-gray-400 mb-2">No Records Yet</h3>
                        <p class="text-gray-400 max-w-xs mx-auto mb-6">Start tracking your health trends by adding your first reading above.</p>
                    </div>
                @endforelse
            </div>
        </div>

    </main>

    <div id="recordModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4 transition-all duration-300 opacity-0" style="transition: opacity 0.3s;">
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
                        <label class="block text-xs font-bold text-gray-500 mb-1.5 ml-1">Date</label>
                        <input type="date" id="dateInput" name="date" value="{{ now()->format('Y-m-d') }}"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-{{ $config['color'] }}-500 focus:ring-0 font-semibold text-gray-900 cursor-pointer">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1.5 ml-1">Time</label>
                        <input type="time" id="timeInput" name="time" value="{{ now()->format('H:i') }}"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-{{ $config['color'] }}-500 focus:ring-0 font-semibold text-gray-900 cursor-pointer">
                    </div>
                </div>

                <div class="mb-8">
                    <label class="block text-xs font-bold text-gray-500 mb-1.5 ml-1">Notes (Optional)</label>
                    <textarea id="notesInput" name="notes" placeholder="Add any details about how you felt..." rows="2"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-{{ $config['color'] }}-500 focus:ring-0 text-sm resize-none"></textarea>
                </div>

                <button type="submit" id="submitBtn" class="w-full py-4 text-lg {{ $colors['btn'] }} text-white font-[800] rounded-xl transition-all transform hover:scale-[1.02] active:scale-[0.98] shadow-lg flex items-center justify-center gap-2">
                    Save Record <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                </button>
            </form>
        </div>
    </div>

    <script>
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;
        const VITAL_TYPE = '{{ $type }}';
        const GOOGLE_FIT_CONNECTED = {{ $googleFitConnected ? 'true' : 'false' }};
        const SUPPORTS_GOOGLE_FIT = {{ $supportsGoogleFit ? 'true' : 'false' }};

        // --- Animations for Modal ---
        const modal = document.getElementById('recordModal');
        const modalContent = document.getElementById('recordModalContent');

        function openRecordModal() {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            // Allow display:flex to apply before changing opacity
            requestAnimationFrame(() => {
                modal.classList.remove('opacity-0');
                modalContent.classList.remove('scale-95');
                modalContent.classList.add('scale-100');
            });
            document.body.style.overflow = 'hidden';
            
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
            try {
                const response = await fetch('/google-fit/sync', {
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
            }
        }

        async function syncGoogleFit() {
            const syncBtn = document.getElementById('syncBtn');
            if (!syncBtn) return;
            const originalContent = syncBtn.innerHTML;
            syncBtn.disabled = true;
            syncBtn.innerHTML = '<span>Checking...</span>';

            try {
                const response = await fetch('/google-fit/sync', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Sync failed');

                if (data.synced && Object.keys(data.synced).length > 0) {
                    showToast('Data Synced Successfully', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast('Already up to date', 'info');
                }
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                syncBtn.disabled = false;
                syncBtn.innerHTML = originalContent;
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
                showToast('Record saved successfully!', 'success');
                setTimeout(() => window.location.reload(), 500);

            } catch (error) {
                showToast(error.message, 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalContent;
            }
        }

        async function deleteRecord(id) {
            if (!confirm('Are you sure you want to remove this record?')) return;
            try {
                const response = await fetch(`/my-vitals/${id}`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) throw new Error('Failed to delete');
                showToast('Record deleted', 'success');
                setTimeout(() => window.location.reload(), 500);
            } catch (error) {
                showToast(error.message, 'error');
            }
        }

        // --- Toasts ---
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            const bgClass = type === 'success' ? 'bg-green-600' : type === 'error' ? 'bg-red-600' : 'bg-gray-800';
            toast.className = `fixed bottom-8 left-1/2 -translate-x-1/2 ${bgClass} text-white px-8 py-4 rounded-2xl shadow-2xl z-[60] font-bold text-base transform transition-all duration-300 translate-y-10 opacity-0 flex items-center gap-3`;
            toast.innerHTML = type === 'success' ? `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>${message}` : message;
            
            document.body.appendChild(toast);
            
            requestAnimationFrame(() => {
                toast.classList.remove('translate-y-10', 'opacity-0');
            });
            
            setTimeout(() => {
                toast.classList.add('translate-y-10', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Modal triggers
        document.getElementById('recordModal').addEventListener('click', function(e) {
            if (e.target === this) closeRecordModal();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeRecordModal();
        });
    </script>
</body>
</html>