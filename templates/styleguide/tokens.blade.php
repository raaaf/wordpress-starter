{{--
    Design-System-Referenz: Tokens

    Jede Probe zeichnet sich mit `var(--token)`. Was hier steht, ist damit immer
    der aktuelle Wert und nie eine Kopie davon. Wird ein Token umbenannt, bleibt
    das Feld leer statt still einen alten Wert zu zeigen.

    Die Namen kommen aus WordpressStarter\Content\StyleguideReference.
--}}

@php
    use WordpressStarter\Content\StyleguideReference;
@endphp

<x-section anchor="tokens" background="primary" padding="lg" class="styleguide-tokens">
    <x-section-header
        chip="Design System"
        headline="Tokens"
        description="Farben, Typografie, Schatten und Abstände, direkt aus den aktiven Design-Tokens gezeichnet."
    />

    {{-- Typografie --}}
    <h3 class="mb-6">Typografie</h3>
    <div class="mb-16 divide-y divide-line rounded-[var(--card-radius)] border border-line overflow-hidden">
        @foreach(StyleguideReference::typeScale() as $class => $label)
            <div class="flex flex-col gap-1 p-6 md:flex-row md:items-baseline md:gap-8">
                <code class="shrink-0 font-mono text-body-small text-content-secondary md:w-48">.{{ $class }}</code>
                <span class="{{ $class }} text-content">{{ $label }}</span>
            </div>
        @endforeach
    </div>

    {{-- Flächen --}}
    <h3 class="mb-6">Flächen</h3>
    <div class="grid grid-cols-2 gap-4 mb-16 md:grid-cols-3 lg:grid-cols-4">
        @foreach(StyleguideReference::surfaces() as $surface)
            <div class="overflow-hidden border rounded-[var(--card-radius)] border-line">
                <div
                    class="flex items-end h-24 p-3"
                    style="background: var({{ $surface['token'] }})"
                >
                    <span class="text-body-small" style="color: var({{ $surface['textRole'] ?? '--text-primary' }})">Aa</span>
                </div>
                <div class="p-3">
                    <p class="mb-0 text-body-small text-content">{{ $surface['label'] }}</p>
                    <code class="font-mono text-caption text-content-secondary">{{ $surface['token'] }}</code>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Textfarben --}}
    <h3 class="mb-6">Textfarben</h3>
    <div class="grid grid-cols-1 gap-4 mb-16 md:grid-cols-2">
        @foreach(StyleguideReference::textRoles() as $token => $label)
            <div class="flex items-baseline gap-4 p-4 border rounded-[var(--card-radius)] border-line">
                <span class="text-body" style="color: var({{ $token }})">Beispieltext</span>
                <code class="ml-auto font-mono text-caption text-content-secondary">{{ $token }}</code>
            </div>
        @endforeach
    </div>

    {{-- Rahmen --}}
    <h3 class="mb-6">Rahmen</h3>
    <div class="grid grid-cols-2 gap-4 mb-16 md:grid-cols-4">
        @foreach(StyleguideReference::borderRoles() as $token => $label)
            <div class="p-4">
                <div
                    class="h-16 mb-3 rounded-[var(--card-radius)]"
                    style="border: 2px solid var({{ $token }})"
                ></div>
                <p class="mb-0 text-body-small text-content">{{ $label }}</p>
                <code class="font-mono text-caption text-content-secondary">{{ $token }}</code>
            </div>
        @endforeach
    </div>

    {{-- Schatten --}}
    <h3 class="mb-6">Schatten</h3>
    <div class="grid grid-cols-2 gap-6 mb-16 md:grid-cols-3 lg:grid-cols-4">
        @foreach(StyleguideReference::shadows() as $token => $label)
            <div
                class="p-5 bg-surface rounded-[var(--card-radius)]"
                style="box-shadow: var({{ $token }})"
            >
                <p class="mb-0 text-body-small text-content">{{ $label }}</p>
                <code class="font-mono text-caption text-content-secondary">{{ $token }}</code>
            </div>
        @endforeach
    </div>

    {{-- Verläufe --}}
    <h3 class="mb-6">Verläufe</h3>
    <div class="grid grid-cols-1 gap-4 mb-16 md:grid-cols-2 lg:grid-cols-3">
        @foreach(StyleguideReference::gradients() as $gradient)
            <div class="overflow-hidden border rounded-[var(--card-radius)] border-line">
                <div
                    class="h-20"
                    style="background: linear-gradient(to bottom, var({{ $gradient['start'] }}), var({{ $gradient['end'] }}))"
                ></div>
                <div class="p-3">
                    <p class="mb-0 text-body-small text-content">{{ $gradient['label'] }}</p>
                    <code class="font-mono text-caption text-content-secondary">{{ $gradient['start'] }} → {{ $gradient['end'] }}</code>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Abstände --}}
    <h3 class="mb-6">Abstände</h3>
    <div class="mb-16 space-y-3">
        @foreach(StyleguideReference::spacing() as $token)
            <div class="flex items-center gap-4">
                <code class="shrink-0 font-mono text-caption text-content-secondary w-28">{{ $token }}</code>
                <div class="h-4 bg-surface-accent rounded-[var(--radius-sm)]" style="width: var({{ $token }})"></div>
            </div>
        @endforeach
    </div>

    {{-- Radien --}}
    <h3 class="mb-6">Radien</h3>
    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
        @foreach(StyleguideReference::radii() as $token => $label)
            <div class="text-center">
                <div
                    class="h-20 mb-3 border bg-surface-secondary border-line"
                    style="border-radius: var({{ $token }})"
                ></div>
                <p class="mb-0 text-body-small text-content">{{ $label }}</p>
            </div>
        @endforeach
    </div>
</x-section>
