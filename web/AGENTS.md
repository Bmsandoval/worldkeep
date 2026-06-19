# Agent instructions — __APP_TITLE__

**Repo:** `__GITHUB_REPO__` · **Integration branch:** `develop` (no `main`) · **Remote:** `git@github.com:__GITHUB_REPO__.git`

## Session handoff (read every new session)

### Where we are

| Item | Status |
|------|--------|
| **Planning shell (v0.0.0)** | File when first PR lands — docs + `AGENTS.md` |
| **Application code** | Laravel shell: health, session auth, `/app` placeholder |
| **Product direction** | [product-vision.md](./docs/planning/product-vision.md) |
| **Work queue** | GitHub issues — one sub-issue per PR |

### What to build next

Work **one sub-issue per PR**. Confirm milestone + sub-issue with the maintainer before coding.

### Product rules

- **North star** — [product-vision.md](./docs/planning/product-vision.md)
- **PoC ≠ MVP** — do not implement [backlog.md](./docs/backlog.md) or [icebox.md](./docs/icebox.md) without filed issues
- **Scope** — ideas in [ideas.md](./docs/ideas.md) need issues before code

### Commands

```bash
make help
make setup
make db-reset
make test
make serve         # http://127.0.0.1:8000/app
```

---

## Development workflow (required)

Follow **[docs/planning/prototype-workflow.md](./docs/planning/prototype-workflow.md)** — shared planning-first, issue-driven process (sources of truth, milestones, parent/sub-issues, `gh issue develop`, PR format, releases).

Templates and label reference: [docs/planning/issue-pr-workflow.md](./docs/planning/issue-pr-workflow.md).

```bash
gh issue list --repo __GITHUB_REPO__ --state open
```

**Maintainer gates:** never merge PRs or cut releases unless explicitly asked. Never implement from docs/backlog/ideas without an open sub-issue.

### Implementation (TDD when possible)

1. `gh issue develop <N> --name issue-<N>-<slug> --checkout --base develop`
2. Failing PHPUnit test for acceptance criteria
3. Implement until green; `make test`
4. PR to `develop`: first line `- Resolves __GITHUB_REPO__#<N>`
5. Ask for review — **do not merge**

---

## Documentation map

| Path | Content |
|------|---------|
| [docs/planning/prototype-workflow.md](./docs/planning/prototype-workflow.md) | **Process** — planning, issues, branches, PRs |
| [docs/planning/product-vision.md](./docs/planning/product-vision.md) | North star |
| [docs/planning/product-phases.md](./docs/planning/product-phases.md) | PoC (`v0.x`) vs MVP (`v1.x`) |
| [docs/planning/issue-pr-workflow.md](./docs/planning/issue-pr-workflow.md) | Issue/PR templates |
| [docs/planning/poc-release-index.md](./docs/planning/poc-release-index.md) | Release epic index |
| [docs/backlog.md](./docs/backlog.md) | PoC → MVP (not yet issued) |
| [docs/icebox.md](./docs/icebox.md) | Far future |
| [docs/ideas.md](./docs/ideas.md) | Informal suggestions |

## Stack (PoC)

| Area | Choice |
|------|--------|
| Backend | **Laravel 13** (PHP 8.4+) |
| DB (PoC) | **SQLite** — `database/database.sqlite` |
| DB (MVP) | **MySQL** — same migrations |
| Auth | Session + JSON API for same user model |
| Tests | **PHPUnit** — TDD for domain and HTTP |
| UI | Blade at `/app` — Bootstrap + League Spartan + Phosphor |

## Factory reference

Canonical workflow source: [Prototyper `docs/prototype-workflow.md`](https://github.com/Bmsandoval/prototyper/blob/main/docs/prototype-workflow.md). Re-sync with `prototyper/scripts/sync-prototype-workflow.sh` after workflow updates.
