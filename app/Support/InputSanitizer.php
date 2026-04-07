<?php

namespace App\Support;

final class InputSanitizer
{
    public static function sanitizePlainText(?string $value, bool $multiline = false): string
    {
        if ($value === null) {
            return '';
        }

        $clean = self::normalizeEncoding((string) $value);
        $clean = str_replace("\0", '', $clean);
        $clean = strip_tags($clean);

        if ($multiline) {
            $clean = str_replace(["\r\n", "\r"], "\n", $clean);
            $clean = preg_replace('/[^\P{C}\n\t]/u', '', $clean) ?? $clean;
            $clean = preg_replace('/[ \t]+/u', ' ', $clean) ?? $clean;
            $clean = preg_replace("/\n{3,}/u", "\n\n", $clean) ?? $clean;
        } else {
            $clean = preg_replace('/[^\P{C}]/u', '', $clean) ?? $clean;
            $clean = preg_replace('/\s+/u', ' ', $clean) ?? $clean;
        }

        return trim($clean);
    }

    public static function sanitizeNullablePlainText(?string $value, bool $multiline = false): ?string
    {
        $clean = self::sanitizePlainText($value, $multiline);

        return $clean === '' ? null : $clean;
    }

    public static function sanitizeTextFields(array $data, array $fields, bool $multiline = false, bool $nullable = false): array
    {
        foreach ($fields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $rawValue = is_scalar($data[$field]) ? (string) $data[$field] : null;

            if ($nullable) {
                $data[$field] = self::sanitizeNullablePlainText($rawValue, $multiline);
                continue;
            }

            $data[$field] = self::sanitizePlainText($rawValue, $multiline);
        }

        return $data;
    }

    public static function decodeJsonStringArray(?string $json, int $maxItems = 100, bool $multiline = false): array
    {
        if ($json === null) {
            return [];
        }

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            return [];
        }

        if (!is_array($decoded)) {
            return [];
        }

        $items = [];
        $seen = [];

        foreach ($decoded as $item) {
            if (!is_scalar($item)) {
                continue;
            }

            $clean = self::sanitizePlainText((string) $item, $multiline);
            if ($clean === '' || isset($seen[$clean])) {
                continue;
            }

            $seen[$clean] = true;
            $items[] = $clean;

            if (count($items) >= max(1, $maxItems)) {
                break;
            }
        }

        return $items;
    }

    public static function sanitizeHttpUrl(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $clean = trim(str_replace(["\r", "\n", "\0"], '', self::normalizeEncoding((string) $value)));
        if ($clean === '' || str_starts_with($clean, '//')) {
            return null;
        }

        if (!preg_match('~^[a-z][a-z0-9+.-]*://~i', $clean)) {
            $clean = 'https://' . ltrim($clean, '/');
        }

        if (!filter_var($clean, FILTER_VALIDATE_URL)) {
            return null;
        }

        $parts = parse_url($clean);
        if ($parts === false) {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = trim((string) ($parts['host'] ?? ''));

        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            return null;
        }

        return $clean;
    }

    public static function sanitizeInternalRedirect(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $clean = trim(str_replace(["\r", "\n", "\0"], '', (string) $value));
        if ($clean === '' || !str_starts_with($clean, '/')) {
            return null;
        }

        $parts = parse_url($clean);
        if ($parts === false) {
            return null;
        }

        foreach (['scheme', 'host', 'user', 'pass', 'port'] as $forbiddenPart) {
            if (isset($parts[$forbiddenPart])) {
                return null;
            }
        }

        return $clean;
    }

    private static function normalizeEncoding(string $value): string
    {
        $clean = $value;

        if (function_exists('mb_convert_encoding')) {
            $converted = @mb_convert_encoding($clean, 'UTF-8', 'UTF-8,ISO-8859-1,WINDOWS-1252');
            if (is_string($converted) && $converted !== '') {
                $clean = $converted;
            }
        }

        if (function_exists('iconv')) {
            $iconv = @iconv('UTF-8', 'UTF-8//IGNORE', $clean);
            if ($iconv !== false) {
                $clean = $iconv;
            }
        }

        return $clean;
    }
}
