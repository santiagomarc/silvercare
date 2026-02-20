<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SilverCare</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/icons/silvercare.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/icons/silvercare.png') }}">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Montserrat', sans-serif; }
        
        /* Custom Animation Classes */
        .fade-in-section {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.8s ease-out, transform 0.8s ease-out;
            will-change: opacity, visibility;
        }
        .fade-in-section.is-visible {
            opacity: 1;
            transform: none;
        }
    </style>
</head>
<body class="antialiased bg-[#DEDEDE] overflow-hidden">

    <div class="min-h-screen w-full flex relative">
        
        <div class="w-full lg:w-5/12 h-screen flex flex-col justify-center items-center px-6 relative z-10">
            
            <div class="w-full max-w-sm flex flex-col items-center gap-y-8">
                
                <div class="fade-in-section transition-delay-100">
                    <div class="w-[180px] h-[180px] flex items-center justify-center transform transition duration-500 hover:scale-110 hover:rotate-3 cursor-pointer">
                        <img src="{{ asset('assets/icons/silvercare.png') }}" alt="Logo" class="w-full h-full object-contain drop-shadow-lg">
                    </div>
                </div>

                <div class="text-center space-y-2 fade-in-section transition-delay-200">
                    <h1 class="text-5xl font-[900] tracking-tighter text-gray-900 drop-shadow-sm">
                        <span class="text-[#6B7280]">SILVER</span><span class="text-black">CARE</span>
                    </h1>
                    <p class="text-gray-500 font-bold text-sm tracking-[0.2em] uppercase">
                        Elderly Care Management
                    </p>
                </div>

                <div class="h-4"></div>

                <div class="w-full space-y-5 fade-in-section transition-delay-300">
                    
                    <a href="{{ route('register') }}" 
                       class="group relative block w-full">
                        <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-[82px] opacity-50 blur transition duration-200 group-hover:opacity-75"></div>
                        <button class="relative w-full py-4 bg-[#000080] text-white font-[800] text-xl rounded-[82px] shadow-[0_8px_20px_rgba(0,0,128,0.3)] transition-all duration-300 transform group-hover:-translate-y-1 group-active:scale-95">
                            SIGN UP
                        </button>
                    </a>

                    <a href="{{ route('login') }}" 
                       class="group block w-full">
                        <button class="w-full py-4 bg-white text-black font-[800] text-xl rounded-[82px] shadow-[0_8px_20px_rgba(0,0,0,0.15)] border-2 border-transparent hover:border-gray-100 transition-all duration-300 transform group-hover:-translate-y-1 group-hover:shadow-[0_12px_24px_rgba(0,0,0,0.2)] group-active:scale-95">
                            SIGN IN
                        </button>
                    </a>
                </div>
                
            </div>


        </div>

        <div class="hidden lg:block lg:w-7/12 relative overflow-hidden bg-gray-900">
            
            <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1581579186913-45ac3e6e3dd2?q=80&w=2048&auto=format&fit=crop')] bg-cover bg-center opacity-60 transition-transform duration-[10s] ease-linear hover:scale-110"></div>
            
            <div class="absolute inset-0 bg-gradient-to-r from-[#DEDEDE] via-[#DEDEDE]/20 to-transparent"></div>
            
            <div class="absolute bottom-24 left-16 max-w-lg fade-in-section transition-delay-700 z-20">
                <div class="bg-black/60 backdrop-blur-md border-l-4 border-[#000080] p-8 rounded-r-2xl shadow-2xl transform transition hover:translate-x-2 duration-300">
                    <h3 class="text-3xl font-bold text-white mb-3 drop-shadow-lg">Always Connected.</h3>
                    <p class="text-gray-100 text-lg font-medium leading-relaxed drop-shadow-md">
                        "Because caring for our loved ones isn't just a duty—it's an honor."
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Staggered Entrance Animation
            // This finds all elements with 'fade-in-section' and adds 'is-visible' one by one
            const observerOptions = {
                root: null,
                rootMargin: '0px',
                threshold: 0.1
            };

            const observer = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        // Add a small delay based on the class name for staggering
                        if (entry.target.classList.contains('transition-delay-100')) {
                            setTimeout(() => entry.target.classList.add('is-visible'), 100);
                        } else if (entry.target.classList.contains('transition-delay-200')) {
                            setTimeout(() => entry.target.classList.add('is-visible'), 300);
                        } else if (entry.target.classList.contains('transition-delay-300')) {
                            setTimeout(() => entry.target.classList.add('is-visible'), 500);
                        } else if (entry.target.classList.contains('transition-delay-500')) {
                            setTimeout(() => entry.target.classList.add('is-visible'), 700);
                        } else {
                            entry.target.classList.add('is-visible');
                        }
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.fade-in-section').forEach((section) => {
                observer.observe(section);
            });
        });
    </script>
</body>
</html>