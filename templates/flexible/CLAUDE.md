# CLAUDE.md (templates/flexible)

Rules for the Flexible Content templates.

## Flexible Template Pattern

```blade
{{-- templates/flexible/example.blade.php --}}
@php
    $title = get_sub_field('title');
    $background = get_sub_field('background_color') ?: 'primary';
@endphp

<x-section :background="$background">
    @if($title)
        <h2>{{ $title }}</h2>
    @endif
</x-section>
```
