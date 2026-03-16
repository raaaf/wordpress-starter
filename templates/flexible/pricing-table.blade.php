{{--
    Pricing Table Flexible Content Layout

    Uses shared components: x-section, x-grid, x-button, x-badge
    Fields: title, plans (repeater: name, price, period, features, cta, is_featured), background_color
--}}

@php
    $title = str_replace('[br]', '<br>', get_sub_field('title') ?: '');
    $plans = get_sub_field('plans') ?: [];
    $background = get_sub_field('background_color') ?: 'primary';

    // Use explicit grid classes to ensure Tailwind includes them
    $planCount = count($plans);
    $gridClass = match(true) {
        $planCount >= 3 => 'md:grid-cols-3',
        $planCount === 2 => 'md:grid-cols-2',
        default => 'md:grid-cols-1',
    };
@endphp

<x-section :anchor="$sectionAnchor" :background="$background" class="pricing-table">
    @if($title)
        <h2 class="mb-12 text-center">{!! $title !!}</h2>
    @endif

    @if(!empty($plans))
        <div class="grid gap-8 {{ $gridClass }}">
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
                        <x-badge variant="accent" size="md" class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2">
                            {{ __('Empfohlen', 'wp-starter') }}
                        </x-badge>
                    @endif

                    @if($name)
                        <h3 class="text-h4 mb-4 {{ $isFeatured ? 'text-content-inverse' : '' }}">{{ $name }}</h3>
                    @endif

                    <div class="mb-6">
                        @if($price)
                            <span class="text-h1 {{ $isFeatured ? 'text-content-inverse' : 'text-content' }}">{{ $price }}</span>
                        @endif
                        @if($period)
                            <span class="{{ $isFeatured ? 'text-content-inverse opacity-80' : 'text-content-secondary' }}">/ {{ $period }}</span>
                        @endif
                    </div>

                    @if($features)
                        <div class="flex-grow mb-8 [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:space-y-2 [&_li]:pl-1 {{ $isFeatured ? 'text-content-inverse [&_li]:marker:text-content-inverse' : 'text-content [&_li]:marker:text-content-brand' }}">
                            {!! wp_kses_post($features ?? '') !!}
                        </div>
                    @endif

                    @if($cta)
                        <x-button
                            :url="$cta['url'] ?? '#'"
                            :target="$cta['target'] ?? '_self'"
                            :title="$cta['title'] ?? 'Auswählen'"
                            :variant="$isFeatured ? 'secondary' : 'primary'"
                            size="lg"
                            class="w-full justify-center"
                        />
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="p-8 text-center rounded-lg bg-surface-secondary">
            <p class="text-content-secondary">{{ __('Bitte füge mindestens ein Preispaket hinzu.', 'wp-starter') }}</p>
        </div>
    @endif
</x-section>
