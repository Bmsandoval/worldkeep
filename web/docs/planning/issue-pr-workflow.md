# Issue and PR workflow

Short reference for __APP_TITLE__. **Full agent operating model:** [prototype-workflow.md](./prototype-workflow.md) (required reading).

Per-app [AGENTS.md](../../AGENTS.md) adds product-specific rules.

**Maintainer gates:** agents **open PRs and ask for review** — **do not merge** or **cut releases** unless explicitly asked.

## Branches

| Branch | Purpose |
|--------|---------|
| `develop` | Integration — squash-merge sub-issue PRs |
| `release-X-Y-Z` | Shipped minor/patch (e.g. `release-0-1-0` for `v0.1.0`) |

No `main` / `master` as the integration branch.

## Issue hierarchy

1. **Milestone** = target release (`v0.0.0`, `v0.1.0`, …)
2. **Parent release issue** — label `release`, checklist of subs
3. **Sub-issues** — one branch / one PR each

Every sub-issue and PR must use the **same milestone** as its parent.

## PR rules

First line:

```text
- Resolves __GITHUB_REPO__#<sub-issue-number>
```

- Target: `develop`
- Merge: **squash** (maintainer only)
- Branch: `issue-<N>-<short-slug>` via `gh issue develop`

## Test-driven development (TDD)

1. **Red** — Failing PHPUnit test for the acceptance criterion.
2. **Green** — Minimal code to pass.
3. **Refactor** — Clean up without changing behavior.

Run `make test` before opening a PR.

## CI

[`.github/workflows/ci.yml`](../../.github/workflows/ci.yml) runs on push/PR to `develop`: PHP 8.4, `composer test`.

## Writing conventions

| Artifact | Style |
|----------|--------|
| Sub-issue title | Short **user need** |
| Sub-issue body | User story + acceptance criteria + **Target release:** `v0.x.y` |
| PR title | `Issue-<N> - <technical change>` |

## Release (maintainer only)

```bash
git checkout develop && git pull
git checkout -b release-0-1-0
git push -u origin release-0-1-0
git tag -a v0.1.0 -m "v0.1.0"
git push origin v0.1.0
```

## Labels

| Label | Use |
|-------|-----|
| `release` | Parent issue |
| `poc` | PoC (`v0.x`) work |
| `enhancement` | Feature sub-issues |
| `documentation` | Docs |
| `planning` | Workflow, milestones |
| `tdd` | Test-first delivery |
