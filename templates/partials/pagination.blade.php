{{--
    Reusable pagination partial.

    @param array  $pagination  Output of paginate_links(['type' => 'array', ...])
    @param string $ariaLabel   aria-label for the <nav> element
    @param string $navClass    Extra classes on the <nav> (e.g. "mt-16 pt-8 border-t border-line")
--}}

@if (!empty($pagination))
    @php
        $baseClasses = 'px-4 py-2 rounded-lg border border-line text-content hover:bg-surface-secondary transition-colors';
        $currentClasses = 'bg-surface-brand text-content-inverse border-surface-brand hover:bg-surface-brand';
        $pageLabel = __('Seite ', 'wp-starter');
    @endphp
    <nav class="{{ $navClass ?? 'mt-16' }}" aria-label="{{ $ariaLabel ?? __('Navigation', 'wp-starter') }}">
        <ul class="flex flex-wrap justify-center gap-2">
            @foreach ($pagination as $link)
                @php
                    // Accessible name first, against paginate_links()'s untouched markup:
                    // bare page numbers get an sr-only "Seite " prefix so they read "Seite 2".
                    $link = preg_replace('/(<a class="page-numbers" href="[^"]*">)/', '$1<span class="sr-only">' . $pageLabel . '</span>', $link);
                    $link = preg_replace('/(<span aria-current="page" class="page-numbers current">)/', '$1<span class="sr-only">' . $pageLabel . '</span>', $link);

                    // Styling via exact class-attribute matches, never the loose word
                    // "current" — that also matches inside "aria-current" and corrupts it.
                    $link = str_replace('class="page-numbers current"', 'class="' . $baseClasses . ' ' . $currentClasses . '"', $link);
                    $link = str_replace('class="page-numbers dots"', 'class="' . $baseClasses . ' dots"', $link);
                    $link = str_replace('class="prev page-numbers"', 'class="prev ' . $baseClasses . '"', $link);
                    $link = str_replace('class="next page-numbers"', 'class="next ' . $baseClasses . '"', $link);
                    $link = str_replace('class="page-numbers"', 'class="' . $baseClasses . '"', $link);
                @endphp
                <li>{!! $link !!}</li>
            @endforeach
        </ul>
    </nav>
@endif
