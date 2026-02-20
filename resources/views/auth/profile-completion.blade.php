<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Complete Profile - SilverCare</title>
    
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
<body class="antialiased bg-[#DEDEDE] min-h-screen">

    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-2xl">
            
            <div class="text-center mb-8">
                <h1 class="text-4xl font-[900] text-gray-900 tracking-tight mb-2">Complete Your Profile</h1>
                <p class="text-gray-600 font-medium">Help us personalize your SilverCare experience</p>
            </div>

            <div x-data="{
                currentStep: 1,
                useCaregiverAsEmergency: false,
                caregiverName: '{{ $caregiver['name'] ?? '' }}',
                caregiverPhone: '{{ $caregiver['phone'] ?? '' }}',
                caregiverRelationship: '{{ $caregiver['relationship'] ?? '' }}',
                emergencyName: '',
                emergencyPhone: '',
                emergencyRelationship: '',
                nextStep() { if (this.currentStep < 3) this.currentStep++; },
                prevStep() { if (this.currentStep > 1) this.currentStep--; },
                toggleCaregiverEmergency() {
                    if (this.useCaregiverAsEmergency) {
                        this.emergencyName = this.caregiverName;
                        this.emergencyRelationship = this.caregiverRelationship;
                    } else {
                        this.emergencyName = '';
                        this.emergencyPhone = '';
                        this.emergencyRelationship = '';
                    }
                }
            }" class="bg-white rounded-2xl shadow-[0_10px_40px_rgba(0,0,0,0.1)] p-8">
                
                <!-- Progress Bar -->
                <div class="mb-10">
                    <div class="flex justify-between items-center relative">
                        
                        <div class="flex flex-col items-center flex-1 z-10">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center transition-all duration-300 font-bold text-lg"
                                 :class="currentStep >= 1 ? 'bg-[#000080] text-white shadow-[0_4px_12px_rgba(0,0,128,0.3)]' : 'bg-gray-200 text-gray-400'">
                                1
                            </div>
                            <span class="text-xs mt-2 font-bold transition-colors" :class="currentStep >= 1 ? 'text-[#000080]' : 'text-gray-400'">Personal</span>
                        </div>
                        
                        <div class="absolute top-6 left-0 right-0 h-1 bg-gray-200 -z-0" style="margin: 0 25%;"></div>
                        <div class="absolute top-6 h-1 bg-[#000080] transition-all duration-500 -z-0" 
                             :style="`left: 25%; width: ${(currentStep - 1) * 25}%;`"></div>
                        
                        <div class="flex flex-col items-center flex-1 z-10">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center transition-all duration-300 font-bold text-lg"
                                 :class="currentStep >= 2 ? 'bg-[#000080] text-white shadow-[0_4px_12px_rgba(0,0,128,0.3)]' : 'bg-gray-200 text-gray-400'">
                                2
                            </div>
                            <span class="text-xs mt-2 font-bold transition-colors" :class="currentStep >= 2 ? 'text-[#000080]' : 'text-gray-400'">Emergency</span>
                        </div>
                        
                        <div class="flex flex-col items-center flex-1 z-10">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center transition-all duration-300 font-bold text-lg"
                                 :class="currentStep >= 3 ? 'bg-[#000080] text-white shadow-[0_4px_12px_rgba(0,0,128,0.3)]' : 'bg-gray-200 text-gray-400'">
                                3
                            </div>
                            <span class="text-xs mt-2 font-bold transition-colors" :class="currentStep >= 3 ? 'text-[#000080]' : 'text-gray-400'">Medical</span>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('profile.completion.store') }}">
                    @csrf

                    <!-- Step 1: Personal Info -->
                    <div x-show="currentStep === 1" 
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform translate-x-8"
                         x-transition:enter-end="opacity-100 transform translate-x-0"
                         class="space-y-5">
                        
                        <div>
                            <label for="age" class="block text-sm font-bold text-gray-700 mb-2">Age (Optional)</label>
                            <input id="age" type="number" name="age" value="{{ old('age') }}"
                                   class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium"
                                   placeholder="65">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="weight" class="block text-sm font-bold text-gray-700 mb-2">Weight (kg)</label>
                                <input id="weight" type="number" step="0.1" name="weight" value="{{ old('weight') }}"
                                       class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium"
                                       placeholder="70.5">
                            </div>

                            <div>
                                <label for="height" class="block text-sm font-bold text-gray-700 mb-2">Height (cm)</label>
                                <input id="height" type="number" step="0.1" name="height" value="{{ old('height') }}"
                                       class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium"
                                       placeholder="170.0">
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Emergency Contact -->
                    <div x-show="currentStep === 2"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform translate-x-8"
                         x-transition:enter-end="opacity-100 transform translate-x-0"
                         class="space-y-5">
                        
                        @if($caregiver)
                        <!-- Checkbox to use caregiver as emergency contact -->
                        <div class="p-4 bg-blue-50 rounded-xl border-2 border-blue-100">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" x-model="useCaregiverAsEmergency" @change="toggleCaregiverEmergency()"
                                       class="w-5 h-5 mt-0.5 rounded border-gray-300 text-[#000080] focus:ring-[#000080]">
                                <div>
                                    <span class="text-sm font-bold text-gray-900">Use my caregiver as emergency contact</span>
                                    <p class="text-xs text-gray-600 mt-1">
                                        <span class="font-semibold">{{ $caregiver['name'] }}</span> 
                                        <span class="text-gray-400">({{ $caregiver['relationship'] }})</span>
                                    </p>
                                    <p class="text-xs text-blue-600 mt-1">You'll only need to confirm their phone number below</p>
                                </div>
                            </label>
                        </div>
                        @endif

                        <div>
                            <label for="emergency_name" class="block text-sm font-bold text-gray-700 mb-2">Emergency Contact Name</label>
                            <input id="emergency_name" type="text" name="emergency_name" 
                                   x-model="emergencyName"
                                   :class="useCaregiverAsEmergency ? 'bg-gray-100' : ''"
                                   :readonly="useCaregiverAsEmergency"
                                   class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium"
                                   placeholder="John Doe">
                        </div>

                        <div>
                            <label for="emergency_phone" class="block text-sm font-bold text-gray-700 mb-2">
                                Emergency Contact Phone
                                <span x-show="useCaregiverAsEmergency" class="text-blue-600 font-normal">(Please enter your caregiver's phone)</span>
                            </label>
                            <input id="emergency_phone" type="tel" name="emergency_phone" 
                                   x-model="emergencyPhone"
                                   class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium"
                                   :class="useCaregiverAsEmergency ? 'ring-2 ring-blue-300 border-blue-300' : ''"
                                   placeholder="+1234567890">
                        </div>

                        <div>
                            <label for="emergency_relationship" class="block text-sm font-bold text-gray-700 mb-2">Relationship</label>
                            <input id="emergency_relationship" type="text" name="emergency_relationship" 
                                   x-model="emergencyRelationship"
                                   :class="useCaregiverAsEmergency ? 'bg-gray-100' : ''"
                                   :readonly="useCaregiverAsEmergency"
                                   class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium"
                                   placeholder="Spouse, Child, etc.">
                        </div>
                    </div>

                    <!-- Step 3: Medical Info -->
                    <div x-show="currentStep === 3"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform translate-x-8"
                         x-transition:enter-end="opacity-100 transform translate-x-0"
                         class="space-y-5">
                        
                        <div>
                            <label for="conditions" class="block text-sm font-bold text-gray-700 mb-2">Medical Conditions (comma-separated)</label>
                            <textarea id="conditions" name="conditions" rows="3"
                                      class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium"
                                      placeholder="Diabetes, Hypertension">{{ old('conditions') }}</textarea>
                        </div>

                        <div>
                            <label for="medications" class="block text-sm font-bold text-gray-700 mb-2">Current Medications (comma-separated)</label>
                            <textarea id="medications" name="medications" rows="3"
                                      class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium"
                                      placeholder="Aspirin, Metformin">{{ old('medications') }}</textarea>
                        </div>

                        <div>
                            <label for="allergies" class="block text-sm font-bold text-gray-700 mb-2">Allergies (comma-separated)</label>
                            <textarea id="allergies" name="allergies" rows="3"
                                      class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium"
                                      placeholder="Penicillin, Peanuts">{{ old('allergies') }}</textarea>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="flex justify-between items-center mt-8 pt-6 border-t-2 border-gray-100">
                        <button type="button" @click="prevStep" 
                                x-show="currentStep > 1"
                                class="px-6 py-3 bg-gray-100 text-gray-700 font-bold rounded-lg hover:bg-gray-200 transition-all duration-200">
                            ← Back
                        </button>

                        <a href="{{ route('profile.completion.skip') }}" 
                           class="text-sm text-gray-500 hover:text-gray-700 font-semibold transition-colors">
                            Skip for now
                        </a>

                        <button type="button" @click="nextStep" 
                                x-show="currentStep < 3"
                                class="group relative">
                            <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-lg opacity-50 blur transition duration-200 group-hover:opacity-75"></div>
                            <div class="relative px-8 py-3 bg-[#000080] text-white font-bold rounded-lg transition-all duration-200">
                                Next →
                            </div>
                        </button>

                        <button type="submit" 
                                x-show="currentStep === 3"
                                class="group relative">
                            <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-lg opacity-50 blur transition duration-200 group-hover:opacity-75"></div>
                            <div class="relative px-8 py-3 bg-[#000080] text-white font-[800] text-lg rounded-lg shadow-[0_4px_12px_rgba(0,0,128,0.3)] transition-all duration-200">
                                Complete Profile
                            </div>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

</body>
</html>
