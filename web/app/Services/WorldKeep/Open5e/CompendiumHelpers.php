<?php

namespace App\Services\WorldKeep\Open5e;

final class CompendiumHelpers
{
    /** @param  list<string>  $allowed */
    public static function addDocumentFilter(array &$query, array $allowed): void
    {
        if ($allowed === []) {
            return;
        }

        if (count($allowed) === 1) {
            $query['document__key'] = $allowed[0];
        } else {
            $query['document__key__in'] = implode(',', $allowed);
        }
    }

    public static function normalizeLimit(int $limit): int
    {
        if ($limit <= 0) {
            return 10;
        }

        return min($limit, 25);
    }

    public static function documentKeyFromRaw(mixed $doc): string
    {
        if (is_string($doc)) {
            return $doc;
        }

        if (is_array($doc)) {
            return (string) ($doc['key'] ?? '');
        }

        return '';
    }

    public static function nestedName(mixed $value): string
    {
        if (! is_array($value)) {
            return '';
        }

        return (string) ($value['name'] ?? '');
    }

    public static function stringField(array $raw, string $key): string
    {
        return (string) ($raw[$key] ?? '');
    }

    public static function firstNonEmpty(string ...$values): string
    {
        foreach ($values as $value) {
            if (trim($value) !== '') {
                return $value;
            }
        }

        return '';
    }

    /** @return list<string> */
    public static function spellComponents(array $raw): array
    {
        $out = [];
        if (! empty($raw['verbal'])) {
            $out[] = 'V';
        }
        if (! empty($raw['somatic'])) {
            $out[] = 'S';
        }
        if (! empty($raw['material'])) {
            $out[] = 'M';
        }

        return $out;
    }

    public static function creatureSnippet(array $raw): string
    {
        $parts = [
            'CR '.($raw['challenge_rating'] ?? ''),
            self::nestedName($raw['type'] ?? null),
            self::nestedName($raw['size'] ?? null),
        ];

        if (isset($raw['armor_class'])) {
            $parts[] = 'AC '.(int) $raw['armor_class'];
        }
        if (isset($raw['hit_points'])) {
            $parts[] = 'HP '.(int) $raw['hit_points'];
        }

        return implode(' · ', array_filter($parts, static fn ($p) => trim((string) $p) !== ''));
    }

    public static function slugConditionKey(string $name): string
    {
        return strtolower(trim($name));
    }

    public static function titleCase(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return $text;
        }

        return strtoupper($text[0]).strtolower(substr($text, 1));
    }

    public static function truncate(string $text, int $max): string
    {
        $text = trim($text);
        if (strlen($text) <= $max) {
            return $text;
        }

        return substr($text, 0, $max).'…';
    }
}
