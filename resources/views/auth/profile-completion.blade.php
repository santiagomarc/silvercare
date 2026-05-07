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
        .hidden-step { display: none !important; }
    </style>
</head>
<body class="antialiased bg-[#DEDEDE] min-h-screen">

    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-2xl">
            
            <div class="text-center mb-8">
                <h1 class="text-4xl font-[900] text-gray-900 tracking-tight mb-2">Complete Your Profile</h1>
                <p class="text-gray-600 font-medium">Help us personalize your SilverCare experience</p>
            </div>

            <div class="bg-white rounded-2xl shadow-[0_10px_40px_rgba(0,0,0,0.1)] p-8">
                
                <input type="hidden" id="currentStep" value="1">

                <!-- Progress Bar -->
                <div class="mb-10">
                    <div class="flex justify-between items-center relative">
                        
                        <div class="flex flex-col items-center flex-1 z-10">
                            <div id="pb-step-1" class="w-12 h-12 rounded-full flex items-center justify-center transition-all duration-300 font-bold text-lg bg-[#000080] text-white shadow-[0_4px_12px_rgba(0,0,128,0.3)]">
                                1
                            </div>
                            <span id="pb-label-1" class="text-xs mt-2 font-bold transition-colors text-[#000080]">Personal</span>
                        </div>
                        
                        <div class="absolute top-6 h-1 bg-gray-200 -z-0" style="left: 16.66%; right: 16.66%;"></div>
                        <div id="pb-line" class="absolute top-6 h-1 bg-[#000080] transition-all duration-500 -z-0" style="left: 16.66%; width: 0%;"></div>
                        
                        <div class="flex flex-col items-center flex-1 z-10">
                            <div id="pb-step-2" class="w-12 h-12 rounded-full flex items-center justify-center transition-all duration-300 font-bold text-lg bg-gray-200 text-gray-400">
                                2
                            </div>
                            <span id="pb-label-2" class="text-xs mt-2 font-bold transition-colors text-gray-400">Emergency</span>
                        </div>
                        
                        <div class="flex flex-col items-center flex-1 z-10">
                            <div id="pb-step-3" class="w-12 h-12 rounded-full flex items-center justify-center transition-all duration-300 font-bold text-lg bg-gray-200 text-gray-400">
                                3
                            </div>
                            <span id="pb-label-3" class="text-xs mt-2 font-bold transition-colors text-gray-400">Medical</span>
                        </div>
                    </div>
                </div>

                <form id="profileForm" method="POST" action="{{ route('profile.completion.store') }}">
                    @csrf

                    <!-- Hidden inputs to persist all 3 steps' values for submission -->
                    <input type="hidden" id="hidden_age" name="age" value="">
                    <input type="hidden" id="hidden_weight" name="weight" value="">
                    <input type="hidden" id="hidden_height" name="height" value="">
                    <input type="hidden" id="hidden_emergency_name" name="emergency_name" value="">
                    <input type="hidden" id="hidden_emergency_phone" name="emergency_phone" value="">
                    <input type="hidden" id="hidden_emergency_relationship" name="emergency_relationship" value="">
                    <input type="hidden" id="hidden_conditions" name="conditions" value="">
                    <input type="hidden" id="hidden_medications" name="medications" value="">
                    <input type="hidden" id="hidden_allergies" name="allergies" value="">

                    <!-- Step 1: Personal Info -->
                    <div id="step-1" class="space-y-5">
                        <div>
                            <label for="age" class="block text-sm font-bold text-gray-700 mb-2">Age</label>
                            <input id="age" type="number" name="age" required value="{{ old('age') }}"
                                   class="step-1-input w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium"
                                   placeholder="65">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="weight" class="block text-sm font-bold text-gray-700 mb-2">Weight (kg)</label>
                                <input id="weight" type="number" step="0.1" name="weight" required value="{{ old('weight') }}"
                                       class="step-1-input w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium"
                                       placeholder="70.5">
                            </div>

                            <div>
                                <label for="height" class="block text-sm font-bold text-gray-700 mb-2">Height (cm)</label>
                                <input id="height" type="number" step="0.1" name="height" required value="{{ old('height') }}"
                                       class="step-1-input w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium"
                                       placeholder="170.0">
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Emergency Contact -->
                    <div id="step-2" class="space-y-5 hidden-step">
                        <div>
                            <label for="emergency_name" class="block text-sm font-bold text-gray-700 mb-2">Emergency Contact Name</label>
                            <input id="emergency_name" type="text" name="emergency_name" required value="{{ old('emergency_name') }}"
                                   class="step-2-input w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium"
                                   placeholder="John Doe">
                        </div>

                        <div>
                            <label for="emergency_phone" class="block text-sm font-bold text-gray-700 mb-2">Emergency Contact Phone</label>
                            <input id="emergency_phone" type="tel" name="emergency_phone" required value="{{ old('emergency_phone') }}"
                                   class="step-2-input w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium"
                                   placeholder="+1234567890">
                        </div>

                        <div>
                            <label for="emergency_relationship" class="block text-sm font-bold text-gray-700 mb-2">Relationship</label>
                            <select id="emergency_relationship" name="emergency_relationship" required
                                   class="step-2-input w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium">
                                <option value="">Select Relationship...</option>
                                <option value="Spouse (Asawa)" {{ old('emergency_relationship') == 'Spouse (Asawa)' ? 'selected' : '' }}>Spouse (Asawa)</option>
                                <option value="Child (Anak)" {{ old('emergency_relationship') == 'Child (Anak)' ? 'selected' : '' }}>Child (Anak)</option>
                                <option value="Family/Relative (Pamilya/Kamag-anak)" {{ old('emergency_relationship') == 'Family/Relative (Pamilya/Kamag-anak)' ? 'selected' : '' }}>Family/Relative (Pamilya/Kamag-anak)</option>
                                <option value="Friend (Kaibigan)" {{ old('emergency_relationship') == 'Friend (Kaibigan)' ? 'selected' : '' }}>Friend (Kaibigan)</option>
                                <option value="Neighbor (Kapitbahay)" {{ old('emergency_relationship') == 'Neighbor (Kapitbahay)' ? 'selected' : '' }}>Neighbor (Kapitbahay)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Step 3: Medical Info -->
                    <div id="step-3" class="space-y-5 hidden-step">
                        <div>
                            <label for="conditions" class="block text-sm font-bold text-gray-700 mb-2">Medical Conditions (comma-separated)</label>
                            <textarea id="conditions" name="conditions" rows="3" required
                                      class="step-3-input w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium mb-3"
                                      placeholder="Diabetes, Hypertension">{{ old('conditions') }}</textarea>
                            <label class="flex items-center gap-2 cursor-pointer w-fit text-sm text-gray-600 font-medium">
                                <input type="checkbox" id="noConditions" 
                                    style="width: 14px; height: 14px; min-width: 30px; min-height: 30px; border-radius: 50%; accent-color: #000080; cursor: pointer;"
                                    class="border-gray-300"> None (Wala)
                            </label>
                            </label>
                        </div>

                        <div>
                            <label for="medications" class="block text-sm font-bold text-gray-700 mb-2">Current Medications (comma-separated)</label>
                            <textarea id="medications" name="medications" rows="3" required
                                      class="step-3-input w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium mb-3"
                                      placeholder="Aspirin, Metformin">{{ old('medications') }}</textarea>
                            <label class="flex items-center gap-2 cursor-pointer w-fit text-sm text-gray-600 font-medium">
                                <input type="checkbox" id="noMedications" 
                                    style="width: 14px; height: 14px; min-width: 30px; min-height: 30px; border-radius: 50%; accent-color: #000080; cursor: pointer;"
                                    class="border-gray-300"> None (Wala)
                            </label>
                            </label>
                        </div>

                        <div>
                            <label for="allergies" class="block text-sm font-bold text-gray-700 mb-2">Allergies (comma-separated)</label>
                            <textarea id="allergies" name="allergies" rows="3" required
                                      class="step-3-input w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/20 transition-all duration-200 font-medium mb-3"
                                      placeholder="Penicillin, Peanuts">{{ old('allergies') }}</textarea>
                            <label class="flex items-center gap-2 cursor-pointer w-fit text-sm text-gray-600 font-medium">
                                <input type="checkbox" id="noAllergies" 
                                    style="width: 14px; height: 14px; min-width: 30px; min-height: 30px; border-radius: 50%; accent-color: #000080; cursor: pointer;"
                                    class="border-gray-300"> None (Wala)
                            </label>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="flex justify-between items-center mt-8 pt-6 border-t-2 border-gray-100">
                        <button type="button" id="btn-back" class="px-6 py-3 bg-gray-100 text-gray-700 font-bold rounded-lg hover:bg-gray-200 transition-all duration-200 hidden-step order-last ml-4">
                            ← Back
                        </button>

                        <button type="button" id="btn-next" class="px-8 py-3 bg-[#000080] text-white font-bold rounded-lg transition-all duration-200 ml-auto opacity-50 cursor-not-allowed">
                            Next →
                        </button>

                        <button type="submit" id="btn-complete" class="group relative ml-auto w-full sm:w-auto hidden-step opacity-50 cursor-not-allowed">
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let currentStep = 1;
            const stateInput = document.getElementById('currentStep');
            const btnBack = document.getElementById('btn-back');
            const btnNext = document.getElementById('btn-next');
            const btnComplete = document.getElementById('btn-complete');

            const validateStep = (step) => {
                const inputs = Array.from(document.querySelectorAll(`.step-${step}-input`));
                return inputs.every(input => input.value.trim() !== '');
            };

            const updateButtonState = () => {
                const isValid = validateStep(currentStep);
                
                if (currentStep < 3) {
                    if (isValid) {
                        btnNext.classList.remove('opacity-50', 'cursor-not-allowed');
                        btnNext.removeAttribute('disabled');
                    } else {
                        btnNext.classList.add('opacity-50', 'cursor-not-allowed');
                        btnNext.setAttribute('disabled', 'true');
                    }
                } else {
                    if (isValid) {
                        btnComplete.classList.remove('opacity-50', 'cursor-not-allowed');
                        btnComplete.removeAttribute('disabled');
                    } else {
                        btnComplete.classList.add('opacity-50', 'cursor-not-allowed');
                        btnComplete.setAttribute('disabled', 'true');
                    }
                }
            };

            const attachInputListeners = () => {
                [1, 2, 3].forEach(step => {
                    document.querySelectorAll(`.step-${step}-input`).forEach(input => {
                        input.addEventListener('input', updateButtonState);
                        input.addEventListener('change', updateButtonState);
                    });
                });
            };

            const handleCheckboxes = () => {
                const config = [
                    { chk: 'noConditions', txt: 'conditions' },
                    { chk: 'noMedications', txt: 'medications' },
                    { chk: 'noAllergies', txt: 'allergies' }
                ];
                
                config.forEach(({chk, txt}) => {
                    const checkbox = document.getElementById(chk);
                    const textarea = document.getElementById(txt);
                    
                    checkbox.addEventListener('change', (e) => {
                        if (e.target.checked) {
                            textarea.value = 'none';
                            textarea.disabled = true;
                            textarea.classList.add('bg-gray-100', 'opacity-70', 'cursor-not-allowed');
                        } else {
                            textarea.value = '';
                            textarea.disabled = false;
                            textarea.classList.remove('bg-gray-100', 'opacity-70', 'cursor-not-allowed');
                        }
                        updateButtonState();
                    });
                });
            };

            // Copy functions to persist data across steps
            const copyStep1ToHidden = () => {
                document.getElementById('hidden_age').value = document.getElementById('age').value;
                document.getElementById('hidden_weight').value = document.getElementById('weight').value;
                document.getElementById('hidden_height').value = document.getElementById('height').value;
            };

            const copyStep2ToHidden = () => {
                document.getElementById('hidden_emergency_name').value = document.getElementById('emergency_name').value;
                document.getElementById('hidden_emergency_phone').value = document.getElementById('emergency_phone').value;
                document.getElementById('hidden_emergency_relationship').value = document.getElementById('emergency_relationship').value;
            };

            const copyStep3ToHidden = () => {
                document.getElementById('hidden_conditions').value = document.getElementById('conditions').value;
                document.getElementById('hidden_medications').value = document.getElementById('medications').value;
                document.getElementById('hidden_allergies').value = document.getElementById('allergies').value;
            };

            const updateUI = () => {
                // UI display toggle
                document.getElementById('step-1').classList.toggle('hidden-step', currentStep !== 1);
                document.getElementById('step-2').classList.toggle('hidden-step', currentStep !== 2);
                document.getElementById('step-3').classList.toggle('hidden-step', currentStep !== 3);
                
                // Button toggle
                btnBack.classList.toggle('hidden-step', currentStep === 1);
                btnNext.classList.toggle('hidden-step', currentStep === 3);
                btnComplete.classList.toggle('hidden-step', currentStep !== 3);

                // Progress bar updating
                document.getElementById('pb-line').style.width = ((currentStep - 1) * 33.33) + '%';
                
                [1, 2, 3].forEach(step => {
                    const stepCircle = document.getElementById(`pb-step-${step}`);
                    const stepLabel = document.getElementById(`pb-label-${step}`);
                    
                    if (step <= currentStep) {
                        stepCircle.className = 'w-12 h-12 rounded-full flex items-center justify-center transition-all duration-300 font-bold text-lg bg-[#000080] text-white shadow-[0_4px_12px_rgba(0,0,128,0.3)]';
                        stepLabel.className = 'text-xs mt-2 font-bold transition-colors text-[#000080]';
                    } else {
                        stepCircle.className = 'w-12 h-12 rounded-full flex items-center justify-center transition-all duration-300 font-bold text-lg bg-gray-200 text-gray-400';
                        stepLabel.className = 'text-xs mt-2 font-bold transition-colors text-gray-400';
                    }
                });

                stateInput.value = currentStep;
                updateButtonState();
            };

            btnNext.addEventListener('click', () => {
                if (currentStep < 3 && validateStep(currentStep)) {
                    // Copy current step data to hidden inputs before moving
                    if (currentStep === 1) {
                        copyStep1ToHidden();
                    } else if (currentStep === 2) {
                        copyStep2ToHidden();
                    }
                    currentStep++;
                    updateUI();
                }
            });

            btnBack.addEventListener('click', () => {
                if (currentStep > 1) {
                    currentStep--;
                    updateUI();
                }
            });

            // Copy all data to hidden fields before submission
            const profileForm = document.getElementById('profileForm');
            profileForm.addEventListener('submit', (e) => {
                copyStep1ToHidden();
                copyStep2ToHidden();
                copyStep3ToHidden();
            });
            
            // Initialization
            attachInputListeners();
            handleCheckboxes();
            updateUI(); // Run once to set initial state correctly
        });
    </script>
</body>
</html>