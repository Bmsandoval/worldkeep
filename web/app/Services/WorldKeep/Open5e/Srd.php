<?php

namespace App\Services\WorldKeep\Open5e;

final class Srd
{
    public const DEFAULT_BASE_URL = 'https://api.open5e.com/v2';

    /** @return list<string> */
    public static function documents(): array
    {
        $version = strtolower(trim((string) config('worldkeep.srd_version', env('WORLDKEEP_SRD_VERSION', 'srd-2014'))));

        return match ($version) {
            'srd-2024', '2024' => ['srd-2024'],
            'both', 'all' => ['srd-2014', 'srd-2024'],
            default => ['srd-2014'],
        };
    }

    /** @param  list<string>  $allowed */
    public static function allowsDocument(string $docKey, array $allowed): bool
    {
        return in_array($docKey, $allowed, true);
    }
}
