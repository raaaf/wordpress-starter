{{--
    Alert Component

    @param string $variant - info, success, warning, error (default: info)
    @param string $message - Alert message text (alternative to slot)
    @param bool $dismissible - Show dismiss button
    @param string $class - Additional CSS classes
--}}

@props([
    'variant' => 'info',
    'message' => null,
    'dismissible' => false,
    'class' => '',
])

@php
    // Body text uses text-content on subtle surfaces to guarantee 4.5:1 contrast.
    // Status-tinted text (text-content-{status}) is reserved for the icon only.
    $variants = [
        'info' => [
            'wrapper' => 'bg-surface-accent-subtle border border-line-accent text-content',
            'icon' => 'info',
            'iconClass' => 'text-icon-accent',
        ],
        'success' => [
            'wrapper' => 'bg-surface-success border border-line-success text-content',
            'icon' => 'check-circle',
            'iconClass' => 'text-icon-success',
        ],
        'warning' => [
            'wrapper' => 'bg-surface-warning border border-line-warning text-content',
            'icon' => 'warning',
            'iconClass' => 'text-icon-warning',
        ],
        'error' => [
            'wrapper' => 'bg-surface-error border border-line-error text-content',
            'icon' => 'warning',
            'iconClass' => 'text-icon-error',
        ],
    ];

    $config = $variants[$variant] ?? $variants['info'];
@endphp

<div
    role="{{ $variant === 'error' ? 'alert' : 'status' }}"
    @if($dismissible) x-data="{ show: true }" x-show="show" @endif
    class="alert flex items-start gap-3 p-4 rounded-lg {{ $config['wrapper'] }} {{ $class }}"
>
    {{-- Das Icon zentriert sich in einer Box von exakt einer Zeilenhoehe (1lh), nicht
         per fester mt-Korrektur. Der Wrapper bleibt bei mehrzeiligen Hinweisen eine
         Zeile hoch, das Icon sitzt also immer auf der ersten Zeile mittig — auch wenn
         Schriftgroesse oder Zeilenabstand sich aendern. Vorher lag es gemessen 2px zu
         tief, weil mt-0.5 auf eine bereits stimmige Position addiert wurde.

         text-sm muss mit, sonst rechnet 1lh mit der geerbten Zeilenhoehe des Alerts
         (24px) statt mit der des Meldungstextes (20px) — und der Versatz waere wieder
         genau die Differenz. --}}
    <span class="flex h-[1lh] shrink-0 items-center text-sm">
        <x-icon name="{{ $config['icon'] }}" size="lg" class="{{ $config['iconClass'] }}" />
    </span>
    <div class="flex-1 text-sm">
        @if($message)
            {{ $message }}
        @else
            {{ $slot }}
        @endif
    </div>
    @if($dismissible)
        <button
            type="button"
            @click="show = false"
            {{-- Zwei Anforderungen, die sich widersprechen: WCAG 2.5.8 will 24x24 als
                 Trefferflaeche, die Textzeile daneben ist aber nur 20px hoch. Ein
                 24px-Kasten an einer 20px-Zeile sitzt zwangslaeufig 2px zu tief.
                 Also volle Trefferflaeche behalten und die Differenz per negativem
                 Rand nach oben ausgleichen — der Button bleibt 24x24, sein Mittelpunkt
                 liegt aber auf der Zeilenmitte. Symmetrisch, damit er die Hoehe des
                 Hinweises nicht veraendert. --}}
            {{-- bg-current/10 derives the hover fill from the button's own inherited
                 colour (text-content, the same in every variant's wrapper), not a
                 fixed value, so it reads on every alert colour without per-variant
                 branching: a light-mode darken, a dark-mode lighten. --}}
            class="flex h-6 w-6 -my-0.5 shrink-0 items-center justify-center rounded-md text-current opacity-70 hover:opacity-100 hover:bg-current/10 transition-[opacity,background-color] duration-150"
            aria-label="{{ __('Schließen', 'wp-starter') }}"
        >
            <x-icon name="close" class="w-4 h-4" />
        </button>
    @endif
</div>
