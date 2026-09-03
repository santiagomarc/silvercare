{{-- Destructive actions only. Kept visually distinct from the primary button
     so "delete" can never be mistaken for "save". --}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'sc-btn sc-btn-danger']) }}>
    {{ $slot }}
</button>
