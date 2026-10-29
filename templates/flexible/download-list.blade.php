{{--
    Download list - Flexible Content Layout

    Uses shared components: x-section, x-section-header, x-grid, x-card
    Fields: title, groups (repeater: title, items (repeater: heading, label, url, note)), columns, background_color
--}}

@php
    $title = \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('title'));
    $kopf = \WordpressStarter\Helpers\SectionHeader::extras($title);
    $groups = get_sub_field('groups');
    $columns = get_sub_field('columns') ?: '3';
    $background = get_sub_field('background_color') ?: 'primary';
@endphp

@if(!empty($groups) || $title || current_user_can('edit_posts'))
<x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :width="$sectionWidth ?? null" :background="$background" class="download-list">
    <x-section-header :chip="$kopf['chip']" :headline="$kopf['headline']" :description="$kopf['description']" :alignment="$kopf['alignment']" />

    @if(!empty($groups))
        <x-grid :cols="$columns" gap="md">
            @foreach($groups as $group)
                <x-card variant="outlined" padding="lg" class="h-full">
                    @if(!empty($group['title']))
                        <h3 class="text-h4 mt-0 mb-4">{{ $group['title'] }}</h3>
                    @endif

                    <ul class="list-none m-0 p-0">
                        @foreach(($group['items'] ?? []) as $item)
                            @php
                                $url = trim((string) ($item['url'] ?? ''));
                                $fileType = $url !== '' ? \WordpressStarter\Helpers\Text::fileType($url) : '';
                                $isExternal = (bool) preg_match('#^https?://#i', $url);
                            @endphp
                            <li class="{{ $loop->first ? '' : 'border-t border-line' }}">
                                @if(!empty($item['heading']))
                                    <p class="text-sm text-content-secondary {{ $loop->first ? 'mt-0' : 'mt-4' }} mb-1">{{ $item['heading'] }}</p>
                                @endif

                                @if($url !== '')
                                    <a href="{{ esc_url($url) }}"
                                       @if($isExternal) target="_blank" rel="noopener noreferrer" @endif
                                       class="block min-h-11 py-2 rounded-sm text-content-link hover:text-content-link-hover transition-colors duration-200 focus-visible:outline-none focus-visible:shadow-[var(--shadow-focus-ring)]">
                                        {{-- Titel links, Dateityp-Pill rechts; h-[1lh] zentriert die Pill an der ersten Titelzeile, auch wenn der Titel umbricht. Der Hinweis steht unter dem Titel. --}}
                                        <span class="flex justify-between gap-3">
                                            <span class="underline underline-offset-4 min-w-0">{{ $item['label'] ?? '' }}</span>
                                            @if($fileType !== '')
                                                <span class="flex shrink-0 items-center h-[1lh]"><x-badge variant="gray" size="sm" class="uppercase tracking-wide">{{ $fileType }}</x-badge></span>
                                            @endif
                                        </span>
                                        @if(!empty($item['note']))
                                            <span class="block mt-1 text-sm text-content-secondary">{{ $item['note'] }}</span>
                                        @endif
                                        @if($isExternal)
                                            <span class="sr-only">{{ __('(öffnet in neuem Tab)', 'wp-starter') }}</span>
                                        @endif
                                    </a>
                                @else
                                    <div class="flex items-center min-h-11 py-2 text-content-tertiary">
                                        <span>
                                            <span>{{ $item['label'] ?? '' }}</span>
                                            @if(!empty($item['note']))
                                                <span class="block text-sm text-content-secondary">{{ $item['note'] }}</span>
                                            @endif
                                        </span>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            @endforeach
        </x-grid>
    @elseif(current_user_can('edit_posts'))
        <div class="p-8 text-center rounded-lg bg-surface-secondary surface-sheen">
            <p class="text-content-secondary">{{ __('Bitte füge mindestens eine Gruppe hinzu.', 'wp-starter') }}</p>
        </div>
    @endif
</x-section>
@endif
