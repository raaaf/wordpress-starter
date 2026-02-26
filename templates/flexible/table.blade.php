{{--
    Table Flexible Content Layout

    Uses shared components: x-section
    Fields: title, headers (repeater: label), rows (repeater: cells), striped, bordered, background_color
--}}

@php
    $title = str_replace('[br]', '<br>', get_sub_field('title') ?: '');
    $headers = get_sub_field('headers') ?: [];
    $rows = get_sub_field('rows') ?: [];
    $striped = get_sub_field('striped') ?? true;
    $bordered = get_sub_field('bordered') ?? false;
    $background = get_sub_field('background_color') ?: 'primary';
@endphp

<x-section :background="$background" class="table-block">
    @if($title)
        <h2 class="mb-8 text-center">{!! $title !!}</h2>
    @endif

    @if(!empty($headers) || !empty($rows))
        <div class="overflow-x-auto rounded-lg">
            <table class="w-full {{ $bordered ? 'border border-line' : '' }}">
                @if(!empty($headers))
                    <thead class="bg-surface-tertiary">
                        <tr>
                            @foreach($headers as $header)
                                <th scope="col" class="px-6 py-4 text-left font-semibold text-content {{ $bordered ? 'border border-line' : '' }}">
                                    {{ $header['label'] ?? '' }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                @endif

                @if(!empty($rows))
                    <tbody>
                        @foreach($rows as $rowIndex => $row)
                            <tr class="{{ $striped && $rowIndex % 2 === 1 ? 'bg-surface-secondary' : 'bg-surface' }}">
                                @php
                                    $cells = $row['cells'] ?? [];
                                @endphp
                                @foreach($cells as $cell)
                                    <td class="px-6 py-4 text-content {{ $bordered ? 'border border-line' : '' }}">
                                        {!! $cell['content'] ?? '' !!}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                @endif
            </table>
        </div>
    @else
        <div class="p-8 text-center rounded-lg bg-surface-secondary">
            <p class="text-content-secondary">Bitte füge Tabellenzeilen hinzu.</p>
        </div>
    @endif
</x-section>
