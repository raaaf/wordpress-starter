{{--
    Table Flexible Content Layout

    Uses shared components: x-section, x-section-header, x-grid (mit Inhaltsspalte), x-prose (mit Inhaltsspalte)
    Fields: title, headers (repeater: label), rows (repeater: cells), striped, bordered,
    compact, sticky_header, background_color, side_content_position (right|left), side_content (WYSIWYG).
    Section header extras (section_chip, section_description, section_alignment) kommen
    ueber SectionHeader::extras($title). Section-Felder (section_spacing, section_width,
    section_anchor) kommen als $sectionSpacing, $sectionWidth, $sectionAnchor von der einbindenden Seite.
    Zeilen haben zusaetzlich thick_border (dicke Linie unter der Zeile).
    Mit Inhaltsspalte: Tabelle 2/3, Inhalt 1/3, mobil untereinander (Tabelle zuerst).
--}}

@php
    $title = \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('title'));
    $kopf = \WordpressStarter\Helpers\SectionHeader::extras($title);
    $headers = get_sub_field('headers') ?: [];
    $rows = get_sub_field('rows') ?: [];
    $columnCount = count($headers);
    $striped = get_sub_field('striped') ?? true;
    $bordered = get_sub_field('bordered') ?? false;
    $compact = (bool) get_sub_field('compact');
    $stickyHeader = (bool) get_sub_field('sticky_header');
    $sidePosition = (string) (get_sub_field('side_content_position') ?: '');
    $sideContent = (string) (get_sub_field('side_content') ?: '');
    $hasSide = $sidePosition !== '' && trim(strip_tags($sideContent, '<img>')) !== '';

    $cellClass = $compact ? 'px-4 py-2' : 'px-6 py-4';

    // Eine mitscrollende Kopfzeile braucht eine Flaeche, in der ueberhaupt
    // gescrollt wird. overflow-x allein reicht nicht: sticky haengt dann an einem
    // Kasten, der selbst mit der Seite wandert, und bleibt wirkungslos.
    // scroll-pt haelt die Kopfhoehe frei, damit ein per Tastatur fokussierter Link
    // nicht unter der mitscrollenden Kopfzeile verschwindet (WCAG 2.4.11).
    $wrapperClass = $stickyHeader ? 'overflow-auto max-h-[70vh] scroll-pt-16' : 'overflow-x-auto';
    $headClass = $stickyHeader ? 'sticky top-0 z-10' : '';
    // Bei border-collapse wandern Zellrahmen nicht mit der sticky Kopfzeile mit:
    // die Unterkante wird deshalb als innerer Schatten auf den th gezeichnet.
    $headCellEdge = $stickyHeader && $bordered ? 'shadow-[inset_0_-1px_0_var(--color-line)]' : '';
    // <br> wird zu einem Leerzeichen, sonst klebt strip_tags die Woerter zusammen.
    $tableLabel = $title ? trim(strip_tags(preg_replace('/<br\s*\/?>/i', ' ', $title))) : __('Tabelle', 'wp-starter');
    $background = get_sub_field('background_color') ?: 'primary';
    $captionId = 'table-caption-' . uniqid();
@endphp

@if(!empty($rows) || current_user_can('edit_posts'))
<x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :width="$sectionWidth ?? null" :background="$background" class="table-block">
    <x-section-header :chip="$kopf['chip']" :headline="$kopf['headline']" :description="$kopf['description']" :alignment="$kopf['alignment']" />

    @if(!empty($rows))
        @php ob_start(); @endphp
        {{-- tabindex, damit der scrollende Bereich auch per Tastatur erreichbar ist (WCAG 2.1.1) --}}
        <div class="{{ $wrapperClass }} rounded-lg {{ $bordered ? 'border border-line' : '' }}" tabindex="0" role="group" aria-labelledby="{{ esc_attr($captionId) }}">
            <table class="w-full border-collapse {{ $bordered ? '[&_tr>*:first-child]:border-l-0 [&_tr>*:last-child]:border-r-0 [&_tr:first-child>*]:border-t-0 [&_tr:last-child>*]:border-b-0' : '' }}">
                <caption class="sr-only" id="{{ esc_attr($captionId) }}">{{ $tableLabel }}</caption>
                @if(!empty($headers))
                    <thead class="bg-surface-tertiary {{ $headClass }}">
                        <tr>
                            @foreach($headers as $header)
                                <th scope="col" class="{{ $cellClass }} text-left font-normal text-xs uppercase tracking-[0.08em] text-content-secondary bg-surface-tertiary {{ $bordered ? 'border border-line' : 'border-b border-line-strong' }} {{ $headCellEdge }}">
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
                                // Dicke Linie unter der Zeile: auf den Zellen, nicht auf dem tr,
                                // damit sie mit und ohne "Mit Rahmen" sichtbar ist.
                                $rowBorderClass = !empty($row['thick_border']) ? 'border-b-2 border-b-content' : '';

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
                                    <th scope="row" class="{{ $cellClass }} font-normal text-left [&_a]:underline [&_a]:underline-offset-2 text-content {{ $bordered ? 'border border-line' : '' }} {{ $rowBorderClass }}">
                                        @kses($cell['content'] ?? '')
                                    </th>
                                @else
                                    <td class="{{ $cellClass }} text-content tabular-nums [&_a]:underline [&_a]:underline-offset-2 {{ $bordered ? 'border border-line' : '' }} {{ $rowBorderClass }}">
                                        @kses($cell['content'] ?? '')
                                    </td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @php $tableBlock = ob_get_clean(); @endphp

        @if($hasSide)
            {{-- DOM-Reihenfolge immer Tabelle, dann Inhalt: mobil steht die Tabelle oben --}}
            <x-grid :cols="$sidePosition === 'left' ? '1/3-2/3' : '2/3-1/3'" gap="lg">
                <div class="min-w-0">{!! $tableBlock !!}</div>
                <div class="{{ $sidePosition === 'left' ? 'md:order-first' : '' }}">
                    <x-prose>@kses($sideContent)</x-prose>
                </div>
            </x-grid>
        @else
            {!! $tableBlock !!}
        @endif
    @elseif(current_user_can('edit_posts'))
        <div class="p-8 text-center rounded-lg bg-surface-secondary surface-sheen">
            <p class="text-content-secondary">{{ __('Bitte füge Tabellenzeilen hinzu.', 'wp-starter') }}</p>
        </div>
    @endif
</x-section>
@endif
