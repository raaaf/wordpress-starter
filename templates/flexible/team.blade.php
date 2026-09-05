{{--
    Team Members Flexible Content Layout

    Supports two data sources:
    - 'manual': Uses repeater field for page-specific team members
    - 'cpt': Uses Team CPT for centrally managed team members

    Uses shared components: x-section, x-section-header, x-grid, x-icon, x-badge, x-button
    Fields: title, source, members (repeater), columns, background_color
--}}

@php
    use WordpressStarter\PostTypes\Team;

    $title = \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('title'));

    $kopf = \WordpressStarter\Helpers\SectionHeader::extras($title);
    $source = get_sub_field('source') ?: 'manual';
    $columns = get_sub_field('columns') ?: 3;
    $background = get_sub_field('background_color') ?: 'primary';

    // Normalize members data from either source
    $members = [];

    if ($source === 'cpt' && class_exists(Team::class)) {
        // Load from CPT - already normalized structure
        $cptMembers = Team::getTeamMembers();
        foreach ($cptMembers as $item) {
            $members[] = [
                'image' => $item['image'],
                'name' => $item['name'],
                'position' => $item['position'],
                'bio' => $item['bio'],
                'email' => $item['email'],
                'linkedin' => $item['linkedin'],
            ];
        }
    } else {
        // Use manual repeater data
        $members = get_sub_field('members') ?: [];
    }
@endphp

@if(!empty($members) || $title || current_user_can('edit_posts'))
<x-section :anchor="$sectionAnchor" :spacing="$sectionSpacing ?? null" :width="$sectionWidth ?? null" :background="$background" class="team">
    <x-section-header :chip="$kopf['chip']" :headline="$kopf['headline']" :description="$kopf['description']" :alignment="$kopf['alignment']" />

    @if(!empty($members))
        {{-- gap="lg" waere responsiv (gap-8 lg:gap-12), gewuenscht ist ein fester
             32px-Abstand auf jeder Breite, deshalb die Klasse statt der Prop. --}}
        <x-grid :cols="$columns" class="gap-8!">
            @foreach($members as $member)
                @php
                    $imageId = $member['image'] ?? null;
                    $name = $member['name'] ?? '';
                    $position = $member['position'] ?? '';
                    $bio = $member['bio'] ?? '';
                    $email = $member['email'] ?? '';
                    $linkedin = $member['linkedin'] ?? '';
                @endphp
                <div class="flex flex-col h-full text-center">
                    @if($imageId)
                        {{-- No hover effect: the portrait is not a link and nothing
                             else on the card is interactive, so a hover reveal
                             would promise an interaction that isn't there. --}}
                        {{-- Schmaler als die Spalte und im Hochformat: bei drei
                             Spalten rendert das Bild sonst 384x384 und die Sektion
                             wird zur Wand aus Gesichtern. --}}
                        <div class="relative mx-auto mb-6 overflow-hidden rounded-[var(--card-radius)] aspect-[4/5] max-w-[260px]">
                            {!! wp_get_attachment_image((int) $imageId, 'team-portrait', false, [
                                'alt' => \WordpressStarter\Helpers\Text::imageAlt((int) $imageId, $name),
                                'class' => 'object-cover w-full h-full',
                                'sizes' => '260px',
                            ]) !!}
                        </div>
                    @else
                        <div class="flex items-center justify-center mx-auto mb-6 rounded-[var(--card-radius)] aspect-[4/5] max-w-[260px] bg-surface-secondary surface-sheen">
                            <x-icon name="user" class="w-24 h-24 text-content-tertiary" />
                        </div>
                    @endif

                    @if($name)
                        <h3 class="text-h4 mb-1">{{ $name }}</h3>
                    @endif

                    @if($position)
                        <div class="mb-3">
                            <x-badge variant="brand" style="outline" size="sm">{{ $position }}</x-badge>
                        </div>
                    @endif

                    @if($bio)
                        <p class="mb-4 text-content-secondary">{!! \WordpressStarter\Helpers\Text::lineBreaks($bio) !!}</p>
                    @endif

                    @if($email || $linkedin)
                        <div class="flex justify-center gap-3 mt-auto pt-2">
                            @if($email)
                                <x-button
                                    url="mailto:{{ $email }}"
                                    title=""
                                    :aria-label="__('E-Mail senden', 'wp-starter') . ': ' . $name"
                                    variant="secondary"
                                    size="sm"
                                    class="p-2.5! min-h-11! min-w-11! hover:bg-surface-brand! hover:text-content-on-brand!"
                                >
                                    <x-icon name="mail" size="lg" />
                                    <span class="sr-only">{{ __('E-Mail', 'wp-starter') }}</span>
                                </x-button>
                            @endif
                            @if($linkedin)
                                <x-button
                                    url="{{ $linkedin }}"
                                    title=""
                                    :aria-label="__('LinkedIn', 'wp-starter') . ': ' . $name"
                                    target="_blank"
                                    variant="secondary"
                                    size="sm"
                                    class="p-2.5! min-h-11! min-w-11! hover:bg-surface-brand! hover:text-content-on-brand!"
                                >
                                    <x-icon name="linkedin" size="lg" />
                                    <span class="sr-only">{{ __('LinkedIn', 'wp-starter') }}</span>
                                </x-button>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </x-grid>
    @elseif(current_user_can('edit_posts'))
        <div class="p-8 text-center rounded-lg bg-surface-secondary surface-sheen">
            <p class="text-content-secondary">{{ __('Bitte füge Teammitglieder hinzu oder wähle eine Quelle mit Einträgen.', 'wp-starter') }}</p>
        </div>
    @endif
</x-section>
@endif
