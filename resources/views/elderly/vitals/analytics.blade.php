<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Health Analytics - SilverCare</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <style>
        body { font-family: 'Montserrat', sans-serif; }
        .period-btn.active { background: white; color: #1f2937; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .card-filter.active { background: white; color: #1f2937; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
        .insights-card { animation: fadeIn 0.3s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes pulse-ring { 0% { transform: scale(0.8); opacity: 1; } 100% { transform: scale(1.3); opacity: 0; } }
        .pulse-ring { animation: pulse-ring 1.5s ease-out infinite; }
        .modal-backdrop { backdrop-filter: blur(4px); }
        .drawer-slide { transition: transform 0.3s ease-out; }
        .drawer-slide.hidden { transform: translateX(100%); }
        .health-ring { transition: stroke-dashoffset 1s ease-out; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

@php
    // Calculate health score based on latest readings and normal ranges
    $healthScore = 0;
    $healthFactors = [];
    $totalFactors = 0;
    
    foreach($analyticsData as $type => $data) {
        if (($data['7days']['count'] ?? 0) > 0) {
            $totalFactors++;
            $score = 0;
            $status = 'unknown';
            
            if ($type === 'blood_pressure') {
                $sys = $data['7days']['systolic_avg'] ?? 120;
                $dia = $data['7days']['diastolic_avg'] ?? 80;
                if ($sys < 120 && $dia < 80) { $score = 100; $status = 'Optimal'; }
                elseif ($sys < 130 && $dia < 85) { $score = 85; $status = 'Normal'; }
                elseif ($sys < 140 && $dia < 90) { $score = 70; $status = 'Elevated'; }
                else { $score = 50; $status = 'High'; }
            } elseif ($type === 'heart_rate') {
                $hr = $data['7days']['avg'] ?? 72;
                if ($hr >= 60 && $hr <= 100) { $score = 100; $status = 'Optimal'; }
                elseif ($hr >= 50 && $hr <= 110) { $score = 80; $status = 'Normal'; }
                else { $score = 60; $status = 'Attention'; }
            } elseif ($type === 'temperature') {
                $temp = $data['7days']['avg'] ?? 36.5;
                if ($temp >= 36.1 && $temp <= 37.2) { $score = 100; $status = 'Normal'; }
                elseif ($temp >= 35.5 && $temp <= 37.8) { $score = 75; $status = 'Mild'; }
                else { $score = 50; $status = 'Attention'; }
            } elseif ($type === 'sugar_level') {
                $sugar = $data['7days']['avg'] ?? 100;
                if ($sugar >= 70 && $sugar <= 100) { $score = 100; $status = 'Optimal'; }
                elseif ($sugar >= 60 && $sugar <= 125) { $score = 80; $status = 'Normal'; }
                else { $score = 60; $status = 'Attention'; }
            }
            
            $healthScore += $score;
            $healthFactors[$type] = ['score' => $score, 'status' => $status];
        }
    }
    
    $healthScore = $totalFactors > 0 ? round($healthScore / $totalFactors) : 0;
    $healthLabel = $healthScore >= 90 ? 'Excellent' : ($healthScore >= 75 ? 'Good' : ($healthScore >= 60 ? 'Fair' : 'Needs Attention'));
    $healthColor = $healthScore >= 90 ? 'emerald' : ($healthScore >= 75 ? 'blue' : ($healthScore >= 60 ? 'amber' : 'red'));
@endphp

<div class="min-h-screen pb-24">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="{{ route('dashboard') }}" class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center hover:bg-gray-200 transition-colors">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-[900] text-gray-900">Health Analytics</h1>
                        <p class="text-sm text-gray-500 font-[600]">Your vitals insights & trends</p>
                    </div>
                </div>
                
                <!-- Global Time Period Selector -->
                <div class="flex items-center gap-3">
                    <div class="flex bg-gray-100 rounded-xl p-1">
                        <button onclick="changePeriod('7days')" class="period-btn active px-4 py-2 rounded-lg text-sm font-[700] transition-all" data-period="7days">Week</button>
                        <button onclick="changePeriod('30days')" class="period-btn px-4 py-2 rounded-lg text-sm font-[700] transition-all" data-period="30days">Month</button>
                        <button onclick="changePeriod('90days')" class="period-btn px-4 py-2 rounded-lg text-sm font-[700] transition-all" data-period="90days">3 Months</button>
                    </div>
                    
                    <!-- Export PDF Button -->
                    <a href="{{ route('elderly.vitals.export') }}" class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-[#000080] to-blue-700 hover:from-blue-800 hover:to-blue-600 text-white rounded-xl font-bold text-sm transition-all shadow-lg shadow-blue-500/20 hover:shadow-blue-500/40">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="hidden sm:inline">Export</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        
        <!-- Health Score + Quick Stats Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Health Score Card -->
            <div class="lg:col-span-1 bg-gradient-to-br from-{{ $healthColor }}-500 to-{{ $healthColor }}-600 rounded-3xl p-6 text-white relative overflow-hidden shadow-xl">
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
                                    stroke-dasharray="{{ 264 }}" 
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
                            <p class="text-sm font-[600] text-white/70 mb-3">Based on {{ $totalFactors }} tracked vitals</p>
                            
                            @if($totalFactors > 0)
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
            
            <!-- Quick Stats -->
            <div class="lg:col-span-2 grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl p-5 shadow-lg border border-gray-100">
                    <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <p class="text-xs font-[700] text-gray-500 uppercase">Total Readings</p>
                    <p class="text-2xl font-[900] text-gray-900 mt-1">{{ $totalReadings }}</p>
                </div>
                
                <div class="bg-white rounded-2xl p-5 shadow-lg border border-gray-100">
                    <div class="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <p class="text-xs font-[700] text-gray-500 uppercase">This Week</p>
                    <p class="text-2xl font-[900] text-gray-900 mt-1">{{ $readingsThisWeek }}</p>
                </div>
                
                @php
                    $consistencyScore = $totalReadings > 0 ? min(100, round(($readingsThisWeek / 28) * 100)) : 0;
                @endphp
                <div class="bg-white rounded-2xl p-5 shadow-lg border border-gray-100">
                    <div class="w-10 h-10 bg-purple-50 rounded-xl flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="text-xs font-[700] text-gray-500 uppercase">Consistency</p>
                    <p class="text-2xl font-[900] text-gray-900 mt-1">{{ $consistencyScore }}%</p>
                </div>
                
                <div class="bg-white rounded-2xl p-5 shadow-lg border border-gray-100">
                    <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="text-xs font-[700] text-gray-500 uppercase">Vitals Tracked</p>
                    <p class="text-2xl font-[900] text-gray-900 mt-1">{{ $totalFactors }}/4</p>
                </div>
            </div>
        </div>

        <!-- Insights Section (Now in Grid with BMI) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Personalized Insights Card (Swapped - now in big square) -->
            @if($totalFactors > 0)
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-50 to-purple-100 p-5 border-b border-indigo-100">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center shadow-sm">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-[900] text-gray-900">Personalized Insights</h3>
                            <p class="text-sm font-[600] text-gray-500">Based on your vitals</p>
                        </div>
                    </div>
                </div>
                <div class="p-5">
                    <div class="space-y-3">
                        @foreach($analyticsData as $type => $data)
                            @if(($data['7days']['count'] ?? 0) > 0)
                                @php
                                    $insight = '';
                                    $insightType = 'info';
                                    
                                    if ($type === 'blood_pressure') {
                                        $sys = $data['7days']['systolic_avg'] ?? 120;
                                        if ($sys < 120) { $insight = 'Your blood pressure is in the optimal range. Keep up the good work!'; $insightType = 'success'; }
                                        elseif ($sys < 130) { $insight = 'Blood pressure is normal. Consider reducing salt intake for even better results.'; $insightType = 'info'; }
                                        else { $insight = 'Blood pressure is elevated. Regular exercise and stress management can help.'; $insightType = 'warning'; }
                                    } elseif ($type === 'heart_rate') {
                                        $hr = $data['7days']['avg'] ?? 72;
                                        if ($hr >= 60 && $hr <= 80) { $insight = 'Your resting heart rate indicates good cardiovascular health!'; $insightType = 'success'; }
                                        elseif ($hr < 60) { $insight = 'Low heart rate detected. This may be normal if you\'re athletic.'; $insightType = 'info'; }
                                        else { $insight = 'Slightly elevated heart rate. Try relaxation techniques.'; $insightType = 'warning'; }
                                    } elseif ($type === 'temperature') {
                                        $temp = $data['7days']['avg'] ?? 36.5;
                                        if ($temp >= 36.1 && $temp <= 37.2) { $insight = 'Body temperature is perfectly normal.'; $insightType = 'success'; }
                                        else { $insight = 'Temperature variations detected. Monitor for any symptoms.'; $insightType = 'warning'; }
                                    } elseif ($type === 'sugar_level') {
                                        $sugar = $data['7days']['avg'] ?? 100;
                                        if ($sugar >= 70 && $sugar <= 100) { $insight = 'Blood sugar levels are in the healthy range!'; $insightType = 'success'; }
                                        elseif ($sugar < 70) { $insight = 'Blood sugar may be low. Ensure regular, balanced meals.'; $insightType = 'warning'; }
                                        else { $insight = 'Blood sugar is slightly elevated. Consider dietary adjustments.'; $insightType = 'warning'; }
                                    }
                                    
                                    $insightColors = [
                                        'success' => 'bg-green-50 border-green-200 text-green-800',
                                        'info' => 'bg-blue-50 border-blue-200 text-blue-800',
                                        'warning' => 'bg-amber-50 border-amber-200 text-amber-800',
                                    ];
                                @endphp
                                <div class="{{ $insightColors[$insightType] }} rounded-xl p-3 border">
                                    <div class="flex items-start gap-3">
                                        <span class="text-xl">{{ $data['config']['icon'] }}</span>
                                        <div>
                                            <h4 class="font-[800] text-sm mb-0.5">{{ $data['config']['name'] }}</h4>
                                            <p class="text-xs font-[600] leading-relaxed">{{ $insight }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- BMI Card -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="bg-gradient-to-r from-purple-50 to-violet-100 p-5 border-b border-purple-100">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-2xl shadow-sm">
                            ⚖️
                        </div>
                        <div>
                            <h3 class="text-lg font-[900] text-gray-900">Body Metrics</h3>
                            <p class="text-sm font-[600] text-gray-500">Weight, Height & BMI</p>
                        </div>
                    </div>
                </div>
                <div class="p-5">
                    @if($bmiData['bmi'])
                        <!-- BMI Score Display -->
                        <div class="text-center mb-6">
                            <div class="inline-flex items-center justify-center w-28 h-28 rounded-full bg-{{ $bmiData['color'] }}-100 mb-3">
                                <div class="text-center">
                                    <p class="text-3xl font-[900] text-{{ $bmiData['color'] }}-600">{{ $bmiData['bmi'] }}</p>
                                    <p class="text-xs font-[700] text-{{ $bmiData['color'] }}-500">BMI</p>
                                </div>
                            </div>
                            <p class="text-lg font-[900] text-gray-900">{{ $bmiData['category'] }}</p>
                        </div>

                        <!-- Weight & Height Cards -->
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <div class="bg-gray-50 rounded-xl p-4 text-center">
                                <div class="text-2xl mb-1">📏</div>
                                <p class="text-xs font-[700] text-gray-500 uppercase">Height</p>
                                <p class="text-xl font-[900] text-gray-900">{{ $bmiData['height'] }} <span class="text-sm font-[600] text-gray-500">cm</span></p>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-4 text-center">
                                <div class="text-2xl mb-1">⚖️</div>
                                <p class="text-xs font-[700] text-gray-500 uppercase">Weight</p>
                                <p class="text-xl font-[900] text-gray-900">{{ $bmiData['weight'] }} <span class="text-sm font-[600] text-gray-500">kg</span></p>
                            </div>
                        </div>

                        <!-- BMI Scale Reference -->
                        <div class="bg-gray-50 rounded-xl p-3">
                            <p class="text-xs font-[700] text-gray-500 mb-2 text-center">BMI Categories</p>
                            <div class="flex justify-between text-xs font-[600]">
                                <span class="text-blue-600">Under 18.5</span>
                                <span class="text-green-600">18.5–24.9</span>
                                <span class="text-amber-600">25–29.9</span>
                                <span class="text-red-600">30+</span>
                            </div>
                            <div class="flex h-2 mt-1 rounded-full overflow-hidden">
                                <div class="bg-blue-400 flex-1"></div>
                                <div class="bg-green-400 flex-1"></div>
                                <div class="bg-amber-400 flex-1"></div>
                                <div class="bg-red-400 flex-1"></div>
                            </div>
                        </div>
                    @else
                        <!-- No BMI Data -->
                        <div class="text-center py-8">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 text-3xl opacity-50">
                                ⚖️
                            </div>
                            <h4 class="text-base font-[800] text-gray-400 mb-1">No Body Metrics</h4>
                            <p class="text-xs text-gray-400 font-[600] mb-4">Update your profile with weight & height</p>
                            <a href="{{ route('profile.edit') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-purple-500 text-white rounded-xl text-sm font-[700] hover:bg-purple-600 transition-colors">
                                Update Profile
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Steps Section (Now horizontal rectangular card) -->
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-2xl p-6 border border-green-100 shadow-lg">
            <div class="flex flex-col md:flex-row items-center gap-6">
                <!-- Left: Icon and Title -->
                <div class="flex items-center gap-4 flex-shrink-0">
                    <div class="w-14 h-14 bg-white rounded-xl flex items-center justify-center text-3xl shadow-sm">
                        👟
                    </div>
                    <div>
                        <h3 class="text-lg font-[900] text-gray-900">Daily Steps</h3>
                        <p class="text-sm font-[600] text-gray-500">Track your activity</p>
                    </div>
                </div>
                
                @if($stepsData['today'])
                    <!-- Center: Progress Ring -->
                    <div class="flex-shrink-0">
                        <div class="relative w-20 h-20">
                            <svg class="w-full h-full -rotate-90" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="42" stroke="#E5E7EB" stroke-width="8" fill="none"/>
                                @php
                                    $stepsPercent = min(100, ($stepsData['today']['value'] / $stepsData['today']['goal']) * 100);
                                    $strokeOffset = 264 - (264 * $stepsPercent / 100);
                                @endphp
                                <circle cx="50" cy="50" r="42" stroke="#22C55E" stroke-width="8" fill="none" 
                                    stroke-dasharray="264" 
                                    stroke-dashoffset="{{ $strokeOffset }}"
                                    stroke-linecap="round"
                                    class="transition-all duration-1000"/>
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-sm font-[900] text-gray-900">{{ round($stepsPercent) }}%</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right: Stats -->
                    <div class="flex-1 grid grid-cols-3 gap-4">
                        <div class="bg-white/80 rounded-xl p-3 text-center">
                            <p class="text-xs font-[700] text-gray-500 uppercase">Today</p>
                            <p class="text-xl font-[900] text-gray-900">{{ number_format($stepsData['today']['value']) }}</p>
                        </div>
                        <div class="bg-white/80 rounded-xl p-3 text-center">
                            <p class="text-xs font-[700] text-gray-500 uppercase">Weekly</p>
                            <p class="text-xl font-[900] text-gray-900">{{ number_format($stepsData['weeklyTotal']) }}</p>
                        </div>
                        <div class="bg-white/80 rounded-xl p-3 text-center">
                            <p class="text-xs font-[700] text-gray-500 uppercase">Daily Avg</p>
                            <p class="text-xl font-[900] text-gray-900">{{ number_format($stepsData['weeklyAvg']) }}</p>
                        </div>
                    </div>
                    
                    @if($stepsData['today']['source'] === 'google_fit')
                    <div class="flex-shrink-0">
                        <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full font-[700] text-xs">🔗 Google Fit</span>
                    </div>
                    @endif
                @else
                    <div class="flex-1 flex items-center justify-center py-4">
                        <div class="text-center">
                            <p class="text-gray-400 font-[700]">No steps data available</p>
                            <p class="text-xs text-gray-400 font-[600]">Connect Google Fit to track steps</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Vitals Analytics Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @foreach($analyticsData as $type => $data)
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden" id="card-{{ $type }}">
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
                        
                        <!-- Expand Button -->
                        <button onclick="openDetailModal('{{ $type }}')" class="px-4 py-2 bg-white rounded-xl text-sm font-[700] text-{{ $data['config']['color'] }}-600 hover:bg-{{ $data['config']['color'] }}-50 transition-all flex items-center gap-2 shadow-sm border border-{{ $data['config']['color'] }}-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path>
                            </svg>
                            Details
                        </button>
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
                                            {{ $data[$period]['metrics']->first()->value_text }}
                                        @else
                                            {{ number_format($data[$period]['metrics']->first()->value, $type === 'temperature' ? 1 : 0) }} {{ $data['config']['unit'] }}
                                        @endif
                                    </p>
                                </div>
                                <p class="text-xs font-[600] text-gray-500">{{ $data[$period]['metrics']->first()->measured_at->diffForHumans() }}</p>
                            </div>
                            @endif
                        @else
                            <!-- No Data -->
                            <div class="text-center py-10">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 text-3xl opacity-50 grayscale">
                                    {{ $data['config']['icon'] }}
                                </div>
                                <h4 class="text-base font-[800] text-gray-400 mb-1">No Data Yet</h4>
                                <p class="text-xs text-gray-400 font-[600] mb-4">Start recording to see analytics</p>
                                <a href="{{ route('elderly.vitals.' . $type) }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-{{ $data['config']['color'] }}-500 text-white rounded-xl text-sm font-[700] hover:bg-{{ $data['config']['color'] }}-600 transition-colors">
                                    <span>+</span> Add Reading
                                </a>
                            </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Detail Modal/Drawer -->
<div id="detailModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40 modal-backdrop" onclick="closeDetailModal()"></div>
    <div class="absolute right-0 top-0 bottom-0 w-full max-w-xl bg-white shadow-2xl drawer-slide overflow-y-auto" id="detailDrawer">
        <div id="detailModalContent">
            <!-- Content will be injected by JS -->
        </div>
    </div>
</div>

<script>
    const charts = {};
    const analyticsData = @json($analyticsData);
    let currentPeriod = '7days';

    // Initialize charts
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

    function openDetailModal(type) {
        const data = analyticsData[type];
        const periodData = data[currentPeriod];
        const modal = document.getElementById('detailModal');
        const content = document.getElementById('detailModalContent');
        
        let historyHtml = '';
        if (periodData && periodData.metrics && periodData.metrics.length > 0) {
            historyHtml = periodData.metrics.map(m => {
                const value = type === 'blood_pressure' ? m.value_text : `${parseFloat(m.value).toFixed(type === 'temperature' ? 1 : 0)} ${data.config.unit}`;
                const date = new Date(m.measured_at);
                const dateStr = date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
                const timeStr = date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
                const source = m.source === 'google_fit' ? '<span class="text-[10px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-bold">Google Fit</span>' : '';
                return `
                    <div class="flex items-center justify-between py-4 border-b border-gray-100 last:border-0">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-${data.config.color}-50 rounded-xl flex items-center justify-center text-xl">${data.config.icon}</div>
                            <div>
                                <p class="text-lg font-[900] text-gray-900">${value}</p>
                                <p class="text-xs text-gray-500 font-[600]">${dateStr} • ${timeStr}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            ${source}
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            historyHtml = '<div class="text-center py-12 text-gray-400"><p class="text-lg font-bold">No readings found</p><p class="text-sm">for this period</p></div>';
        }

        const statsHtml = type === 'blood_pressure' ? `
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-red-50 rounded-xl p-4 text-center">
                    <p class="text-xs font-[700] text-red-600 uppercase mb-1">Systolic Avg</p>
                    <p class="text-2xl font-[900] text-gray-900">${periodData?.systolic_avg || '-'}</p>
                    <p class="text-xs text-gray-500 mt-1">${periodData?.systolic_min || '-'} - ${periodData?.systolic_max || '-'}</p>
                </div>
                <div class="bg-purple-50 rounded-xl p-4 text-center">
                    <p class="text-xs font-[700] text-purple-600 uppercase mb-1">Diastolic Avg</p>
                    <p class="text-2xl font-[900] text-gray-900">${periodData?.diastolic_avg || '-'}</p>
                    <p class="text-xs text-gray-500 mt-1">${periodData?.diastolic_min || '-'} - ${periodData?.diastolic_max || '-'}</p>
                </div>
            </div>
        ` : `
            <div class="grid grid-cols-3 gap-3">
                <div class="bg-gray-50 rounded-xl p-4 text-center">
                    <p class="text-xs font-[700] text-gray-500 uppercase mb-1">Average</p>
                    <p class="text-2xl font-[900] text-gray-900">${periodData?.avg || '-'}</p>
                </div>
                <div class="bg-gray-50 rounded-xl p-4 text-center">
                    <p class="text-xs font-[700] text-gray-500 uppercase mb-1">Minimum</p>
                    <p class="text-2xl font-[900] text-gray-900">${periodData?.min || '-'}</p>
                </div>
                <div class="bg-gray-50 rounded-xl p-4 text-center">
                    <p class="text-xs font-[700] text-gray-500 uppercase mb-1">Maximum</p>
                    <p class="text-2xl font-[900] text-gray-900">${periodData?.max || '-'}</p>
                </div>
            </div>
        `;

        const periodLabel = currentPeriod === '7days' ? 'Last 7 Days' : (currentPeriod === '30days' ? 'Last 30 Days' : 'Last 90 Days');

        content.innerHTML = `
            <div class="sticky top-0 bg-white border-b border-gray-200 z-10">
                <div class="flex items-center justify-between p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-${data.config.color}-100 rounded-xl flex items-center justify-center text-2xl">${data.config.icon}</div>
                        <div>
                            <h2 class="text-xl font-[900] text-gray-900">${data.config.name}</h2>
                            <p class="text-sm text-gray-500 font-[600]">${periodLabel} • ${periodData?.count || 0} readings</p>
                        </div>
                    </div>
                    <button onclick="closeDetailModal()" class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center hover:bg-gray-200 transition-colors">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="p-5 space-y-6">
                <!-- Stats Summary -->
                <div>
                    <h3 class="text-sm font-[800] text-gray-500 uppercase mb-3">Statistics</h3>
                    ${statsHtml}
                </div>
                
                <!-- Add Reading Button -->
                <a href="${window.location.origin}/my-vitals/${type}" class="flex items-center justify-center gap-2 w-full py-4 bg-${data.config.color}-500 text-white rounded-xl font-[800] hover:bg-${data.config.color}-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    Add New Reading
                </a>
                
                <!-- Full History -->
                <div>
                    <h3 class="text-sm font-[800] text-gray-500 uppercase mb-3">All Readings</h3>
                    <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100 max-h-[400px] overflow-y-auto">
                        ${historyHtml}
                    </div>
                </div>
            </div>
        `;

        modal.classList.remove('hidden');
        setTimeout(() => {
            document.getElementById('detailDrawer').classList.remove('hidden');
        }, 10);
    }

    function closeDetailModal() {
        const modal = document.getElementById('detailModal');
        const drawer = document.getElementById('detailDrawer');
        drawer.classList.add('hidden');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // Close on escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeDetailModal();
    });

    document.addEventListener('DOMContentLoaded', () => {
        initCharts();
    });
</script>

</body>
</html>
