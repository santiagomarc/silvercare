{{-- Session flash messages.

     One block per outcome. `role="status"` for success (announced politely)
     and `role="alert"` for errors (announced immediately) — the difference
     matters to anyone using a screen reader.

     Colour is never the only signal: each message carries an icon and its
     own wording. --}}

@php
    $flashes = [
        ['key' => 'success', 'class' => 'sc-flash-ok',    'icon' => 'circle-check',  'role' => 'status', 'label' => 'Success'],
        ['key' => 'warning', 'class' => 'sc-flash-warn',  'icon' => 'triangle-alert','role' => 'status', 'label' => 'Warning'],
        ['key' => 'error',   'class' => 'sc-flash-alert', 'icon' => 'circle-alert',  'role' => 'alert',  'label' => 'Problem'],
    ];
@endphp

@foreach ($flashes as $flash)
    @if (session($flash['key']))
        <div x-data="{ show: true }"
             x-show="show"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             role="{{ $flash['role'] }}"
             class="sc-flash {{ $flash['class'] }} mb-6">

            <x-dynamic-component :component="'lucide-' . $flash['icon']"
                                 class="sc-i w-6 h-6 mt-0.5" aria-hidden="true" />

            <p class="min-w-0">
                <span class="sr-only">{{ $flash['label'] }}: </span>{{ session($flash['key']) }}
            </p>

            <button type="button" @click="show = false" class="sc-flash-close">
                <x-lucide-x class="sc-i w-5 h-5" aria-hidden="true" />
                <span class="sr-only">Dismiss this message</span>
            </button>
        </div>
    @endif
@endforeach
