{{--
    Design-System-Referenz: Komponenten

    Hier stehen die echten Blade-Komponenten, nicht nachgebautes HTML. Das ist der
    ganze Zweck dieser Datei: was der Styleguide zeigt, IST die Komponente. Ändert
    sich <x-button>, ändert sich diese Seite mit, ohne dass jemand daran denken muss.

    Die Vorgängerlösung schrieb HTML-Strings in ein WYSIWYG-Feld und ist genau daran
    auseinandergelaufen, die Badge-Demo zeigte eine solide Variante, die die
    Komponente nie hatte, und die Formular-Demo zeigte native Inputs.
--}}

@php
    use WordpressStarter\Services\StyleguidePage;
@endphp

<x-section anchor="{{ StyleguidePage::DESIGN_SYSTEM_ANCHORS['components'] }}" background="secondary" padding="lg" class="styleguide-components">
    <x-section-header
        chip="Design System"
        :headline="__('Komponenten', 'wp-starter')"
        :description="__('Gerendert aus den Blade-Komponenten selbst. Was hier steht, ist der aktuelle Stand.', 'wp-starter')"
    />

    {{-- Buttons --}}
    <h3 class="mb-6">Buttons</h3>

    @php
        $btnVarianten = ['primary' => __('Primär', 'wp-starter'), 'secondary' => __('Sekundär', 'wp-starter'), 'ghost' => __('Dezent', 'wp-starter'), 'danger' => __('Warnung', 'wp-starter'), 'inverse' => __('Invertiert', 'wp-starter')];
        $btnZustaende = ['' => __('Standard', 'wp-starter'), 'zeige-hover' => 'Hover', 'zeige-fokus' => __('Fokus', 'wp-starter'), 'zeige-aktiv' => __('Aktiv', 'wp-starter')];
    @endphp

    {{-- Varianten mal Zustaende.
         Die Zustandsspalten sind keine echten Pseudoklassen, die lassen sich nicht
         erzwingen. Die Demo-Klassen in app.css greifen auf dieselben Tokens zu wie
         die Komponente, kopieren also keine Werte, nur die Struktur. --}}
    <x-card variant="outlined" padding="none" class="p-6 mb-6">
        <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr>
                    <th class="pb-4 pr-6 text-body-small text-content-secondary font-normal">{{ __('Variante', 'wp-starter') }}</th>
                    @foreach($btnZustaende as $label)
                        <th class="pb-4 pr-6 text-body-small text-content-secondary font-normal">{{ $label }}</th>
                    @endforeach
                    <th class="pb-4 text-body-small text-content-secondary font-normal">{{ __('Deaktiviert', 'wp-starter') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($btnVarianten as $variante => $label)
                    <tr>
                        <th scope="row" class="py-3 pr-6 font-normal text-content-secondary text-body-small">{{ $label }}</th>
                        @foreach($btnZustaende as $klasse => $zustand)
                            <td class="py-3 pr-6 {{ $klasse }}">
                                <x-button url="#" :title="$label" :variant="$variante" />
                            </td>
                        @endforeach
                        <td class="py-3">
                            <x-button url="#" :title="$label" :variant="$variante" :disabled="true" />
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </x-card>

    {{-- Groessen --}}
    <x-card variant="outlined" padding="none" class="p-6 mb-6">
        <p class="mb-4 text-body-small text-content-secondary">{{ __('Größen. Sichtbar 32, 40 und 48 Pixel hoch; die Trefferfläche misst am Finger überall mindestens 44 Pixel.', 'wp-starter') }}</p>
        <div class="flex flex-wrap items-center gap-4">
            <x-button url="#" :title="__('Klein', 'wp-starter')" size="sm" />
            <x-button url="#" :title="__('Mittel', 'wp-starter')" size="md" />
            <x-button url="#" :title="__('Groß', 'wp-starter')" size="lg" />
            <x-button url="#" :title="__('Über die Breite', 'wp-starter')" size="md" class="w-full sm:w-auto" />
        </div>
    </x-card>

    {{-- Auf fremden Flaechen --}}
    <div class="grid gap-4 mb-16 md:grid-cols-2">
        <div class="p-6 bg-surface-inverse text-content-inverse rounded-[var(--card-radius)]">
            <p class="mb-4 text-body-small">{{ __('Auf der inversen Fläche', 'wp-starter') }}</p>
            <div class="flex flex-wrap items-center gap-4">
                <x-button url="#" :title="__('Primär', 'wp-starter')" variant="primary" />
                <x-button url="#" :title="__('Sekundär', 'wp-starter')" variant="secondary" />
                <x-button url="#" :title="__('Dezent', 'wp-starter')" variant="ghost" />
            </div>
        </div>
        <div class="p-6 bg-surface-brand text-content-on-brand rounded-[var(--card-radius)]">
            <p class="mb-4 text-body-small">{{ __('Auf der Markenfläche', 'wp-starter') }}</p>
            <div class="flex flex-wrap items-center gap-4">
                <x-button url="#" :title="__('Invertiert', 'wp-starter')" variant="inverse" />
                <x-button url="#" :title="__('Sekundär', 'wp-starter')" variant="secondary" />
                <x-button url="#" :title="__('Dezent', 'wp-starter')" variant="ghost" />
            </div>
        </div>
    </div>

    {{-- Badges --}}
    <h3 class="mb-6">Badges</h3>
    <x-card variant="outlined" padding="none" class="p-6 mb-6">
        <div class="flex flex-wrap items-center gap-3">
            @foreach(['gray', 'brand', 'accent', 'success', 'warning', 'error'] as $variant)
                <x-badge :variant="$variant">{{ ucfirst($variant) }}</x-badge>
            @endforeach
        </div>
    </x-card>
    <x-card variant="outlined" padding="none" class="p-6 mb-16">
        <div class="flex flex-wrap items-center gap-3">
            <x-badge variant="brand" style="outline">Outline</x-badge>
            <x-badge variant="success" :dot="true">{{ __('Mit Punkt', 'wp-starter') }}</x-badge>
            <x-badge variant="gray" size="sm">Small</x-badge>
            <x-badge variant="gray" size="lg">Large</x-badge>
        </div>
    </x-card>

    {{-- Alerts --}}
    <h3 class="mb-6">{{ __('Hinweise', 'wp-starter') }}</h3>
    <div class="mb-16 space-y-4">
        <x-alert variant="info" :message="__('Ein Hinweis mit neutraler Information.', 'wp-starter')" />
        <x-alert variant="success" :message="__('Die Aktion war erfolgreich.', 'wp-starter')" />
        <x-alert variant="warning" :message="__('Bitte prüf diese Angabe.', 'wp-starter')" />
        <x-alert variant="error" :message="__('Etwas ist schiefgelaufen.', 'wp-starter')" :dismissible="true" />
    </div>

    {{-- Formular --}}
    <h3 class="mb-6">{{ __('Formular-Elemente', 'wp-starter') }}</h3>
    <x-card variant="outlined" padding="none" class="p-6 mb-16">
        <x-grid cols="2" gap="lg">
            <div class="space-y-6">
                <x-input name="sg_text" :label="__('Textfeld', 'wp-starter')" :placeholder="__('Beispieltext', 'wp-starter')" :hint="__('Ein Hinweis unter dem Feld.', 'wp-starter')" />
                <x-input name="sg_search" :label="__('Mit Icon', 'wp-starter')" :placeholder="__('Suchen', 'wp-starter')" iconLeft="search" />
                <x-input name="sg_error" :label="__('Fehlerzustand', 'wp-starter')" :value="__('Ungültig', 'wp-starter')" :error="true" :errorMessage="__('Bitte korrigier diese Eingabe.', 'wp-starter')" />
                <x-textarea name="sg_textarea" :label="__('Mehrzeilig', 'wp-starter')" :placeholder="__('Mehrzeiliger Text...', 'wp-starter')" :rows="3" />
            </div>
            <div class="space-y-6">
                <x-select
                    name="sg_select"
                    :label="__('Auswahl', 'wp-starter')"
                    :options="['a' => 'Option A', 'b' => 'Option B', 'c' => 'Option C']"
                    :placeholder="__('Bitte wählen', 'wp-starter')"
                />
                <div class="flex flex-col items-start gap-3">
                    <x-checkbox name="sg_check_1" label="Checkbox" />
                    <x-checkbox name="sg_check_2" :label="__('Vorausgewählt', 'wp-starter')" :checked="true" />
                    <x-checkbox name="sg_check_3" :label="__('Deaktiviert', 'wp-starter')" :disabled="true" />
                </div>
                <div class="flex flex-col items-start gap-3">
                    <x-radio name="sg_radio" value="1" :label="__('Radio eins', 'wp-starter')" :checked="true" />
                    <x-radio name="sg_radio" value="2" :label="__('Radio zwei', 'wp-starter')" />
                </div>
                <div class="flex flex-col items-start gap-3">
                    <x-toggle name="sg_toggle_1" :label="__('Schalter', 'wp-starter')" />
                    <x-toggle name="sg_toggle_2" :label="__('Aktiv', 'wp-starter')" :checked="true" />
                </div>
            </div>
        </x-grid>
    </x-card>

    {{-- Karten --}}
    <h3 class="mb-6">{{ __('Karten', 'wp-starter') }}</h3>
    <x-grid cols="3" gap="lg" class="mb-16">
        <x-card
            variant="default"
            :title="__('Standard', 'wp-starter')"
            :description="__('Karte mit Rahmen und leichtem Schatten.', 'wp-starter')"
        />
        <x-card
            variant="elevated"
            title="Elevated"
            :description="__('Karte ohne Rahmen, die über den Schatten steht.', 'wp-starter')"
        />
        <x-card
            variant="filled"
            title="Filled"
            :description="__('Karte mit Hintergrundfarbe und Rahmen.', 'wp-starter')"
        />
    </x-grid>

    {{-- Links und Icons --}}
    <h3 class="mb-6">{{ __('Links und Icons', 'wp-starter') }}</h3>
    <x-card variant="outlined" padding="none" class="p-6 mb-6">
        <div class="flex flex-wrap items-center gap-6">
            <x-link url="#" variant="accent">{{ __('Akzent-Link', 'wp-starter') }}</x-link>
            <x-link url="#" variant="dark">{{ __('Dunkler Link', 'wp-starter') }}</x-link>
            <x-link url="#" variant="accent" iconRight="chevron-right">{{ __('Mit Icon', 'wp-starter') }}</x-link>
            <x-link url="#" :disabled="true">{{ __('Deaktiviert', 'wp-starter') }}</x-link>
        </div>
    </x-card>
    <x-card variant="outlined" padding="none" class="p-6 mb-16">
        <div class="flex flex-wrap items-center gap-6 text-icon-primary">
            @foreach(['calendar', 'check', 'close', 'download', 'eye', 'info', 'lock', 'mail', 'phone', 'search', 'user', 'warning'] as $icon)
                <span class="flex flex-col items-center gap-2">
                    <x-icon :name="$icon" size="xl" />
                    <code class="text-code text-content-secondary">{{ $icon }}</code>
                </span>
            @endforeach
        </div>
    </x-card>

    {{-- Layout-Helfer --}}
    <h3 class="mb-6">{{ __('Layout-Helfer', 'wp-starter') }}</h3>
    <x-card variant="outlined" padding="none" class="p-6">
        <p class="text-body-small text-content-secondary">
            {!! wp_kses(__('<code>x-grid</code> mit vier Spalten. <code>x-section</code> rahmt jeden Abschnitt dieser Seite, <code>x-prose</code> die Fließtextbereiche der Flexible-Layouts.', 'wp-starter'), ['code' => []]) !!}
        </p>
        <x-grid cols="4" gap="md">
            @for($i = 1; $i <= 4; $i++)
                <div class="p-4 text-center bg-surface-secondary rounded-[var(--rafael-radius-md)] text-body-small text-content">
                    {{ sprintf(__('Spalte %d', 'wp-starter'), $i) }}
                </div>
            @endfor
        </x-grid>
    </x-card>
</x-section>
