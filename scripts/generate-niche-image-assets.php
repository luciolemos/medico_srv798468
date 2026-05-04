#!/usr/bin/env php
<?php

declare(strict_types=1);

if (!extension_loaded('gd')) {
    fwrite(STDERR, "[error] extensão GD não está disponível.\n");
    exit(2);
}

$projectRoot = dirname(__DIR__);
$slugs = array_slice($argv, 1);
if ($slugs === [] || in_array('--all', $slugs, true)) {
    $slugs = ['pediatria', 'odontologia', 'veterinaria'];
}

$themes = [
    'pediatria' => [
        'bg1' => '#edfdf7',
        'bg2' => '#d8f3eb',
        'floor' => '#cdece5',
        'accent' => '#10b981',
        'accent2' => '#f59e0b',
        'deep' => '#0f766e',
        'soft' => '#ffffff',
        'skin' => '#d59b75',
        'hair' => '#4b2f24',
        'shirt' => '#34d399',
        'coat' => '#f8fafc',
    ],
    'odontologia' => [
        'bg1' => '#f7fbff',
        'bg2' => '#dcecff',
        'floor' => '#c8dbf3',
        'accent' => '#2563eb',
        'accent2' => '#38bdf8',
        'deep' => '#1e3a8a',
        'soft' => '#ffffff',
        'skin' => '#c98b64',
        'hair' => '#223047',
        'shirt' => '#dbeafe',
        'coat' => '#f8fafc',
    ],
    'veterinaria' => [
        'bg1' => '#f4fbf7',
        'bg2' => '#d8efe4',
        'floor' => '#cce5d8',
        'accent' => '#059669',
        'accent2' => '#d97706',
        'deep' => '#065f46',
        'soft' => '#ffffff',
        'skin' => '#c8875f',
        'hair' => '#3b2a1f',
        'shirt' => '#bbf7d0',
        'coat' => '#f8fafc',
    ],
];

$sizes = [
    'hero' => [
        ['suffix' => '640', 'width' => 640, 'height' => 360, 'format' => 'webp'],
        ['suffix' => '960', 'width' => 960, 'height' => 540, 'format' => 'webp'],
        ['suffix' => '1896', 'width' => 1896, 'height' => 1067, 'format' => 'webp'],
        ['suffix' => 'mobile-640', 'width' => 640, 'height' => 800, 'format' => 'webp'],
    ],
    'social' => [
        ['suffix' => 'og', 'width' => 1200, 'height' => 630, 'format' => 'jpg'],
    ],
];

foreach ($slugs as $slug) {
    if (!isset($themes[$slug])) {
        fwrite(STDERR, "[error] slug sem tema de imagem: {$slug}\n");
        exit(2);
    }

    foreach ($sizes['hero'] as $size) {
        $image = renderScene($slug, $themes[$slug], $size['width'], $size['height']);
        $path = "{$projectRoot}/public/assets/img/hero/{$slug}-{$size['suffix']}.webp";
        saveWebp($image, $path);
    }

    foreach ($sizes['social'] as $size) {
        $image = renderScene($slug, $themes[$slug], $size['width'], $size['height']);
        $path = "{$projectRoot}/public/assets/img/social/{$slug}-{$size['suffix']}.jpg";
        saveJpeg($image, $path);
    }

    echo "[ok  ] assets gerados: {$slug}\n";
}

function renderScene(string $slug, array $theme, int $width, int $height): GdImage
{
    $scale = 2;
    $canvas = imagecreatetruecolor($width * $scale, $height * $scale);
    imagealphablending($canvas, true);
    imagesavealpha($canvas, true);
    imageantialias($canvas, true);

    $draw = new Draw($canvas, $scale);
    drawBackground($draw, $theme, $width, $height);

    match ($slug) {
        'pediatria' => drawPediatria($draw, $theme, $width, $height),
        'odontologia' => drawOdontologia($draw, $theme, $width, $height),
        'veterinaria' => drawVeterinaria($draw, $theme, $width, $height),
        default => null,
    };

    $final = imagecreatetruecolor($width, $height);
    imagealphablending($final, true);
    imagesavealpha($final, true);
    imagecopyresampled($final, $canvas, 0, 0, 0, 0, $width, $height, $width * $scale, $height * $scale);
    imagedestroy($canvas);

    return $final;
}

function drawBackground(Draw $draw, array $theme, int $width, int $height): void
{
    $draw->gradient($width, $height, $theme['bg1'], $theme['bg2']);

    $floorY = $height * 0.68;
    $draw->rect(0, $floorY, $width, $height - $floorY, $theme['floor']);
    $draw->line(0, $floorY, $width, $floorY, '#ffffff', 3);

    $margin = $width * 0.07;
    $windowW = $width * 0.25;
    $windowH = $height * 0.28;
    $draw->roundedRect($margin, $height * 0.12, $windowW, $windowH, 18, '#f8fbff');
    $draw->line($margin + $windowW * 0.5, $height * 0.12, $margin + $windowW * 0.5, $height * 0.12 + $windowH, '#d7e5ee', 2);
    $draw->line($margin, $height * 0.12 + $windowH * 0.52, $margin + $windowW, $height * 0.12 + $windowH * 0.52, '#d7e5ee', 2);

    $panelX = $width * 0.66;
    $panelY = $height * 0.15;
    $draw->roundedRect($panelX, $panelY, $width * 0.22, $height * 0.2, 18, rgbaHex($theme['soft'], 18));
    $draw->line($panelX + $width * 0.04, $panelY + $height * 0.07, $panelX + $width * 0.18, $panelY + $height * 0.07, $theme['accent'], 5);
    $draw->line($panelX + $width * 0.04, $panelY + $height * 0.13, $panelX + $width * 0.14, $panelY + $height * 0.13, '#94a3b8', 4);

    $draw->roundedRect($width * 0.08, $height * 0.72, $width * 0.82, $height * 0.08, 18, rgbaHex('#ffffff', 24));
}

function drawPediatria(Draw $draw, array $theme, int $width, int $height): void
{
    $isMobile = $height > $width;
    $baseY = $height * ($isMobile ? 0.76 : 0.72);
    $doctorX = $width * ($isMobile ? 0.38 : 0.43);
    $childX = $width * ($isMobile ? 0.61 : 0.61);
    $parentX = $width * ($isMobile ? 0.78 : 0.74);
    $s = min($width / 900, $height / 560) * ($isMobile ? 1.35 : 1.0);

    drawExamTable($draw, $width * ($isMobile ? 0.16 : 0.2), $baseY - 40 * $s, $width * ($isMobile ? 0.66 : 0.5), 54 * $s, $theme);
    drawPerson($draw, $doctorX, $baseY, 1.22 * $s, $theme['skin'], $theme['hair'], $theme['coat'], $theme['accent'], true);
    drawPerson($draw, $childX, $baseY + 8 * $s, 0.82 * $s, '#c98a62', '#7c3f1d', '#fef3c7', $theme['accent2'], false);
    drawPerson($draw, $parentX, $baseY + 4 * $s, 1.02 * $s, '#b97954', '#2b2118', '#d1fae5', $theme['deep'], false);

    drawStethoscope($draw, $doctorX + 26 * $s, $baseY - 120 * $s, 0.9 * $s, $theme['deep']);
    drawToyBlocks($draw, $width * ($isMobile ? 0.18 : 0.17), $baseY + 15 * $s, $s, $theme);
    drawPlant($draw, $width * 0.11, $height * 0.67, 0.85 * $s, $theme['accent'], $theme['deep']);
}

function drawOdontologia(Draw $draw, array $theme, int $width, int $height): void
{
    $isMobile = $height > $width;
    $baseY = $height * ($isMobile ? 0.74 : 0.72);
    $s = min($width / 900, $height / 560) * ($isMobile ? 1.22 : 1.0);

    $chairX = $width * ($isMobile ? 0.19 : 0.2);
    $chairY = $baseY - 98 * $s;
    drawDentalChair($draw, $chairX, $chairY, $width * ($isMobile ? 0.54 : 0.42), 130 * $s, $theme);

    drawDentalLamp($draw, $width * ($isMobile ? 0.62 : 0.62), $height * 0.2, 1.05 * $s, $theme);
    drawPerson($draw, $width * ($isMobile ? 0.67 : 0.7), $baseY, 1.2 * $s, $theme['skin'], $theme['hair'], $theme['coat'], $theme['accent'], true);
    drawInstrumentTray($draw, $width * ($isMobile ? 0.12 : 0.68), $baseY - 12 * $s, 1.0 * $s, $theme);
    drawSparkles($draw, $width * 0.22, $height * 0.22, 1.0 * $s, $theme['accent2']);
}

function drawVeterinaria(Draw $draw, array $theme, int $width, int $height): void
{
    $isMobile = $height > $width;
    $baseY = $height * ($isMobile ? 0.76 : 0.72);
    $s = min($width / 900, $height / 560) * ($isMobile ? 1.25 : 1.0);

    drawExamTable($draw, $width * ($isMobile ? 0.14 : 0.2), $baseY - 36 * $s, $width * ($isMobile ? 0.7 : 0.48), 54 * $s, $theme);
    drawPerson($draw, $width * ($isMobile ? 0.34 : 0.42), $baseY, 1.18 * $s, $theme['skin'], $theme['hair'], $theme['coat'], $theme['accent'], true);
    drawDog($draw, $width * ($isMobile ? 0.59 : 0.58), $baseY - 36 * $s, 1.15 * $s, '#8b5e34', '#5b3418');
    drawPerson($draw, $width * ($isMobile ? 0.8 : 0.75), $baseY + 4 * $s, 1.02 * $s, '#b87955', '#2b2118', '#ffedd5', $theme['accent2'], false);
    drawPawBadge($draw, $width * ($isMobile ? 0.2 : 0.18), $height * 0.28, 1.05 * $s, $theme);
    drawPlant($draw, $width * 0.11, $height * 0.67, 0.85 * $s, $theme['accent'], $theme['deep']);
}

function drawPerson(Draw $draw, float $x, float $baseY, float $scale, string $skin, string $hair, string $body, string $accent, bool $coat): void
{
    $headR = 24 * $scale;
    $headY = $baseY - 156 * $scale;
    $torsoY = $baseY - 120 * $scale;

    $draw->ellipse($x, $headY, $headR * 2.0, $headR * 2.0, $skin);
    $draw->ellipse($x - 4 * $scale, $headY - 12 * $scale, $headR * 1.8, $headR * 0.9, $hair);
    $draw->roundedRect($x - 30 * $scale, $torsoY, 60 * $scale, 104 * $scale, 18 * $scale, $body);

    if ($coat) {
        $draw->polygon([
            [$x - 30 * $scale, $torsoY + 8 * $scale],
            [$x - 4 * $scale, $torsoY + 40 * $scale],
            [$x - 22 * $scale, $torsoY + 104 * $scale],
        ], '#ffffff');
        $draw->polygon([
            [$x + 30 * $scale, $torsoY + 8 * $scale],
            [$x + 4 * $scale, $torsoY + 40 * $scale],
            [$x + 22 * $scale, $torsoY + 104 * $scale],
        ], '#ffffff');
        $draw->line($x, $torsoY + 24 * $scale, $x, $torsoY + 98 * $scale, '#dbe6ef', 2 * $scale);
    }

    $draw->line($x - 28 * $scale, $torsoY + 30 * $scale, $x - 58 * $scale, $torsoY + 72 * $scale, $body, 13 * $scale);
    $draw->line($x + 28 * $scale, $torsoY + 30 * $scale, $x + 58 * $scale, $torsoY + 72 * $scale, $body, 13 * $scale);
    $draw->ellipse($x - 60 * $scale, $torsoY + 74 * $scale, 15 * $scale, 15 * $scale, $skin);
    $draw->ellipse($x + 60 * $scale, $torsoY + 74 * $scale, 15 * $scale, 15 * $scale, $skin);
    $draw->line($x - 16 * $scale, $torsoY + 102 * $scale, $x - 28 * $scale, $baseY, '#475569', 13 * $scale);
    $draw->line($x + 16 * $scale, $torsoY + 102 * $scale, $x + 28 * $scale, $baseY, '#475569', 13 * $scale);
    $draw->ellipse($x - 28 * $scale, $baseY + 3 * $scale, 34 * $scale, 10 * $scale, $accent);
    $draw->ellipse($x + 28 * $scale, $baseY + 3 * $scale, 34 * $scale, 10 * $scale, $accent);
}

function drawExamTable(Draw $draw, float $x, float $y, float $w, float $h, array $theme): void
{
    $draw->roundedRect($x, $y, $w, $h, $h * 0.45, '#ffffff');
    $draw->roundedRect($x + $w * 0.03, $y + $h * 0.12, $w * 0.94, $h * 0.42, $h * 0.22, rgbaHex($theme['accent'], 12));
    $draw->line($x + $w * 0.2, $y + $h, $x + $w * 0.16, $y + $h * 2.3, '#64748b', 8);
    $draw->line($x + $w * 0.8, $y + $h, $x + $w * 0.84, $y + $h * 2.3, '#64748b', 8);
}

function drawDentalChair(Draw $draw, float $x, float $y, float $w, float $h, array $theme): void
{
    $draw->roundedRect($x + $w * 0.1, $y + $h * 0.45, $w * 0.72, $h * 0.28, $h * 0.14, '#ffffff');
    $draw->roundedRect($x + $w * 0.45, $y + $h * 0.04, $w * 0.28, $h * 0.58, $h * 0.12, '#ffffff');
    $draw->roundedRect($x + $w * 0.16, $y + $h * 0.53, $w * 0.5, $h * 0.12, $h * 0.06, rgbaHex($theme['accent2'], 24));
    $draw->line($x + $w * 0.43, $y + $h * 0.72, $x + $w * 0.42, $y + $h * 1.45, '#64748b', 9);
    $draw->roundedRect($x + $w * 0.24, $y + $h * 1.38, $w * 0.38, $h * 0.1, $h * 0.05, $theme['deep']);
}

function drawDentalLamp(Draw $draw, float $x, float $y, float $scale, array $theme): void
{
    $draw->line($x, $y, $x + 50 * $scale, $y + 70 * $scale, '#64748b', 6 * $scale);
    $draw->line($x + 50 * $scale, $y + 70 * $scale, $x + 18 * $scale, $y + 128 * $scale, '#64748b', 6 * $scale);
    $draw->ellipse($x + 12 * $scale, $y + 136 * $scale, 96 * $scale, 42 * $scale, '#ffffff');
    $draw->ellipse($x + 12 * $scale, $y + 142 * $scale, 70 * $scale, 22 * $scale, rgbaHex($theme['accent2'], 28));
}

function drawInstrumentTray(Draw $draw, float $x, float $baseY, float $scale, array $theme): void
{
    $draw->line($x + 56 * $scale, $baseY - 60 * $scale, $x + 56 * $scale, $baseY + 64 * $scale, '#64748b', 5 * $scale);
    $draw->roundedRect($x, $baseY - 80 * $scale, 112 * $scale, 24 * $scale, 10 * $scale, '#ffffff');
    $draw->line($x + 18 * $scale, $baseY - 74 * $scale, $x + 44 * $scale, $baseY - 74 * $scale, $theme['accent'], 3 * $scale);
    $draw->line($x + 62 * $scale, $baseY - 74 * $scale, $x + 92 * $scale, $baseY - 74 * $scale, '#94a3b8', 3 * $scale);
}

function drawStethoscope(Draw $draw, float $x, float $y, float $scale, string $color): void
{
    $draw->line($x, $y, $x + 18 * $scale, $y + 46 * $scale, $color, 5 * $scale);
    $draw->line($x + 34 * $scale, $y, $x + 18 * $scale, $y + 46 * $scale, $color, 5 * $scale);
    $draw->ellipse($x + 18 * $scale, $y + 57 * $scale, 20 * $scale, 20 * $scale, '#ffffff');
    $draw->ellipse($x + 18 * $scale, $y + 57 * $scale, 12 * $scale, 12 * $scale, $color);
}

function drawToyBlocks(Draw $draw, float $x, float $y, float $scale, array $theme): void
{
    $size = 30 * $scale;
    $draw->roundedRect($x, $y, $size, $size, 6 * $scale, $theme['accent2']);
    $draw->roundedRect($x + $size * 0.9, $y - $size * 0.35, $size, $size, 6 * $scale, $theme['accent']);
    $draw->roundedRect($x + $size * 1.78, $y, $size, $size, 6 * $scale, '#60a5fa');
}

function drawDog(Draw $draw, float $x, float $baseY, float $scale, string $body, string $dark): void
{
    $draw->ellipse($x, $baseY - 34 * $scale, 130 * $scale, 58 * $scale, $body);
    $draw->ellipse($x + 70 * $scale, $baseY - 56 * $scale, 58 * $scale, 50 * $scale, $body);
    $draw->polygon([
        [$x + 52 * $scale, $baseY - 80 * $scale],
        [$x + 72 * $scale, $baseY - 112 * $scale],
        [$x + 84 * $scale, $baseY - 72 * $scale],
    ], $dark);
    $draw->polygon([
        [$x + 84 * $scale, $baseY - 76 * $scale],
        [$x + 108 * $scale, $baseY - 104 * $scale],
        [$x + 105 * $scale, $baseY - 62 * $scale],
    ], $dark);
    $draw->line($x - 56 * $scale, $baseY - 34 * $scale, $x - 92 * $scale, $baseY - 82 * $scale, $dark, 8 * $scale);
    $draw->line($x - 36 * $scale, $baseY - 5 * $scale, $x - 44 * $scale, $baseY + 34 * $scale, $dark, 8 * $scale);
    $draw->line($x + 22 * $scale, $baseY - 5 * $scale, $x + 28 * $scale, $baseY + 34 * $scale, $dark, 8 * $scale);
    $draw->ellipse($x + 88 * $scale, $baseY - 60 * $scale, 7 * $scale, 7 * $scale, '#111827');
    $draw->ellipse($x + 104 * $scale, $baseY - 48 * $scale, 10 * $scale, 7 * $scale, '#111827');
}

function drawPawBadge(Draw $draw, float $x, float $y, float $scale, array $theme): void
{
    $draw->roundedRect($x - 54 * $scale, $y - 42 * $scale, 108 * $scale, 84 * $scale, 22 * $scale, '#ffffff');
    $draw->ellipse($x, $y + 12 * $scale, 42 * $scale, 34 * $scale, $theme['accent']);
    foreach ([[-28, -18], [-10, -30], [10, -30], [28, -18]] as [$dx, $dy]) {
        $draw->ellipse($x + $dx * $scale, $y + $dy * $scale, 22 * $scale, 24 * $scale, $theme['accent']);
    }
}

function drawSparkles(Draw $draw, float $x, float $y, float $scale, string $color): void
{
    for ($i = 0; $i < 3; $i++) {
        $cx = $x + $i * 46 * $scale;
        $cy = $y + ($i % 2 === 0 ? 0 : 34) * $scale;
        $r = (16 + $i * 3) * $scale;
        $draw->polygon([[$cx, $cy - $r], [$cx + $r * 0.25, $cy - $r * 0.25], [$cx + $r, $cy], [$cx + $r * 0.25, $cy + $r * 0.25], [$cx, $cy + $r], [$cx - $r * 0.25, $cy + $r * 0.25], [$cx - $r, $cy], [$cx - $r * 0.25, $cy - $r * 0.25]], $color);
    }
}

function drawPlant(Draw $draw, float $x, float $baseY, float $scale, string $green, string $deep): void
{
    $draw->roundedRect($x - 28 * $scale, $baseY, 56 * $scale, 44 * $scale, 12 * $scale, '#ffffff');
    $draw->line($x, $baseY, $x, $baseY - 92 * $scale, $deep, 5 * $scale);
    $draw->ellipse($x - 20 * $scale, $baseY - 58 * $scale, 42 * $scale, 22 * $scale, $green);
    $draw->ellipse($x + 26 * $scale, $baseY - 78 * $scale, 48 * $scale, 24 * $scale, $green);
    $draw->ellipse($x - 6 * $scale, $baseY - 102 * $scale, 38 * $scale, 22 * $scale, $green);
}

function saveWebp(GdImage $image, string $path): void
{
    if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0775, true);
    }
    imagewebp($image, $path, 84);
    imagedestroy($image);
}

function saveJpeg(GdImage $image, string $path): void
{
    if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0775, true);
    }
    imagejpeg($image, $path, 88);
    imagedestroy($image);
}

function rgbaHex(string $hex, int $alpha): string
{
    return $hex . ':' . max(0, min(127, $alpha));
}

final class Draw
{
    public function __construct(private GdImage $image, private int $scale) {}

    public function gradient(int $width, int $height, string $from, string $to): void
    {
        [$r1, $g1, $b1] = $this->rgb($from);
        [$r2, $g2, $b2] = $this->rgb($to);
        $h = $height * $this->scale;
        $w = $width * $this->scale;

        for ($y = 0; $y < $h; $y++) {
            $t = $y / max(1, $h - 1);
            $color = imagecolorallocate(
                $this->image,
                (int) round($r1 + ($r2 - $r1) * $t),
                (int) round($g1 + ($g2 - $g1) * $t),
                (int) round($b1 + ($b2 - $b1) * $t)
            );
            imageline($this->image, 0, $y, $w, $y, $color);
        }
    }

    public function rect(float $x, float $y, float $w, float $h, string $hex): void
    {
        imagefilledrectangle($this->image, $this->n($x), $this->n($y), $this->n($x + $w), $this->n($y + $h), $this->color($hex));
    }

    public function roundedRect(float $x, float $y, float $w, float $h, float $r, string $hex): void
    {
        $c = $this->color($hex);
        $x1 = $this->n($x);
        $y1 = $this->n($y);
        $x2 = $this->n($x + $w);
        $y2 = $this->n($y + $h);
        $rr = $this->n($r);
        imagefilledrectangle($this->image, $x1 + $rr, $y1, $x2 - $rr, $y2, $c);
        imagefilledrectangle($this->image, $x1, $y1 + $rr, $x2, $y2 - $rr, $c);
        imagefilledellipse($this->image, $x1 + $rr, $y1 + $rr, $rr * 2, $rr * 2, $c);
        imagefilledellipse($this->image, $x2 - $rr, $y1 + $rr, $rr * 2, $rr * 2, $c);
        imagefilledellipse($this->image, $x1 + $rr, $y2 - $rr, $rr * 2, $rr * 2, $c);
        imagefilledellipse($this->image, $x2 - $rr, $y2 - $rr, $rr * 2, $rr * 2, $c);
    }

    public function ellipse(float $cx, float $cy, float $w, float $h, string $hex): void
    {
        imagefilledellipse($this->image, $this->n($cx), $this->n($cy), $this->n($w), $this->n($h), $this->color($hex));
    }

    public function line(float $x1, float $y1, float $x2, float $y2, string $hex, float $thickness = 1): void
    {
        imagesetthickness($this->image, max(1, $this->n($thickness)));
        imageline($this->image, $this->n($x1), $this->n($y1), $this->n($x2), $this->n($y2), $this->color($hex));
        imagesetthickness($this->image, 1);
    }

    /** @param list<array{0: float, 1: float}> $points */
    public function polygon(array $points, string $hex): void
    {
        $flat = [];
        foreach ($points as $point) {
            $flat[] = $this->n($point[0]);
            $flat[] = $this->n($point[1]);
        }

        imagefilledpolygon($this->image, $flat, count($points), $this->color($hex));
    }

    private function color(string $hex): int
    {
        $alpha = 0;
        if (str_contains($hex, ':')) {
            [$hex, $rawAlpha] = explode(':', $hex, 2);
            $alpha = max(0, min(127, (int) $rawAlpha));
        }

        [$r, $g, $b] = $this->rgb($hex);
        return imagecolorallocatealpha($this->image, $r, $g, $b, $alpha);
    }

    /** @return array{0: int, 1: int, 2: int} */
    private function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return [0, 0, 0];
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private function n(float $value): int
    {
        return (int) round($value * $this->scale);
    }
}
