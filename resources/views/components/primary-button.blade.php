<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-6 py-3 bg-navy-600 text-white font-bold text-sm rounded-xl min-h-touch shadow-glow-brand hover:bg-navy-700 hover:-translate-y-0.5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-navy-500 active:scale-[0.97] transition-all duration-200']) }}>
    {{ $slot }}
</button>
