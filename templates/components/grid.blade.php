{{--
    Grid Component - Flexible column layouts

    @param int|string $cols - Number of columns: 1, 2, 3, 4, '1/3-2/3', '2/3-1/3'
    @param string $gap - Gap size: sm, md, lg, xl (default: lg)
    @param string $align - items-start, items-center, items-end (default: items-start)
    @param string $class - Additional CSS classes
--}}

@props([
    'cols' => 2,
    'gap' => 'lg',
    'align' => 'items-start',
    'class' => '',
])

@php
    $colClasses = [
        1 => 'grid-cols-1',
        2 => 'grid-cols-1 md:grid-cols-2',
        3 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
        4 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
        '1/3-2/3' => 'grid-cols-1 md:grid-cols-3',
        '2/3-1/3' => 'grid-cols-1 md:grid-cols-3',
    ];

    $gaps = [
        'none' => 'gap-0',
        'sm' => 'gap-4',
        'md' => 'gap-6 lg:gap-8',
        'lg' => 'gap-8 lg:gap-12',
        'xl' => 'gap-12 lg:gap-16',
    ];

    $colClass = $colClasses[$cols] ?? $colClasses[2];
    $gapClass = $gaps[$gap] ?? $gaps['lg'];
@endphp

<div class="grid {{ $colClass }} {{ $gapClass }} {{ $align }} {{ $class }}">
    {{ $slot }}
</div>
