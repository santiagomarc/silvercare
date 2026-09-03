{{-- The one loud button on a screen. If a page needs two, the second one is a
     <x-secondary-button>. --}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'sc-btn sc-btn-primary']) }}>
    {{ $slot }}
</button>
