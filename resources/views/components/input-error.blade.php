{{-- Shared field error. Sits directly under its field, carries an icon as well
     as the colour (colour alone is not a signal), and announces itself.

     Prefer the `field` form — it pulls the messages itself and sets the id that
     <x-text-input> points its aria-describedby at:

         <x-input-error field="email" />

     `:messages` is still accepted for older call sites. --}}
@props(['messages' => null, 'field' => null])

@php
    $items = $messages ?? ($field ? $errors->get($field) : []);
@endphp

@if (! empty($items))
    <div @if ($field) id="{{ $field }}-error" @endif
         {{ $attributes->merge(['class' => 'space-y-1']) }} role="alert">
        @foreach ((array) $items as $message)
            <p class="sc-error">
                <svg class="sc-i w-5 h-5" aria-hidden="true" focusable="false"><use href="#i-alert"/></svg>
                <span>{{ $message }}</span>
            </p>
        @endforeach
    </div>
@endif
