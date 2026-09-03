{{-- Flash message above an auth form ("we sent you a link", "password reset"). --}}
@props(['status'])

@if ($status)
    <p {{ $attributes->merge(['class' => 'flex items-start gap-2.5 p-4 rounded-2xl']) }}
       style="background:var(--sc-ok-tint);border:1px solid var(--sc-ok-line);color:var(--sc-ok)">
        <svg class="sc-i w-5 h-5 mt-0.5" aria-hidden="true" focusable="false"><use href="#i-check-circle"/></svg>
        <span class="font-medium">{{ $status }}</span>
    </p>
@endif
