{{--
    Pricing Table Block

    Uses shared components: x-section, x-grid
    Fields: title, plans (repeater: name, price, period, features, cta, is_featured), background_color
--}}

@php
    $title = $fields['title'] ?? '';
    $plans = $fields['plans'] ?? [];
    $background = $fields['background_color'] ?? 'primary';
@endphp

<x-section :background="$background" :anchor="$anchor" class="{{ $classes }} pricing-table">
    @if($title)
        <h2 class="text-h2 mb-12 text-center text-content">{{ $title }}</h2>
    @endif

    @if(!empty($plans))
        <div class="grid gap-8 md:grid-cols-{{ min(count($plans), 3) }}">
            @foreach($plans as $plan)
                @php
                    $isFeatured = $plan['is_featured'] ?? false;
                    $name = $plan['name'] ?? '';
                    $price = $plan['price'] ?? '';
                    $period = $plan['period'] ?? '';
                    $features = $plan['features'] ?? '';
                    $cta = $plan['cta'] ?? null;
                @endphp
                <div class="relative flex flex-col p-8 rounded-xl {{ $isFeatured ? 'bg-surface-brand text-content-inverse ring-4 ring-surface-brand ring-offset-2' : 'bg-surface-secondary' }}">
                    @if($isFeatured)
                        <span class="absolute top-0 px-4 py-1 text-sm font-medium -translate-x-1/2 -translate-y-1/2 rounded-full left-1/2 bg-surface-brand-secondary text-content-inverse">
                            Empfohlen
                        </span>
                    @endif

                    @if($name)
                        <h3 class="text-h4 mb-4 {{ $isFeatured ? 'text-content-inverse' : 'text-content' }}">{{ $name }}</h3>
                    @endif

                    <div class="mb-6">
                        @if($price)
                            <span class="text-h1 {{ $isFeatured ? 'text-content-inverse' : 'text-content' }}">{{ $price }}</span>
                        @endif
                        @if($period)
                            <span class="{{ $isFeatured ? 'text-content-inverse/80' : 'text-content-secondary' }}">/ {{ $period }}</span>
                        @endif
                    </div>

                    @if($features)
                        <div class="flex-grow mb-8 prose {{ $isFeatured ? 'prose-invert' : '' }} prose-li:marker:text-content-brand">
                            {!! $features !!}
                        </div>
                    @endif

                    @if($cta)
                        <a
                            href="{{ $cta['url'] ?? '#' }}"
                            @if($cta['target'] ?? false) target="{{ $cta['target'] }}" @endif
                            class="block w-full px-6 py-3 font-medium text-center transition-colors rounded-lg {{ $isFeatured ? 'bg-surface text-content-brand hover:bg-surface/90' : 'bg-surface-brand text-content-inverse hover:bg-surface-brand-hover' }}"
                        >
                            {{ $cta['title'] ?? 'Auswählen' }}
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-section>
