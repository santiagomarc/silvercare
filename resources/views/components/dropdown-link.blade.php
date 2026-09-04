{{-- One row inside <x-dropdown>. Same shape as a drawer row: full width,
     44px+ tall, and named by its own text. --}}
<a {{ $attributes->merge(['class' => 'sc-menu-link']) }}>{{ $slot }}</a>
