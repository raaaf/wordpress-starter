{{--
    Team Members Flexible Content Layout

    Supports two data sources:
    - 'manual': Uses repeater field for page-specific team members
    - 'cpt': Uses Team CPT for centrally managed team members

    Uses shared components: x-section, x-grid, x-icon, x-badge, x-button
    Fields: title, source, members (repeater), columns, background_color
--}}

@php
    use WordpressStarter\PostTypes\Team;

    $title = \WordpressStarter\Helpers\Text::lineBreaks(get_sub_field('title'));
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

<x-section :anchor="$sectionAnchor" :background="$background" class="team">
    @if($title)
        <h2 class="mb-12 text-center">{!! $title !!}</h2>
    @endif

    @if(!empty($members))
        @php
            $gridClass = match((int) $columns) {
                2 => 'md:grid-cols-2',
                3 => 'md:grid-cols-3',
                4 => 'md:grid-cols-4',
                default => 'md:grid-cols-3',
            };
        @endphp
        <div class="grid gap-8 {{ $gridClass }}">
            @foreach($members as $member)
                @php
                    $imageId = $member['image'] ?? null;
                    $name = $member['name'] ?? '';
                    $position = $member['position'] ?? '';
                    $bio = $member['bio'] ?? '';
                    $email = $member['email'] ?? '';
                    $linkedin = $member['linkedin'] ?? '';
                @endphp
                <div class="text-center group">
                    @if($imageId)
                        <div class="relative mb-6 overflow-hidden rounded-xl aspect-square">
                            {!! wp_get_attachment_image($imageId, 'team-portrait', false, [
                                'class' => 'object-cover w-full h-full transition-transform duration-300 group-hover:scale-105',
                                'loading' => 'lazy',
                                'sizes' => '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw',
                            ]) !!}
                        </div>
                    @else
                        <div class="flex items-center justify-center mb-6 rounded-xl aspect-square bg-surface-secondary">
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
                        <div class="flex justify-center gap-3">
                            @if($email)
                                <x-button
                                    url="mailto:{{ $email }}"
                                    title=""
                                    :aria-label="__('E-Mail senden', 'wp-starter') . ': ' . $name"
                                    variant="secondary"
                                    size="sm"
                                    class="p-2! min-h-0! hover:bg-surface-brand! hover:text-content-inverse!"
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
                                    class="p-2! min-h-0! hover:bg-surface-brand! hover:text-content-inverse!"
                                >
                                    <x-icon name="linkedin" size="lg" />
                                    <span class="sr-only">{{ __('LinkedIn', 'wp-starter') }}</span>
                                </x-button>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-section>
