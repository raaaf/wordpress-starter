{{--
    Gate card header (icon badge, heading, description) shared by the member
    login form and the per-page password form. Extracted because both used
    to duplicate this block with diverging token families; callers keep
    their own form markup below this include.

    Variables:
      $gateIcon         - icon name for x-icon
      $gateHeading      - heading text
      $gateHeadingLevel - heading tag, e.g. 'h1' (default 'h1')
      $gateDescription  - description text, optional
--}}
@php
    $gateHeadingLevel = $gateHeadingLevel ?? 'h1';
    if (!in_array($gateHeadingLevel, ['h1', 'h2', 'h3'], true)) {
        $gateHeadingLevel = 'h2';
    }
@endphp
<div class="text-center mb-8">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-[var(--bg-brand-tint)] border border-line-brand text-content-brand [&_svg]:text-content-brand mb-4">
        <x-icon name="{{ $gateIcon }}" class="w-8 h-8 text-icon-brand" />
    </div>
    <{{ $gateHeadingLevel }} class="text-h3 mb-2">{{ $gateHeading }}</{{ $gateHeadingLevel }}>
    @if(!empty($gateDescription))
        <p class="text-content-secondary">{{ $gateDescription }}</p>
    @endif
</div>
