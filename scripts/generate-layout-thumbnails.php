<?php

declare(strict_types=1);

/**
 * Vorschaubilder fuer den Layout-Auswahldialog erzeugen.
 *
 * Aufruf: php scripts/generate-layout-thumbnails.php
 *
 * Warum schematisch und nicht als Screenshot: ein Screenshot zeigt die
 * Demo-Inhalte und veraltet mit jeder Textaenderung. Gefragt ist im Auswahl-
 * dialog aber nur eine Antwort, naemlich "wie ist das aufgebaut". Ein Schema
 * beantwortet das in einem Blick und bleibt richtig, solange die Struktur
 * stimmt.
 *
 * Die Bausteine sind absichtlich wenige: Flaeche fuer Bild, Balken fuer
 * Ueberschrift, duenne Linien fuer Text, Pille fuer Button. Wer ein neues
 * Layout ergaenzt, beschreibt es in LAYOUTS mit denselben Bausteinen.
 */
$targetDir = dirname(__DIR__) . '/resources/images/layouts';

if (!is_dir($targetDir) && !mkdir($targetDir, 0o755, true) && !is_dir($targetDir)) {
    fwrite(STDERR, "Zielordner konnte nicht angelegt werden: {$targetDir}\n");
    exit(1);
}

const W = 480;
const H = 300;

/**
 * Aufbau je Layout.
 *
 * cols: Anzahl gleich breiter Spalten, oder ein Array mit Gewichtungen.
 * cell: was in jeder Spalte steht, von oben nach unten.
 *       img, head, text, button, quote, avatar, chart, table, video, map, dots
 * head: true = Sektionsueberschrift ueber den Spalten.
 */
const LAYOUTS = [
    'hero' => ['cols' => [1, 1], 'cell' => [['head', 'text', 'button'], ['img']], 'head' => false],
    'one_column' => ['cols' => 1, 'cell' => [['head', 'text', 'text']], 'head' => false],
    'two_columns' => ['cols' => 2, 'cell' => [['head', 'text'], ['head', 'text']], 'head' => false],
    'three_columns' => ['cols' => 3, 'cell' => [['head', 'text'], ['head', 'text'], ['head', 'text']], 'head' => false],
    'four_columns' => ['cols' => 4, 'cell' => [['head', 'text'], ['head', 'text'], ['head', 'text'], ['head', 'text']], 'head' => false],
    'one_third_two_thirds' => ['cols' => [1, 2], 'cell' => [['head', 'text'], ['head', 'text', 'text']], 'head' => false],
    'two_thirds_one_third' => ['cols' => [2, 1], 'cell' => [['head', 'text', 'text'], ['head', 'text']], 'head' => false],
    'one_column_image' => ['cols' => 1, 'cell' => [['img', 'head', 'text']], 'head' => false],
    'two_columns_images' => ['cols' => 2, 'cell' => [['img', 'head', 'text'], ['img', 'head', 'text']], 'head' => false],
    'three_columns_images' => ['cols' => 3, 'cell' => [['img', 'head'], ['img', 'head'], ['img', 'head']], 'head' => true],
    'four_columns_images' => ['cols' => 4, 'cell' => [['img', 'head'], ['img', 'head'], ['img', 'head'], ['img', 'head']], 'head' => true],
    'cards' => ['cols' => 3, 'cell' => [['card'], ['card'], ['card']], 'head' => true],
    'testimonials' => ['cols' => 2, 'cell' => [['quote', 'avatar'], ['quote', 'avatar']], 'head' => true],
    'team' => ['cols' => 3, 'cell' => [['portrait', 'head'], ['portrait', 'head'], ['portrait', 'head']], 'head' => true],
    'stats' => ['cols' => 4, 'cell' => [['big', 'text'], ['big', 'text'], ['big', 'text'], ['big', 'text']], 'head' => true],
    'pricing_table' => ['cols' => 3, 'cell' => [['price'], ['price'], ['price']], 'head' => true],
    'timeline' => ['cols' => 1, 'cell' => [['timeline']], 'head' => true],
    'posts' => ['cols' => 3, 'cell' => [['img', 'head', 'text'], ['img', 'head', 'text'], ['img', 'head', 'text']], 'head' => true],
    'table' => ['cols' => 1, 'cell' => [['table']], 'head' => true],
    'accordion' => ['cols' => 1, 'cell' => [['accordion']], 'head' => false],
    'tabs' => ['cols' => 1, 'cell' => [['tabs']], 'head' => false],
    'gallery' => ['cols' => 3, 'cell' => [['img', 'img'], ['img', 'img'], ['img', 'img']], 'head' => true],
    'image' => ['cols' => 1, 'cell' => [['bigimg']], 'head' => false],
    'video' => ['cols' => 1, 'cell' => [['video']], 'head' => false],
    'before_after' => ['cols' => 1, 'cell' => [['beforeafter']], 'head' => true],
    'logo_slider' => ['cols' => 1, 'cell' => [['logos']], 'head' => true],
    'cta' => ['cols' => 1, 'cell' => [['ctabox']], 'head' => false],
    'button' => ['cols' => 1, 'cell' => [['buttononly']], 'head' => false],
    'divider' => ['cols' => 1, 'cell' => [['divider']], 'head' => false],
    'contact_form' => ['cols' => [1, 1], 'cell' => [['head', 'text'], ['form']], 'head' => false],
    'map' => ['cols' => 1, 'cell' => [['map']], 'head' => true],
    'member_downloads' => ['cols' => 1, 'cell' => [['table']], 'head' => true],
];

final class Canvas
{
    public GdImage $img;

    public int $bg;

    public int $ink;

    public int $inkSoft;

    public int $accent;

    public int $line;

    public function __construct()
    {
        $this->img = imagecreatetruecolor(W, H);
        imageantialias($this->img, true);
        $this->bg = imagecolorallocate($this->img, 0xF7, 0xF8, 0xF8);
        $this->ink = imagecolorallocate($this->img, 0x9A, 0xA0, 0xA8);
        $this->inkSoft = imagecolorallocate($this->img, 0xC8, 0xCD, 0xD2);
        // Akzentfarbe aus den Design-Tokens des Themes, nicht fest verdrahtet.
        // Sonst tragen die Vorschaubilder eines Kundenthemes die Markenfarbe des
        // Starters, was im Auswahldialog sofort auffaellt.
        [$r, $g, $b] = self::akzent();
        $this->accent = imagecolorallocate($this->img, $r, $g, $b);
        $this->line = imagecolorallocate($this->img, 0xDD, 0xE1, 0xE4);
        imagefilledrectangle($this->img, 0, 0, W, H, $this->bg);
    }

    public function box(int $x, int $y, int $w, int $h, int $color, int $radius = 4): void
    {
        imagefilledrectangle($this->img, $x + $radius, $y, $x + $w - $radius, $y + $h, $color);
        imagefilledrectangle($this->img, $x, $y + $radius, $x + $w, $y + $h - $radius, $color);
        foreach ([[$x + $radius, $y + $radius], [$x + $w - $radius, $y + $radius], [$x + $radius, $y + $h - $radius], [$x + $w - $radius, $y + $h - $radius]] as [$cx, $cy]) {
            imagefilledellipse($this->img, $cx, $cy, $radius * 2, $radius * 2, $color);
        }
    }

    public function outline(int $x, int $y, int $w, int $h, int $color): void
    {
        imagerectangle($this->img, $x, $y, $x + $w, $y + $h, $color);
    }

    public function save(string $path): void
    {
        imagepng($this->img, $path, 9);
    }

    /**
     * Akzentfarbe des Themes aus tokens.css lesen.
     *
     * Bewusst per Textsuche und nicht ueber WordPress: das Skript laeuft
     * eigenstaendig auf der Kommandozeile, ohne geladene Installation.
     *
     * @return array{0: int, 1: int, 2: int}
     */
    private static function akzent(): array
    {
        $datei = dirname(__DIR__) . '/resources/css/tokens.css';
        $inhalt = is_readable($datei) ? (string) file_get_contents($datei) : '';

        if (preg_match('/--color-accent-500:\s*#([0-9A-Fa-f]{6})/', $inhalt, $treffer) !== 1) {
            // Notnagel: das Orange des Starters.
            return [0xFF, 0x6B, 0x35];
        }

        return [
            (int) hexdec(substr($treffer[1], 0, 2)),
            (int) hexdec(substr($treffer[1], 2, 2)),
            (int) hexdec(substr($treffer[1], 4, 2)),
        ];
    }
}

/**
 * Einen Baustein zeichnen und die neue Y-Position zurueckgeben.
 */
function draw(Canvas $c, string $kind, int $x, int $y, int $w, int $avail): int
{
    $gap = 8;

    switch ($kind) {
        case 'img':
            $h = min(58, (int) ($avail * 0.42));
            $c->box($x, $y, $w, $h, $c->inkSoft);
            // Bergsymbol wie bei den Platzhaltern
            imagefilledpolygon($c->img, [
                $x + (int) ($w * 0.2), $y + $h - 6,
                $x + (int) ($w * 0.45), $y + (int) ($h * 0.45),
                $x + (int) ($w * 0.7), $y + $h - 6,
            ], $c->ink);
            imagefilledellipse($c->img, $x + (int) ($w * 0.72), $y + (int) ($h * 0.3), 10, 10, $c->ink);

            return $y + $h + $gap;

        case 'bigimg':
            $h = (int) ($avail * 0.8);
            $c->box($x, $y, $w, $h, $c->inkSoft);
            imagefilledpolygon($c->img, [
                $x + (int) ($w * 0.3), $y + $h - 12,
                $x + (int) ($w * 0.5), $y + (int) ($h * 0.4),
                $x + (int) ($w * 0.7), $y + $h - 12,
            ], $c->ink);

            return $y + $h + $gap;

        case 'portrait':
            $pw = (int) ($w * 0.62);
            $ph = (int) ($pw * 1.25);
            $c->box($x + (int) (($w - $pw) / 2), $y, $pw, $ph, $c->inkSoft);

            return $y + $ph + $gap;

        case 'head':
            $c->box($x, $y, (int) ($w * 0.7), 9, $c->ink, 3);

            return $y + 9 + $gap;

        case 'big':
            $c->box($x, $y, (int) ($w * 0.6), 20, $c->accent, 3);

            return $y + 20 + $gap;

        case 'text':
            $c->box($x, $y, $w, 5, $c->inkSoft, 2);
            $c->box($x, $y + 10, (int) ($w * 0.85), 5, $c->inkSoft, 2);

            return $y + 15 + $gap;

        case 'button':
            $c->box($x, $y, 74, 20, $c->accent, 10);

            return $y + 20 + $gap;

        case 'buttononly':
            $c->box($x + (int) (($w - 96) / 2), $y + (int) ($avail / 2) - 12, 96, 24, $c->accent, 12);

            return $y + $avail;

        case 'card':
            $h = min(120, $avail);
            $c->box($x, $y, $w, $h, 0xFFFFFF === 0 ? $c->bg : imagecolorallocate($c->img, 255, 255, 255), 6);
            $c->outline($x, $y, $w, $h, $c->line);
            $c->box($x + 12, $y + 14, 22, 22, $c->accent, 6);
            $c->box($x + 12, $y + 48, (int) ($w * 0.6), 8, $c->ink, 3);
            $c->box($x + 12, $y + 66, $w - 24, 5, $c->inkSoft, 2);
            $c->box($x + 12, $y + 78, (int) (($w - 24) * 0.8), 5, $c->inkSoft, 2);

            return $y + $h + $gap;

        case 'quote':
            $h = min(96, $avail);
            $white = imagecolorallocate($c->img, 255, 255, 255);
            $c->box($x, $y, $w, $h, $white, 6);
            $c->outline($x, $y, $w, $h, $c->line);
            $c->box($x + 12, $y + 14, 16, 12, $c->accent, 3);
            $c->box($x + 12, $y + 36, $w - 24, 5, $c->inkSoft, 2);
            $c->box($x + 12, $y + 48, (int) (($w - 24) * 0.9), 5, $c->inkSoft, 2);

            return $y + $h + $gap;

        case 'avatar':
            imagefilledellipse($c->img, $x + 16, $y + 4, 24, 24, $c->inkSoft);
            $c->box($x + 36, $y - 2, (int) ($w * 0.4), 7, $c->ink, 3);

            return $y + 24 + $gap;

        case 'price':
            $h = min(150, $avail);
            $white = imagecolorallocate($c->img, 255, 255, 255);
            $c->box($x, $y, $w, $h, $white, 6);
            $c->outline($x, $y, $w, $h, $c->line);
            $c->box($x + 12, $y + 14, (int) ($w * 0.5), 8, $c->ink, 3);
            $c->box($x + 12, $y + 32, (int) ($w * 0.7), 18, $c->accent, 3);
            for ($i = 0; $i < 3; $i++) {
                $c->box($x + 12, $y + 62 + $i * 12, $w - 30, 4, $c->inkSoft, 2);
            }
            $c->box($x + 12, $y + $h - 28, $w - 24, 18, $c->accent, 9);

            return $y + $h + $gap;

        case 'timeline':
            $mid = $x + (int) ($w / 2);
            imagefilledrectangle($c->img, $mid - 1, $y, $mid + 1, $y + $avail - 10, $c->line);
            for ($i = 0; $i < 3; $i++) {
                $ty = $y + 8 + $i * 46;
                $links = $i % 2 === 0;
                $bx = $links ? $x : $mid + 22;
                $c->box($bx, $ty, (int) ($w / 2) - 22, 34, imagecolorallocate($c->img, 255, 255, 255), 5);
                $c->outline($bx, $ty, (int) ($w / 2) - 22, 34, $c->line);
                $c->box($bx + 10, $ty + 9, 40, 6, $c->accent, 3);
                $c->box($bx + 10, $ty + 21, (int) ($w * 0.3), 4, $c->inkSoft, 2);
                imagefilledellipse($c->img, $mid, $ty + 17, 10, 10, $c->accent);
            }

            return $y + $avail;

        case 'table':
            $rows = 4;
            $cols = 3;
            $rh = 22;
            $cw = (int) ($w / $cols);
            for ($r = 0; $r < $rows; $r++) {
                $ry = $y + $r * $rh;
                if ($r === 0) {
                    $c->box($x, $ry, $w, $rh - 2, $c->inkSoft, 3);
                } elseif ($r % 2 === 0) {
                    $c->box($x, $ry, $w, $rh - 2, imagecolorallocate($c->img, 240, 242, 243), 0);
                }
                for ($col = 0; $col < $cols; $col++) {
                    $c->box($x + $col * $cw + 10, $ry + 8, (int) ($cw * 0.5), 5, $r === 0 ? $c->ink : $c->inkSoft, 2);
                }
            }

            return $y + $rows * $rh + $gap;

        case 'accordion':
            for ($i = 0; $i < 3; $i++) {
                $ry = $y + $i * 34;
                $c->box($x, $ry, $w, 26, imagecolorallocate($c->img, 255, 255, 255), 4);
                $c->outline($x, $ry, $w, 26, $c->line);
                $c->box($x + 12, $ry + 10, (int) ($w * 0.45), 6, $c->ink, 3);
                $c->box($x + $w - 26, $ry + 12, 12, 3, $c->inkSoft, 1);
            }

            return $y + 3 * 34 + $gap;

        case 'tabs':
            $tw = (int) ($w / 3) - 8;
            for ($i = 0; $i < 3; $i++) {
                $tx = $x + $i * ($tw + 8);
                $c->box($tx, $y, $tw, 20, $i === 0 ? $c->accent : $c->inkSoft, 4);
            }
            $c->box($x, $y + 30, $w, 3, $c->line, 1);
            $c->box($x, $y + 44, (int) ($w * 0.5), 8, $c->ink, 3);
            $c->box($x, $y + 60, $w, 5, $c->inkSoft, 2);
            $c->box($x, $y + 72, (int) ($w * 0.8), 5, $c->inkSoft, 2);

            return $y + 90;

        case 'video':
            $h = (int) ($avail * 0.78);
            $c->box($x, $y, $w, $h, $c->inkSoft, 6);
            $cx = $x + (int) ($w / 2);
            $cy = $y + (int) ($h / 2);
            imagefilledellipse($c->img, $cx, $cy, 46, 46, imagecolorallocate($c->img, 255, 255, 255));
            imagefilledpolygon($c->img, [$cx - 7, $cy - 11, $cx - 7, $cy + 11, $cx + 12, $cy], $c->accent);

            return $y + $h + $gap;

        case 'map':
            $h = (int) ($avail * 0.8);
            $c->box($x, $y, $w, $h, $c->inkSoft, 6);
            $cx = $x + (int) ($w / 2);
            $cy = $y + (int) ($h / 2) - 6;
            imagefilledellipse($c->img, $cx, $cy, 26, 26, $c->accent);
            imagefilledpolygon($c->img, [$cx - 9, $cy + 6, $cx + 9, $cy + 6, $cx, $cy + 24], $c->accent);
            imagefilledellipse($c->img, $cx, $cy, 10, 10, imagecolorallocate($c->img, 255, 255, 255));

            return $y + $h + $gap;

        case 'beforeafter':
            $h = (int) ($avail * 0.8);
            $c->box($x, $y, $w, $h, $c->inkSoft, 6);
            $mid = $x + (int) ($w / 2);
            imagefilledrectangle($c->img, $x, $y, $mid, $y + $h, imagecolorallocate($c->img, 200, 205, 210));
            imagefilledrectangle($c->img, $mid - 2, $y, $mid + 2, $y + $h, imagecolorallocate($c->img, 255, 255, 255));
            imagefilledellipse($c->img, $mid, $y + (int) ($h / 2), 28, 28, imagecolorallocate($c->img, 255, 255, 255));
            imagefilledellipse($c->img, $mid, $y + (int) ($h / 2), 10, 10, $c->accent);

            return $y + $h + $gap;

        case 'logos':
            $lw = (int) ($w / 5) - 10;
            for ($i = 0; $i < 5; $i++) {
                $c->box($x + $i * ($lw + 12), $y + 10, $lw, 26, $c->inkSoft, 4);
            }

            return $y + 46 + $gap;

        case 'ctabox':
            $h = min(120, $avail);
            $c->box($x, $y, $w, $h, $c->accent, 8);
            $white = imagecolorallocate($c->img, 255, 255, 255);
            $c->box($x + (int) ($w * 0.2), $y + 24, (int) ($w * 0.6), 10, $white, 4);
            $c->box($x + (int) ($w * 0.28), $y + 46, (int) ($w * 0.44), 5, $white, 2);
            $c->box($x + (int) (($w - 100) / 2), $y + $h - 40, 100, 22, $white, 11);

            return $y + $h + $gap;

        case 'form':
            for ($i = 0; $i < 3; $i++) {
                $fy = $y + $i * 34;
                $c->box($x, $fy, 60, 5, $c->ink, 2);
                $c->box($x, $fy + 11, $w, 18, imagecolorallocate($c->img, 255, 255, 255), 4);
                $c->outline($x, $fy + 11, $w, 18, $c->line);
            }
            $c->box($x, $y + 3 * 34, 90, 22, $c->accent, 11);

            return $y + 3 * 34 + 30;

        case 'divider':
            $c->box($x, $y + (int) ($avail / 2), $w, 3, $c->line, 1);

            return $y + $avail;
    }

    return $y;
}

$pad = 26;
$erzeugt = 0;

foreach (LAYOUTS as $name => $spec) {
    $c = new Canvas();

    $y = $pad;
    $innerW = W - 2 * $pad;

    if ($spec['head']) {
        $c->box($pad + (int) ($innerW * 0.25), $y, (int) ($innerW * 0.5), 11, $c->ink, 4);
        $c->box($pad + (int) ($innerW * 0.15), $y + 20, (int) ($innerW * 0.7), 5, $c->inkSoft, 2);
        $y += 42;
    }

    $cols = is_array($spec['cols']) ? $spec['cols'] : array_fill(0, $spec['cols'], 1);
    $summe = array_sum($cols);
    $gap = 14;
    $verfuegbar = $innerW - $gap * (count($cols) - 1);
    $avail = H - $y - $pad;

    $x = $pad;
    foreach ($cols as $i => $gewicht) {
        $cw = (int) round($verfuegbar * ($gewicht / $summe));
        $cy = $y;
        foreach ($spec['cell'][$i] ?? [] as $kind) {
            $cy = draw($c, $kind, $x, $cy, $cw, $avail);
        }
        $x += $cw + $gap;
    }

    $c->save("{$targetDir}/{$name}.png");
    $erzeugt++;
}

printf("%d Vorschaubilder erzeugt in %s\n", $erzeugt, str_replace(dirname(__DIR__) . '/', '', $targetDir));
