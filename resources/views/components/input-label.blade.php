{{-- Shared field label. Always visible, never replaced by a placeholder:
     a placeholder disappears the moment someone types, which is exactly when
     an older reader looks up to check what they were filling in.

     <x-input-label for="email" :value="__('Email address')" required /> --}}
@props(['value' => null, 'required' => false])

<label {{ $attributes->merge(['class' => 'sc-label' . ($required ? ' sc-label-req' : '')]) }}>
    {{ $value ?? $slot }}
</label>
