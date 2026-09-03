{{-- Shared text input.

     The error state is worked out here, from the field's own name, rather than
     being passed in. `@error(...)` does not compile inside a Blade *component*
     tag — it is passed through as a literal string and ends up as a class name,
     which silently paints every field red. Doing it here removes that trap.

         <x-text-input id="email" name="email" type="email" required />

     Styling lives in resources/css/silvercare-ui.css (.sc-input). --}}
@props(['disabled' => false])

@php
    $field    = $attributes->get('name');
    $hasError = $field && $errors->has($field);
@endphp

<input @disabled($disabled)
       @if ($hasError) aria-invalid="true" aria-describedby="{{ $field }}-error" @endif
       {{ $attributes->merge(['class' => 'sc-input' . ($hasError ? ' sc-input-error' : '')]) }}>
