{{--
    Hero - Flexible Content Layout

    Variants: centered, split, background
    Uses wp_get_attachment_image() for automatic srcset/responsive images (split variant)
    ACF Fields: variant, badge, title, copy, cta_primary, cta_secondary, image, background_image, background_color
--}}

@php
    $variant = get_sub_field('variant') ?: 'centered';
    $badge = get_sub_field('badge');
    $title = \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('title'));
    $copy = \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('copy'));

    // Render h1 only for the first hero on a singular page; all others use h2.
    // $layoutCounters is set by the flexible loop in page.blade.php.
    $heroHeadingTag = (is_singular() && ($layoutCounters['hero'] ?? 1) === 1) ? 'h1' : 'h2';
    $cta_primary = get_sub_field('cta_primary');
    $cta_secondary = get_sub_field('cta_secondary');
    $image = get_sub_field('image');
    $background_image = get_sub_field('background_image');
    $background_color = get_sub_field('background_color') ?: 'primary';
    $overlay_opacity = get_sub_field('overlay_opacity');
    // Fallback gleich dem ACF-Default, sonst weicht ein frisch angelegter Hero
    // vor dem ersten Speichern von der Einstellung ab.
    $overlay_opacity = is_numeric($overlay_opacity) ? (int) $overlay_opacity : 80;

    // Convert 0-100 to 0-1 for CSS opacity
    $overlay_opacity_css = $overlay_opacity / 100;

    // Der Scrim dunkelt immer ab, in beiden Farbschemata. Ein heller Schleier
    // ueber dem Bild zieht jedes Motiv ins Pastellige und garantiert trotzdem
    // keine Lesbarkeit, weil sie vom hellsten Fleck des Bildes abhaengt.
    // Abdunkeln plus helle Schrift ist die uebliche Loesung (NN/g, Smashing):
    // 40 bis 60 Prozent Schwarz fuer weisse Schrift.
    //
    // Kein flaechiger Schleier, sondern ein Verlauf, der hinter dem Text am
    // staerksten deckt und zu den Ecken hin nachlaesst. Das Bild behaelt dort
    // seine Farbe, wo kein Text steht.
    $scrimMitte = min(0.85, $overlay_opacity_css * 0.75);
    $scrimAussen = round($scrimMitte * 0.55, 2);
    $scrimCss = sprintf(
        'radial-gradient(ellipse 90%% 75%% at 50%% 50%%, rgba(0,0,0,%s) 0%%, rgba(0,0,0,%s) 55%%, rgba(0,0,0,%s) 100%%)',
        round($scrimMitte, 2),
        round($scrimMitte * 0.8, 2),
        $scrimAussen,
    );

    // Handle ID vs array format for images - preserve ID for wp_get_attachment_image()
    $imageId = null;
    if (is_numeric($image)) {
        $imageId = (int) $image;
        $imageSrc = wp_get_attachment_image_src($imageId, 'hero-split');
        $image = [
            'ID' => $imageId,
            'url' => $imageSrc ? $imageSrc[0] : wp_get_attachment_url($imageId),
            'alt' => get_post_meta($imageId, '_wp_attachment_image_alt', true) ?: '',
            'width' => $imageSrc ? $imageSrc[1] : '',
            'height' => $imageSrc ? $imageSrc[2] : '',
        ];
    } elseif (is_array($image) && !empty($image['ID'])) {
        $imageId = (int) $image['ID'];
    }

    if (is_numeric($background_image)) {
        $bgId = (int) $background_image;
        $bgSrc = wp_get_attachment_image_src($bgId, 'hero-background');
        $background_image = [
            'ID' => $bgId,
            'url' => $bgSrc ? $bgSrc[0] : wp_get_attachment_url($bgId),
            'alt' => get_post_meta($bgId, '_wp_attachment_image_alt', true) ?: '',
            'width' => $bgSrc ? $bgSrc[1] : '',
            'height' => $bgSrc ? $bgSrc[2] : '',
        ];
    } elseif (is_array($background_image) && !empty($background_image['ID'])) {
        $background_image['ID'] = (int) $background_image['ID'];
    }

    // A hero is worth rendering if it has any text/CTA content, or, per variant,
    // an image that carries the section on its own (background image, split image).
    $hasText = $badge || $title || $copy || $cta_primary || $cta_secondary;

    // Ein Hero, der nur eine Ueberschrift traegt, braucht keine halbe oder ganze
    // Viewporthoehe: die Flaeche darunter war leer und die erste Sektion rutschte
    // ohne Grund unter die Falz. Die Hoehe kommt deshalb aus dem Inhalt.
    $isTitleOnly = $title && !$badge && !$copy && !$cta_primary && !$cta_secondary;
    $heroHeightClass = $isTitleOnly ? 'hero--compact' : 'hero--full';
    $hasBackgroundImage = $background_image && (!empty($background_image['ID']) || !empty($background_image['url']));
    $hasSplitImage = $imageId || ($image && !empty($image['url']));
@endphp

@if($variant === 'background')
    @if($hasText || $hasBackgroundImage)
    {{-- BACKGROUND VARIANT: Full-width image with overlay --}}
    {{-- id nicht vergessen: die Varianten centered und split reichen den Anker
         ueber x-section durch, diese hier rendert ein rohes section-Tag. Ohne id
         lief jeder Anker-Link auf einen Hero mit Hintergrundbild ins Leere. --}}
    @php($shouldAnimate = \WordpressStarter\Acf\Fields::option('animations_enabled', false))
    {{-- Die Einblendung steht hier als Klasse statt als Alpine-Zustand, weil
         diese Variante als einzige kein x-section nutzt und weil sie ueber der
         Falz liegt: ein Reveal, das auf das deferrte Modul wartet, zeigt das
         Bild zuerst ohne Text. Die Klasse startet eine CSS-Animation ab dem
         ersten Frame, die Regeln stehen in app.css unter .hero--reveal. --}}
    <section
        @if($sectionAnchor) id="{{ esc_attr($sectionAnchor) }}" @endif
        class="hero hero--background {{ $heroHeightClass }} @if($shouldAnimate) hero--reveal @endif relative overflow-hidden flex items-center"
    >
        @if($background_image && (!empty($background_image['ID']) || !empty($background_image['url'])))
            <div class="absolute inset-0">
                @if(!empty($background_image['ID']))
                    {!! wp_get_attachment_image($background_image['ID'], 'hero-background', false, [
                        'class' => 'w-full h-full object-cover',
                        'loading' => 'eager',
                        'fetchpriority' => 'high',
                        'sizes' => '100vw',
                        'alt' => '',
                    ]) !!}
                @else
                    <img src="{{ $background_image['url'] }}"
                         alt="{{ $background_image['alt'] ?? '' }}"
                         @if(!empty($background_image['width']) && !empty($background_image['height']))width="{{ $background_image['width'] }}" height="{{ $background_image['height'] }}"@endif
                         class="w-full h-full object-cover"
                         loading="eager"
                         fetchpriority="high">
                @endif
                {{-- Scrim, siehe Berechnung oben --}}
                <div class="absolute inset-0" style="background-image: {{ $scrimCss }};"></div>
            </div>
        @else
            <div class="absolute inset-0 bg-surface-brand"></div>
        @endif

        <div class="hero-reveal relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 flex items-center justify-center text-center w-full">
            <div class="max-w-3xl">
                @if($badge)
                    <x-badge variant="brand" size="md" class="mb-8">{{ $badge }}</x-badge>
                @endif

                @if($title)
                    <{{ $heroHeadingTag }} class="text-display mt-0! mb-6 text-content-inverse">
                        {!! $title !!}
                    </{{ $heroHeadingTag }}>
                @endif

                @if($copy)
                    <p class="text-body-large mb-8 max-w-[52ch] mx-auto text-pretty text-content-inverse/85">{!! $copy !!}</p>
                @endif

                @if($cta_primary || $cta_secondary)
                    <div class="flex flex-wrap gap-4 justify-center">
                        @if($cta_primary)
                            <x-button
                                :url="$cta_primary['url']"
                                :title="$cta_primary['title']"
                                :target="$cta_primary['target'] ?? '_self'"
                                variant="primary"
                                size="lg"
                            />
                        @endif
                        @if($cta_secondary)
                            {{-- Umriss statt Fuellung: der gefuellte sekundaere Button war
                                 auf dem abgedunkelten Bild heller als die Ueberschrift und
                                 zog die Hierarchie zu sich. --}}
                            <x-button
                                :url="$cta_secondary['url']"
                                :title="$cta_secondary['title']"
                                :target="$cta_secondary['target'] ?? '_self'"
                                variant="secondary"
                                size="lg"
                                class="bg-transparent! border-white/60! text-white! shadow-none! hover:bg-white/10! hover:border-white!"
                            />
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>
    @endif

@elseif($variant === 'split')
    @if($hasText || $hasSplitImage)
    {{-- SPLIT VARIANT: Content left, image right --}}
    <x-section :anchor="$sectionAnchor" :background="$background_color" padding="lg" class="hero hero--split hero--full">
        <div class="grid md:grid-cols-2 gap-12 items-center">
            <div>
                @if($badge)
                    <x-badge variant="accent" size="md" class="mb-8">{{ $badge }}</x-badge>
                @endif

                @if($title)
                    <{{ $heroHeadingTag }} class="mt-0! mb-6">
                        {!! $title !!}
                    </{{ $heroHeadingTag }}>
                @endif

                {{-- text-body-large statt text-lg: gleiche Intro-Typografie wie in der background-Variante --}}
                @if($copy)
                    <p class="text-body-large mb-8 max-w-[52ch] text-content-secondary">{!! $copy !!}</p>
                @endif

                @if($cta_primary || $cta_secondary)
                    <div class="flex flex-wrap gap-4">
                        @if($cta_primary)
                            <x-button
                                :url="$cta_primary['url']"
                                :title="$cta_primary['title']"
                                :target="$cta_primary['target'] ?? '_self'"
                                variant="primary"
                                size="lg"
                            />
                        @endif
                        @if($cta_secondary)
                            <x-button
                                :url="$cta_secondary['url']"
                                :title="$cta_secondary['title']"
                                :target="$cta_secondary['target'] ?? '_self'"
                                variant="secondary"
                                size="lg"
                            />
                        @endif
                    </div>
                @endif
            </div>

            @if($imageId)
                <div class="relative">
                    {!! wp_get_attachment_image($imageId, 'hero-split', false, [
                        'class' => 'w-full h-auto rounded-[var(--card-radius)] shadow-xl',
                        'loading' => 'eager',
                        'fetchpriority' => 'high',
                        'sizes' => '(max-width: 768px) 100vw, (max-width: 1280px) 50vw, 640px',
                    ]) !!}
                </div>
            @elseif($image && !empty($image['url']))
                {{-- Fallback for URL-only images --}}
                <div class="relative">
                    <img src="{{ $image['url'] }}"
                         alt="{{ $image['alt'] ?? '' }}"
                         @if(!empty($image['width']) && !empty($image['height']))width="{{ $image['width'] }}" height="{{ $image['height'] }}"@endif
                         class="w-full h-auto rounded-[var(--card-radius)] shadow-xl"
                         loading="eager"
                         fetchpriority="high">
                </div>
            @endif
        </div>
    </x-section>
    @endif

@else
    @if($hasText)
    {{-- CENTERED VARIANT (default): Centered content --}}
    <x-section :anchor="$sectionAnchor" :background="$background_color" :padding="$isTitleOnly ? 'md' : 'xl'" class="hero hero--centered {{ $heroHeightClass }}">
        <div class="max-w-3xl mx-auto text-center">
            @if($badge)
                <x-badge variant="accent" size="md" class="mb-8">{{ $badge }}</x-badge>
            @endif

            {{-- Der untere Abstand gehoert zum Folgeelement, nicht zur Ueberschrift:
                 steht sie allein, schob mb-6 den Hero ohne Grund auseinander. --}}
            @if($title)
                <{{ $heroHeadingTag }} class="mt-0! {{ $isTitleOnly ? 'mb-0!' : 'mb-6' }}">
                    {!! $title !!}
                </{{ $heroHeadingTag }}>
            @endif

            {{-- text-body-large statt text-lg md:text-xl: gleiche Intro-Typografie wie in den anderen Varianten.
                 Die Breite in ch statt aus dem 3xl des Wrappers: zentrierter Text
                 lief gemessen 83 Zeichen weit, und zentriert liest sich lang
                 schwerer als linksbuendig. ch ist die Breite der Null und damit
                 breiter als das mittlere Zeichen, deshalb 52ch fuer rund 70.
                 Der Text bleibt zentriert, die Variante heisst so. text-pretty
                 verteilt die Zeilen gleichmaessig, statt lang, lang, kurz. Die Ueberschrift behaelt die volle
                 Breite, sie ist kurz und soll tragen. --}}
            @if($copy)
                <p class="text-body-large mb-8 max-w-[52ch] mx-auto text-pretty text-content-secondary">{!! $copy !!}</p>
            @endif

            @if($cta_primary || $cta_secondary)
                <div class="flex flex-wrap gap-4 justify-center">
                    @if($cta_primary)
                        <x-button
                            :url="$cta_primary['url']"
                            :title="$cta_primary['title']"
                            :target="$cta_primary['target'] ?? '_self'"
                            variant="primary"
                            size="lg"
                        />
                    @endif
                    {{-- variant secondary statt outline: "outline" existiert im Button nicht und fiel still auf primary zurück --}}
                    @if($cta_secondary)
                        <x-button
                            :url="$cta_secondary['url']"
                            :title="$cta_secondary['title']"
                            :target="$cta_secondary['target'] ?? '_self'"
                            variant="secondary"
                            size="lg"
                        />
                    @endif
                </div>
            @endif
        </div>
    </x-section>
    @endif
@endif
