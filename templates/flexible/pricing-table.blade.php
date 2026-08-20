{{--
    Pricing Table Flexible Content Layout

    Uses shared components: x-section, x-section-header, x-grid, x-button, x-badge
    Fields: title, plans (repeater: name, price, period, price_yearly, period_yearly,
    features, cta, is_featured), billing_toggle, background_color

    Die Spaltenzahl ist bewusst kein Feld: sie folgt der Zahl der Pakete, damit
    eine unvollstaendige letzte Zeile mittig bleibt.
--}}

@php
    $title = \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('title'));
    $kopf = \WordpressStarter\Helpers\SectionHeader::extras($title);
    $plans = get_sub_field('plans') ?: [];
    $background = get_sub_field('background_color') ?: 'primary';
    $billingToggle = (bool) get_sub_field('billing_toggle');

    // Flex statt Grid, damit eine unvollstaendige letzte Zeile mittig steht.
    // Ab drei Plaenen standen immer drei nebeneinander; bei vieren sass der
    // vierte allein und linksbuendig unter den anderen. Siehe stats.blade.php,
    // dasselbe Muster.
    //
    // Die Breitenklassen bleiben fest notiert, damit Tailwind sie findet.
    $planCount = count($plans);
    $itemClass = match(true) {
        $planCount >= 3 => 'md:w-[calc(33.333%-1.334rem)]',
        $planCount === 2 => 'md:w-[calc(50%-1rem)]',
        default => 'md:w-full',
    };
@endphp

@if($title || !empty($plans) || current_user_can('edit_posts'))
<x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :width="$sectionWidth ?? null" :background="$background" class="pricing-table">
    <x-section-header :chip="$kopf['chip']" :headline="$kopf['headline']" :description="$kopf['description']" :alignment="$kopf['alignment']" />

    @if(!empty($plans))
        <div @if($billingToggle) x-data="{ yearly: false }" @endif>
        @if($billingToggle)
            <div class="flex justify-center mb-8">
                <div class="inline-flex rounded-[var(--radius-md)] border border-line overflow-hidden" role="group" aria-label="{{ __('Abrechnungszeitraum', 'wp-starter') }}">
                    <button type="button"
                            @click="yearly = false"
                            :aria-pressed="!yearly"
                            :class="yearly ? 'bg-surface text-content-secondary' : 'bg-surface-brand text-content-on-brand'"
                            class="px-5 py-2 cursor-pointer transition-colors focus-visible:outline-none focus-visible:shadow-[var(--shadow-focus-ring)]">
                        {{ __('Monatlich', 'wp-starter') }}
                    </button>
                    <button type="button"
                            @click="yearly = true"
                            :aria-pressed="yearly"
                            :class="yearly ? 'bg-surface-brand text-content-on-brand' : 'bg-surface text-content-secondary'"
                            class="px-5 py-2 cursor-pointer transition-colors focus-visible:outline-none focus-visible:shadow-[var(--shadow-focus-ring)]">
                        {{ __('Jährlich', 'wp-starter') }}
                    </button>
                </div>
            </div>
        @endif

        <div class="flex flex-wrap justify-center gap-8">
            @foreach($plans as $plan)
                @php
                    $isFeatured = $plan['is_featured'] ?? false;
                    $name = $plan['name'] ?? '';
                    $price = $plan['price'] ?? '';
                    $period = $plan['period'] ?? '';
                    $features = $plan['features'] ?? '';
                    $cta = $plan['cta'] ?? null;

                    // Ein Paket ohne Jahrespreis behaelt seinen Monatspreis, sonst
                    // stuende dort im Jahrestarif eine Luecke.
                    $priceYearly = $plan['price_yearly'] ?? '';
                    $periodYearly = $plan['period_yearly'] ?? '';
                    $switchesPrice = $billingToggle && ($priceYearly !== '' || $periodYearly !== '');
                    $priceYearly = $priceYearly !== '' ? $priceYearly : $price;
                    $periodYearly = $periodYearly !== '' ? $periodYearly : $period;
                @endphp
                <div class="relative flex flex-col w-full p-8 rounded-[var(--card-radius)] border {{ $itemClass }} {{ $isFeatured ? 'bg-surface-brand text-content-on-brand border-line-brand shadow-[var(--shadow-card-hover)]' : 'bg-surface-secondary border-line shadow-[var(--shadow-card)]' }}">
                    @if($isFeatured)
                        <x-badge variant="accent" size="md" class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2">
                            {{ __('Empfohlen', 'wp-starter') }}
                        </x-badge>
                    @endif

                    @if($name)
                        <h3 class="text-h4 mb-4 {{ $isFeatured ? 'text-content-on-brand' : '' }}">{{ $name }}</h3>
                    @endif

                    <div class="mb-6">
                        @if($price !== '')
                            {{-- x-text statt zweier Spans mit x-show: der Server liefert den
                                 Monatspreis, Alpine tauscht nur den Text. Kein Aufblitzen des
                                 zweiten Preises, bevor Alpine laeuft. --}}
                            <span class="text-h1 tabular-nums {{ $isFeatured ? 'text-content-on-brand' : 'text-content' }}"
                                  @if($switchesPrice) x-text="yearly ? @js($priceYearly) : @js($price)" @endif>{{ $price }}</span>
                        @endif
                        @if($period)
                            <span class="{{ $isFeatured ? 'text-content-on-brand' : 'text-content-secondary' }}"
                                  @if($switchesPrice) x-text="'/ ' + (yearly ? @js($periodYearly) : @js($period))" @endif>/ {{ $period }}</span>
                        @endif
                    </div>

                    @if($features)
                        <div class="flex-grow mb-8 [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:space-y-2 [&_li]:pl-1 {{ $isFeatured ? 'text-content-on-brand [&_li]:marker:text-content-on-brand' : 'text-content [&_li]:marker:text-content-brand' }}">
                            {!! wp_kses_post($features ?? '') !!}
                        </div>
                    @endif

                    @if($cta)
                        <x-button
                            :url="$cta['url'] ?? '#'"
                            :target="$cta['target'] ?? '_self'"
                            :title="$cta['title'] ?? __('Auswählen', 'wp-starter')"
                            :variant="$isFeatured ? 'secondary' : 'primary'"
                            size="lg"
                            class="w-full justify-center"
                        />
                    @endif
                </div>
            @endforeach
        </div>
        </div>
    @elseif(current_user_can('edit_posts'))
        <div class="p-8 text-center rounded-[var(--card-radius)] bg-surface-secondary">
            <p class="text-content-secondary">{{ __('Bitte füge mindestens ein Preispaket hinzu.', 'wp-starter') }}</p>
        </div>
    @endif
</x-section>
@endif
