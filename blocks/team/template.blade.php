{{--
    Team Members Block

    Uses shared components: x-section, x-grid, x-icon
    Fields: title, members (repeater: image, name, position, bio, social_links), columns, background_color
--}}

@php
    $title = $fields['title'] ?? '';
    $members = $fields['members'] ?? [];
    $columns = $fields['columns'] ?? 3;
    $background = $fields['background_color'] ?? 'primary';
@endphp

<x-section :background="$background" :anchor="$anchor" class="{{ $classes }} team-block">
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
                        <p class="mb-3 font-medium text-content-brand">{{ $position }}</p>
                    @endif

                    @if($bio)
                        <p class="mb-4 text-content-secondary">{{ $bio }}</p>
                    @endif

                    @if($email || $linkedin)
                        <div class="flex justify-center gap-3">
                            @if($email)
                                <a href="mailto:{{ $email }}" class="p-2 transition-colors rounded-lg bg-surface-secondary hover:bg-surface-brand hover:text-content-inverse" title="E-Mail">
                                    <x-icon name="mail" size="lg" />
                                </a>
                            @endif
                            @if($linkedin)
                                <a href="{{ $linkedin }}" target="_blank" rel="noopener noreferrer" class="p-2 transition-colors rounded-lg bg-surface-secondary hover:bg-surface-brand hover:text-content-inverse" title="LinkedIn">
                                    <x-icon name="linkedin" size="lg" />
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-section>
