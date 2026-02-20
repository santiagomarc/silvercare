<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Manage Medications - SilverCare</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/icons/silvercare.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/icons/silvercare.png') }}">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Montserrat', sans-serif; }
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
                    <h2 class="text-lg font-[800] text-gray-900">Manage Medications</h2>
                    <p class="text-xs text-gray-500 font-medium -mt-0.5">{{ now()->format('l, F j, Y') }}</p>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <a href="{{ route('caregiver.dashboard') }}" class="flex items-center gap-1.5 px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-bold text-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    <span class="hidden sm:inline">Back to Dashboard</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="max-w-[1600px] mx-auto px-6 lg:px-12 py-6">
        
        @if(session('success'))
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded-lg shadow-sm flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                {{ session('success') }}
            </div>
        @endif

        <!-- HEADER WITH ADD BUTTON -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-[900] text-gray-900">Medications</h1>
                <p class="text-gray-500 font-medium text-sm">Manage medication schedules for your patient</p>
            </div>
            <a href="{{ route('caregiver.medications.create') }}" class="group flex items-center gap-2 bg-gradient-to-r from-blue-500 to-indigo-600 text-white px-5 py-3 rounded-2xl font-[700] shadow-lg shadow-blue-200 hover:-translate-y-0.5 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Add Medication
            </a>
        </div>

        <!-- MEDICATIONS GRID -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($medications as $medication)
            <div class="bg-white rounded-[24px] shadow-md border border-gray-100 overflow-hidden hover:shadow-xl transition-all duration-300 group">
                <!-- Card Header with Gradient -->
                <div class="relative p-5 bg-gradient-to-br from-blue-50 to-indigo-50">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-20 h-20 bg-blue-100 rounded-full opacity-50"></div>
                    <div class="relative z-10 flex justify-between items-start">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-blue-200/50">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('caregiver.medications.edit', $medication->id) }}" class="w-9 h-9 rounded-xl bg-white shadow flex items-center justify-center text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </a>
                            <form action="{{ route('caregiver.medications.destroy', $medication->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this medication?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-9 h-9 rounded-xl bg-white shadow flex items-center justify-center text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                    <h3 class="text-xl font-[900] text-gray-900 mt-3">{{ $medication->name }}</h3>
                </div>
                
                <!-- Card Body -->
                <div class="p-5">
                    <!-- Dosage -->
                    <div class="flex items-center text-gray-700 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path></svg>
                        </div>
                        <span class="font-[700]">{{ $medication->dosage }} {{ $medication->dosage_unit }}</span>
                    </div>

                    <!-- Days of Week -->
                    @if(!empty($medication->days_of_week))
                        <div class="mb-4">
                            <p class="text-[10px] font-[800] uppercase tracking-wider text-gray-400 mb-2">Schedule</p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $index => $shortDay)
                                    @php $fullDay = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'][$index]; @endphp
                                    <span class="px-2.5 py-1 text-[11px] font-[700] rounded-full {{ in_array($fullDay, $medication->days_of_week) ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-400' }}">
                                        {{ $shortDay }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Times of Day -->
                    @if(!empty($medication->times_of_day))
                        <div class="mb-4">
                            <p class="text-[10px] font-[800] uppercase tracking-wider text-gray-400 mb-2">Times</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($medication->times_of_day as $time)
                                    <span class="inline-flex items-center px-3 py-1.5 bg-gradient-to-r from-amber-50 to-orange-50 text-amber-700 text-xs font-[700] rounded-xl border border-amber-100">
                                        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        {{ \Carbon\Carbon::parse($time)->format('g:i A') }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Instructions -->
                    @if($medication->instructions)
                        <p class="text-sm text-gray-500 italic border-t border-gray-100 pt-3 mt-3">{{ Str::limit($medication->instructions, 80) }}</p>
                    @endif
                </div>

                <!-- Card Footer -->
                <div class="bg-gray-50 px-5 py-3 border-t border-gray-100 flex justify-between items-center">
                    <span class="text-xs font-[700] px-2.5 py-1 rounded-full {{ $medication->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600' }}">
                        {{ $medication->is_active ? '● Active' : '○ Inactive' }}
                    </span>
                    @if($medication->track_inventory)
                        <span class="text-xs font-[700] {{ ($medication->current_stock <= ($medication->low_stock_threshold ?? 5)) ? 'text-red-500' : 'text-gray-500' }}">
                            📦 Stock: {{ $medication->current_stock ?? 0 }}
                        </span>
                    @endif
                </div>
            </div>
            @empty
            <div class="col-span-full text-center py-16">
                <div class="w-20 h-20 bg-gradient-to-br from-blue-100 to-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                </div>
                <h3 class="text-xl font-[800] text-gray-900 mb-2">No Medications Yet</h3>
                <p class="text-gray-500 font-medium mb-6">Get started by adding a medication schedule for your patient.</p>
                <a href="{{ route('caregiver.medications.create') }}" class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-500 to-indigo-600 text-white px-6 py-3 rounded-2xl font-[700] shadow-lg shadow-blue-200 hover:-translate-y-0.5 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Add Medication
                </a>
            </div>
            @endforelse
        </div>
    </main>

</body>
</html>
