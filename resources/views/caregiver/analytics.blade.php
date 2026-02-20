<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Health Analytics - SilverCare</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/icons/silvercare.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/icons/silvercare.png') }}">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Montserrat', sans-serif; }
        .period-btn.active { background: white; color: #1f2937; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .health-ring { transition: stroke-dashoffset 1s ease-out; }
    </style>
</head>
<body class="bg-[#EBEBEB] min-h-screen">

    <!-- NAV BAR -->
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-[1600px] mx-auto px-6 lg:px-12 h-16 flex justify-between items-center">
            <div class="flex items-center gap-6">
                <a href="{{ route('caregiver.dashboard') }}" class="flex items-center gap-3 group">
                    <img src="{{ asset('assets/icons/silvercare.png') }}" alt="SilverCare" class="w-9 h-9 object-contain group-hover:scale-105 transition-transform">
                    <h1 class="text-xl font-[900] tracking-tight text-gray-900 hidden sm:block">SILVER<span class="text-[#000080]">CARE</span></h1>
                </a>
                <div class="h-6 w-[1px] bg-gray-200 hidden md:block"></div>
                <div class="hidden md:block">
                    <h2 class="text-lg font-[800] text-gray-900">Health Analytics</h2>
                    <p class="text-xs text-gray-500 font-medium -mt-0.5">{{ $elderlyUser->name ?? 'Elder' }}'s health insights</p>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <!-- Time Period Selector -->
                <div class="flex bg-gray-100 rounded-xl p-1 hidden md:flex">
                    <button onclick="changePeriod('7days')" class="period-btn active px-4 py-2 rounded-lg text-sm font-[700] transition-all" data-period="7days">Week</button>
                    <button onclick="changePeriod('30days')" class="period-btn px-4 py-2 rounded-lg text-sm font-[700] transition-all" data-period="30days">Month</button>
                    <button onclick="changePeriod('90days')" class="period-btn px-4 py-2 rounded-lg text-sm font-[700] transition-all" data-period="90days">3 Months</button>
                </div>
                
                <!-- Export PDF Button -->
                <a href="{{ route('caregiver.analytics.export') }}" class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-[#000080] to-blue-700 hover:from-blue-800 hover:to-blue-600 text-white rounded-xl font-bold text-sm transition-all shadow-lg shadow-blue-500/20 hover:shadow-blue-500/40">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span class="hidden sm:inline">Export Report</span>
                </a>

                <a href="{{ route('caregiver.dashboard') }}" class="flex items-center gap-1.5 px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-bold text-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    <span class="hidden sm:inline">Back</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="max-w-[1600px] mx-auto px-6 lg:px-12 py-6">
        
        @if(!$elderly)
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-2xl mb-6 shadow-sm">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-[800] text-yellow-800">No Elder Assigned</h3>
                        <p class="text-sm text-yellow-700 mt-1">Analytics will be available once an elder is assigned to your account.</p>
                    </div>
                </div>
            </div>
        @else

        <!-- HEALTH SCORE + QUICK STATS ROW -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            
            <!-- Health Score Card -->
            <div class="lg:col-span-1 bg-gradient-to-br from-{{ $healthColor }}-500 to-{{ $healthColor }}-600 rounded-[24px] p-6 text-white relative overflow-hidden shadow-xl">
                <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full"></div>
                <div class="absolute -bottom-8 -left-8 w-32 h-32 bg-white/5 rounded-full"></div>
                
                <div class="relative">
                    <div class="flex items-center gap-2 mb-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                        <h3 class="text-sm font-[800] uppercase tracking-wider text-white/80">Health Score</h3>
                    </div>
                    
                    <div class="flex items-center gap-6">
                        <!-- Ring Chart -->
                        <div class="relative w-28 h-28 flex-shrink-0">
                            <svg class="w-full h-full -rotate-90" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="42" stroke="rgba(255,255,255,0.2)" stroke-width="8" fill="none"/>
                                <circle cx="50" cy="50" r="42" stroke="white" stroke-width="8" fill="none" 
                                    stroke-dasharray="264" 
                                    stroke-dashoffset="{{ 264 - (264 * $healthScore / 100) }}"
                                    stroke-linecap="round"
                                    class="health-ring"/>
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-3xl font-[900]">{{ $healthScore }}</span>
                            </div>
                        </div>
                        
                        <div class="flex-grow">
                            <p class="text-2xl font-[900] mb-1">{{ $healthLabel }}</p>
                            <p class="text-sm font-[600] text-white/70 mb-3">Based on {{ $totalFactors ?? 0 }} tracked vitals</p>
                            
                            @if(isset($healthFactors) && count($healthFactors) > 0)
                            <div class="flex flex-wrap gap-2">
                                @foreach($healthFactors as $type => $factor)
                                <span class="px-2 py-1 bg-white/20 rounded-lg text-xs font-[700]">
                                    {{ $analyticsData[$type]['config']['icon'] }} {{ $factor['status'] }}
                                </span>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Stats Grid -->
            <div class="lg:col-span-2 grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl p-5 shadow-md border border-gray-100">
                    <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <p class="text-xs font-[700] text-gray-500 uppercase">Total Readings</p>
                    <p class="text-2xl font-[900] text-gray-900 mt-1">{{ $totalReadings }}</p>
                </div>
                
                <div class="bg-white rounded-2xl p-5 shadow-md border border-gray-100">
                    <div class="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <p class="text-xs font-[700] text-gray-500 uppercase">This Week</p>
                    <p class="text-2xl font-[900] text-gray-900 mt-1">{{ $readingsThisWeek }}</p>
                </div>
                
                <div class="bg-white rounded-2xl p-5 shadow-md border border-gray-100">
                    <div class="w-10 h-10 bg-purple-50 rounded-xl flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                        </svg>
                    </div>
                    <p class="text-xs font-[700] text-gray-500 uppercase">Med Adherence</p>
                    <p class="text-2xl font-[900] text-gray-900 mt-1">{{ $medicationSummary['adherenceRate'] ?? '—' }}%</p>
                </div>
                
                <div class="bg-white rounded-2xl p-5 shadow-md border border-gray-100">
                    <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                        </svg>
                    </div>
                    <p class="text-xs font-[700] text-gray-500 uppercase">Task Completion</p>
                    <p class="text-2xl font-[900] text-gray-900 mt-1">{{ $taskSummary['completionRate'] ?? '—' }}%</p>
                </div>
            </div>
        </div>

        <!-- VITALS ANALYTICS CARDS (with Charts) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            @foreach($analyticsData as $type => $data)
            <div class="bg-white rounded-[24px] shadow-md border border-gray-100 overflow-hidden">
                <!-- Card Header -->
                <div class="bg-gradient-to-r from-{{ $data['config']['color'] }}-50 to-{{ $data['config']['color'] }}-100/50 p-5 border-b border-{{ $data['config']['color'] }}-100">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-2xl shadow-sm">
                                {{ $data['config']['icon'] }}
                            </div>
                            <div>
                                <h3 class="text-lg font-[900] text-gray-900">{{ $data['config']['name'] }}</h3>
                                <p class="text-sm font-[600] text-gray-500">{{ $data['config']['unit'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Body -->
                <div class="p-5">
                    @foreach(['7days', '30days', '90days'] as $period)
                    <div class="period-data {{ $period !== '7days' ? 'hidden' : '' }}" data-period="{{ $period }}" data-type="{{ $type }}">
                        @if(($data[$period]['count'] ?? 0) > 0)
                            <!-- Mini Chart -->
                            <div class="mb-4 bg-gray-50 rounded-xl p-3 h-[140px]">
                                <canvas id="chart-{{ $type }}-{{ $period }}" class="w-full h-full"></canvas>
                            </div>

                            <!-- Key Stats Row -->
                            <div class="grid grid-cols-3 gap-3 mb-4">
                                @if($type === 'blood_pressure')
                                    <div class="text-center bg-gray-50 rounded-xl p-3">
                                        <p class="text-[10px] font-[700] text-gray-500 uppercase">Systolic Avg</p>
                                        <p class="text-xl font-[900] text-gray-900">{{ $data[$period]['systolic_avg'] ?? '-' }}</p>
                                    </div>
                                    <div class="text-center bg-gray-50 rounded-xl p-3">
                                        <p class="text-[10px] font-[700] text-gray-500 uppercase">Diastolic Avg</p>
                                        <p class="text-xl font-[900] text-gray-900">{{ $data[$period]['diastolic_avg'] ?? '-' }}</p>
                                    </div>
                                    <div class="text-center bg-gray-50 rounded-xl p-3">
                                        <p class="text-[10px] font-[700] text-gray-500 uppercase">Readings</p>
                                        <p class="text-xl font-[900] text-gray-900">{{ $data[$period]['count'] }}</p>
                                    </div>
                                @else
                                    <div class="text-center bg-gray-50 rounded-xl p-3">
                                        <p class="text-[10px] font-[700] text-gray-500 uppercase">Average</p>
                                        <p class="text-xl font-[900] text-gray-900">{{ $data[$period]['avg'] ?? '-' }}</p>
                                    </div>
                                    <div class="text-center bg-gray-50 rounded-xl p-3">
                                        <p class="text-[10px] font-[700] text-gray-500 uppercase">Min / Max</p>
                                        <p class="text-lg font-[900] text-gray-900">{{ $data[$period]['min'] ?? '-' }}<span class="text-gray-400">/</span>{{ $data[$period]['max'] ?? '-' }}</p>
                                    </div>
                                    <div class="text-center bg-gray-50 rounded-xl p-3">
                                        <p class="text-[10px] font-[700] text-gray-500 uppercase">Trend</p>
                                        @php
                                            $trend = $data[$period]['trend'] ?? 'stable';
                                            $trendIcon = $trend === 'increasing' ? '↗' : ($trend === 'decreasing' ? '↘' : '→');
                                            $trendColor = $trend === 'stable' ? 'text-gray-600' : ($trend === 'increasing' ? 'text-red-600' : 'text-green-600');
                                        @endphp
                                        <p class="text-xl font-[900] {{ $trendColor }}">{{ $trendIcon }}</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Latest Reading -->
                            @if($data[$period]['metrics']->count() > 0)
                            <div class="flex items-center justify-between py-3 px-4 bg-{{ $data['config']['color'] }}-50 rounded-xl border border-{{ $data['config']['color'] }}-100">
                                <div>
                                    <p class="text-[10px] font-[700] text-{{ $data['config']['color'] }}-600 uppercase">Latest Reading</p>
                                    <p class="text-lg font-[900] text-gray-900">
                                        @if($type === 'blood_pressure')
                                            {{ $data[$period]['metrics']->last()->value_text }}
                                        @else
                                            {{ number_format($data[$period]['metrics']->last()->value, $type === 'temperature' ? 1 : 0) }} {{ $data['config']['unit'] }}
                                        @endif
                                    </p>
                                </div>
                                <p class="text-xs font-[600] text-gray-500">{{ $data[$period]['metrics']->last()->measured_at->diffForHumans() }}</p>
                            </div>
                            @endif
                        @else
                            <!-- No Data -->
                            <div class="text-center py-10">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 text-3xl opacity-50 grayscale">
                                    {{ $data['config']['icon'] }}
                                </div>
                                <h4 class="text-base font-[800] text-gray-400 mb-1">No Data Yet</h4>
                                <p class="text-xs text-gray-400 font-[600]">No readings recorded for this period</p>
                            </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>

        <!-- MEDICATION & TASK SUMMARY ROW -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Medication Summary Card -->
            <div class="bg-white rounded-[24px] p-6 shadow-md border border-gray-100">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-[800] text-xl text-gray-900">Medication Summary</h3>
                        <p class="text-sm text-gray-500 font-medium">Last 7 days</p>
                    </div>
                </div>

                @if($medicationSummary['totalMedications'] > 0)
                    <!-- Overall Stats -->
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="text-center bg-blue-50 rounded-2xl p-4">
                            <p class="text-3xl font-[900] text-blue-600">{{ $medicationSummary['totalTaken'] }}</p>
                            <p class="text-xs font-[700] text-blue-600/70">Doses Taken</p>
                        </div>
                        <div class="text-center bg-gray-50 rounded-2xl p-4">
                            <p class="text-3xl font-[900] text-gray-700">{{ $medicationSummary['totalScheduled'] }}</p>
                            <p class="text-xs font-[700] text-gray-500">Scheduled</p>
                        </div>
                        <div class="text-center {{ $medicationSummary['adherenceRate'] >= 80 ? 'bg-green-50' : ($medicationSummary['adherenceRate'] >= 50 ? 'bg-yellow-50' : 'bg-red-50') }} rounded-2xl p-4">
                            <p class="text-3xl font-[900] {{ $medicationSummary['adherenceRate'] >= 80 ? 'text-green-600' : ($medicationSummary['adherenceRate'] >= 50 ? 'text-yellow-600' : 'text-red-600') }}">{{ $medicationSummary['adherenceRate'] ?? 0 }}%</p>
                            <p class="text-xs font-[700] {{ $medicationSummary['adherenceRate'] >= 80 ? 'text-green-600/70' : ($medicationSummary['adherenceRate'] >= 50 ? 'text-yellow-600/70' : 'text-red-600/70') }}">Adherence</p>
                        </div>
                    </div>

                    @if($medicationSummary['lowStockCount'] > 0)
                        <div class="bg-red-50 border border-red-100 rounded-xl p-3 mb-4 flex items-center gap-2">
                            <span class="text-lg">⚠️</span>
                            <p class="text-sm font-[700] text-red-700">{{ $medicationSummary['lowStockCount'] }} medication(s) running low on stock</p>
                        </div>
                    @endif

                    <!-- Per Medication List -->
                    <div class="space-y-3 max-h-48 overflow-y-auto">
                        @foreach($medicationSummary['medications'] as $med)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                                <div class="flex items-center gap-3">
                                    <span class="text-xl">💊</span>
                                    <div>
                                        <p class="font-[700] text-gray-900 text-sm">{{ $med['name'] }}</p>
                                        <p class="text-xs text-gray-500">{{ $med['taken'] }}/{{ $med['scheduled'] }} doses</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($med['lowStock'])
                                        <span class="text-xs bg-red-100 text-red-600 px-2 py-1 rounded-full font-[700]">Low</span>
                                    @endif
                                    <span class="text-sm font-[800] {{ ($med['adherence'] ?? 0) >= 80 ? 'text-green-600' : (($med['adherence'] ?? 0) >= 50 ? 'text-yellow-600' : 'text-red-600') }}">{{ $med['adherence'] ?? 0 }}%</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 text-3xl opacity-50">💊</div>
                        <p class="text-gray-400 font-[700]">No active medications</p>
                    </div>
                @endif

                <a href="{{ route('caregiver.medications.index') }}" class="mt-4 flex items-center justify-center gap-2 w-full py-3 bg-blue-50 text-blue-600 rounded-xl font-[700] hover:bg-blue-100 transition-colors">
                    Manage Medications →
                </a>
            </div>

            <!-- Task Summary Card -->
            <div class="bg-white rounded-[24px] p-6 shadow-md border border-gray-100">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-[800] text-xl text-gray-900">Task Summary</h3>
                        <p class="text-sm text-gray-500 font-medium">Last 7 days</p>
                    </div>
                </div>

                @if($taskSummary['total'] > 0)
                    <!-- Overall Stats -->
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="text-center bg-green-50 rounded-2xl p-4">
                            <p class="text-3xl font-[900] text-green-600">{{ $taskSummary['completed'] }}</p>
                            <p class="text-xs font-[700] text-green-600/70">Completed</p>
                        </div>
                        <div class="text-center bg-gray-50 rounded-2xl p-4">
                            <p class="text-3xl font-[900] text-gray-700">{{ $taskSummary['total'] }}</p>
                            <p class="text-xs font-[700] text-gray-500">Total Tasks</p>
                        </div>
                        <div class="text-center {{ ($taskSummary['completionRate'] ?? 0) >= 80 ? 'bg-green-50' : (($taskSummary['completionRate'] ?? 0) >= 50 ? 'bg-yellow-50' : 'bg-red-50') }} rounded-2xl p-4">
                            <p class="text-3xl font-[900] {{ ($taskSummary['completionRate'] ?? 0) >= 80 ? 'text-green-600' : (($taskSummary['completionRate'] ?? 0) >= 50 ? 'text-yellow-600' : 'text-red-600') }}">{{ $taskSummary['completionRate'] ?? 0 }}%</p>
                            <p class="text-xs font-[700] {{ ($taskSummary['completionRate'] ?? 0) >= 80 ? 'text-green-600/70' : (($taskSummary['completionRate'] ?? 0) >= 50 ? 'text-yellow-600/70' : 'text-red-600/70') }}">Rate</p>
                        </div>
                    </div>

                    @if($taskSummary['overdue'] > 0)
                        <div class="bg-red-50 border border-red-100 rounded-xl p-3 mb-4 flex items-center gap-2">
                            <span class="text-lg">⏰</span>
                            <p class="text-sm font-[700] text-red-700">{{ $taskSummary['overdue'] }} overdue task(s)</p>
                        </div>
                    @endif

                    @if($taskSummary['dueToday'] > 0)
                        <div class="bg-amber-50 border border-amber-100 rounded-xl p-3 mb-4 flex items-center gap-2">
                            <span class="text-lg">📅</span>
                            <p class="text-sm font-[700] text-amber-700">{{ $taskSummary['dueToday'] }} task(s) due today</p>
                        </div>
                    @endif

                    <!-- By Category -->
                    @if($taskSummary['byCategory']->count() > 0)
                        <h4 class="text-sm font-[700] text-gray-700 mb-3">By Category</h4>
                        <div class="space-y-2">
                            @foreach($taskSummary['byCategory'] as $cat)
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">{{ $cat['category'] }}</span>
                                    <div class="flex items-center gap-2">
                                        <div class="w-24 h-2 bg-gray-100 rounded-full overflow-hidden">
                                            <div class="h-full bg-green-500 rounded-full" style="width: {{ $cat['rate'] }}%"></div>
                                        </div>
                                        <span class="text-xs font-[700] text-gray-600 w-8 text-right">{{ $cat['rate'] }}%</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 text-3xl opacity-50">📋</div>
                        <p class="text-gray-400 font-[700]">No tasks this week</p>
                    </div>
                @endif

                <a href="{{ route('caregiver.checklists.index') }}" class="mt-4 flex items-center justify-center gap-2 w-full py-3 bg-green-50 text-green-600 rounded-xl font-[700] hover:bg-green-100 transition-colors">
                    Manage Checklists →
                </a>
            </div>
        </div>

        @endif
    </main>

    <script>
        const charts = {};
        const analyticsData = @json($analyticsData ?? []);
        let currentPeriod = '7days';

        function initCharts() {
            Object.keys(analyticsData).forEach(type => {
                const data = analyticsData[type];
                ['7days', '30days', '90days'].forEach(period => {
                    const periodData = data[period];
                    if (periodData && periodData.count > 0) {
                        const canvasId = `chart-${type}-${period}`;
                        const ctx = document.getElementById(canvasId);
                        if (ctx) {
                            charts[canvasId] = createChart(ctx, type, periodData, data.config);
                        }
                    }
                });
            });
        }

        function createChart(ctx, type, periodData, config) {
            const metrics = periodData.metrics || [];
            const labels = metrics.map(m => {
                const d = new Date(m.measured_at);
                return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            });
            
            const colorMap = {
                'red': { bg: 'rgba(239, 68, 68, 0.15)', border: 'rgb(239, 68, 68)' },
                'blue': { bg: 'rgba(59, 130, 246, 0.15)', border: 'rgb(59, 130, 246)' },
                'orange': { bg: 'rgba(249, 115, 22, 0.15)', border: 'rgb(249, 115, 22)' },
                'rose': { bg: 'rgba(244, 63, 94, 0.15)', border: 'rgb(244, 63, 94)' },
                'pink': { bg: 'rgba(236, 72, 153, 0.15)', border: 'rgb(236, 72, 153)' },
            };
            const colors = colorMap[config.color] || colorMap.blue;

            let datasets = [];

            if (type === 'blood_pressure') {
                const systolic = [], diastolic = [];
                metrics.forEach(m => {
                    if (m.value_text) {
                        const parts = m.value_text.split('/');
                        systolic.push(parseInt(parts[0]));
                        diastolic.push(parseInt(parts[1]));
                    }
                });
                datasets = [
                    { label: 'Sys', data: systolic, borderColor: colors.border, backgroundColor: colors.bg, borderWidth: 2, fill: true, tension: 0.4, pointRadius: 3 },
                    { label: 'Dia', data: diastolic, borderColor: 'rgb(147, 51, 234)', backgroundColor: 'rgba(147, 51, 234, 0.15)', borderWidth: 2, fill: true, tension: 0.4, pointRadius: 3 }
                ];
            } else {
                const values = metrics.map(m => parseFloat(m.value));
                datasets = [{ label: config.name, data: values, borderColor: colors.border, backgroundColor: colors.bg, borderWidth: 2, fill: true, tension: 0.4, pointRadius: 3 }];
            }

            return new Chart(ctx, {
                type: 'line',
                data: { labels, datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: type === 'blood_pressure', position: 'top', labels: { font: { weight: 'bold', size: 10 }, usePointStyle: true, padding: 8 } },
                        tooltip: { backgroundColor: 'rgba(0,0,0,0.8)', padding: 10, titleFont: { size: 12, weight: 'bold' }, bodyFont: { size: 11 }, cornerRadius: 8 }
                    },
                    scales: {
                        y: { beginAtZero: false, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { weight: '600', size: 10 } } },
                        x: { grid: { display: false }, ticks: { font: { weight: '600', size: 9 }, maxRotation: 45, minRotation: 45 } }
                    }
                }
            });
        }

        function changePeriod(period) {
            currentPeriod = period;
            document.querySelectorAll('.period-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.period === period) btn.classList.add('active');
            });
            document.querySelectorAll('.period-data').forEach(el => {
                el.classList.toggle('hidden', el.dataset.period !== period);
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            initCharts();
        });
    </script>

</body>
</html>
