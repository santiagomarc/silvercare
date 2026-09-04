{{-- The same link stacked in a menu. --}}
@props(['active'])

@php
$classes = ($active ?? false) ? 'sc-menu-link sc-menu-link-on' : 'sc-menu-link';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if($active ?? false) aria-current="page" @endif>
    {{ $slot }}
</a>
