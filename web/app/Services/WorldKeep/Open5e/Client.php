<?php

namespace App\Services\WorldKeep\Open5e;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class Client
{
    /** @param  list<string>|null  $srdDocKeys */
    public function __construct(
        public readonly string $baseUrl = Srd::DEFAULT_BASE_URL,
        public readonly int $timeoutSeconds = 15,
        public readonly ?array $srdDocKeys = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function searchRulesReference(string $query, int $limit = 10): array
    {
        $query = trim($query);
        if ($query === '') {
            throw new RuntimeException('query required');
        }

        $limit = CompendiumHelpers::normalizeLimit($limit);
        $allowed = $this->srdDocs();

        $url = rtrim($this->baseUrl(), '/').'/search/';
        $response = $this->get($url, [
            'query' => $query,
            'limit' => (string) ($limit * 3),
        ]);

        $hits = [];
        foreach ($response['results'] ?? [] as $row) {
            $docKey = (string) ($row['document']['key'] ?? '');
            if (! Srd::allowsDocument($docKey, $allowed)) {
                continue;
            }

            $hit = [
                'name' => (string) ($row['object_name'] ?? ''),
                'object_model' => (string) ($row['object_model'] ?? ''),
                'document_key' => $docKey,
                'document_name' => (string) ($row['document']['name'] ?? ''),
                'snippet' => CompendiumHelpers::truncate((string) ($row['text'] ?? ''), 480),
                'match_score' => (float) ($row['match_score'] ?? 0),
            ];

            if (($row['object_model'] ?? '') === 'Rule' && ($row['object_pk'] ?? '') !== '') {
                $hit['rule_key'] = (string) $row['object_pk'];
            }

            $hits[] = $hit;
            if (count($hits) >= $limit) {
                break;
            }
        }

        return [
            'query' => $query,
            'srd_filter' => $allowed,
            'count' => count($hits),
            'results' => $hits,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getRulesSection(string $key): array
    {
        $key = trim($key);
        if ($key === '') {
            throw new RuntimeException('key required');
        }

        $rule = $this->get(rtrim($this->baseUrl(), '/').'/rules/'.rawurlencode($key).'/');
        if (($rule['key'] ?? '') === '') {
            throw new RuntimeException("rule not found: {$key}");
        }

        if (! Srd::allowsDocument((string) ($rule['document'] ?? ''), $this->srdDocs())) {
            throw new RuntimeException("rule {$key} is outside configured SRD filter (".json_encode($this->srdDocs()).')');
        }

        return $rule;
    }

    /**
     * @return array<string, mixed>
     */
    public function searchSpells(string $query, int $limit = 10): array
    {
        $query = trim($query);
        if ($query === '') {
            throw new RuntimeException('query required');
        }

        $limit = CompendiumHelpers::normalizeLimit($limit);
        $allowed = $this->srdDocs();
        $params = ['search' => $query, 'limit' => (string) $limit];
        CompendiumHelpers::addDocumentFilter($params, $allowed);

        $page = $this->get(rtrim($this->baseUrl(), '/').'/spells/', $params);
        $out = [];

        foreach ($page['results'] ?? [] as $raw) {
            if (! is_array($raw)) {
                continue;
            }

            $doc = CompendiumHelpers::documentKeyFromRaw($raw['document'] ?? null);
            if (! Srd::allowsDocument($doc, $allowed)) {
                continue;
            }

            $out[] = [
                'key' => CompendiumHelpers::stringField($raw, 'key'),
                'name' => CompendiumHelpers::stringField($raw, 'name'),
                'level' => (int) ($raw['level'] ?? 0),
                'school' => CompendiumHelpers::nestedName($raw['school'] ?? null),
                'document' => $doc,
                'snippet' => CompendiumHelpers::truncate(CompendiumHelpers::stringField($raw, 'desc'), 240),
            ];
        }

        return [
            'query' => $query,
            'srd_filter' => $allowed,
            'count' => count($out),
            'results' => $out,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getSpell(string $key): array
    {
        $key = trim($key);
        if ($key === '') {
            throw new RuntimeException('key required');
        }

        $raw = $this->get(rtrim($this->baseUrl(), '/').'/spells/'.rawurlencode($key).'/');
        $doc = CompendiumHelpers::documentKeyFromRaw($raw['document'] ?? null);
        if ($doc === '') {
            throw new RuntimeException("spell not found: {$key}");
        }
        if (! Srd::allowsDocument($doc, $this->srdDocs())) {
            throw new RuntimeException("spell {$key} is outside configured SRD filter (".json_encode($this->srdDocs()).')');
        }

        $classes = [];
        foreach ($raw['classes'] ?? [] as $item) {
            $name = CompendiumHelpers::nestedName($item);
            if ($name !== '') {
                $classes[] = $name;
            }
        }

        return [
            'key' => CompendiumHelpers::stringField($raw, 'key'),
            'name' => CompendiumHelpers::stringField($raw, 'name'),
            'level' => (int) ($raw['level'] ?? 0),
            'school' => CompendiumHelpers::nestedName($raw['school'] ?? null),
            'casting_time' => CompendiumHelpers::stringField($raw, 'casting_time'),
            'range_text' => CompendiumHelpers::firstNonEmpty(
                CompendiumHelpers::stringField($raw, 'range_text'),
                CompendiumHelpers::stringField($raw, 'range'),
            ),
            'components' => CompendiumHelpers::spellComponents($raw),
            'desc' => CompendiumHelpers::stringField($raw, 'desc'),
            'higher_level' => CompendiumHelpers::stringField($raw, 'higher_level'),
            'document' => $doc,
            'classes' => $classes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveSpell(string $key, string $name): array
    {
        if (trim($key) !== '') {
            return $this->getSpell($key);
        }

        $name = trim($name);
        if ($name === '') {
            throw new RuntimeException('key or name required');
        }

        $res = $this->searchSpells($name, 10);
        foreach ($res['results'] ?? [] as $hit) {
            if (strcasecmp((string) ($hit['name'] ?? ''), $name) === 0) {
                return $this->getSpell((string) $hit['key']);
            }
        }

        $count = count($res['results'] ?? []);
        if ($count === 1) {
            return $this->getSpell((string) $res['results'][0]['key']);
        }
        if ($count === 0) {
            throw new RuntimeException("spell not found: {$name}");
        }

        throw new RuntimeException("ambiguous spell name \"{$name}\" ({$count} matches); pass key from search_spells");
    }

    /**
     * @return array<string, mixed>
     */
    public function searchCreatures(string $query, int $limit = 10): array
    {
        $query = trim($query);
        if ($query === '') {
            throw new RuntimeException('query required');
        }

        $limit = CompendiumHelpers::normalizeLimit($limit);
        $allowed = $this->srdDocs();
        $params = ['search' => $query, 'limit' => (string) $limit];
        CompendiumHelpers::addDocumentFilter($params, $allowed);

        $page = $this->get(rtrim($this->baseUrl(), '/').'/creatures/', $params);
        $out = [];

        foreach ($page['results'] ?? [] as $raw) {
            if (! is_array($raw)) {
                continue;
            }

            $doc = CompendiumHelpers::documentKeyFromRaw($raw['document'] ?? null);
            if (! Srd::allowsDocument($doc, $allowed)) {
                continue;
            }

            $out[] = [
                'key' => CompendiumHelpers::stringField($raw, 'key'),
                'name' => CompendiumHelpers::stringField($raw, 'name'),
                'challenge_rating' => (float) ($raw['challenge_rating'] ?? 0),
                'type' => CompendiumHelpers::nestedName($raw['type'] ?? null),
                'size' => CompendiumHelpers::nestedName($raw['size'] ?? null),
                'document' => $doc,
                'snippet' => CompendiumHelpers::truncate(CompendiumHelpers::creatureSnippet($raw), 240),
            ];
        }

        return [
            'query' => $query,
            'srd_filter' => $allowed,
            'count' => count($out),
            'results' => $out,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getCreature(string $key): array
    {
        $key = trim($key);
        if ($key === '') {
            throw new RuntimeException('key required');
        }

        $raw = $this->get(rtrim($this->baseUrl(), '/').'/creatures/'.rawurlencode($key).'/');
        $doc = CompendiumHelpers::documentKeyFromRaw($raw['document'] ?? null);
        if ($doc === '') {
            throw new RuntimeException("creature not found: {$key}");
        }
        if (! Srd::allowsDocument($doc, $this->srdDocs())) {
            throw new RuntimeException("creature {$key} is outside configured SRD filter (".json_encode($this->srdDocs()).')');
        }

        return [
            'key' => CompendiumHelpers::stringField($raw, 'key'),
            'name' => CompendiumHelpers::stringField($raw, 'name'),
            'challenge_rating' => (float) ($raw['challenge_rating'] ?? 0),
            'type' => CompendiumHelpers::nestedName($raw['type'] ?? null),
            'size' => CompendiumHelpers::nestedName($raw['size'] ?? null),
            'alignment' => CompendiumHelpers::stringField($raw, 'alignment'),
            'armor_class' => (int) ($raw['armor_class'] ?? 0),
            'hit_points' => (int) ($raw['hit_points'] ?? 0),
            'hit_dice' => CompendiumHelpers::stringField($raw, 'hit_dice'),
            'speed' => is_array($raw['speed'] ?? null) ? $raw['speed'] : null,
            'ability_scores' => is_array($raw['ability_scores'] ?? null) ? $raw['ability_scores'] : null,
            'actions' => is_array($raw['actions'] ?? null) ? $raw['actions'] : [],
            'traits' => is_array($raw['traits'] ?? null) ? $raw['traits'] : [],
            'document' => $doc,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveCreature(string $key, string $name): array
    {
        if (trim($key) !== '') {
            return $this->getCreature($key);
        }

        $name = trim($name);
        if ($name === '') {
            throw new RuntimeException('key or name required');
        }

        $res = $this->searchCreatures($name, 10);
        foreach ($res['results'] ?? [] as $hit) {
            if (strcasecmp((string) ($hit['name'] ?? ''), $name) === 0) {
                return $this->getCreature((string) $hit['key']);
            }
        }

        $count = count($res['results'] ?? []);
        if ($count === 1) {
            return $this->getCreature((string) $res['results'][0]['key']);
        }
        if ($count === 0) {
            throw new RuntimeException("creature not found: {$name}");
        }

        throw new RuntimeException("ambiguous creature name \"{$name}\" ({$count} matches); pass key from search_creatures");
    }

    /**
     * @return array<string, mixed>
     */
    public function getCondition(string $name, string $key = ''): array
    {
        if (trim($key) !== '') {
            return $this->fetchConditionByKey($key);
        }

        $name = trim($name);
        if ($name === '') {
            throw new RuntimeException('name or key required');
        }

        try {
            return $this->fetchConditionByKey(CompendiumHelpers::slugConditionKey($name));
        } catch (RuntimeException) {
            // Fall through to search.
        }

        $allowed = $this->srdDocs();
        $params = ['search' => $name, 'limit' => '25'];
        CompendiumHelpers::addDocumentFilter($params, $allowed);

        try {
            $page = $this->get(rtrim($this->baseUrl(), '/').'/conditions/', $params);
            foreach ($page['results'] ?? [] as $raw) {
                if (! is_array($raw)) {
                    continue;
                }
                if (strcasecmp(CompendiumHelpers::stringField($raw, 'name'), $name) !== 0) {
                    continue;
                }
                $doc = CompendiumHelpers::documentKeyFromRaw($raw['document'] ?? null);
                if (Srd::allowsDocument($doc, $allowed)) {
                    return $this->mapCondition($raw, 'open5e_conditions');
                }
            }
        } catch (RuntimeException) {
            // Fall through to global search.
        }

        $search = $this->get(rtrim($this->baseUrl(), '/').'/search/', [
            'query' => $name,
            'limit' => '20',
        ]);

        foreach ($search['results'] ?? [] as $hit) {
            $docKey = (string) ($hit['document']['key'] ?? '');
            if (! Srd::allowsDocument($docKey, $allowed)) {
                continue;
            }

            $objectModel = (string) ($hit['object_model'] ?? '');
            if ($objectModel !== 'Rule' && $objectModel !== 'Condition') {
                continue;
            }

            $objectName = (string) ($hit['object_name'] ?? '');
            if (strcasecmp($objectName, $name) !== 0 && strcasecmp($objectName, CompendiumHelpers::titleCase($name)) !== 0) {
                continue;
            }

            if ($objectModel === 'Rule' && ($hit['object_pk'] ?? '') !== '') {
                try {
                    $rule = $this->getRulesSection((string) $hit['object_pk']);

                    return [
                        'key' => (string) ($rule['key'] ?? ''),
                        'name' => (string) ($rule['name'] ?? ''),
                        'desc' => (string) ($rule['desc'] ?? ''),
                        'document' => (string) ($rule['document'] ?? ''),
                        'source' => 'open5e_rules',
                    ];
                } catch (RuntimeException) {
                    // Continue to search fallback payload.
                }
            }

            return [
                'key' => (string) ($hit['object_pk'] ?? ''),
                'name' => $objectName,
                'desc' => (string) ($hit['text'] ?? ''),
                'document' => $docKey,
                'source' => 'open5e_search',
            ];
        }

        throw new RuntimeException('condition not found in SRD filter ('.json_encode($allowed)."): {$name}");
    }

    /** @return list<string> */
    private function srdDocs(): array
    {
        $docs = $this->srdDocKeys ?? Srd::documents();

        return $docs !== [] ? $docs : Srd::documents();
    }

    private function baseUrl(): string
    {
        $url = trim($this->baseUrl);

        return $url !== '' ? $url : Srd::DEFAULT_BASE_URL;
    }

    /**
     * @param  array<string, string>  $query
     * @return array<string, mixed>
     */
    private function get(string $url, array $query = []): array
    {
        $response = Http::timeout($this->timeoutSeconds)
            ->withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => 'worldkeep-mcp/1.7',
            ])
            ->get($url, $query);

        if (! $response->successful()) {
            throw new RuntimeException('open5e HTTP '.$response->status().': '.trim($response->body()));
        }

        return $response->json() ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchConditionByKey(string $key): array
    {
        $raw = $this->get(rtrim($this->baseUrl(), '/').'/conditions/'.rawurlencode($key).'/');
        if (CompendiumHelpers::stringField($raw, 'key') === '') {
            throw new RuntimeException("condition not found: {$key}");
        }

        $doc = CompendiumHelpers::documentKeyFromRaw($raw['document'] ?? null);
        if ($doc !== '' && ! Srd::allowsDocument($doc, $this->srdDocs())) {
            throw new RuntimeException("condition {$key} is outside configured SRD filter (".json_encode($this->srdDocs()).')');
        }

        return $this->mapCondition($raw, 'open5e_conditions');
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    private function mapCondition(array $raw, string $source): array
    {
        return [
            'key' => CompendiumHelpers::stringField($raw, 'key'),
            'name' => CompendiumHelpers::stringField($raw, 'name'),
            'desc' => CompendiumHelpers::stringField($raw, 'desc'),
            'document' => CompendiumHelpers::documentKeyFromRaw($raw['document'] ?? null),
            'source' => $source,
        ];
    }
}
