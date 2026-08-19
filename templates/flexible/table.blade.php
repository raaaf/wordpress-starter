{{--
    Table Flexible Content Layout

    Uses shared components: x-section, x-section-header
    Fields: title, headers (repeater: label), rows (repeater: cells), striped, bordered,
    compact, sticky_header, background_color
--}}

@php
    $title = \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('title'));
    $headers = get_sub_field('headers') ?: [];
    $rows = get_sub_field('rows') ?: [];
    $columnCount = count($headers);
    $striped = get_sub_field('striped') ?? true;
    $bordered = get_sub_field('bordered') ?? false;
    $compact = (bool) get_sub_field('compact');
    $stickyHeader = (bool) get_sub_field('sticky_header');

    $cellClass = $compact ? 'px-4 py-2' : 'px-6 py-4';

    // Eine mitscrollende Kopfzeile braucht eine Flaeche, in der ueberhaupt
    // gescrollt wird. overflow-x allein reicht nicht: sticky haengt dann an einem
    // Kasten, der selbst mit der Seite wandert, und bleibt wirkungslos.
    $wrapperClass = $stickyHeader ? 'overflow-auto max-h-[70vh]' : 'overflow-x-auto';
    $headClass = $stickyHeader ? 'sticky top-0 z-10' : '';
    $background = get_sub_field('background_color') ?: 'primary';
@endphp

@if($title || !empty($rows) || current_user_can('edit_posts'))
<x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :width="$sectionWidth ?? null" :background="$background" class="table-block">
    <x-section-header :headline="$title" />

    @if(!empty($rows))
        {{-- tabindex, damit der scrollende Bereich auch per Tastatur erreichbar ist (WCAG 2.1.1) --}}
        <div class="{{ $wrapperClass }} rounded-lg" tabindex="0" role="group" aria-label="{{ $title ? strip_tags($title) : __('Tabelle', 'wp-starter') }}">
            <table class="w-full {{ $bordered ? 'border border-line' : '' }}">
                <caption class="sr-only">{{ $title ? strip_tags($title) : __('Tabelle', 'wp-starter') }}</caption>
                @if(!empty($headers))
                    <thead class="bg-surface-tertiary {{ $headClass }}">
                        <tr>
                            @foreach($headers as $header)
                                <th scope="col" class="{{ $cellClass }} text-left font-semibold text-content bg-surface-tertiary {{ $bordered ? 'border border-line' : '' }}">
                                    {{ $header['label'] ?? '' }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                @endif

                <tbody>
                    @foreach($rows as $rowIndex => $row)
                        <tr class="{{ $striped && $rowIndex % 2 === 1 ? 'bg-surface-secondary' : 'bg-surface' }}">
                            @php
                                $cells = $row['cells'] ?? [];

                                // Auf die Spaltenzahl bringen. Ohne das erzeugt eine
                                // Zeile mit zu wenigen Zellen eine luecken hafte
                                // Tabelle und eine mit zu vielen sprengt das Raster.
                                if ($columnCount > 0) {
                                    $cells = array_slice($cells, 0, $columnCount);
                                    $cells = array_pad($cells, $columnCount, ['content' => '']);
                                }
                            @endphp
                            @foreach($cells as $cellIndex => $cell)
                                @if($cellIndex === 0 && !empty($headers))
                                    <th scope="row" class="{{ $cellClass }} font-normal text-left text-content {{ $bordered ? 'border border-line' : '' }}">
                                        {!! wp_kses_post($cell['content'] ?? '') !!}
                                    </th>
                                @else
                                    <td class="{{ $cellClass }} text-content tabular-nums {{ $bordered ? 'border border-line' : '' }}">
                                        {!! wp_kses_post($cell['content'] ?? '') !!}
                                    </td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @elseif(current_user_can('edit_posts'))
        <div class="p-8 text-center rounded-lg bg-surface-secondary">
            <p class="text-content-secondary">{{ __('Bitte füge Tabellenzeilen hinzu.', 'wp-starter') }}</p>
        </div>
    @endif
</x-section>
@endif
