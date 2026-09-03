{{-- Subordinate action: same size and shape as the primary, quieter surface. --}}
<button {{ $attributes->merge(['type' => 'button', 'class' => 'sc-btn sc-btn-ghost']) }}>
    {{ $slot }}
</button>
