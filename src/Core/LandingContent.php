<?php

declare(strict_types=1);

namespace App\Core;

final class LandingContent
{
    public static function load(string $projectRoot, string $contentFile = '', string $slug = ''): array
    {
        $contentDir = rtrim($projectRoot, DIRECTORY_SEPARATOR) . '/config/content';
        $default = self::readContentFile($contentDir . '/landing.php');

        foreach ([$contentFile, $slug] as $candidate) {
            $name = self::normalizeContentName($candidate);
            if ($name === '' || $name === 'landing') {
                continue;
            }

            $path = $contentDir . '/' . $name . '.php';
            if (!is_file($path)) {
                continue;
            }

            return self::mergeContent($default, self::readContentFile($path));
        }

        return $default;
    }

    private static function normalizeContentName(string $candidate): string
    {
        $candidate = trim($candidate);
        if ($candidate === '') {
            return '';
        }

        $candidate = preg_replace('/\.php$/i', '', $candidate) ?? '';
        $candidate = basename(str_replace('\\', '/', $candidate));

        return preg_replace('/[^a-z0-9_-]/i', '', $candidate) ?? '';
    }

    private static function readContentFile(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        $content = require $path;

        return is_array($content) ? $content : [];
    }

    private static function mergeContent(array $default, array $override): array
    {
        foreach ($override as $key => $value) {
            if (!is_string($key)) {
                return $override;
            }

            $defaultValue = $default[$key] ?? null;
            if (is_array($defaultValue) && is_array($value) && !array_is_list($defaultValue) && !array_is_list($value)) {
                $default[$key] = self::mergeContent($defaultValue, $value);
                continue;
            }

            $default[$key] = $value;
        }

        return $default;
    }
}
