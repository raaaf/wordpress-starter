{{--
    Team Members Block

    Uses shared components: x-section, x-grid
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
                            <svg class="w-24 h-24 text-content-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
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
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </a>
                            @endif
                            @if($linkedin)
                                <a href="{{ $linkedin }}" target="_blank" rel="noopener noreferrer" class="p-2 transition-colors rounded-lg bg-surface-secondary hover:bg-surface-brand hover:text-content-inverse" title="LinkedIn">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
                                    </svg>
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-section>
