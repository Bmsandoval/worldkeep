<?php

namespace App\Services\WorldKeep\Services;

use App\Services\WorldKeep\Data\Entity;

final class ImportMarkdown
{
    /**
     * @return list<Entity>
     */
    public static function parse(string $content): array
    {
        $out = [];
        $blocks = explode("\n## ", $content);

        foreach ($blocks as $block) {
            $block = trim($block);
            if ($block === '') {
                continue;
            }

            $lines = explode("\n", $block, 2);
            $title = trim($lines[0]);
            $title = trim(str_replace('## ', '', $title));
            if ($title === '') {
                continue;
            }

            $body = isset($lines[1]) ? trim($lines[1]) : '';
            [$entityType, $name] = self::splitHeading($title);

            $out[] = new Entity(
                id: '',
                campaignId: '',
                type: $entityType,
                name: $name,
                summary: self::firstLine($body),
                data: '{}',
                createdAt: '',
                updatedAt: '',
            );
        }

        return $out;
    }

    /** @return array{0: string, 1: string} */
    private static function splitHeading(string $title): array
    {
        $pos = strpos($title, ':');
        if ($pos !== false && $pos > 0) {
            return [strtolower(trim(substr($title, 0, $pos))), trim(substr($title, $pos + 1))];
        }

        return ['npc', $title];
    }

    private static function firstLine(string $text): string
    {
        if ($text === '') {
            return '';
        }

        $parts = explode("\n", $text, 2);

        return trim($parts[0]);
    }
}
