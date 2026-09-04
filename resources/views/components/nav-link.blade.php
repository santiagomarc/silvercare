{{-- A link in a horizontal bar. The current page keeps its rule drawn, so
     the state is carried by weight and colour, not by colour alone. --}}
@props(['active'])

@php
$classes = ($active ?? false) ? 'sc-nav-link sc-nav-link-on' : 'sc-nav-link';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if($active ?? false) aria-current="page" @endif>
    {{ $slot }}
</a>
