{{--
    Icon Component - Based on Figma Design System

    Renders inline SVG icons from resources/icons/ directory.
    Icons inherit text color via currentColor for easy styling.

    @param string $name - Icon name (without .svg extension)
    @param string $size - xs, sm, md, lg, xl or custom Tailwind class (default: md)
    @param string $class - Additional CSS classes

    Available icons:
    UI: calendar, check, chevron, close, eye, lock,
        mail, minus, phone, plus, search, user, warning
    Social: facebook, instagram, linkedin, x, xing, youtube

    Usage:
    <x-icon name="search" />
    <x-icon name="check" size="lg" class="text-icon-success" />
    <x-icon name="linkedin" size="lg" />
    <x-icon name="close" class="w-8 h-8 text-icon-error" />
--}}

@props([
    'name',
    'size' => 'md',
    'class' => '',
])

@php
    $sizes = [
        'xs' => 'w-3 h-3',
        'sm' => 'w-3.5 h-3.5',
        'md' => 'w-4 h-4',
        'lg' => 'w-5 h-5',
        'xl' => 'w-6 h-6',
    ];

    $sizeClass = $sizes[$size] ?? $size;
    $iconPath = get_template_directory() . '/resources/icons/' . $name . '.svg';

    $svgContent = '';
    if (file_exists($iconPath)) {
        $svgContent = file_get_contents($iconPath);
        $svgContent = preg_replace('/\s*(width|height)="[^"]*"/', '', $svgContent);
        $svgContent = preg_replace(
            '/<svg/',
            '<svg class="' . $sizeClass . ' ' . $class . ' inline-block shrink-0" aria-hidden="true"',
            $svgContent,
            1
        );
    }
@endphp

@if($svgContent)
    {!! $svgContent !!}
@endif
