<?php

declare(strict_types=1);

namespace App\Core;

final class SeoMetadata
{
    public function __construct(private array $config, private array $content)
    {
    }

    public function meta(): array
    {
        $seo = $this->arrayValue($this->content['seo'] ?? []);
        $hero = $this->arrayValue($this->content['hero'] ?? []);
        $heroImage = $this->arrayValue($hero['image'] ?? []);
        $seoImage = $this->arrayValue($seo['image'] ?? []);
        $image = $seoImage !== [] ? $seoImage : $heroImage;

        $description = $this->firstNonEmpty(
            $seo['description'] ?? null,
            'Atendimento com hora marcada, equipe organizada e retorno claro.'
        );
        $imageSrc = $this->firstNonEmpty($image['src'] ?? null, 'assets/img/social/' . $this->slug() . '-og.jpg');

        return [
            'title' => $this->firstNonEmpty(
                $this->config['page_title'] ?? null,
                $seo['title'] ?? null,
                $this->brandName()
            ),
            'description' => $description,
            'canonical_url' => $this->canonicalHomeUrl(),
            'type' => $this->firstNonEmpty($seo['type'] ?? null, 'website'),
            'locale' => $this->firstNonEmpty($seo['locale'] ?? null, 'pt_BR'),
            'site_name' => $this->firstNonEmpty($seo['site_name'] ?? null, $this->brandName()),
            'twitter_card' => $this->firstNonEmpty($seo['twitter_card'] ?? null, 'summary_large_image'),
            'image' => [
                'url' => $this->absolutePublicUrl($imageSrc),
                'width' => (int) ($image['width'] ?? 0),
                'height' => (int) ($image['height'] ?? 0),
                'alt' => $this->firstNonEmpty($image['alt'] ?? null, $description),
            ],
        ];
    }

    public function structuredDataJson(array $meta): string
    {
        $seo = $this->arrayValue($this->content['seo'] ?? []);
        $schema = $this->arrayValue($seo['schema'] ?? []);
        if (($schema['enabled'] ?? true) === false) {
            return '';
        }

        $business = [
            '@type' => $this->schemaType((string) ($schema['type'] ?? 'MedicalClinic')),
            '@id' => $meta['canonical_url'] . '#organization',
            'name' => $this->brandName(),
            'url' => $meta['canonical_url'],
            'description' => $meta['description'],
            'image' => $meta['image']['url'] ?? '',
        ];

        $logo = $this->firstNonEmpty($schema['logo'] ?? null, 'assets/img/' . $this->slug() . '-mark.svg');
        if ($logo !== '') {
            $business['logo'] = $this->absolutePublicUrl($logo);
        }

        $phone = $this->telephoneFromUrl((string) ($this->config['whatsapp_url'] ?? ''));
        if ($phone !== '') {
            $business['telephone'] = $phone;
        }

        $areaServed = $this->firstNonEmpty($schema['area_served'] ?? null);
        if ($areaServed !== '') {
            $business['areaServed'] = $areaServed;
        }

        $priceRange = $this->firstNonEmpty($schema['price_range'] ?? null);
        if ($priceRange !== '') {
            $business['priceRange'] = $priceRange;
        }

        $sameAs = $this->sameAsUrls($schema['same_as'] ?? []);
        if ($sameAs !== []) {
            $business['sameAs'] = $sameAs;
        }

        if (($schema['include_services'] ?? true) !== false) {
            $offerCatalog = $this->serviceOfferCatalog();
            if ($offerCatalog !== []) {
                $business['hasOfferCatalog'] = $offerCatalog;
            }
        }

        $graph = [$business];
        if (($schema['include_faq'] ?? true) !== false) {
            $faqPage = $this->faqStructuredData($meta['canonical_url']);
            if ($faqPage !== []) {
                $graph[] = $faqPage;
            }
        }

        $json = json_encode([
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        return is_string($json) ? $json : '';
    }

    private function serviceOfferCatalog(): array
    {
        $services = $this->arrayValue($this->arrayValue($this->content['services'] ?? [])['items'] ?? []);
        $items = [];
        foreach ($services as $service) {
            if (!is_array($service)) {
                continue;
            }

            $name = $this->firstNonEmpty($service['title'] ?? null);
            if ($name === '') {
                continue;
            }

            $itemOffered = [
                '@type' => 'Service',
                'name' => $name,
            ];
            $description = $this->firstNonEmpty($service['text'] ?? null);
            if ($description !== '') {
                $itemOffered['description'] = $description;
            }

            $items[] = [
                '@type' => 'Offer',
                'itemOffered' => $itemOffered,
            ];
        }

        if ($items === []) {
            return [];
        }

        return [
            '@type' => 'OfferCatalog',
            'name' => $this->firstNonEmpty($this->arrayValue($this->content['services'] ?? [])['title'] ?? null, 'Serviços'),
            'itemListElement' => $items,
        ];
    }

    private function slug(): string
    {
        $slug = strtolower(trim((string) ($this->config['app_slug'] ?? '')));
        return preg_match('/^[a-z0-9][a-z0-9-]*$/', $slug) === 1 ? $slug : 'medico';
    }

    private function faqStructuredData(string $canonicalUrl): array
    {
        $items = $this->arrayValue($this->arrayValue($this->content['faq'] ?? [])['items'] ?? []);
        $questions = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $question = $this->firstNonEmpty($item['question'] ?? null);
            $answer = $this->firstNonEmpty($item['answer'] ?? null);
            if ($question === '' || $answer === '') {
                continue;
            }

            $questions[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $answer,
                ],
            ];
        }

        if ($questions === []) {
            return [];
        }

        return [
            '@type' => 'FAQPage',
            '@id' => $canonicalUrl . '#faq',
            'mainEntity' => $questions,
        ];
    }

    private function sameAsUrls(mixed $configuredUrls): array
    {
        $urls = is_array($configuredUrls) ? $configuredUrls : [];
        $urls[] = $this->config['facebook_url'] ?? '';
        $urls[] = $this->config['instagram_url'] ?? '';
        $urls[] = $this->config['x_url'] ?? '';

        $clean = [];
        foreach ($urls as $url) {
            $url = trim((string) $url);
            if ($url === '' || $url === '#' || !filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            if ($this->isPlaceholderUrl($url)) {
                continue;
            }

            $clean[] = $url;
        }

        return array_values(array_unique($clean));
    }

    private function isPlaceholderUrl(string $url): bool
    {
        if (str_contains($url, 'seu-perfil') || str_contains($url, 'example.com') || str_contains($url, 'exemplo.com')) {
            return true;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        $genericHosts = ['facebook.com', 'www.facebook.com', 'instagram.com', 'www.instagram.com', 'x.com', 'www.x.com'];

        return in_array($host, $genericHosts, true) && $path === '';
    }

    private function telephoneFromUrl(string $url): string
    {
        if (preg_match('~(?:wa\.me/|phone=)(\d{10,15})~', $url, $matches)) {
            return '+' . $matches[1];
        }

        return '';
    }

    private function schemaType(string $type): string
    {
        $type = trim($type);
        if (preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,80}$/', $type) === 1) {
            return $type;
        }

        return 'LocalBusiness';
    }

    private function canonicalHomeUrl(): string
    {
        $configured = trim((string) ($this->config['canonical_url'] ?? ''));
        if ($configured !== '' && filter_var($configured, FILTER_VALIDATE_URL)) {
            return rtrim($configured, '/') . '/';
        }

        return rtrim($this->resolveOrigin(), '/') . '/';
    }

    private function absolutePublicUrl(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        if (preg_match('~^https?://~i', $path) === 1) {
            return $path;
        }

        return rtrim($this->canonicalHomeUrl(), '/') . '/' . ltrim($path, '/');
    }

    private function resolveOrigin(): string
    {
        $forwardedProto = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
        $scheme = $forwardedProto === 'https' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = rtrim((string) ($this->config['base_url'] ?? ''), '/');
        return $scheme . '://' . $host . ($base !== '' ? $base : '');
    }

    private function brandName(): string
    {
        $name = trim((string) ($this->config['app_name'] ?? ''));
        return $name !== '' ? $name : 'Landing de saúde';
    }

    private function arrayValue(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    private function firstNonEmpty(mixed ...$values): string
    {
        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}
