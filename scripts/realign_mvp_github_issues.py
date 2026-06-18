#!/usr/bin/env python3
"""Create MVP-aligned milestones and issue queue (Phase 2 per docs/mvp.md)."""
from __future__ import annotations

import subprocess

REPO = "Bmsandoval/worldkeep"

MILESTONES = [
    ("v0.7.0", "MVP — Campaign dashboard + session brief aggregate"),
    ("v0.8.0", "MVP — Secrets and visibility filtering"),
    ("v0.9.0", "MVP — Session workspace enhancements"),
    ("v1.0.0", "MVP — Hybrid search, import, permissions (Phase 2 completion)"),
]


def gh(*args: str, check: bool = True) -> str:
    r = subprocess.run(["gh", *args], capture_output=True, text=True, check=check)
    return (r.stdout or "").strip()


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
        ("mvp", "MVP phase work", "5319E7"),
    ]:
        if name not in existing:
            subprocess.run(
                ["gh", "label", "create", name, "--repo", REPO,
                 "--description", desc, "--color", color],
                check=True,
            )
    for stage in ("7", "8", "9", "10"):
        name = f"mvp:{stage}"
        if name not in existing:
            subprocess.run(
                ["gh", "label", "create", name, "--repo", REPO,
                 "--description", f"MVP stage {stage}", "--color", "5319E7"],
                check=True,
            )


def sub_body(story: str, criteria: list[str], refs: list[str] | None = None) -> str:
    lines = ["## User story", "", story, "", "## Acceptance criteria", ""]
    for c in criteria:
        lines.append(f"- [ ] {c}")
    lines += ["", "## Planning refs", ""]
    for ref in refs or ["docs/mvp.md", "docs/roadmap.md", "docs/mcp.md"]:
        lines.append(f"- [{ref}]({ref})")
    return "\n".join(lines)


def parent_body(summary: str, criteria: list[str], subs: list[tuple[int, str]]) -> str:
    lines = ["## Summary", "", summary, "", "## Sub-issues", ""]
    for num, title in subs:
        lines.append(f"- [ ] #{num} — {title}")
    lines += ["", "## Release acceptance criteria", ""]
    for c in criteria:
        lines.append(f"- [ ] {c}")
    lines += ["", "## Planning refs", "", "- [docs/mvp.md](docs/mvp.md) §6 Campaign Dashboard"]
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


def create_v070() -> int:
    subs: list[tuple[int, str]] = []
    items = [
        (
            "get_campaign_dashboard MCP tool",
            [
                "`get_campaign_dashboard(campaign_id?)` returns campaign summary, open session, active plots, recent events, pending updates with warnings, and continuity warning count",
                "Registered in `tools/list` with readOnlyHint",
                "Integration test on seeded Blackport covers all dashboard sections",
            ],
            ["docs/mvp.md", "docs/mcp.md"],
        ),
        (
            "prepare_session_brief alias or wrapper",
            [
                "Session prep can call dashboard or a thin `prepare_session_brief` that delegates to dashboard fields",
                "Documented in `docs/mcp.md`",
            ],
            ["docs/mvp.md"],
        ),
        (
            "Playtest: dashboard before session prompt",
            [
                "Extend `scripts/playtest-mcp.sh` with dashboard smoke call",
                "Dashboard output includes Missing Prince plot on seeded campaign",
            ],
            ["docs/playtest-notes.md"],
        ),
    ]
    for title, criteria, refs in items:
        n = create_issue(
            title, "v0.7.0", ["enhancement", "mvp", "mvp:7"],
            sub_body(
                f"As an **AI DM**, I want {title.lower()}, so I can assess campaign health before narrating.",
                criteria, refs,
            ),
        )
        subs.append((n, title))

    return create_issue(
        "Release v0.7.0 — Campaign dashboard MCP aggregate",
        "v0.7.0", ["release", "mvp", "mvp:7"],
        parent_body(
            "Single MCP read that aggregates open session, plots, events, pending approvals, and continuity warnings.",
            [
                "DM prep prompt answered from `get_campaign_dashboard` without multiple tool calls",
                "Merged to `develop` with tests green",
            ],
            subs,
        ),
    )


def create_v080() -> int:
    subs: list[tuple[int, str]] = []
    items = [
        (
            "Secrets entity type and dm_only storage",
            [
                "Store secrets with `visibility: dm_only`",
                "`create_secret` MCP write tool (propose flow)",
                "Secrets excluded from party-scoped context by default",
            ],
            ["docs/mvp.md"],
        ),
        (
            "Visibility filtering in context tools",
            [
                "`compile_scene_context` and dashboard omit dm_only facts for party prompts",
                "Optional `scope` argument: `dm` | `party` (default party)",
            ],
            ["docs/mvp.md", "docs/context-pipeline.md"],
        ),
        (
            "Blackport demo secret + spoiler regression test",
            [
                "Seed includes at least one dm_only secret (e.g. prince alive)",
                "Test asserts secret absent from party-scoped dashboard/context",
            ],
            ["docs/poc.md"],
        ),
    ]
    for title, criteria, refs in items:
        n = create_issue(
            title, "v0.8.0", ["enhancement", "mvp", "mvp:8"],
            sub_body(
                f"As a **DM**, I want {title.lower()}, so AI clients cannot leak hidden plot.",
                criteria, refs,
            ),
        )
        subs.append((n, title))

    return create_issue(
        "Release v0.8.0 — Secrets and visibility filtering",
        "v0.8.0", ["release", "mvp", "mvp:8"],
        parent_body(
            "Prevent accidental spoiler leakage while keeping dm_only canon in the store.",
            [
                "Party-scoped tools never return dm_only secrets in tests",
            ],
            subs,
        ),
    )


def create_v090() -> int:
    subs: list[tuple[int, str]] = []
    items = [
        (
            "Session workspace: notes and entity change tracking",
            [
                "Sessions store optional notes blob",
                "Track entities modified during open session",
            ],
            ["docs/mvp.md"],
        ),
        (
            "get_session MCP tool and session timeline",
            [
                "`get_session(session_id)` returns summary, events, pending updates linked to session",
                "Dashboard `open_session` links to full session workspace",
            ],
            ["docs/mvp.md", "docs/session-lifecycle.md"],
        ),
        (
            "End-session bundle improvements",
            [
                "End-session proposes grouped canon review queue items",
                "Session view lists events created during session",
            ],
            ["docs/mvp.md"],
        ),
    ]
    for title, criteria, refs in items:
        n = create_issue(
            title, "v0.9.0", ["enhancement", "mvp", "mvp:9"],
            sub_body(
                f"As a **DM**, I want {title.lower()}, so each play session is a reviewable workspace.",
                criteria, refs,
            ),
        )
        subs.append((n, title))

    return create_issue(
        "Release v0.9.0 — Session workspace enhancements",
        "v0.9.0", ["release", "mvp", "mvp:9"],
        parent_body(
            "First-class session objects with notes, timeline, and canon review linkage.",
            [
                "Closed session shows events and approved updates from that session",
            ],
            subs,
        ),
    )


def create_v100() -> int:
    subs: list[tuple[int, str]] = []
    items = [
        (
            "Hybrid search (keyword + embedding stub)",
            [
                "Search API accepts natural-language queries beyond SQL LIKE",
                "Embedding store interface with noop/local stub for MVP",
            ],
            ["docs/mvp.md"],
        ),
        (
            "Campaign import from Markdown",
            [
                "Import path for Markdown/Obsidian export",
                "AI-assisted entity extraction produces propose_world_update bundle",
            ],
            ["docs/mvp.md"],
        ),
        (
            "Permissions model (owner / DM / player)",
            [
                "Role field on campaign access (single-user MVP acceptable)",
                "Player role cannot call dm_only reads",
            ],
            ["docs/mvp.md"],
        ),
    ]
    for title, criteria, refs in items:
        n = create_issue(
            title, "v1.0.0", ["enhancement", "mvp", "mvp:10"],
            sub_body(
                f"As a **maintainer**, I want {title.lower()}, so WorldKeep scales to long campaigns.",
                criteria, refs,
            ),
        )
        subs.append((n, title))

    return create_issue(
        "Release v1.0.0 — MVP Phase 2 completion",
        "v1.0.0", ["release", "mvp", "mvp:10"],
        parent_body(
            "Search, import, and permissions — remaining Phase 2 items from roadmap.",
            [
                "MVP success metrics in `docs/mvp.md` §17 assessable on a 10+ session campaign",
            ],
            subs,
        ),
    )


def main() -> None:
    print("Ensuring MVP milestones and labels…")
    ensure_milestones()
    ensure_labels()

    print("Creating MVP release epics…")
    parents = {
        "v0.7.0": create_v070(),
        "v0.8.0": create_v080(),
        "v0.9.0": create_v090(),
        "v1.0.0": create_v100(),
    }
    for milestone, num in parents.items():
        print(f"{milestone} parent: #{num}")
    print("\nDone. Update HANDOFF.md with new issue numbers.")


if __name__ == "__main__":
    main()
