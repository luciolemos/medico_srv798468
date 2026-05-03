#!/usr/bin/env php
<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$contentName = 'landing';
$slug = '';
$strict = false;

$usage = static function (): void {
    echo <<<'USAGE'
Usage:
  php scripts/validate-landing-content.php [--project-root PATH] [--content NAME] [--slug SLUG] [--strict]

Options:
  --project-root PATH  Raiz do projeto. Default: diretório pai de scripts/
  --content NAME       Arquivo em config/content sem .php. Default: landing
  --slug SLUG          Slug usado para comparar presets. Default: APP_SLUG ou nome da pasta
  --strict             Warnings também retornam exit code 1
  --help               Mostra esta ajuda

USAGE;
};

for ($i = 1; $i < $argc; $i++) {
    $arg = $argv[$i];
    switch ($arg) {
        case '--project-root':
            $projectRoot = (string) ($argv[++$i] ?? '');
            break;
        case '--content':
            $contentName = (string) ($argv[++$i] ?? '');
            break;
        case '--slug':
            $slug = (string) ($argv[++$i] ?? '');
            break;
        case '--strict':
            $strict = true;
            break;
        case '--help':
        case '-h':
            $usage();
            exit(0);
        default:
            fwrite(STDERR, "[error] argumento desconhecido: {$arg}\n");
            $usage();
            exit(2);
    }
}

$projectRoot = rtrim($projectRoot, DIRECTORY_SEPARATOR);
if ($projectRoot === '' || !is_dir($projectRoot)) {
    fwrite(STDERR, "[error] project root inválido: {$projectRoot}\n");
    exit(2);
}

$normalizeName = static function (string $name): string {
    $name = preg_replace('/\.php$/i', '', trim($name)) ?? '';
    $name = basename(str_replace('\\', '/', $name));

    return preg_replace('/[^a-z0-9_-]/i', '', $name) ?? '';
};

$contentName = $normalizeName($contentName);
if ($contentName === '') {
    fwrite(STDERR, "[error] content inválido\n");
    exit(2);
}

$env = [];
$envPath = $projectRoot . '/.env';
if (is_file($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $env[trim($key)] = trim($value, " \t\n\r\0\x0B\"");
    }
}

if ($slug === '') {
    $slug = (string) ($env['APP_SLUG'] ?? basename($projectRoot));
}
$slug = $normalizeName($slug);

$contentPath = $projectRoot . '/config/content/' . $contentName . '.php';
if (!is_file($contentPath)) {
    fwrite(STDERR, "[error] arquivo de conteúdo não encontrado: {$contentPath}\n");
    exit(2);
}

$content = require $contentPath;
if (!is_array($content)) {
    fwrite(STDERR, "[error] conteúdo deve retornar array: {$contentPath}\n");
    exit(2);
}

$presetsPath = $projectRoot . '/config/presets/niches.php';
$presets = is_file($presetsPath) ? require $presetsPath : [];
$presets = is_array($presets) ? $presets : [];

$allowedPalettes = ['blue', 'red', 'emerald', 'amber', 'violet'];
$allowedTypography = ['clinical', 'family', 'premium', 'warm', 'technical'];
$allowedSchemaTypes = [
    'Dentist',
    'HealthAndBeautyBusiness',
    'LocalBusiness',
    'MedicalBusiness',
    'MedicalClinic',
    'Physician',
    'VeterinaryCare',
];

$checks = 0;
$warnings = 0;
$failures = 0;

$line = static function (string $type, string $message) use (&$checks, &$warnings, &$failures): void {
    if ($type === 'ok') {
        $checks++;
        echo "[ok  ] {$message}\n";
        return;
    }

    if ($type === 'warn') {
        $warnings++;
        echo "[warn] {$message}\n";
        return;
    }

    $failures++;
    echo "[fail] {$message}\n";
};

$get = static function (array $source, string $path): mixed {
    $value = $source;
    foreach (explode('.', $path) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return null;
        }

        $value = $value[$segment];
    }

    return $value;
};

$string = static function (mixed $value): string {
    return is_scalar($value) ? trim((string) $value) : '';
};

$requireString = static function (string $path) use ($content, $get, $string, $line): string {
    $value = $string($get($content, $path));
    if ($value === '') {
        $line('fail', "{$path} ausente ou vazio");
        return '';
    }

    $line('ok', "{$path} preenchido");
    return $value;
};

$checkAsset = static function (string $path, string $label, ?int $expectedWidth = null, ?int $expectedHeight = null) use ($projectRoot, $line): void {
    $path = trim($path);
    if ($path === '') {
        $line('fail', "{$label} sem caminho de asset");
        return;
    }

    if (preg_match('~^https?://~i', $path) === 1 || str_contains($path, '..')) {
        $line('fail', "{$label} deve apontar para asset local seguro: {$path}");
        return;
    }

    $publicPath = $projectRoot . '/public/' . ltrim($path, '/');
    if (!is_file($publicPath)) {
        $line('fail', "{$label} não encontrado: public/" . ltrim($path, '/'));
        return;
    }

    $size = filesize($publicPath);
    if ($size === false || $size <= 0) {
        $line('fail', "{$label} vazio: public/" . ltrim($path, '/'));
        return;
    }

    $extension = strtolower(pathinfo($publicPath, PATHINFO_EXTENSION));
    if ($extension === 'svg') {
        $line('ok', "{$label} existe: {$path}");
        return;
    }

    $dimensions = @getimagesize($publicPath);
    if ($dimensions === false) {
        $line('warn', "{$label} existe, mas dimensões não puderam ser lidas: {$path}");
        return;
    }

    [$actualWidth, $actualHeight] = $dimensions;
    if ($expectedWidth !== null && $expectedWidth > 0 && $actualWidth !== $expectedWidth) {
        $line('fail', "{$label} largura divergente: esperado {$expectedWidth}px, arquivo tem {$actualWidth}px ({$path})");
        return;
    }

    if ($expectedHeight !== null && $expectedHeight > 0 && $actualHeight !== $expectedHeight) {
        $line('fail', "{$label} altura divergente: esperado {$expectedHeight}px, arquivo tem {$actualHeight}px ({$path})");
        return;
    }

    $line('ok', "{$label} existe: {$path} ({$actualWidth}x{$actualHeight})");
};

$line('ok', 'conteúdo carregado: config/content/' . $contentName . '.php');

$seoTitle = $requireString('seo.title');
$requireString('seo.description');
$requireString('seo.site_name');
$schemaType = $requireString('seo.schema.type');
if ($schemaType !== '' && !in_array($schemaType, $allowedSchemaTypes, true)) {
    $line('fail', "seo.schema.type inválido: {$schemaType}");
}

$typographyProfile = $string($get($content, 'typography.profile')) ?: 'clinical';
if (!in_array($typographyProfile, $allowedTypography, true)) {
    $line('fail', "typography.profile inválido: {$typographyProfile}");
} else {
    $line('ok', "typography.profile={$typographyProfile}");
}

$palette = (string) ($env['APP_PALETTE'] ?? '');
if ($palette !== '') {
    if (!in_array($palette, $allowedPalettes, true)) {
        $line('fail', "APP_PALETTE inválida no .env: {$palette}");
    } else {
        $line('ok', "APP_PALETTE={$palette}");
    }
}

if ($slug !== '' && isset($presets[$slug]) && is_array($presets[$slug])) {
    $preset = $presets[$slug];
    $line('ok', "preset encontrado para slug={$slug}");

    if (($preset['typography'] ?? '') !== '' && $preset['typography'] !== $typographyProfile) {
        $line('warn', "preset {$slug} recomenda typography={$preset['typography']}, conteúdo usa {$typographyProfile}");
    }

    if (($preset['schema_type'] ?? '') !== '' && $preset['schema_type'] !== $schemaType) {
        $line('warn', "preset {$slug} recomenda schema_type={$preset['schema_type']}, conteúdo usa {$schemaType}");
    }

    if ($palette !== '' && ($preset['palette'] ?? '') !== '' && $preset['palette'] !== $palette) {
        $line('warn', "preset {$slug} recomenda palette={$preset['palette']}, .env usa {$palette}");
    }
} elseif ($slug !== '') {
    $line('warn', "nenhum preset cadastrado para slug={$slug}");
}

$seoImage = $get($content, 'seo.image');
if (is_array($seoImage)) {
    $checkAsset(
        $string($seoImage['src'] ?? ''),
        'seo.image.src',
        (int) ($seoImage['width'] ?? 0) ?: null,
        (int) ($seoImage['height'] ?? 0) ?: null
    );
    $requireString('seo.image.alt');

    if (((int) ($seoImage['width'] ?? 0) !== 1200) || ((int) ($seoImage['height'] ?? 0) !== 630)) {
        $line('warn', 'imagem social recomenda 1200x630 para Open Graph/Twitter');
    }
} else {
    $line('fail', 'seo.image ausente');
}

$schemaLogo = $string($get($content, 'seo.schema.logo'));
if ($schemaLogo !== '') {
    $checkAsset($schemaLogo, 'seo.schema.logo');
}

$requireString('hero.badge');
$titleParts = $get($content, 'hero.title_parts');
if (!is_array($titleParts) || count(array_filter($titleParts, static fn (mixed $item): bool => $string($item) !== '')) < 3) {
    $line('fail', 'hero.title_parts precisa de 3 partes preenchidas');
} else {
    $line('ok', 'hero.title_parts preenchido');
}
$requireString('hero.lead');

$heroImage = $get($content, 'hero.image');
if (is_array($heroImage)) {
    $checkAsset(
        $string($heroImage['src'] ?? ''),
        'hero.image.src',
        (int) ($heroImage['width'] ?? 0) ?: null,
        (int) ($heroImage['height'] ?? 0) ?: null
    );
    $requireString('hero.image.alt');

    foreach (($heroImage['sources'] ?? []) as $index => $source) {
        if (!is_array($source)) {
            $line('fail', "hero.image.sources[{$index}] inválido");
            continue;
        }

        $checkAsset($string($source['path'] ?? ''), "hero.image.sources[{$index}]", (int) ($source['width'] ?? 0) ?: null);
    }

    $mobile = $heroImage['mobile'] ?? null;
    if (is_array($mobile)) {
        $checkAsset(
            $string($mobile['src'] ?? ''),
            'hero.image.mobile.src',
            (int) ($mobile['width'] ?? 0) ?: null,
            (int) ($mobile['height'] ?? 0) ?: null
        );

        foreach (($mobile['sources'] ?? []) as $index => $source) {
            if (!is_array($source)) {
                $line('fail', "hero.image.mobile.sources[{$index}] inválido");
                continue;
            }

            $checkAsset($string($source['path'] ?? ''), "hero.image.mobile.sources[{$index}]", (int) ($source['width'] ?? 0) ?: null);
        }
    } else {
        $line('warn', 'hero.image.mobile ausente; mobile pode usar corte desktop');
    }
} else {
    $line('fail', 'hero.image ausente');
}

foreach (['proof.avatar.src' => 'hero.proof.avatar.src'] as $path => $label) {
    $asset = $string($get($content, 'hero.' . $path));
    if ($asset !== '') {
        $checkAsset($asset, $label);
    }
}

$services = $get($content, 'services.items');
if (!is_array($services) || count($services) === 0) {
    $line('fail', 'services.items precisa ter pelo menos 1 item');
} else {
    $line('ok', 'services.items preenchido');
    foreach ($services as $index => $service) {
        if (!is_array($service) || $string($service['title'] ?? '') === '' || $string($service['text'] ?? '') === '') {
            $line('fail', "services.items[{$index}] precisa de title e text");
        }
    }
}

$includeFaq = (bool) ($get($content, 'seo.schema.include_faq') ?? true);
$faqItems = $get($content, 'faq.items');
if ($includeFaq && (!is_array($faqItems) || count($faqItems) === 0)) {
    $line('fail', 'faq.items precisa ter itens quando seo.schema.include_faq=true');
} elseif (is_array($faqItems)) {
    $line('ok', 'faq.items preenchido');
}

foreach ([
    'form.title',
    'form.text',
    'form.fields.name_label',
    'form.fields.phone_label',
    'form.fields.email_label',
    'form.fields.message_label',
    'form.buttons.submit',
    'form.privacy_note',
    'footer.label',
    'footer.credit',
] as $requiredPath) {
    $requireString($requiredPath);
}

echo "\nSummary\n";
echo "  checks: {$checks}\n";
echo "  warnings: {$warnings}\n";
echo "  failures: {$failures}\n";

if ($failures > 0 || ($strict && $warnings > 0)) {
    exit(1);
}

exit(0);
