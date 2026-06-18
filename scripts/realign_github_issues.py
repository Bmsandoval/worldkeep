#!/usr/bin/env python3
"""Close stale bootstrap issues and create POC-aligned milestones/issues."""
from __future__ import annotations

import subprocess
import sys

REPO = "Bmsandoval/worldkeep"
SUPERSEDED_NOTE = """Superseded by the POC product spec in `docs/` (promoted 2026-06-17).

The June 2026 bootstrap (`docs/planning/`, Pokémon premise, `start_campaign` / `move_to`) is obsolete.
See [docs/poc.md](docs/poc.md) and [docs/mcp.md](docs/mcp.md) for the active plan.

Replaced by the v0.1.0+ issue queue created in the POC realignment."""

MILESTONES = [
    ("v0.0.0", "POC: Planning + product spec"),
    ("v0.1.0", "POC Phase 1 — Manual world store (SQLite)"),
    ("v0.2.0", "POC Phase 2 — MCP read access (stdio)"),
    ("v0.3.0", "POC Phase 3 — Propose/commit canon updates"),
    ("v0.4.0", "POC Phase 4 — Session workflow"),
    ("v0.5.0", "POC Phase 5 — Conflict warnings + playtest"),
    ("v0.6.0", "POC — ChatGPT via tunnel (HTTP MCP)"),
]

STALE_OPEN = list(range(3, 23))  # #3 parent v0.0.0 through #22 parent v0.4.0


def gh(*args: str, check: bool = True) -> str:
    r = subprocess.run(["gh", *args], capture_output=True, text=True, check=check)
    return (r.stdout or "").strip()


def close_stale() -> None:
    for num in STALE_OPEN:
        gh(
            "issue", "close", str(num), "--repo", REPO,
            "--comment", SUPERSEDED_NOTE,
        )
        print(f"closed #{num}")


def ensure_milestones() -> None:
    for title, desc in MILESTONES:
        num = gh(
            "api", f"repos/{REPO}/milestones", "--jq",
            f'.[] | select(.title=="{title}") | .number',
            check=False,
        )
        if num:
            gh(
                "api", f"repos/{REPO}/milestones/{num}", "-X", "PATCH",
                "-f", f"description={desc}",
            )
            continue
        gh(
            "api", f"repos/{REPO}/milestones", "-X", "POST",
            "-f", f"title={title}", "-f", f"description={desc}",
        )
        print(f"milestone: {title}")


def ensure_labels() -> None:
    existing = {
        line.split("\t")[0]
        for line in gh("label", "list", "--repo", REPO, "--limit", "100").splitlines()
        if line
    }
    for name, desc, color in [
        ("prototype", "Prototype phase work", "C5DEF5"),
        ("release", "Parent release epic", "FBCA04"),
        ("enhancement", "New feature", "84B6EB"),
        ("documentation", "Docs only", "0075CA"),
    ]:
        if name not in existing:
            subprocess.run(
                ["gh", "label", "create", name, "--repo", REPO,
                 "--description", desc, "--color", color],
                check=True,
            )
    for stage in range(7):
        name = f"stage:{stage}"
        if name not in existing:
            subprocess.run(
                ["gh", "label", "create", name, "--repo", REPO,
                 "--description", f"POC stage {stage}", "--color", "C5DEF5"],
                check=True,
            )


def sub_body(story: str, criteria: list[str], refs: list[str] | None = None) -> str:
    lines = ["## User story", "", story, "", "## Acceptance criteria", ""]
    for c in criteria:
        lines.append(f"- [ ] {c}")
    lines += ["", "## Planning refs", ""]
    for ref in refs or ["docs/poc.md", "docs/mcp.md", "docs/roadmap.md"]:
        lines.append(f"- [{ref}]({ref})")
    return "\n".join(lines)


def parent_body(summary: str, criteria: list[str], subs: list[tuple[int, str]]) -> str:
    lines = ["## Summary", "", summary, "", "## Sub-issues", ""]
    for num, title in subs:
        lines.append(f"- [ ] #{num} — {title}")
    lines += ["", "## Release acceptance criteria", ""]
    for c in criteria:
        lines.append(f"- [ ] {c}")
    lines += ["", "## Planning refs", "", "- [docs/poc.md](docs/poc.md) §14 build order"]
    return "\n".join(lines)


def create_issue(title: str, milestone: str, labels: list[str], body: str) -> int:
    cmd = [
        "gh", "issue", "create", "--repo", REPO, "--title", title,
        "--milestone", milestone, "--body", body,
    ]
    for lab in labels:
        cmd.extend(["--label", lab])
    url = subprocess.run(cmd, capture_output=True, text=True, check=True).stdout.strip()
    return int(url.rsplit("/", 1)[-1])


def create_v010() -> int:
    subs: list[tuple[int, str]] = []
    items = [
        (
            "Go module, SQLite schema, migrations, and Blackport seed",
            [
                "Go module under `mcp/` with `go test ./...` green",
                "SQLite schema: campaigns, sessions, entities, facts, events, rulings, pending_updates",
                "Migrations applied on store open",
                "`make seed` loads Shadows of Blackport demo (`docs/poc.md` §13)",
            ],
            ["docs/poc.md", "docs/entity-model.md"],
        ),
        (
            "Store CRUD layer and SQL search",
            [
                "Campaign and entity get/upsert",
                "Facts, events, rulings create + list",
                "`search_world` uses SQL LIKE across entities and facts (POC §16)",
                "Integration test covers seed → search round-trip without live LLM",
            ],
            ["docs/poc.md"],
        ),
    ]
    for title, criteria, refs in items:
        n = create_issue(
            title, "v0.1.0", ["enhancement", "prototype", "stage:1"],
            sub_body(
                f"As a **maintainer**, I want {title.lower()}, so WorldKeep has a local campaign store.",
                criteria, refs,
            ),
        )
        subs.append((n, title))

    return create_issue(
        "Release v0.1.0 — POC Phase 1: Manual world store",
        "v0.1.0", ["release", "prototype", "stage:1"],
        parent_body(
            "SQLite campaign store with CRUD, search, and Blackport demo seed. No MCP yet.",
            [
                "`make test` and `make seed` succeed on a clean checkout",
                "Demo entities match `docs/poc.md` §13",
            ],
            subs,
        ),
    )


def create_v020() -> int:
    subs: list[tuple[int, str]] = []
    items = [
        (
            "stdio MCP server with play-agent instructions",
            [
                "MCP stdio transport for Cursor / Claude Desktop",
                "Server instructions enforce retrieve-before-narrate and propose→commit canon flow",
                "`tools/list` exposes Phase 2 read catalog",
            ],
            ["docs/mcp.md", "docs/session-lifecycle.md", "AGENTS.md"],
        ),
        (
            "MCP read tools: overview, entity, search",
            [
                "`get_campaign_overview(campaign_id)`",
                "`get_entity(entity_id)`",
                "`search_world(campaign_id, query)`",
            ],
            ["docs/poc.md", "docs/mcp.md"],
        ),
        (
            "MCP read tools: compile_scene_context and plot/event/ruling helpers",
            [
                "`compile_scene_context` (or `get_relevant_context`) returns actors, facts, events, plots for a prompt",
                "`get_recent_events`, `get_active_plots`, `search_rulings`",
                "Context pipeline follows `docs/context-pipeline.md` at POC depth",
            ],
            ["docs/context-pipeline.md", "docs/poc.md"],
        ),
        (
            "Integration test: Blackport read path via MCP handlers",
            [
                "Test seeds Blackport, calls read handlers, asserts Finn / Crimson Guild / flanking ruling present",
                "No live LLM in tests",
            ],
            ["docs/poc.md", "docs/poc.md §13 demo prompts"],
        ),
    ]
    for title, criteria, refs in items:
        n = create_issue(
            title, "v0.2.0", ["enhancement", "prototype", "stage:2"],
            sub_body(
                f"As an **AI DM client**, I want {title.lower()}, so I can retrieve canon before narrating.",
                criteria, refs,
            ),
        )
        subs.append((n, title))

    return create_issue(
        "Release v0.2.0 — POC Phase 2: MCP read access",
        "v0.2.0", ["release", "prototype", "stage:2"],
        parent_body(
            "Local stdio MCP exposing read tools; AI can prepare session briefs from stored canon.",
            [
                "Cursor MCP smoke test against seeded Blackport campaign",
                "Demo prompts 1–3 from `docs/poc.md` §13 answer from tool data",
            ],
            subs,
        ),
    )


def create_v030() -> int:
    subs: list[tuple[int, str]] = []
    items = [
        (
            "Pending update queue (propose, list, commit, reject)",
            [
                "`propose_world_update(campaign_id, changes, reason)`",
                "`list_pending_updates(campaign_id)`",
                "`commit_world_update(update_id)` applies changes",
                "`reject_world_update(update_id, reason?)`",
            ],
            ["docs/poc.md", "docs/mcp.md"],
        ),
        (
            "Write helpers and MCP expose propose→commit flow",
            [
                "Direct writes (`create_entity`, `update_entity`, `add_fact`, `record_event`) used only behind commit",
                "MCP write tools call propose flow — no silent canon overwrites",
            ],
            ["docs/poc.md", "docs/session-lifecycle.md"],
        ),
        (
            "Basic conflict detection before commit",
            [
                "`check_for_conflicts(campaign_id, proposed_changes)`",
                "Detect name duplicates, alive/dead conflicts, obvious fact contradictions (POC §9)",
                "Warnings surfaced on pending updates; do not hard-block POC commits",
            ],
            ["docs/poc.md"],
        ),
    ]
    for title, criteria, refs in items:
        n = create_issue(
            title, "v0.3.0", ["enhancement", "prototype", "stage:3"],
            sub_body(
                f"As a **DM**, I want {title.lower()}, so canon changes require my approval.",
                criteria, refs,
            ),
        )
        subs.append((n, title))

    return create_issue(
        "Release v0.3.0 — POC Phase 3: Propose/commit updates",
        "v0.3.0", ["release", "prototype", "stage:3"],
        parent_body(
            "Two-step canon updates with pending queue and basic conflict warnings.",
            [
                "Demo prompt 4 (`Finn agrees to spy`) produces a pending update DM can commit",
                "Committed state visible on subsequent read calls",
            ],
            subs,
        ),
    )


def create_v040() -> int:
    subs: list[tuple[int, str]] = []
    items = [
        (
            "Session lifecycle (start_session, end_session)",
            [
                "Sessions are first-class rows linked to campaigns",
                "`start_session` / `end_session` MCP tools",
            ],
            ["docs/session-lifecycle.md", "docs/mcp.md"],
        ),
        (
            "record_event and record_ruling MCP tools",
            [
                "`record_event` ties events to active session",
                "`record_ruling` persists house rules with campaign scope",
            ],
            ["docs/poc.md", "docs/mcp.md"],
        ),
        (
            "End-of-session summary and world update proposal",
            [
                "End-session flow produces summary + `propose_world_update` bundle",
                "Events, facts, and plot status changes captured for approval",
            ],
            ["docs/poc.md", "docs/session-lifecycle.md"],
        ),
    ]
    for title, criteria, refs in items:
        n = create_issue(
            title, "v0.4.0", ["enhancement", "prototype", "stage:4"],
            sub_body(
                f"As a **DM**, I want {title.lower()}, so play sessions persist as structured history.",
                criteria, refs,
            ),
        )
        subs.append((n, title))

    return create_issue(
        "Release v0.4.0 — POC Phase 4: Session workflow",
        "v0.4.0", ["release", "prototype", "stage:4"],
        parent_body(
            "Session objects, event/ruling recording, end-of-session canon proposals.",
            [
                "Full session loop in `docs/poc.md` §10 works with MCP tools",
            ],
            subs,
        ),
    )


def create_v050() -> int:
    subs: list[tuple[int, str]] = []
    items = [
        (
            "Conflict warnings on pending updates UI/tool output",
            [
                "Pending updates include `check_for_conflicts` warnings in tool JSON",
                "Finn one-eye contradiction example from POC §9 covered by test",
            ],
            ["docs/poc.md"],
        ),
        (
            "Shadows of Blackport multi-chat playtest checklist",
            [
                "`docs/playtest-notes.md` documents demo prompts 1–7 across separate chats",
                "POC success criteria in `docs/poc.md` §12 checked off with evidence",
            ],
            ["docs/poc.md"],
        ),
    ]
    for title, criteria, refs in items:
        n = create_issue(
            title, "v0.5.0", ["enhancement", "prototype", "stage:5"],
            sub_body(
                f"As a **maintainer**, I want {title.lower()}, so we can validate POC success.",
                criteria, refs,
            ),
        )
        subs.append((n, title))

    return create_issue(
        "Release v0.5.0 — POC Phase 5: Conflict warnings + playtest",
        "v0.5.0", ["release", "prototype", "stage:5"],
        parent_body(
            "Harden conflict surfacing and run the Blackport multi-chat POC demo.",
            [
                "POC success metric met: campaign survives multiple AI conversations",
                "Maintainer sign-off to tag POC complete",
            ],
            subs,
        ),
    )


def create_v060() -> int:
    subs: list[tuple[int, str]] = []
    items = [
        (
            "Streamable HTTP MCP transport (POST /mcp, /healthz)",
            [
                "HTTP mode on `WORLDKEEP_MCP_ADDR`",
                "Follow timelord/mcp remote shape; no auth in POC",
            ],
            ["docs/poc.md", "ex.env"],
        ),
        (
            "Tunnel script and ChatGPT connector setup docs",
            [
                "Tunnel script (cloudflared) documented",
                "ChatGPT MCP connector setup path documented",
            ],
            ["HANDOFF.md"],
        ),
        (
            "Tool annotations for ChatGPT",
            [
                "readOnlyHint / destructiveHint on all MCP tools",
            ],
            ["docs/mcp.md"],
        ),
    ]
    for title, criteria, refs in items:
        n = create_issue(
            title, "v0.6.0", ["enhancement", "prototype", "stage:6"],
            sub_body(
                f"As a **player**, I want {title.lower()}, so ChatGPT can use WorldKeep remotely.",
                criteria, refs,
            ),
        )
        subs.append((n, title))

    return create_issue(
        "Release v0.6.0 — ChatGPT via tunnel",
        "v0.6.0", ["release", "prototype", "stage:6"],
        parent_body(
            "Expose local MCP over HTTPS tunnel for ChatGPT — after POC read/write path works in Cursor.",
            [
                "ChatGPT connects through tunnel and completes a read + propose flow",
            ],
            subs,
        ),
    )


def create_all() -> dict[str, int]:
    print("Ensuring milestones and labels…")
    ensure_milestones()
    ensure_labels()

    print("Creating POC-aligned release epics…")
    parents = {
        "v0.1.0": create_v010(),
        "v0.2.0": create_v020(),
        "v0.3.0": create_v030(),
        "v0.4.0": create_v040(),
        "v0.5.0": create_v050(),
        "v0.6.0": create_v060(),
    }
    for milestone, num in parents.items():
        print(f"{milestone} parent: #{num}")
    return parents


def main() -> None:
    print("Closing stale bootstrap issues #3–#22…")
    close_stale()
    create_all()
    print("\nDone. Update HANDOFF.md with new issue numbers.")


if __name__ == "__main__":
    main()
