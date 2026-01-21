{{--
    Team Members Flexible Content Layout

    Uses shared components: x-section, x-grid, x-icon
    Fields: title, members (repeater: image, name, position, bio, social_links), columns, background_color
--}}

@php
    $title = get_sub_field('title') ?: '';
    $members = get_sub_field('members') ?: [];
    $columns = get_sub_field('columns') ?: 3;
    $background = get_sub_field('background_color') ?: 'primary';
@endphp

<x-section :background="$background" class="team">
    @if($title)
        <h2 class="text-h2 mb-12 text-center text-content">{{ $title }}</h2>
    @endif

    @if(!empty($members))
        <div class="grid gap-8 md:grid-cols-{{ $columns }}">
            @foreach($members as $member)
                @php
                    $image = wp_get_attachment_image_src($member['image'] ?? null, 'medium_large');
                    $name = $member['name'] ?? '';
                    $position = $member['position'] ?? '';
                    $bio = $member['bio'] ?? '';
                    $email = $member['email'] ?? '';
                    $linkedin = $member['linkedin'] ?? '';
                @endphp
                <div class="text-center group">
                    @if($image)
                        <div class="relative mb-6 overflow-hidden rounded-xl aspect-square">
                            <img
                                src="{{ $image[0] }}"
                                alt="{{ $name }}"
                                class="object-cover w-full h-full transition-transform duration-300 group-hover:scale-105"
                                loading="lazy"
                            >
                        </div>
                    @else
                        <div class="flex items-center justify-center mb-6 rounded-xl aspect-square bg-surface-secondary">
                            <x-icon name="user" class="w-24 h-24 text-content-tertiary" />
                        </div>
                    @endif

                    @if($name)
                        <h3 class="text-h4 mb-1 text-content">{{ $name }}</h3>
                    @endif

                    @if($position)
                        <div class="mb-3">
                            <x-badge variant="brand" style="outline" size="sm">{{ $position }}</x-badge>
                        </div>
                    @endif

                    @if($bio)
                        <p class="mb-4 text-content-secondary">{{ $bio }}</p>
                    @endif

                    @if($email || $linkedin)
                        <div class="flex justify-center gap-3">
                            @if($email)
                                <x-button
                                    url="mailto:{{ $email }}"
                                    title=""
                                    variant="secondary"
                                    size="sm"
                                    class="!p-2 !min-h-0 hover:!bg-surface-brand hover:!text-content-inverse"
                                >
                                    <x-icon name="mail" size="lg" />
                                    <span class="sr-only">E-Mail</span>
                                </x-button>
                            @endif
                            @if($linkedin)
                                <x-button
                                    url="{{ $linkedin }}"
                                    title=""
                                    target="_blank"
                                    variant="secondary"
                                    size="sm"
                                    class="!p-2 !min-h-0 hover:!bg-surface-brand hover:!text-content-inverse"
                                >
                                    <x-icon name="linkedin" size="lg" />
                                    <span class="sr-only">LinkedIn</span>
                                </x-button>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-section>
