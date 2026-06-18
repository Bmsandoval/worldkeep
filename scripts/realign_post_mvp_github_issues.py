#!/usr/bin/env python3
"""Create post-MVP epics: Web UI (v1.1–v1.3) and participant handoff (v1.4–v1.6)."""
from __future__ import annotations

import subprocess

REPO = "Bmsandoval/worldkeep"

MILESTONES = [
    ("v1.1.0", "Web UI — REST API foundation"),
    ("v1.2.0", "Web UI — Minimal admin (dashboard + approval queue)"),
    ("v1.3.0", "Web UI — World browser + session timeline"),
    ("v1.4.0", "Campaign seats — schema and MCP"),
    ("v1.5.0", "Participant handoff — human ↔ AI seat cycling"),
    ("v1.6.0", "Web UI — Seat management and invites"),
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
        ("ui", "Web UI and REST API", "0E8A16"),
        ("multiplayer", "Seats, handoff, invites", "F9A825"),
    ]:
        if name not in existing:
            subprocess.run(
                ["gh", "label", "create", name, "--repo", REPO,
                 "--description", desc, "--color", color],
                check=True,
            )
    for stage in ("11", "12", "13", "14", "15", "16"):
        name = f"post-mvp:{stage}"
        if name not in existing:
            subprocess.run(
                ["gh", "label", "create", name, "--repo", REPO,
                 "--description", f"Post-MVP stage {stage}", "--color", "0E8A16"],
                check=True,
            )


def sub_body(story: str, criteria: list[str], refs: list[str]) -> str:
    lines = ["## User story", "", story, "", "## Acceptance criteria", ""]
    for c in criteria:
        lines.append(f"- [ ] {c}")
    lines += ["", "## Planning refs", ""]
    for ref in refs:
        lines.append(f"- [{ref}]({ref})")
    return "\n".join(lines)


def parent_body(summary: str, criteria: list[str], subs: list[tuple[int, str]], ref: str) -> str:
    lines = ["## Summary", "", summary, "", "## Sub-issues", ""]
    for num, title in subs:
        lines.append(f"- [ ] #{num} — {title}")
    lines += ["", "## Release acceptance criteria", ""]
    for c in criteria:
        lines.append(f"- [ ] {c}")
    lines += ["", "## Planning refs", "", f"- [{ref}]({ref})"]
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


def create_v110() -> int:
    subs: list[tuple[int, str]] = []
    items = [
        (
            "REST API package mirroring core MCP read/write",
            [
                "HTTP routes: dashboard, entities, search, pending updates, commit/reject",
                "Shares `internal/store` with MCP — no duplicate canon logic",
                "OpenAPI or static route doc checked into repo",
            ],
            ["docs/web-ui.md"],
        ),
        (
            "Local auth stub for single-campaign prototype",
            [
                "Session cookie or bearer token for local dev",
                "Maps to existing campaign_roles (owner/dm/player)",
            ],
            ["docs/web-ui.md", "docs/mvp.md"],
        ),
    ]
    for title, criteria, refs in items:
        n = create_issue(
            title, "v1.1.0", ["enhancement", "ui", "post-mvp:11"],
            sub_body(
                f"As a **maintainer**, I want {title.lower()}, so the web UI has a backend.",
                criteria, refs,
            ),
        )
        subs.append((n, title))
    return create_issue(
        "Release v1.1.0 — Web UI REST API foundation",
        "v1.1.0", ["release", "ui", "post-mvp:11"],
        parent_body(
            "HTTP API layer for dashboard, canon queue, and entities — prerequisite for any browser UI.",
            ["API integration tests green alongside `go test ./...`"],
            subs, "docs/web-ui.md",
        ),
    )


def create_v120() -> int:
    subs: list[tuple[int, str]] = []
    items = [
        (
            "Campaign dashboard page",
            [
                "Cards: active plots, open session, pending count, continuity warnings",
                "Data from REST dashboard endpoint (parity with `get_campaign_dashboard`)",
            ],
            ["docs/web-ui.md", "docs/mvp.md"],
        ),
        (
            "Canon approval queue page",
            [
                "List pending updates with conflict warnings",
                "Commit and reject actions call REST endpoints",
            ],
            ["docs/web-ui.md"],
        ),
    ]
    for title, criteria, refs in items:
        n = create_issue(
            title, "v1.2.0", ["enhancement", "ui", "post-mvp:12"],
            sub_body(
                f"As a **DM**, I want {title.lower()}, so I can run canon without MCP.",
                criteria, refs,
            ),
        )
        subs.append((n, title))
    return create_issue(
        "Release v1.2.0 — First browser UI (dashboard + approvals)",
        "v1.2.0", ["release", "ui", "post-mvp:12"],
        parent_body(
            "**First web UI milestone** — minimal admin for session prep and canon review.",
            ["DM completes approve/reject flow entirely in browser on seeded Blackport"],
            subs, "docs/web-ui.md",
        ),
    )


def create_v130() -> int:
    subs: list[tuple[int, str]] = []
    items = [
        (
            "World browser (entity tabs)",
            [
                "Tabs or filters: NPCs, locations, factions, plots, events",
                "Entity detail view read-only; link to approval queue for edits",
            ],
            ["docs/web-ui.md", "docs/mvp.md"],
        ),
        (
            "Session timeline view",
            [
                "Shows session summary, events, modified entities (parity with `get_session`)",
                "Lists pending updates proposed during session",
            ],
            ["docs/web-ui.md", "docs/session-lifecycle.md"],
        ),
    ]
    for title, criteria, refs in items:
        n = create_issue(
            title, "v1.3.0", ["enhancement", "ui", "post-mvp:13"],
            sub_body(
                f"As a **DM**, I want {title.lower()}, so I can audit campaign history visually.",
                criteria, refs,
            ),
        )
        subs.append((n, title))
    return create_issue(
        "Release v1.3.0 — World browser + session timeline UI",
        "v1.3.0", ["release", "ui", "post-mvp:13"],
        parent_body(
            "Read-heavy UI surfaces from MVP spec §16 not yet in browser.",
            ["World browser and session view usable on a campaign with 3+ closed sessions"],
            subs, "docs/web-ui.md",
        ),
    )


def create_v140() -> int:
    subs: list[tuple[int, str]] = []
    items = [
        (
            "Campaign seats schema (dm + player slots)",
            [
                "Tables: campaign_seats (seat_type, controller, actor_id, user ref, status)",
                "Migration v3; Blackport demo: AI DM + 2 AI player seats",
            ],
            ["docs/participant-handoff.md"],
        ),
        (
            "MCP: list_campaign_seats and assign_seat_controller",
            [
                "`list_campaign_seats`, `get_seat`, `create_player_seat`, `assign_seat_controller`",
                "Tests cover solo AI DM + AI companions configuration",
            ],
            ["docs/participant-handoff.md", "docs/mcp.md"],
        ),
    ]
    for title, criteria, refs in items:
        n = create_issue(
            title, "v1.4.0", ["enhancement", "multiplayer", "post-mvp:14"],
            sub_body(
                f"As a **host**, I want {title.lower()}, so the campaign knows who controls each role.",
                criteria, refs,
            ),
        )
        subs.append((n, title))
    return create_issue(
        "Release v1.4.0 — Campaign seats model",
        "v1.4.0", ["release", "multiplayer", "post-mvp:14"],
        parent_body(
            "Foundation for human/AI control of DM and party member seats.",
            ["Seats visible via MCP on seeded demo; controller human|ai persisted"],
            subs, "docs/participant-handoff.md",
        ),
    )


def create_v150() -> int:
    subs: list[tuple[int, str]] = []
    items = [
        (
            "handoff_seat and release_seat_to_ai MCP tools",
            [
                "`handoff_seat(seat_id, controller, user_id?, reason?)` with audit event",
                "`release_seat_to_ai` shorthand; actor profile unchanged on handoff",
            ],
            ["docs/participant-handoff.md"],
        ),
        (
            "Session floor state for mixed human/AI table",
            [
                "Store floor_seat_id + party_beat queue on open session",
                "MCP: get_session_floor; docs for player-led vs party beat",
            ],
            ["docs/participant-handoff.md", "docs/party-system.md"],
        ),
        (
            "Handoff playtest: solo AI → friend joins companion → leaves to AI",
            [
                "Script or doc scenario: AI DM + AI Lia → human Lia → AI Lia again",
                "Canon and personality continuity asserted in tests",
            ],
            ["docs/participant-handoff.md"],
        ),
    ]
    for title, criteria, refs in items:
        n = create_issue(
            title, "v1.5.0", ["enhancement", "multiplayer", "post-mvp:15"],
            sub_body(
                f"As a **player**, I want {title.lower()}, so friends can join or leave without resetting the campaign.",
                criteria, refs,
            ),
        )
        subs.append((n, title))
    return create_issue(
        "Release v1.5.0 — Participant handoff (human ↔ AI)",
        "v1.5.0", ["release", "multiplayer", "post-mvp:15"],
        parent_body(
            "DM and party seats cycle between human and AI controllers mid-campaign.",
            [
                "Demo scenario in docs/participant-handoff.md §8 passes via MCP playtest",
                "Human DM takeover can commit canon; released player seat resumes AI voice",
            ],
            subs, "docs/participant-handoff.md",
        ),
    )


def create_v160() -> int:
    subs: list[tuple[int, str]] = []
    items = [
        (
            "Seat management UI (list, assign, handoff)",
            [
                "Campaign settings: seats table with controller badges (human/AI)",
                "Handoff and release-to-AI actions with confirmation",
            ],
            ["docs/participant-handoff.md", "docs/web-ui.md"],
        ),
        (
            "Invite link / join flow for player seats",
            [
                "Generate invite token tied to vacant or AI player seat",
                "Join assigns human controller + maps to campaign player role",
            ],
            ["docs/participant-handoff.md"],
        ),
    ]
    for title, criteria, refs in items:
        n = create_issue(
            title, "v1.6.0", ["enhancement", "ui", "multiplayer", "post-mvp:16"],
            sub_body(
                f"As a **host**, I want {title.lower()}, so friends can join from the browser.",
                criteria, refs,
            ),
        )
        subs.append((n, title))
    return create_issue(
        "Release v1.6.0 — Web UI seat management",
        "v1.6.0", ["release", "ui", "multiplayer", "post-mvp:16"],
        parent_body(
            "Browser flows for invite, join, handoff, and release-to-AI.",
            ["Friend joins Lia seat via UI; host sees controller change on dashboard"],
            subs, "docs/web-ui.md",
        ),
    )


def main() -> None:
    print("Ensuring post-MVP milestones and labels…")
    ensure_milestones()
    ensure_labels()
    print("Creating Web UI + participant handoff epics…")
    parents = {
        "v1.1.0": create_v110(),
        "v1.2.0": create_v120(),
        "v1.3.0": create_v130(),
        "v1.4.0": create_v140(),
        "v1.5.0": create_v150(),
        "v1.6.0": create_v160(),
    }
    for milestone, num in parents.items():
        print(f"{milestone} parent: #{num}")
    print("\nDone. Update HANDOFF.md with new issue numbers.")


if __name__ == "__main__":
    main()
