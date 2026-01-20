{{--
    Stats/Counter Block

    Uses shared components: x-section
    Uses Alpine.js for animated counting
    Fields: title, stats (repeater: number, suffix, label, icon), background_color
--}}

@php
    $title = $fields['title'] ?? '';
    $stats = $fields['stats'] ?? [];
    $background = $fields['background_color'] ?? 'brand';
@endphp

<x-section :background="$background" :anchor="$anchor" class="{{ $classes }} stats-block">
    @if($title)
        <h2 class="text-h2 mb-12 text-center text-content">{{ $title }}</h2>
    @endif

    @if(!empty($stats))
        <div class="grid gap-8 text-center md:grid-cols-{{ min(count($stats), 4) }}">
            @foreach($stats as $stat)
                @php
                    $number = intval($stat['number'] ?? 0);
                    $suffix = $stat['suffix'] ?? '';
                    $label = $stat['label'] ?? '';
                    $icon = $stat['icon'] ?? '';
                @endphp
                <div
                    x-data="{
                        target: {{ $number }},
                        current: 0,
                        duration: 2000,
                        started: false,
                        init() {
                            const observer = new IntersectionObserver((entries) => {
                                if (entries[0].isIntersecting && !this.started) {
                                    this.started = true;
                                    this.animate();
                                }
                            }, { threshold: 0.5 });
                            observer.observe(this.$el);
                        },
                        animate() {
                            const start = performance.now();
                            const step = (timestamp) => {
                                const progress = Math.min((timestamp - start) / this.duration, 1);
                                this.current = Math.floor(progress * this.target);
                                if (progress < 1) {
                                    requestAnimationFrame(step);
                                } else {
                                    this.current = this.target;
                                }
                            };
                            requestAnimationFrame(step);
                        }
                    }"
                    class="p-6"
                >
                    @if($icon)
                        <div class="flex justify-center mb-4">
                            <span class="text-4xl">{{ $icon }}</span>
                        </div>
                    @endif

                    <div class="text-display mb-2 text-content">
                        <span x-text="current.toLocaleString('de-DE')">0</span><span>{{ $suffix }}</span>
                    </div>

                    @if($label)
                        <p class="text-body-large text-content-secondary">{{ $label }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-section>
