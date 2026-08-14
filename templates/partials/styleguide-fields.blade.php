{{--
    ACF-Feldreferenz eines Moduls, eingeklappt.

    Die Liste entsteht aus FlexibleContent::layouts() zur Laufzeit, nie von
    Hand gepflegt: eine handgeschriebene Liste liefe der echten Felddefinition
    davon, sobald ein Feld umbenannt, ergaenzt oder entfernt wird, und ein
    Entwickler saehe Feldnamen, die es nicht mehr gibt.

    <details> statt Alpine: das Auf-/Zuklappen ist reines An-/Aus, braucht
    keinen Zustand ausserhalb des Elements selbst. Nativ bekommt es
    Tastaturbedienung geschenkt und funktioniert auch ohne JS, waehrend Alpine
    hier nur zusaetzlichen Code fuer dasselbe Ergebnis waere.

    @var string $layout  Layout-Name, z.B. 'cards'
    @var bool   $offen   Panel vorab geoeffnet (?variants=all: alles sichtbar)
--}}
@php($layout = $layout ?? '')
@php($offen = $offen ?? false)
@php($zeilen = \WordpressStarter\Content\StyleguideFieldReference::flach($layout))

@if(!empty($zeilen))
    <details class="mt-2" @if($offen) open @endif>
        <summary class="inline-flex items-center gap-1 text-sm cursor-pointer text-content-secondary hover:text-content focus-visible:outline-none focus-visible:shadow-[var(--shadow-focus-ring)]">
            {{ __('Felder anzeigen', 'wp-starter') }} ({{ count($zeilen) }})
        </summary>

        <div class="mt-2 overflow-x-auto">
            <table class="min-w-full text-sm text-left border-collapse">
                <caption class="sr-only">{{ sprintf(__('ACF-Felder von %s', 'wp-starter'), $layout) }}</caption>
                <thead>
                    <tr class="border-b border-line">
                        <th scope="col" class="py-1 pr-4 font-medium text-content-secondary">{{ __('Feld', 'wp-starter') }}</th>
                        <th scope="col" class="py-1 pr-4 font-medium text-content-secondary">{{ __('Typ', 'wp-starter') }}</th>
                        <th scope="col" class="py-1 pr-4 font-medium text-content-secondary">{{ __('Pflicht', 'wp-starter') }}</th>
                        <th scope="col" class="py-1 pr-4 font-medium text-content-secondary">{{ __('Auswahl', 'wp-starter') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($zeilen as $feld)
                        @php($tiefe = $feld['tiefe'])
                        <tr class="border-b border-line last:border-0">
                            <td class="py-1 pr-4 {{ $tiefe === 1 ? 'pl-4' : ($tiefe >= 2 ? 'pl-8' : '') }}"><code>{{ $feld['name'] }}</code></td>
                            <td class="py-1 pr-4"><code>{{ $feld['type'] }}</code></td>
                            <td class="py-1 pr-4">{{ $feld['required'] ? __('Ja', 'wp-starter') : '' }}</td>
                            <td class="py-1 pr-4 break-words">{{ implode(', ', $feld['choices']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
@endif
