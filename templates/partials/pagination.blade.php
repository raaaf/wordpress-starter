{{--
    Reusable pagination partial.

    @param array  $pagination  Output of paginate_links(['type' => 'array', ...])
    @param string $ariaLabel   aria-label for the <nav> element
    @param string $navClass    Extra classes on the <nav> (e.g. "mt-16 pt-8 border-t border-line")
--}}

@if (!empty($pagination))
    <nav class="{{ $navClass ?? 'mt-16' }}" aria-label="{{ $ariaLabel ?? __('Navigation', 'wp-starter') }}">
        <ul class="flex flex-wrap justify-center gap-2">
            @foreach ($pagination as $link)
                <li>{!! str_replace(
                    ['page-numbers', 'current'],
                    ['px-4 py-2 rounded-lg border border-line text-content hover:bg-surface-secondary transition-colors', 'bg-surface-brand text-content-inverse border-surface-brand hover:bg-surface-brand'],
                    $link
                ) !!}</li>
            @endforeach
        </ul>
    </nav>
@endif
