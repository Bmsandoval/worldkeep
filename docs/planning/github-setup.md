# GitHub setup notes

## Repo

- **Remote:** `git@github.com:Bmsandoval/worldkeep.git`
- **Default branch:** `develop` (no `main`)

## Bootstrap (one-time)

```bash
cd ~/projects/prototyper/prototypes/worldkeep
git checkout -b develop
git remote add origin git@github.com:Bmsandoval/worldkeep.git
git push -u origin develop
```

Set default branch to **`develop`** in GitHub repo Settings → Branches.

## Milestones and issues

```bash
python3 scripts/create_github_issues.py
```

Run once on a fresh repo. Updates [prototype-release-backlog.md](./prototype-release-backlog.md) issue numbers manually after creation.

## Sync workflow doc from factory

When canonical workflow changes:

```bash
~/projects/prototyper/scripts/sync-prototype-workflow.sh \
  ~/projects/prototyper/prototypes/worldkeep \
  Bmsandoval/worldkeep
```

## Repo About (suggested)

**Description:** `Prototype · MCP world memory for LLM-assisted RPG and fiction play`

**Topics:** `prototype`, `mcp`, `golang`, `sqlite`, `rpg`, `interactive-fiction`

## First implementation PR pattern

1. Parent issue `Release v0.1.0 — Prototype: Core MCP + SQLite`
2. Sub-issue — Go module + SQLite schema
3. `gh issue develop <N> --name issue-<N>-<slug> --checkout --base develop`
