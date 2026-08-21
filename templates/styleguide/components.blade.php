{{--
    Design-System-Referenz: Komponenten

    Hier stehen die echten Blade-Komponenten, nicht nachgebautes HTML. Das ist der
    ganze Zweck dieser Datei: was der Styleguide zeigt, IST die Komponente. Ändert
    sich <x-button>, ändert sich diese Seite mit, ohne dass jemand daran denken muss.

    Die Vorgängerlösung schrieb HTML-Strings in ein WYSIWYG-Feld und ist genau daran
    auseinandergelaufen — die Badge-Demo zeigte eine solide Variante, die die
    Komponente nie hatte, und die Formular-Demo zeigte native Inputs.
--}}

<x-section anchor="komponenten" background="secondary" padding="lg" class="styleguide-components">
    <x-section-header
        chip="Design System"
        headline="Komponenten"
        description="Gerendert aus den Blade-Komponenten selbst. Was hier steht, ist der aktuelle Stand."
    />

    {{-- Buttons --}}
    <h3 class="mb-6">Buttons</h3>

    @php
        $btnVarianten = ['primary' => 'Primär', 'secondary' => 'Sekundär', 'ghost' => 'Dezent', 'danger' => 'Warnung', 'inverse' => 'Invertiert'];
        $btnZustaende = ['' => 'Standard', 'zeige-hover' => 'Hover', 'zeige-fokus' => 'Fokus', 'zeige-aktiv' => 'Aktiv'];
    @endphp

    {{-- Varianten mal Zustaende.
         Die Zustandsspalten sind keine echten Pseudoklassen, die lassen sich nicht
         erzwingen. Die Demo-Klassen in app.css greifen auf dieselben Tokens zu wie
         die Komponente, kopieren also keine Werte, nur die Struktur. --}}
    <div class="p-6 mb-6 overflow-x-auto bg-surface rounded-[var(--card-radius)] border border-line">
        <table class="w-full text-left">
            <thead>
                <tr>
                    <th class="pb-4 pr-6 text-body-small text-content-secondary font-normal">Variante</th>
                    @foreach($btnZustaende as $label)
                        <th class="pb-4 pr-6 text-body-small text-content-secondary font-normal">{{ $label }}</th>
                    @endforeach
                    <th class="pb-4 text-body-small text-content-secondary font-normal">Deaktiviert</th>
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

    {{-- Groessen --}}
    <div class="p-6 mb-6 bg-surface rounded-[var(--card-radius)] border border-line">
        <p class="mb-4 text-body-small text-content-secondary">Größen. Sichtbar 32, 40 und 48 Pixel hoch; die Trefferfläche misst am Finger überall mindestens 44 Pixel.</p>
        <div class="flex flex-wrap items-center gap-4">
            <x-button url="#" title="Klein" size="sm" />
            <x-button url="#" title="Mittel" size="md" />
            <x-button url="#" title="Groß" size="lg" />
            <x-button url="#" title="Über die Breite" size="md" class="w-full sm:w-auto" />
        </div>
    </div>

    {{-- Auf fremden Flaechen --}}
    <div class="grid gap-4 mb-16 md:grid-cols-2">
        <div class="p-6 bg-surface-inverse text-content-inverse rounded-[var(--card-radius)]">
            <p class="mb-4 text-body-small">Auf der inversen Fläche</p>
            <div class="flex flex-wrap items-center gap-4">
                <x-button url="#" title="Primär" variant="primary" />
                <x-button url="#" title="Sekundär" variant="secondary" />
                <x-button url="#" title="Dezent" variant="ghost" />
            </div>
        </div>
        <div class="p-6 bg-surface-brand text-content-on-brand rounded-[var(--card-radius)]">
            <p class="mb-4 text-body-small">Auf der Markenfläche</p>
            <div class="flex flex-wrap items-center gap-4">
                <x-button url="#" title="Invertiert" variant="inverse" />
                <x-button url="#" title="Sekundär" variant="secondary" />
                <x-button url="#" title="Dezent" variant="ghost" />
            </div>
        </div>
    </div>

    {{-- Badges --}}
    <h3 class="mb-6">Badges</h3>
    <div class="p-6 mb-6 bg-surface rounded-[var(--card-radius)] border border-line">
        <div class="flex flex-wrap items-center gap-3">
            @foreach(['gray', 'brand', 'accent', 'success', 'warning', 'error'] as $variant)
                <x-badge :variant="$variant">{{ ucfirst($variant) }}</x-badge>
            @endforeach
        </div>
    </div>
    <div class="p-6 mb-16 bg-surface rounded-[var(--card-radius)] border border-line">
        <div class="flex flex-wrap items-center gap-3">
            <x-badge variant="brand" style="outline">Outline</x-badge>
            <x-badge variant="success" :dot="true">Mit Punkt</x-badge>
            <x-badge variant="gray" size="sm">Small</x-badge>
            <x-badge variant="gray" size="lg">Large</x-badge>
        </div>
    </div>

    {{-- Alerts --}}
    <h3 class="mb-6">Hinweise</h3>
    <div class="mb-16 space-y-4">
        <x-alert variant="info" message="Ein Hinweis mit neutraler Information." />
        <x-alert variant="success" message="Die Aktion war erfolgreich." />
        <x-alert variant="warning" message="Bitte prüf diese Angabe." />
        <x-alert variant="error" message="Etwas ist schiefgelaufen." :dismissible="true" />
    </div>

    {{-- Formular --}}
    <h3 class="mb-6">Formular-Elemente</h3>
    <div class="p-6 mb-16 bg-surface rounded-[var(--card-radius)] border border-line">
        <x-grid cols="2" gap="lg">
            <div class="space-y-6">
                <x-input name="sg_text" label="Textfeld" placeholder="Beispieltext" hint="Ein Hinweis unter dem Feld." />
                <x-input name="sg_search" label="Mit Icon" placeholder="Suchen" iconLeft="search" />
                <x-input name="sg_error" label="Fehlerzustand" value="Ungültig" :error="true" errorMessage="Bitte korrigier diese Eingabe." />
                <x-textarea name="sg_textarea" label="Mehrzeilig" placeholder="Mehrzeiliger Text..." :rows="3" />
            </div>
            <div class="space-y-6">
                <x-select
                    name="sg_select"
                    label="Auswahl"
                    :options="['a' => 'Option A', 'b' => 'Option B', 'c' => 'Option C']"
                    placeholder="Bitte wählen"
                />
                <div class="flex flex-col items-start gap-3">
                    <x-checkbox name="sg_check_1" label="Checkbox" />
                    <x-checkbox name="sg_check_2" label="Vorausgewählt" :checked="true" />
                    <x-checkbox name="sg_check_3" label="Deaktiviert" :disabled="true" />
                </div>
                <div class="flex flex-col items-start gap-3">
                    <x-radio name="sg_radio" value="1" label="Radio eins" :checked="true" />
                    <x-radio name="sg_radio" value="2" label="Radio zwei" />
                </div>
                <div class="flex flex-col items-start gap-3">
                    <x-toggle name="sg_toggle_1" label="Schalter" />
                    <x-toggle name="sg_toggle_2" label="Aktiv" :checked="true" />
                </div>
            </div>
        </x-grid>
    </div>

    {{-- Karten --}}
    <h3 class="mb-6">Karten</h3>
    <x-grid cols="3" gap="lg" class="mb-16">
        <x-card
            variant="default"
            title="Standard"
            description="Karte mit Rahmen und leichtem Schatten."
        />
        <x-card
            variant="elevated"
            title="Elevated"
            description="Karte ohne Rahmen, die über den Schatten steht."
        />
        <x-card
            variant="filled"
            title="Filled"
            description="Karte mit Hintergrundfarbe und Rahmen."
        />
    </x-grid>

    {{-- Links und Icons --}}
    <h3 class="mb-6">Links und Icons</h3>
    <div class="p-6 mb-6 bg-surface rounded-[var(--card-radius)] border border-line">
        <div class="flex flex-wrap items-center gap-6">
            <x-link url="#" variant="accent">Akzent-Link</x-link>
            <x-link url="#" variant="dark">Dunkler Link</x-link>
            <x-link url="#" variant="accent" iconRight="chevron-right">Mit Icon</x-link>
            <x-link url="#" :disabled="true">Deaktiviert</x-link>
        </div>
    </div>
    <div class="p-6 mb-16 bg-surface rounded-[var(--card-radius)] border border-line">
        <div class="flex flex-wrap items-center gap-6 text-icon-primary">
            @foreach(['calendar', 'check', 'close', 'download', 'eye', 'info', 'lock', 'mail', 'phone', 'search', 'user', 'warning'] as $icon)
                <span class="flex flex-col items-center gap-2">
                    <x-icon :name="$icon" size="xl" />
                    <code class="text-code text-content-secondary">{{ $icon }}</code>
                </span>
            @endforeach
        </div>
    </div>

    {{-- Layout-Helfer --}}
    <h3 class="mb-6">Layout-Helfer</h3>
    <div class="p-6 bg-surface rounded-[var(--card-radius)] border border-line">
        <p class="text-body-small text-content-secondary">
            <code>x-grid</code> mit vier Spalten. <code>x-section</code> rahmt jeden Abschnitt dieser
            Seite, <code>x-prose</code> die Fliesstextbereiche der Flexible-Layouts weiter unten.
        </p>
        <x-grid cols="4" gap="md">
            @for($i = 1; $i <= 4; $i++)
                <div class="p-4 text-center bg-surface-secondary rounded-[var(--radius-md)] text-body-small text-content">
                    Spalte {{ $i }}
                </div>
            @endfor
        </x-grid>
    </div>
</x-section>
