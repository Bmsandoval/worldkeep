#!/usr/bin/env python3
"""Create v1.7.0 sub-issues: Open5e rules (primary) + optional DDB sync."""
from __future__ import annotations

import subprocess

REPO = "Bmsandoval/worldkeep"
MILESTONE = "v1.7.0"
PARENT = 101


def gh(*args: str, check: bool = True) -> str:
    r = subprocess.run(["gh", *args], capture_output=True, text=True, check=check)
    return (r.stdout or "").strip()


def sub_body(story: str, criteria: list[str], refs: list[str]) -> str:
    lines = ["## User story", "", story, "", "## Acceptance criteria", ""]
    for c in criteria:
        lines.append(f"- [ ] {c}")
    lines += ["", "## Planning refs", ""]
    for ref in refs:
        lines.append(f"- {ref}")
    return "\n".join(lines)


def create_issue(title: str, labels: list[str], body: str) -> int:
    cmd = [
        "gh", "issue", "create", "--repo", REPO, "--title", title,
        "--milestone", MILESTONE, "--body", body,
    ]
    for lab in labels:
        cmd.extend(["--label", lab])
    url = subprocess.run(cmd, capture_output=True, text=True, check=True).stdout.strip()
    return int(url.rsplit("/", 1)[-1])


def main() -> None:
    gh(
        "api", f"repos/{REPO}/milestones/18", "-X", "PATCH",
        "-f", "description=Open5e SRD rules (primary) + optional D&D Beyond party/game-log",
    )

    existing = {
        line.split("\t")[0]
        for line in gh("label", "list", "--repo", REPO, "--limit", "100").splitlines()
        if line
    }
    if "post-mvp:17" not in existing:
        subprocess.run(
            ["gh", "label", "create", "post-mvp:17", "--repo", REPO,
             "--description", "Post-MVP stage 17 — rules reference", "--color", "5319E7"],
            check=True,
        )
    if "integration" not in existing:
        subprocess.run(
            ["gh", "label", "create", "integration", "--repo", REPO,
             "--description", "External service integration", "--color", "5319E7"],
            check=True,
        )

    base_labels = ["enhancement", "integration", "post-mvp:17"]
    subs: list[tuple[int, str]] = []

    items = [
        (
            "Open5e client: search_rules_reference and get_rules_section MCP tools",
            "As a **DM using AI**, I want to search SRD rule text from Open5e, so the model cites real mechanics instead of guessing.",
            [
                "HTTP client for `api.open5e.com/v2` with timeout and error handling",
                "`search_rules_reference(query, document_key?)` → `/v2/search/` filtered to SRD",
                "`get_rules_section(key)` → `/v2/rules/{key}/` full `desc`",
                "Default document filter: `srd-2014` (overridable via env)",
                "Unit tests with mocked HTTP or recorded fixtures",
            ],
            [
                "docs/open5e-integration.md",
                "https://open5e.com/api-docs",
            ],
        ),
        (
            "Open5e compendium tools: spells, creatures, conditions",
            "As a **DM**, I want spell and monster stat blocks from Open5e in MCP, so prep and combat narration stay accurate.",
            [
                "`search_spells` / `get_spell` — filter `document__key` to campaign SRD version",
                "`search_creatures` / `get_creature` — structured stat block fields in response",
                "`get_condition` — condition text from `/v2/conditions/`",
                "`WORLDKEEP_SRD_VERSION` env: `srd-2014`, `srd-2024`, or `both`",
                "Playtest script covers at least one spell + one creature lookup",
            ],
            ["docs/open5e-integration.md"],
        ),
        (
            "Rules source policy in MCP instructions and README",
            "As a **maintainer**, I want documented precedence (table rulings > Open5e SRD > optional DDB), so agents use the right source.",
            [
                "Update MCP server instructions: `search_rulings` before Open5e; `record_ruling` when table deviates",
                "README section: Open5e (no auth), optional ddb-mcp sidecar for sheets/owned books",
                "Example `.cursor/mcp.json` with worldkeep-only vs worldkeep+ddb-mcp",
                "Link docs/open5e-integration.md and docs/dndbeyond-integration.md",
            ],
            [
                "docs/open5e-integration.md",
                "docs/dndbeyond-integration.md",
                "docs/mcp.md",
            ],
        ),
        (
            "Optional DDB: entity character link and dual-MCP party snapshot",
            "As a **player on D&D Beyond**, I want WorldKeep entities linked to DDB character IDs, so party prep can cross-check names without overwriting canon.",
            [
                "Optional `ddb_character_id` on entity metadata (or dedicated column)",
                "Document dual-MCP setup with `@iamjameslennon/ddb-mcp` — no required in-process DDB code",
                "`prepare_session_brief` or doc: pull `ddb_get_party` checklist vs WorldKeep roster",
                "Explicit: HP/slots ephemeral — never auto-commit to canon",
            ],
            ["docs/dndbeyond-integration.md"],
        ),
        (
            "Spike: DDB game-log events and map-aware canon (no VTT control)",
            "As a **DM**, I want rolls and narrative position recorded in WorldKeep, so the AI knows combat state without driving DDB Maps.",
            [
                "Spike doc: game-log WebSocket feasibility (Cobalt session) → `record_event`",
                "Document DDB Maps limitations — no grid API; DM-declared position facts only",
                "Recommend human token movement + WorldKeep facts/events for position",
                "Out of scope: browser automation of Maps UI",
            ],
            ["docs/dndbeyond-integration.md"],
        ),
    ]

    for title, story, criteria, refs in items:
        n = create_issue(title, base_labels, sub_body(story, criteria, refs))
        subs.append((n, title))
        print(f"  #{n} — {title}")

    parent_body = f"""## Summary

Integrate **Open5e** as the **primary** SRD rules reference (built into WorldKeep MCP) and **optional D&D Beyond** for character sheets, owned books, and game-log sync.

**Docs:**
- [`docs/open5e-integration.md`](https://github.com/Bmsandoval/worldkeep/blob/develop/docs/open5e-integration.md) — **primary**
- [`docs/dndbeyond-integration.md`](https://github.com/Bmsandoval/worldkeep/blob/develop/docs/dndbeyond-integration.md) — optional DDB

## Source precedence

1. **WorldKeep** `search_rulings` / facts — table canon (wins)
2. **Open5e API** — SRD spells, monsters, conditions, rules text (no auth)
3. **DDB via ddb-mcp** (optional) — character sheets, owned PHB text, roll feed

## Sub-issues

"""
    for num, title in subs:
        parent_body += f"- [ ] #{num} — {title}\n"

    parent_body += """
## Non-goals (v1.7)

- Replacing D&D Beyond character sheets or Maps as the VTT UI
- Caching paid PHB/DMG text in WorldKeep without license review
- Grid/token automation on DDB Maps

## Release acceptance criteria

- [ ] Open5e search + rules section tools shipped and playtested
- [ ] Spell/creature/condition tools with `WORLDKEEP_SRD_VERSION`
- [ ] MCP instructions document rules precedence
- [ ] DDB integration documented as optional sidecar (party link + game-log spike)

## References

- [Open5e API docs](https://open5e.com/api-docs)
- [ddb-mcp](https://github.com/iamjameslennon/ddb-mcp)
- WorldKeep: `search_rulings`, `record_ruling`, `compile_scene_context`
"""

    gh(
        "issue", "edit", str(PARENT), "--repo", REPO,
        "--title", "Release v1.7.0 — Open5e rules reference + optional D&D Beyond",
        "--body", parent_body,
    )
    print(f"\nUpdated parent #{PARENT}")
    print("Update HANDOFF.md with sub-issue numbers.")


if __name__ == "__main__":
    main()
