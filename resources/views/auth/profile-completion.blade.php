<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#FAF9F6" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0B1220" media="(prefers-color-scheme: dark)">
    <title>Complete your profile — SilverCare</title>

    <link rel="icon" type="image/png" href="{{ asset('assets/icons/silvercare.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/icons/silvercare.png') }}">

    @include('partials.sc-theme-boot')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@500;600;700;800&family=Newsreader:ital,opsz,wght@1,6..72,400;1,6..72,500&display=swap" rel="stylesheet">
    @include('partials.sc-fonts')

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* The wizard shows one step at a time; the script toggles this class.
           It must beat the display the design system gives these blocks. */
        .hidden-step { display: none !important; }
    </style>
</head>
<body class="sc-page antialiased">

<a class="sc-skip" href="#main-content">Skip to main content</a>

@include('partials.sc-icons')

<div class="sc-ambient min-h-screen flex items-center justify-center px-5 py-12">
    <div class="w-full max-w-2xl">

        <div class="flex justify-center mb-8">
            <span class="sc-brand">
                <span class="sc-brand-mark"><img src="{{ asset('assets/icons/silvercare.png') }}" alt=""></span>
                <span class="sc-brand-word">SilverCare</span>
            </span>
        </div>

        <main id="main-content" class="sc-card p-6 sm:p-9">

            <div class="text-center mb-12">
                <h1 class="sc-h3">Just a few details</h1>
                <p class="mt-3 mx-auto max-w-sm" style="color:var(--sc-body)">
                    This is what lets us watch out for you properly. It takes about a minute.
                </p>
            </div>

            <input type="hidden" id="currentStep" value="1">

            {{-- Step indicator. The script rewrites the dot and label classes,
                 so those class names are part of its contract — see the bottom
                 of this file. --}}
            <div class="sc-steps mb-10" role="group" aria-label="Progress through 3 steps">
                <span class="sc-steps-track" aria-hidden="true"></span>
                <span id="pb-line" class="sc-steps-fill" aria-hidden="true"></span>

                <div class="flex flex-col items-center flex-1 relative z-10">
                    <span id="pb-step-1" class="sc-step-dot sc-step-dot-on">1</span>
                    <span id="pb-label-1" class="sc-step-label sc-step-label-on">About you</span>
                </div>
                <div class="flex flex-col items-center flex-1 relative z-10">
                    <span id="pb-step-2" class="sc-step-dot">2</span>
                    <span id="pb-label-2" class="sc-step-label">Emergency</span>
                </div>
                <div class="flex flex-col items-center flex-1 relative z-10">
                    <span id="pb-step-3" class="sc-step-dot">3</span>
                    <span id="pb-label-3" class="sc-step-label">Health</span>
                </div>
            </div>

            <form id="profileForm" method="POST" action="{{ route('profile.completion.store') }}">
                @csrf

                {{-- Values from every step are copied here before submit, so a
                     step that is currently hidden still posts. --}}
                <input type="hidden" id="hidden_age" name="age" value="">
                <input type="hidden" id="hidden_weight" name="weight" value="">
                <input type="hidden" id="hidden_height" name="height" value="">
                <input type="hidden" id="hidden_emergency_name" name="emergency_name" value="">
                <input type="hidden" id="hidden_emergency_phone" name="emergency_phone" value="">
                <input type="hidden" id="hidden_emergency_relationship" name="emergency_relationship" value="">
                <input type="hidden" id="hidden_conditions" name="conditions" value="">
                <input type="hidden" id="hidden_medications" name="medications" value="">
                <input type="hidden" id="hidden_allergies" name="allergies" value="">

                {{-- ── Step 1 ──────────────────────────────────────── --}}
                <div id="step-1">
                    <div class="sc-field">
                        <label for="age" class="sc-label sc-label-req">Age</label>
                        <input id="age" type="number" name="age" required value="{{ old('age') }}"
                               min="1" max="120" inputmode="numeric"
                               class="step-1-input sc-input" placeholder="65">
                    </div>

                    <div class="grid sm:grid-cols-2 gap-x-5">
                        <div class="sc-field">
                            <label for="weight" class="sc-label sc-label-req">Weight <span class="sc-label-opt">in kg</span></label>
                            <input id="weight" type="number" step="0.1" name="weight" required value="{{ old('weight') }}"
                                   inputmode="decimal"
                                   class="step-1-input sc-input" placeholder="70.5">
                        </div>

                        <div class="sc-field">
                            <label for="height" class="sc-label sc-label-req">Height <span class="sc-label-opt">in cm</span></label>
                            <input id="height" type="number" step="0.1" name="height" required value="{{ old('height') }}"
                                   inputmode="decimal"
                                   class="step-1-input sc-input" placeholder="170.0">
                        </div>
                    </div>
                </div>

                {{-- ── Step 2 ──────────────────────────────────────── --}}
                <div id="step-2" class="hidden-step">
                    <p class="sc-help mb-6" style="margin-top:0">
                        The person we contact first if something is wrong.
                    </p>

                    <div class="sc-field">
                        <label for="emergency_name" class="sc-label sc-label-req">Their name</label>
                        <input id="emergency_name" type="text" name="emergency_name" required value="{{ old('emergency_name') }}"
                               autocomplete="name"
                               class="step-2-input sc-input" placeholder="Sarah Pendelton">
                    </div>

                    <div class="sc-field">
                        <label for="emergency_phone" class="sc-label sc-label-req">Their phone number</label>
                        <input id="emergency_phone" type="tel" name="emergency_phone" required value="{{ old('emergency_phone') }}"
                               autocomplete="tel" inputmode="tel"
                               class="step-2-input sc-input" placeholder="+63 912 345 6789">
                    </div>

                    <div class="sc-field">
                        <label for="emergency_relationship" class="sc-label sc-label-req">How you know them</label>
                        <select id="emergency_relationship" name="emergency_relationship" required
                                class="step-2-input sc-select">
                            <option value="">Choose one…</option>
                            <option value="Spouse (Asawa)" {{ old('emergency_relationship') == 'Spouse (Asawa)' ? 'selected' : '' }}>Spouse (Asawa)</option>
                            <option value="Child (Anak)" {{ old('emergency_relationship') == 'Child (Anak)' ? 'selected' : '' }}>Child (Anak)</option>
                            <option value="Family/Relative (Pamilya/Kamag-anak)" {{ old('emergency_relationship') == 'Family/Relative (Pamilya/Kamag-anak)' ? 'selected' : '' }}>Family/Relative (Pamilya/Kamag-anak)</option>
                            <option value="Friend (Kaibigan)" {{ old('emergency_relationship') == 'Friend (Kaibigan)' ? 'selected' : '' }}>Friend (Kaibigan)</option>
                            <option value="Neighbor (Kapitbahay)" {{ old('emergency_relationship') == 'Neighbor (Kapitbahay)' ? 'selected' : '' }}>Neighbor (Kapitbahay)</option>
                        </select>
                    </div>
                </div>

                {{-- ── Step 3 ──────────────────────────────────────── --}}
                <div id="step-3" class="hidden-step">
                    <p class="sc-help mb-6" style="margin-top:0">
                        Separate each one with a comma. If none apply, tick the box underneath.
                    </p>

                    <div class="sc-field">
                        <label for="conditions" class="sc-label sc-label-req">Medical conditions</label>
                        <textarea id="conditions" name="conditions" rows="3" required
                                  class="step-3-input sc-textarea"
                                  placeholder="Diabetes, Hypertension">{{ old('conditions') }}</textarea>
                        <label class="sc-check">
                            <input type="checkbox" id="noConditions">
                            <span style="color:var(--sc-body)">None (Wala)</span>
                        </label>
                    </div>

                    <div class="sc-field">
                        <label for="medications" class="sc-label sc-label-req">Current medications</label>
                        <textarea id="medications" name="medications" rows="3" required
                                  class="step-3-input sc-textarea"
                                  placeholder="Aspirin, Metformin">{{ old('medications') }}</textarea>
                        <label class="sc-check">
                            <input type="checkbox" id="noMedications">
                            <span style="color:var(--sc-body)">None (Wala)</span>
                        </label>
                    </div>

                    <div class="sc-field">
                        <label for="allergies" class="sc-label sc-label-req">Allergies</label>
                        <textarea id="allergies" name="allergies" rows="3" required
                                  class="step-3-input sc-textarea"
                                  placeholder="Penicillin, Peanuts">{{ old('allergies') }}</textarea>
                        <label class="sc-check">
                            <input type="checkbox" id="noAllergies">
                            <span style="color:var(--sc-body)">None (Wala)</span>
                        </label>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 mt-8 pt-7"
                     style="border-top:1px solid var(--sc-line)">
                    <button type="button" id="btn-back" class="sc-btn sc-btn-ghost hidden-step">
                        <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-chevron-left"/></svg>
                        Back
                    </button>

                    <button type="button" id="btn-next" class="sc-btn sc-btn-primary ml-auto" disabled>
                        Next
                        <svg class="sc-i sc-arrow w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-arrow-right"/></svg>
                    </button>

                    <button type="submit" id="btn-complete" class="sc-btn sc-btn-primary ml-auto hidden-step" disabled>
                        Finish
                        <svg class="sc-i sc-arrow w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-check"/></svg>
                    </button>
                </div>
            </form>
        </main>
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
                        btnNext.disabled = false;
                        btnNext.removeAttribute('disabled');
                    } else {
                        btnNext.disabled = true;
                        btnNext.setAttribute('disabled', 'true');
                    }
                } else {
                    if (isValid) {
                        btnComplete.disabled = false;
                        btnComplete.removeAttribute('disabled');
                    } else {
                        btnComplete.disabled = true;
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
                            textarea.classList.add('sc-input-muted');
                        } else {
                            textarea.value = '';
                            textarea.disabled = false;
                            textarea.classList.remove('sc-input-muted');
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
                        stepCircle.className = 'sc-step-dot sc-step-dot-on';
                        stepLabel.className = 'sc-step-label sc-step-label-on';
                    } else {
                        stepCircle.className = 'sc-step-dot';
                        stepLabel.className = 'sc-step-label';
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
