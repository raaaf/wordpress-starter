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
    $variants = [
        'info' => [
            'wrapper' => 'bg-surface-accent-subtle border border-line-accent text-content-accent',
            'icon' => 'plus',
            'iconClass' => 'text-icon-accent',
        ],
        'success' => [
            'wrapper' => 'bg-surface-success border border-line-success text-content-success',
            'icon' => 'check',
            'iconClass' => 'text-icon-success',
        ],
        'warning' => [
            'wrapper' => 'bg-surface-warning border border-line-warning text-content-warning',
            'icon' => 'warning',
            'iconClass' => 'text-icon-warning',
        ],
        'error' => [
            'wrapper' => 'bg-surface-error border border-line-error text-content-error',
            'icon' => 'warning',
            'iconClass' => 'text-icon-error',
        ],
    ];

    $config = $variants[$variant] ?? $variants['info'];
@endphp

<div
    role="alert"
    @if($dismissible) x-data="{ show: true }" x-show="show" @endif
    class="flex items-start gap-3 p-4 rounded-lg {{ $config['wrapper'] }} {{ $class }}"
>
    <x-icon name="{{ $config['icon'] }}" class="w-5 h-5 {{ $config['iconClass'] }} shrink-0 mt-0.5" />
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
            class="shrink-0 text-current opacity-70 hover:opacity-100 transition-opacity"
            aria-label="{{ __('Schließen', 'wp-starter') }}"
        >
            <x-icon name="close" class="w-4 h-4" />
        </button>
    @endif
</div>
