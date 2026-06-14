#!/usr/bin/env python3
"""Create Worldkeep milestones, labels, and v0.0.0–v0.4.0 release issues."""
from __future__ import annotations

import subprocess
import sys

REPO = "Bmsandoval/worldkeep"

MILESTONES = [
    ("v0.0.0", "Prototype: Planning + repo bootstrap"),
    ("v0.1.0", "Prototype: Core MCP + SQLite"),
    ("v0.2.0", "Prototype: ChatGPT via tunnel"),
    ("v0.3.0", "Prototype: Templates + export"),
    ("v0.4.0", "Prototype: Hardening"),
]

STAGE_LABELS = {
    0: "Stage 0 — Foundation",
    1: "Stage 1 — Core MCP",
    2: "Stage 2 — ChatGPT tunnel",
    3: "Stage 3 — Templates",
    4: "Stage 4 — Hardening",
}


def gh(*args: str) -> str:
    r = subprocess.run(["gh", *args], capture_output=True, text=True, check=True)
    return (r.stdout or "").strip()


def ensure_milestones() -> None:
    existing = {line.split("\t")[0] for line in gh("api", f"repos/{REPO}/milestones", "--jq", ".[].title").splitlines() if line}
    for title, desc in MILESTONES:
        if title in existing:
            continue
        gh(
            "api", f"repos/{REPO}/milestones", "-X", "POST",
            "-f", f"title={title}", "-f", f"description={desc}",
        )
        print(f"milestone: {title}")


def ensure_labels() -> None:
    existing = {line.split("\t")[0] for line in gh("label", "list", "--repo", REPO, "--limit", "100").splitlines() if line}
    defaults = {
        "prototype": ("Prototype phase work", "C5DEF5"),
        "release": ("Parent release epic", "FBCA04"),
        "enhancement": ("New feature", "84B6EB"),
        "documentation": ("Docs only", "0075CA"),
    }
    for name, (desc, color) in defaults.items():
        if name not in existing:
            subprocess.run(
                ["gh", "label", "create", name, "--repo", REPO, "--description", desc, "--color", color],
                check=True,
            )
    for stage, desc in STAGE_LABELS.items():
        name = f"stage:{stage}"
        if name not in existing:
            subprocess.run(
                ["gh", "label", "create", name, "--repo", REPO, "--description", desc, "--color", "C5DEF5"],
                check=True,
            )


def create_issue(title: str, milestone: str, labels: list[str], body: str) -> int:
    cmd = [
        "gh", "issue", "create", "--repo", REPO, "--title", title,
        "--milestone", milestone, "--body", body,
    ]
    for lab in labels:
        cmd.extend(["--label", lab])
    url = subprocess.run(cmd, capture_output=True, text=True, check=True).stdout.strip()
    return int(url.rsplit("/", 1)[-1])


def sub_body(story: str, criteria: list[str]) -> str:
    lines = ["## User story", "", story, "", "## Acceptance criteria", ""]
    for c in criteria:
        lines.append(f"- [ ] {c}")
    lines += ["", "## Planning refs", "", "- [full-expansion-roadmap.md](docs/planning/full-expansion-roadmap.md)"]
    return "\n".join(lines)


def parent_body(summary: str, criteria: list[str], subs: list[tuple[int, str]]) -> str:
    lines = ["## Summary", "", summary, "", "## Sub-issues", ""]
    for num, title in subs:
        lines.append(f"- [ ] #{num} — {title}")
    lines += ["", "## Release acceptance criteria", ""]
    for c in criteria:
        lines.append(f"- [ ] {c}")
    return "\n".join(lines)


def main() -> None:
    open_count = gh("issue", "list", "--repo", REPO, "--state", "open", "--limit", "1", "--json", "number", "-q", "length")
    if open_count != "0":
        print(f"Repo already has open issues — aborting to stay idempotent.", file=sys.stderr)
        sys.exit(1)

    ensure_milestones()
    ensure_labels()

    # --- v0.0.0 subs ---
    subs_00: list[tuple[int, str]] = []
    n = create_issue(
        "Planning docs, AGENTS.md, and full expansion roadmap",
        "v0.0.0",
        ["documentation", "prototype", "stage:0"],
        sub_body(
            "As a **maintainer**, I want planning docs and agent instructions committed, so contributors and agents share one vision.",
            [
                "`docs/planning/` includes vision, phases, full-expansion-roadmap, scenario-bootstrap, mcp-tools-design, local-mcp-architecture",
                "`AGENTS.md` links workflow and states stack constraints",
                "`README.md` describes prototype status",
                "Scenario bootstrap documents `start_campaign` with premise dictation",
            ],
        ),
    )
    subs_00.append((n, "Planning docs, AGENTS.md, and full expansion roadmap"))

    n = create_issue(
        "GitHub milestones, labels, and release issue structure",
        "v0.0.0",
        ["documentation", "prototype", "stage:0"],
        sub_body(
            "As a **maintainer**, I want GitHub milestones and release epics, so work is issue-driven from v0.1 onward.",
            [
                "Milestones v0.0.0–v0.4.0 exist",
                "Parent release issues filed for v0.1.0–v0.4.0 with sub-issue placeholders",
                "`prototype-release-backlog.md` reflects structure",
            ],
        ),
    )
    subs_00.append((n, "GitHub milestones, labels, and release issue structure"))

    p00 = create_issue(
        "Release v0.0.0 — Prototype: Planning + repo bootstrap",
        "v0.0.0",
        ["release", "prototype", "stage:0"],
        parent_body(
            "Repo bootstrap: planning-first workflow, full product roadmap, scenario bootstrap design, GitHub queue for v0.1+.",
            [
                "Planning docs merged to `develop`",
                "GitHub milestones and v0.1–v0.4 parent epics created",
                "Maintainer can start v0.1 sub-issues",
            ],
            subs_00,
        ),
    )
    print(f"v0.0.0 parent: #{p00}")

    # --- v0.1.0 subs ---
    subs_01: list[tuple[int, str]] = []
    v01_items = [
        (
            "Go module, SQLite schema, and migrations",
            [
                "Go module under `cmd/worldkeep-mcp` and `internal/`",
                "SQLite schema matches mcp-tools-design.md",
                "Migrations run on campaign create",
                "`go test ./...` passes",
            ],
        ),
        (
            "stdio MCP server with server instructions",
            [
                "MCP initialize returns Worldkeep server instructions",
                "tools/list exposes v0.1 tool catalog",
                "stdio transport works with MCP Inspector",
            ],
        ),
        (
            "Campaign lifecycle tools (start_campaign, load_campaign, list_campaigns)",
            [
                "`start_campaign` requires name + premise; creates SQLite under data/",
                "Premise stored as critical lore",
                "`load_campaign` switches active campaign",
            ],
        ),
        (
            "Read tools (get_scene_context, get_location, get_entity, search_world, get_player_state)",
            [
                "get_scene_context returns campaign premise + player location + local entities/events/lore",
                "search_world uses FTS5",
            ],
        ),
        (
            "Write tools (move_to, upsert_*, record_event, add_lore, update_player_state)",
            [
                "move_to creates location stub if missing",
                "Upserts merge fields without blind overwrite",
            ],
        ),
        (
            "Integration test: premise to search round-trip",
            [
                "Test covers start_campaign with Pokémon premise → move_to → upsert_entity → search_world",
                "No live LLM in tests",
            ],
        ),
    ]
    for title, criteria in v01_items:
        num = create_issue(
            title,
            "v0.1.0",
            ["enhancement", "prototype", "stage:1"],
            sub_body(f"As a **player**, I want {title.lower()}, so Worldkeep persists canon during local MCP play.", criteria),
        )
        subs_01.append((num, title))

    p01 = create_issue(
        "Release v0.1.0 — Prototype: Core MCP + SQLite",
        "v0.1.0",
        ["release", "prototype", "stage:1"],
        parent_body(
            "First code release: local stdio MCP, SQLite campaign store, scenario bootstrap, core read/write tools.",
            [
                "Play in Cursor with premise dictation works",
                "Integration test green",
                "Tag v0.1.0 on release branch when maintainer approves",
            ],
            subs_01,
        ),
    )
    print(f"v0.1.0 parent: #{p01}")

    # --- v0.2.0 subs ---
    subs_02: list[tuple[int, str]] = []
    v02_items = [
        ("Streamable HTTP transport (POST /mcp, /healthz)", ["HTTP mode on WORLDKEEP_MCP_ADDR", "MCP Inspector smoke test"]),
        ("Tunnel script and ChatGPT setup docs", ["scripts/tunnel.sh for cloudflared", "docs for ChatGPT connector URL"]),
        ("Tool annotations for ChatGPT", ["readOnlyHint/destructiveHint on all tools"]),
    ]
    for title, criteria in v02_items:
        num = create_issue(title, "v0.2.0", ["enhancement", "prototype", "stage:2"], sub_body("As a **player**, I want ChatGPT connected via tunnel.", criteria))
        subs_02.append((num, title))

    p02 = create_issue(
        "Release v0.2.0 — Prototype: ChatGPT via tunnel",
        "v0.2.0",
        ["release", "prototype", "stage:2"],
        parent_body("Expose local MCP over HTTPS tunnel; validate ChatGPT play loop.", ["ChatGPT connects through tunnel", "Documented setup path"], subs_02),
    )
    print(f"v0.2.0 parent: #{p02}")

    # --- v0.3.0 subs ---
    subs_03: list[tuple[int, str]] = []
    v03_items = [
        ("Scenario template packs (blank, journey, mystery, salvage)", ["start_campaign accepts optional template id", "Templates seed generic locations/NPC stubs"]),
        ("get_map and connect_locations tools", ["Returns discovered location graph"]),
        ("export_campaign and import_campaign JSON", ["Round-trip backup restores playable campaign"]),
    ]
    for title, criteria in v03_items:
        num = create_issue(title, "v0.3.0", ["enhancement", "prototype", "stage:3"], sub_body("As a **player**, I want templates and export.", criteria))
        subs_03.append((num, title))

    p03 = create_issue(
        "Release v0.3.0 — Prototype: Templates + export",
        "v0.3.0",
        ["release", "prototype", "stage:3"],
        parent_body("Template packs, map graph tools, campaign JSON export/import.", ["Template + premise combo works", "Export/import tested"], subs_03),
    )
    print(f"v0.3.0 parent: #{p03}")

    # --- v0.4.0 subs ---
    subs_04: list[tuple[int, str]] = []
    v04_items = [
        ("summarize_session and check_contradiction tools", ["Session digest stored as lore", "Contradiction returns warnings only"]),
        ("Provenance source field on writes", ["source: player|dm|inferred persisted"]),
        ("Multi-session ChatGPT playtest notes doc", ["docs/planning/playtest-notes.md with findings"]),
    ]
    for title, criteria in v04_items:
        num = create_issue(title, "v0.4.0", ["enhancement", "prototype", "stage:4"], sub_body("As a **maintainer**, I want hardening from real play.", criteria))
        subs_04.append((num, title))

    p04 = create_issue(
        "Release v0.4.0 — Prototype: Hardening",
        "v0.4.0",
        ["release", "prototype", "stage:4"],
        parent_body("Playtest-driven fixes: session summary, contradiction hints, provenance.", ["Multi-session play without canon drift", "Playtest doc merged"], subs_04),
    )
    print(f"v0.4.0 parent: #{p04}")

    print("\nDone. Update prototype-release-backlog.md with issue numbers if needed.")


if __name__ == "__main__":
    main()
