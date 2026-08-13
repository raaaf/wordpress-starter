<?php

/**
 * Platzhalterbilder erzeugen.
 *
 * Aufruf: php scripts/generate-placeholders.php
 *
 * Erzeugt die Demo-Bilder fuer den Styleguide neu. Die Bilder sind bewusst
 * generiert und nicht fotografiert: keine Lizenzfrage, sie sind in jeder
 * Aufloesung scharf, und sie zeigen an, wenn ein Zuschnitt zuschlaegt.
 *
 * Reine Farbverlaeufe sahen sauber aus, verrieten aber nicht, ob ein Zuschnitt
 * den Bildinhalt zerschneidet. Deshalb bekommt jedes Bild:
 *
 * - ein zentriertes Bildsymbol, das jeder als Platzhalter erkennt,
 * - einen duennen Innenrahmen mit gleichem Abstand zu allen Kanten, an dem ein
 *   Beschnitt sofort auffaellt, weil er auf einer Seite verschwindet,
 * - eine Massangabe in der Ecke aus Bloecken, damit man die Quelle erkennt,
 *   ohne dass eine Schriftart noetig waere.
 */

$targetDir = dirname(__DIR__) . '/assets/images';

$palette = [
    1 => ['0f1729', '1e3a5f'],
    2 => ['1f2937', '374151'],
    3 => ['292524', '57534e'],
    4 => ['0c2a2a', '115e59'],
    5 => ['2a1f1a', '7c3f1d'],
    6 => ['1e1b31', '4c1d95'],
];

/** @return array{0:int,1:int,2:int} */
function hex2rgb(string $hex): array
{
    return [
        (int) hexdec(substr($hex, 0, 2)),
        (int) hexdec(substr($hex, 2, 2)),
        (int) hexdec(substr($hex, 4, 2)),
    ];
}

function render(string $path, int $w, int $h, string $from, string $to): void
{
    $img = imagecreatetruecolor($w, $h);
    imageantialias($img, true);

    [$r1, $g1, $b1] = hex2rgb($from);
    [$r2, $g2, $b2] = hex2rgb($to);

    // Diagonaler Verlauf, zeilenweise gezeichnet.
    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x += 8) {
            $t = ( ( $x / $w ) * 0.55 ) + ( ( $y / $h ) * 0.45 );
            $c = imagecolorallocate(
                $img,
                (int) round($r1 + ( $r2 - $r1 ) * $t),
                (int) round($g1 + ( $g2 - $g1 ) * $t),
                (int) round($b1 + ( $b2 - $b1 ) * $t)
            );
            imagefilledrectangle($img, $x, $y, $x + 8, $y, $c);
        }
    }

    $unit = (int) round(min($w, $h) / 100);
    $line = max(2, (int) round($unit * 0.6));

    // Innenrahmen: verschwindet auf der beschnittenen Seite und macht damit
    // jeden Beschnitt sichtbar.
    $frameColor = imagecolorallocatealpha($img, 255, 255, 255, 100);
    $inset = $unit * 6;
    for ($i = 0; $i < $line; $i++) {
        imagerectangle($img, $inset + $i, $inset + $i, $w - 1 - $inset - $i, $h - 1 - $inset - $i, $frameColor);
    }

    // Bildsymbol, mittig: Rahmen, Berge, Sonne.
    $iconW = (int) round(min($w, $h) * 0.30);
    $iconH = (int) round($iconW * 0.78);
    $ix = (int) round(( $w - $iconW ) / 2);
    $iy = (int) round(( $h - $iconH ) / 2);

    $glyph = imagecolorallocatealpha($img, 255, 255, 255, 78);
    $glyphSoft = imagecolorallocatealpha($img, 255, 255, 255, 104);

    $stroke = max(3, (int) round($iconW * 0.035));
    for ($i = 0; $i < $stroke; $i++) {
        imagerectangle($img, $ix + $i, $iy + $i, $ix + $iconW - $i, $iy + $iconH - $i, $glyph);
    }

    // Sonne
    $sunR = (int) round($iconW * 0.10);
    imagefilledellipse(
        $img,
        (int) round($ix + $iconW * 0.30),
        (int) round($iy + $iconH * 0.30),
        $sunR * 2,
        $sunR * 2,
        $glyph
    );

    // Zwei Berge, unten buendig im Rahmen
    $base = $iy + $iconH - $stroke;
    imagefilledpolygon($img, [
        (int) round($ix + $iconW * 0.12), $base,
        (int) round($ix + $iconW * 0.45), (int) round($iy + $iconH * 0.45),
        (int) round($ix + $iconW * 0.78), $base,
    ], $glyphSoft);
    imagefilledpolygon($img, [
        (int) round($ix + $iconW * 0.50), $base,
        (int) round($ix + $iconW * 0.72), (int) round($iy + $iconH * 0.58),
        (int) round($ix + $iconW * 0.94), $base,
    ], $glyph);

    // Ecke oben links: drei Bloecke als Orientierung, damit man erkennt, wenn
    // ein Zuschnitt die Ecke abschneidet.
    $mark = imagecolorallocatealpha($img, 255, 255, 255, 120);
    $mx = $inset + $unit * 3;
    $my = $inset + $unit * 3;
    for ($i = 0; $i < 3; $i++) {
        imagefilledrectangle(
            $img,
            $mx + $i * $unit * 3,
            $my,
            $mx + $i * $unit * 3 + $unit * 2,
            $my + $unit * 2,
            $mark
        );
    }

    imagejpeg($img, $path, 82);

    printf("%-42s %dx%d  %s\n", basename($path), $w, $h, size_format_local(filesize($path)));
}

function size_format_local(int $bytes): string
{
    return $bytes > 1024 ? round($bytes / 1024) . ' KB' : $bytes . ' B';
}

foreach ($palette as $i => [$from, $to]) {
    render("{$targetDir}/placeholder-{$i}.jpg", 2400, 1600, $from, $to);
}

render("{$targetDir}/placeholder-portrait.jpg", 1200, 1500, '1c1917', '44403c');
