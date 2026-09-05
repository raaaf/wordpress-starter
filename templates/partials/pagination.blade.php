{{--
    Reusable pagination partial.

    @param array  $pagination  Output of paginate_links(['type' => 'array', ...])
    @param string $ariaLabel   aria-label for the <nav> element
    @param string $navClass    Extra classes on the <nav> (e.g. "mt-16 pt-8 border-t border-line")
--}}

@if (!empty($pagination))
    @php
        // inline-flex + min-h-11!/min-w-11!: 44px Ziel-Flaeche wie bei den Icon-Buttons
        // in team.blade.php, vorher lag die Hoehe ohne festes Mass bei ca. 40px.
        $baseClasses = 'inline-flex items-center justify-center min-h-11! min-w-11! px-4 py-2 rounded-lg border border-line text-content hover:bg-surface-secondary transition-colors';
        $currentClasses = 'bg-surface-brand text-content-on-brand border-surface-brand hover:bg-surface-brand';
        $dotsClasses = 'inline-flex items-center justify-center min-h-11! min-w-11! px-4 py-2 text-content-tertiary';
        // Trailing space added outside the string so the msgid itself never
        // ends in whitespace.
        $srOnlyPagePrefix = '<span class="sr-only">' . __('Seite', 'wp-starter') . ' </span>';
    @endphp
    <nav class="{{ $navClass ?? 'mt-16' }}" aria-label="{{ $ariaLabel ?? __('Navigation', 'wp-starter') }}">
        <ul class="flex flex-wrap justify-center gap-2">
            @foreach ($pagination as $link)
                @php
                    // Accessible name first, against paginate_links()'s untouched markup:
                    // bare page numbers get an sr-only "Seite " prefix so they read "Seite 2".
                    // preg_replace_callback, not preg_replace: a plain preg_replace treats
                    // $/\ sequences in the replacement as backreferences, so the label
                    // would corrupt the injected markup if the translation ever contained
                    // one. The callback's return value is used verbatim.
                    $link = preg_replace_callback(
                        '/(<a class="page-numbers" href="[^"]*">)/',
                        static fn (array $matches): string => $matches[1] . $srOnlyPagePrefix,
                        $link
                    );
                    $link = preg_replace_callback(
                        '/(<span aria-current="page" class="page-numbers current">)/',
                        static fn (array $matches): string => $matches[1] . $srOnlyPagePrefix,
                        $link
                    );

                    // Styling via exact class-attribute matches, never the loose word
                    // "current", which also matches inside "aria-current" and corrupts it.
                    $link = str_replace('class="page-numbers current"', 'class="' . $baseClasses . ' ' . $currentClasses . '"', $link);
                    $link = str_replace('class="page-numbers dots"', 'class="' . $dotsClasses . ' dots"', $link);
                    $link = str_replace('class="prev page-numbers"', 'class="prev ' . $baseClasses . '"', $link);
                    $link = str_replace('class="next page-numbers"', 'class="next ' . $baseClasses . '"', $link);
                    $link = str_replace('class="page-numbers"', 'class="' . $baseClasses . '"', $link);
                @endphp
                <li>{!! $link !!}</li>
            @endforeach
        </ul>
    </nav>
@endif
