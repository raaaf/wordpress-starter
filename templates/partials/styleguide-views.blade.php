{{--
    Umschalter zwischen den beiden Haelften des Styleguides.

    Zwei Aufgaben, zwei Ansichten: Tokens und Komponenten schlaegt man nach,
    Module vergleicht man. Zusammen auf einer Seite hiess, fuer das eine immer
    am anderen vorbeizuscrollen (8.281px Design-System vor 23.561px Galerie).

    Echte Links statt Schaltflaechen: die Ansicht wird serverseitig gerendert,
    ist damit teilbar, im Verlauf und ohne JavaScript nutzbar. `aria-current`
    statt `role="tablist"`, weil das hier eine Navigation ist und kein Tabpanel
    im selben Dokument.

    @var string $ansicht Aktive Ansicht: 'module' oder 'design-system'
--}}
{{-- TemplateRenderTest rendert jedes Template einmal ohne Variablen. --}}
@php($ansicht = $ansicht ?? 'module')
@php($ansichten = ['module' => __('Module', 'wp-starter'), 'design-system' => __('Design-System', 'wp-starter')])

<nav
    aria-label="{{ __('Ansicht', 'wp-starter') }}"
    class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-6"
>
    <div class="inline-flex items-center gap-1 p-1 border rounded-full border-line bg-surface-secondary">
        @foreach($ansichten as $wert => $beschriftung)
            @php($aktiv = $ansicht === $wert)
            <a
                href="{{ esc_url(add_query_arg('ansicht', $wert, get_permalink())) }}"
                @if($aktiv) aria-current="page" @endif
                class="px-3 py-1 text-sm font-medium no-underline transition-colors rounded-full focus-visible:outline-none focus-visible:shadow-[var(--shadow-focus-ring)] {{ $aktiv ? 'bg-surface text-content shadow-[var(--shadow-button)]' : 'text-content-secondary hover:text-content' }}"
            >{{ $beschriftung }}</a>
        @endforeach
    </div>
</nav>

{{-- Nonce nicht vergessen: die Content Security Policy des Themes nennt einen,
     und damit ignoriert der Browser 'unsafe-inline'. Ohne Nonce laeuft das
     Skript nie, und zwar lautlos bis auf eine Konsolenmeldung. --}}
<script nonce="{{ $GLOBALS['csp_nonce'] ?? '' }}">
    /*
     * Ein Anker aus der jeweils anderen Ansicht liefe sonst ins Leere: ein
     * geteilter Link auf `#komponenten` landet in der Modulansicht, wo es das
     * Ziel nicht gibt.
     *
     * Umgeleitet wird nur, wenn die URL keine Ansicht nennt. Wer eine nennt,
     * wird beim Wort genommen, und ein Tippfehler im Anker kann nicht zwischen
     * beiden Ansichten hin und her pendeln.
     *
     * Allowlist statt Blindflug: nur die bekannten, stabilen Anker der
     * Design-System-Ansicht loesen eine Umleitung aus. Ein schlicht
     * veralteter Anker (z. B. #cards-99) bleibt sonst ohne Ziel stehen,
     * statt in eine Ansicht umgeleitet zu werden, in der er ebenfalls fehlt.
     *
     * Stille Kopie: 'tokens' und 'komponenten' sind identisch zu
     * anchor="tokens" in templates/styleguide/tokens.blade.php:15 und
     * anchor="komponenten" in templates/styleguide/components.blade.php:13.
     * Wird einer der beiden Anker dort umbenannt, muss diese Liste hier
     * manuell nachgezogen werden — sonst bricht die Umleitung lautlos.
     */
    document.addEventListener('DOMContentLoaded', function () {
        var ziel = window.location.hash.slice(1);
        var designSystemAnker = ['tokens', 'komponenten'];

        if (!ziel || document.getElementById(ziel) || designSystemAnker.indexOf(ziel) === -1) {
            return;
        }

        var url = new URL(window.location.href);

        if (url.searchParams.has('ansicht')) {
            return;
        }

        url.searchParams.set('ansicht', 'design-system');
        window.location.replace(url.toString());
    });
</script>
