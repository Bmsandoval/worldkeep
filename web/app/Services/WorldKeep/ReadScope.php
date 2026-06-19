<?php

namespace App\Services\WorldKeep;

enum ReadScope: string
{
    case Party = 'party';
    case Dm = 'dm';

    public static function parse(string $raw): self
    {
        if (strcasecmp(trim($raw), 'dm') === 0) {
            return self::Dm;
        }

        return self::Party;
    }

    public function includesDmOnly(): bool
    {
        return $this === self::Dm;
    }
}
