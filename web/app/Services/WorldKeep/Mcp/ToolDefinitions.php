<?php

namespace App\Services\WorldKeep\Mcp;

final class ToolDefinitions
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function all(): array
    {
        $campaignId = self::strProp('Campaign id (default: campaign_001)');
        $limit = self::intProp('Max results (default 10)');
        $scope = self::strProp('Read scope: party (default) or dm');
        $hybrid = self::boolProp('Use hybrid token search (MVP stub; default false)');

        return [
            self::tool('get_campaign_dashboard',
                'Campaign health snapshot: open session, active plots, recent events, pending approvals, continuity warnings.',
                ['campaign_id' => $campaignId, 'event_limit' => $limit, 'scope' => $scope], null, true, false),
            self::tool('prepare_session_brief',
                'Focused session prep — active plots, recent events, open session, pending approvals, continuity warnings.',
                ['campaign_id' => $campaignId, 'event_limit' => $limit, 'scope' => $scope], null, true, false),
            self::tool('get_campaign_overview',
                'Campaign summary, active plots, and major actors.',
                ['campaign_id' => $campaignId, 'scope' => $scope], null, true, false),
            self::tool('get_entity',
                'Return one full entity by id.',
                ['entity_id' => self::strProp('Entity id'), 'scope' => $scope], ['entity_id'], true, false),
            self::tool('search_world',
                'Search entities and facts by keyword.',
                ['campaign_id' => $campaignId, 'query' => self::strProp('Search text'), 'limit' => $limit, 'scope' => $scope, 'hybrid' => $hybrid],
                ['query'], true, false),
            self::tool('compile_scene_context',
                'Primary context endpoint — relevant actors, facts, events, plots for a scene prompt.',
                [
                    'campaign_id' => $campaignId,
                    'prompt' => self::strProp('Natural-language scene prompt'),
                    'limit' => $limit,
                    'scope' => $scope,
                    'hybrid' => $hybrid,
                ], ['prompt'], true, false),
            self::tool('get_recent_events',
                'Recent campaign events.',
                ['campaign_id' => $campaignId, 'limit' => $limit], null, true, false),
            self::tool('get_active_plots',
                'Unresolved active plots.',
                ['campaign_id' => $campaignId, 'scope' => $scope], null, true, false),
            self::tool('search_rulings',
                'Search prior house rulings.',
                ['campaign_id' => $campaignId, 'query' => self::strProp('Search text'), 'limit' => $limit],
                ['query'], true, false),
            self::tool('propose_world_update',
                'Propose canon changes for DM approval.',
                [
                    'campaign_id' => $campaignId,
                    'changes' => self::objProp('JSON array of change objects'),
                    'reason' => self::strProp('Why these changes should be saved'),
                ], ['changes'], false, false),
            self::tool('list_pending_updates',
                'List pending canon update proposals.',
                ['campaign_id' => $campaignId], null, true, false),
            self::tool('commit_world_update',
                'Apply a pending update after DM approval.',
                ['update_id' => self::strProp('Pending update id')], ['update_id'], false, true),
            self::tool('reject_world_update',
                'Reject a pending update.',
                ['update_id' => self::strProp('Pending update id'), 'reason' => self::strProp('Optional rejection reason')],
                ['update_id'], false, true),
            self::tool('check_for_conflicts',
                'Detect possible contradictions in proposed changes.',
                ['campaign_id' => $campaignId, 'changes' => self::objProp('Proposed changes JSON')],
                ['changes'], true, false),
            self::tool('start_session',
                'Open a new play session.',
                ['campaign_id' => $campaignId, 'title' => self::strProp('Session title'), 'notes' => self::strProp('Optional session notes')],
                null, false, false),
            self::tool('get_session',
                'Session workspace — notes, summary, events, entities modified, pending updates.',
                ['session_id' => self::strProp('Session id (defaults to active session)')], null, true, false),
            self::tool('end_session',
                'Close the active session; optional summary, notes, and changes propose a pending canon update.',
                [
                    'session_id' => self::strProp('Session id (defaults to active session)'),
                    'summary' => self::strProp('End-of-session summary for the DM'),
                    'notes' => self::strProp('Optional session notes'),
                    'changes' => self::objProp('Optional JSON array of canon changes to propose'),
                    'reason' => self::strProp('Reason for proposed changes'),
                ], null, false, false),
            self::tool('record_event',
                'Record a campaign event (via active session when set).',
                [
                    'campaign_id' => $campaignId,
                    'session_id' => self::strProp('Session id (optional)'),
                    'title' => self::strProp('Event title'),
                    'summary' => self::strProp('Event summary'),
                    'entity_ids' => self::objProp('JSON array of entity ids'),
                ], ['title', 'summary'], false, false),
            self::tool('record_ruling',
                'Record a house ruling.',
                [
                    'campaign_id' => $campaignId,
                    'question' => self::strProp('Rules question'),
                    'answer' => self::strProp('Ruling answer'),
                    'scope' => self::strProp('Scope (default campaign)'),
                ], ['question', 'answer'], false, false),
            self::tool('create_secret',
                'Propose a DM-only secret entity for approval.',
                [
                    'campaign_id' => $campaignId,
                    'title' => self::strProp('Secret title'),
                    'text' => self::strProp('Secret detail'),
                    'reason' => self::strProp('Why this secret should be stored'),
                ], ['title'], false, false),
            self::tool('import_campaign_markdown',
                'Parse Markdown headings into entity proposals for DM approval.',
                [
                    'campaign_id' => $campaignId,
                    'markdown' => self::strProp('Markdown notes (## headings)'),
                    'reason' => self::strProp('Import reason'),
                ], ['markdown'], false, false),
            self::tool('set_campaign_role',
                'Set permissions role for campaign access (owner, dm, player).',
                [
                    'campaign_id' => $campaignId,
                    'role' => self::strProp('owner, dm, or player'),
                ], ['role'], false, false),
            self::tool('list_campaign_seats',
                'List DM and player seats with current controller (human or AI).',
                ['campaign_id' => $campaignId], null, true, false),
            self::tool('get_seat',
                'Return one campaign seat by id.',
                ['seat_id' => self::strProp('Seat id')], ['seat_id'], true, false),
            self::tool('create_player_seat',
                'Create a player seat linked to a party actor entity.',
                [
                    'campaign_id' => $campaignId,
                    'actor_id' => self::strProp('Party actor entity id'),
                    'display_name' => self::strProp('Optional display name'),
                ], ['actor_id'], false, false),
            self::tool('assign_seat_controller',
                'Assign human or AI controller to a seat (host/DM only).',
                [
                    'seat_id' => self::strProp('Seat id'),
                    'controller' => self::strProp('human or ai'),
                    'controller_user_id' => self::strProp('Required when controller is human'),
                ], ['seat_id', 'controller'], false, false),
            self::tool('handoff_seat',
                'Hand off seat control with audit log (host/DM only).',
                [
                    'seat_id' => self::strProp('Seat id'),
                    'controller' => self::strProp('human or ai'),
                    'controller_user_id' => self::strProp('Required when controller is human'),
                    'reason' => self::strProp('Why the handoff happened'),
                    'session_id' => self::strProp('Optional session for audit linkage'),
                ], ['seat_id', 'controller'], false, false),
            self::tool('release_seat_to_ai',
                'Release a seat back to AI control (shorthand handoff).',
                [
                    'seat_id' => self::strProp('Seat id'),
                    'reason' => self::strProp('Why the participant left'),
                    'session_id' => self::strProp('Optional session for audit linkage'),
                ], ['seat_id'], false, false),
            self::tool('get_session_floor',
                'Who has the narrative floor in the open session (player-led default).',
                ['session_id' => self::strProp('Session id (defaults to open session)')], null, true, false),
            self::tool('search_rules_reference',
                'Search D&D 5e SRD rules text via Open5e (filtered by WORLDKEEP_SRD_VERSION). Returns snippets and rule_key for get_rules_section.',
                [
                    'query' => self::strProp('Rules keyword or phrase'),
                    'limit' => $limit,
                ], ['query'], true, false),
            self::tool('get_rules_section',
                'Fetch full Open5e SRD rule section by key (from search_rules_reference rule_key).',
                ['key' => self::strProp('Open5e rule key, e.g. srd_monsters_grapple-rules')], ['key'], true, false),
            self::tool('search_spells',
                'Search SRD spells via Open5e (WORLDKEEP_SRD_VERSION filter).',
                [
                    'query' => self::strProp('Spell name or keyword'),
                    'limit' => $limit,
                ], ['query'], true, false),
            self::tool('get_spell',
                'Fetch SRD spell by Open5e key or exact name.',
                [
                    'key' => self::strProp('Open5e spell key from search_spells'),
                    'name' => self::strProp('Spell name (used when key omitted)'),
                ], null, true, false),
            self::tool('search_creatures',
                'Search SRD creature stat blocks via Open5e.',
                [
                    'query' => self::strProp('Creature name or keyword'),
                    'limit' => $limit,
                ], ['query'], true, false),
            self::tool('get_creature',
                'Fetch SRD creature stat block by Open5e key or exact name.',
                [
                    'key' => self::strProp('Open5e creature key from search_creatures'),
                    'name' => self::strProp('Creature name (used when key omitted)'),
                ], null, true, false),
            self::tool('get_condition',
                'Fetch condition rules text (name or key). SRD-only when configured.',
                [
                    'name' => self::strProp('Condition name, e.g. Grappled'),
                    'key' => self::strProp('Open5e condition key (optional)'),
                ], null, true, false),
        ];
    }

    /** @param  array<string, mixed>|null  $props */
    private static function tool(
        string $name,
        string $desc,
        ?array $props,
        ?array $required,
        bool $readOnly,
        bool $destructive,
    ): array {
        $props ??= [];
        $schema = ['type' => 'object', 'properties' => $props];
        if ($required !== null && $required !== []) {
            $schema['required'] = $required;
        }

        return [
            'name' => $name,
            'description' => $desc,
            'inputSchema' => $schema,
            'annotations' => [
                'title' => $name,
                'readOnlyHint' => $readOnly,
                'destructiveHint' => $destructive,
                'openWorldHint' => false,
            ],
            'securitySchemes' => [
                ['type' => 'oauth2', 'scopes' => ['openid', 'email']],
            ],
        ];
    }

    /** @return array<string, string> */
    private static function strProp(string $desc): array
    {
        return ['type' => 'string', 'description' => $desc];
    }

    /** @return array<string, string> */
    private static function intProp(string $desc): array
    {
        return ['type' => 'integer', 'description' => $desc];
    }

    /** @return array<string, string> */
    private static function objProp(string $desc): array
    {
        return ['type' => 'object', 'description' => $desc];
    }

    /** @return array<string, string> */
    private static function boolProp(string $desc): array
    {
        return ['type' => 'boolean', 'description' => $desc];
    }
}
