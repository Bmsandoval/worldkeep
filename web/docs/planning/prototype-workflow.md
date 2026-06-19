# Prototype agent workflow

Shared **planning-first, issue-driven** process for all PoCs spawned from [Prototyper](../AGENTS.md). Each app copies this file to `docs/planning/prototype-workflow.md` with its repo slug substituted.

**App repo:** `__GITHUB_REPO__` · **Integration branch:** `develop` (no `main`)

Per-app templates and examples: [issue-pr-workflow.md](./planning/issue-pr-workflow.md) in each repo (Laravel kit ships a stub).

---

## Sources of truth

| Priority | Source | Use it for |
|----------|--------|------------|
| **1 — Work queue** | **GitHub issues** (prioritized with maintainer) | **What to build right now** — scope, acceptance criteria |
| **2 — Strategy** | **`docs/planning/`** (+ backlog, icebox, ideas) | Why, phases, constraints |
| **3 — Process** | **`AGENTS.md`** + this file | How to branch, PR, test, release |

**Rules**

- **Never merge a pull request** unless the maintainer **explicitly** asks (e.g. “merge the PR”, “LGTM merge”). After opening a PR: report the URL, what was tested, and **ask for review**.
- **Never cut a release** (`release-*` branch, tag, `gh release create`) unless explicitly asked. You may **recommend** a release when criteria look met.
- **Do not implement from planning docs alone.** Every change ties to an **open, agreed sub-issue**. If planning implies new work, **create or propose an issue** — do not expand scope silently.
- **Before coding:** read the **active sub-issue**; skim planning docs only for context.
- **After shipping:** close/link the issue; update planning docs only when strategy materially changes.
- Issues win on scope conflicts; planning docs win on long-term direction.

---

## Agent operating model

**On every new session, start in the planning phase.** Do not jump to application code until the work queue is clear.

### 1. Planning phase (first)

1. **Read strategy** — `product-phases.md`, `product-vision.md`, and any doc relevant to the goal.
2. **Read the queue** — open milestones, parent release issues, sub-issues (`gh issue list --repo __GITHUB_REPO__`).
3. **Align with the maintainer** — confirm milestone and sub-issue for this session. Create milestone/parent if missing.
4. **Break work into issues** — parent release issue + sub-issues with acceptance criteria. Use [issue-pr-workflow.md](./planning/issue-pr-workflow.md) templates.

Planning docs = *what could be built*; **GitHub issues = what we are building now**.

### 2. Create and manage issues (ongoing)

| Action | When |
|--------|------|
| **Create milestone** | Starting a new minor version (e.g. `v0.1.0`) |
| **Create parent release issue** | New release batch; link sub-issues in GitHub |
| **Create sub-issues** | Each implementable slice, bug found in testing, scope split |
| **Update parent checklist** | Check off sub-issues as they merge to `develop` |
| **Close parent** | When release criteria met (maintainer cuts tag) |

Use `gh issue create`, milestones, and labels. **Do not** implement features that exist only in planning until there is a **prioritized sub-issue**.

### 3. Implementation phase (after planning)

Only after an **active sub-issue** is agreed:

1. `gh issue develop <N> --name issue-<N>-<slug> --checkout --base develop` (**before** the first real commit)
2. Optional bootstrap: empty commit + push to link **Development** early
3. Implement **only** that sub-issue’s acceptance criteria (TDD when practical)
4. Open PR to `develop` with `- Resolves __GITHUB_REPO__#<N>` as the **first line**
5. Ask for review — **do not merge** unless explicitly approved

### New session quick start

```
1. Read docs/planning/ + list open issues
2. Confirm milestone / parent / next sub-issue with maintainer
3. Create or refine issues if the queue is missing or stale
4. Branch → code → PR for one sub-issue at a time
```

---

## Issue hierarchy

| Level | Purpose | PRs? |
|-------|---------|------|
| **Milestone** | Target version (`v0.1.0`, `v1.0.0`, …) | — |
| **Parent release issue** | Release scope + checklist; label `release` | **No** |
| **Sub-issue** | One deliverable slice — day-to-day work | **Yes** — one per branch/PR |

**Patch vs minor**

| Type | Example | When |
|------|---------|------|
| **Patch** | `v0.0.1` | Small increment on a patch line | 
| **Minor** | `v0.1.0`, `v1.0.0` | Thin user-visible slice per `product-phases.md` |

Do not label patch work as a minor version unless the maintainer says so.

**Bugs found while testing** → **new sub-issue** under the same parent; never bundle on another sub-issue’s branch.

---

## Branches and merges

**No `main` or `master`.** Use **`develop`** + **`release-X-Y-Z`** branches.

| Branch | Pattern | Purpose |
|--------|---------|---------|
| `develop` | — | Integration — all sub-issue PRs squash-merge here |
| `release-X-Y-Z` | e.g. `release-0-1-0` | Shipped version; hotfixes only after cut |

**Feature / sub-issue PRs → `develop`**

- Branch: `issue-<N>-<kebab-slug>` (technical slug, not issue title)
- PR title: `Issue-<N> - <engineering summary>`
- **Squash merge** into `develop` (maintainer only)

**Cutting a release (maintainer-requested only)**

```bash
git checkout develop && git pull
git checkout -b release-0-1-0
git push -u origin release-0-1-0
git tag -a v0.1.0 -m "v0.1.0 — …"
git push origin v0.1.0
# optional: gh release create …
```

**Hotfixes on a shipped release**

1. Branch from **`release-X-Y-Z`**, not `develop`
2. PR into that release branch; **squash merge**
3. **Backmerge** release → `develop` with a **regular merge** (not squash)
4. Tag patch on release branch if applicable (`v0.1.1`)

| Work | PR base | Merge |
|------|---------|-------|
| Sub-issue / feature | `develop` | Squash |
| Hotfix on shipped release | `release-X-Y-Z` | Squash |
| Backmerge after hotfix | `develop` | Regular merge |

---

## Link branches and PRs (Development panel)

GitHub **Development** on the **sub-issue** must show the branch and/or PR.

1. `git checkout develop && git pull`
2. `gh issue develop <N> --name issue-<N>-<slug> --checkout --base develop`
3. Optional early link:

```bash
git commit --allow-empty -m "Start issue-<N>-<slug>"
git push -u origin HEAD
```

4. Implement, push, open PR; verify Development panel

**Never** use GraphQL `createLinkedBranch` on an existing branch — GitHub creates auto-named branches from the issue title. Delete stale `issue-<N>-*` and any `N-add-…` auto branches after merge.

**Parent release issues** do not get branches — only **sub-issues**.

---

## Pull requests

**First line (required):**

```text
- Resolves __GITHUB_REPO__#<sub-issue-number>
```

Use a list item (`-` prefix) so GitHub unfurls the issue. Do not duplicate with full URLs or an **Issues** section.

**Body skeleton:**

```markdown
- Resolves __GITHUB_REPO__#<sub>

## Summary

<Problem in 1–2 sentences.>
<Approach in 1–2 sentences.>

## Changes

- ...

## Test plan

- [ ] ...
```

**PR checklist**

- [ ] Targets `develop` (unless hotfix)
- [ ] Same **milestone** and **labels** as sub-issue
- [ ] Body has **no** Cursor / “Made with” / AI tool footers (`gh pr view` to verify)
- [ ] Ask maintainer for review — **do not** `gh pr merge` unprompted

---

## Test-driven development

When the stack supports it (PHPUnit, `go test`, etc.):

1. **Red** — failing test for acceptance criterion
2. **Green** — minimal implementation
3. **Refactor** — clean up without behavior change

Run the project test command before opening a PR (`make test`, `go test ./...`, etc.).

---

## Labels and milestones

| Item | Milestone | Labels |
|------|-----------|--------|
| Parent release | Minor version | `release` |
| Sub-issue | Same as parent | `enhancement`, `documentation`, `bug`, `tdd`, … |
| PR | Same as sub-issue | Match sub-issue (omit stage labels on PR if preferred) |

```bash
gh issue list --repo __GITHUB_REPO__ --state open
gh api repos/__GITHUB_REPO__/milestones -f title="v0.1.0" -f description="…"
```

---

## Planning documents

Agent-generated planning artifacts live under **`docs/planning/`** — not repo root or source trees.

Planning informs issue writing; **issues drive implementation**. Do not implement backlog, icebox, or platform scope without a prioritized issue.

---

## No AI branding in artifacts

Do not add “Made with Cursor”, co-author trailers, or “AI-assisted” disclaimers to issues, PRs, commits, or release notes. GitHub may append IDE footers — remove them before merge.

---

## Agent checklist

**Planning (before code)**

- [ ] Read relevant `docs/planning/`
- [ ] List open issues; confirm milestone + parent + next sub-issue
- [ ] Create/update milestone, parent, sub-issues if queue is incomplete

**Before coding**

- [ ] Active sub-issue agreed with maintainer
- [ ] `gh issue develop <N> --name issue-<N>-<slug> --checkout --base develop`

**Before opening PR**

- [ ] Changes map only to that sub-issue
- [ ] Tests run green
- [ ] First line: `- Resolves __GITHUB_REPO__#<N>`

**After PR**

- [ ] Share PR URL + test results; ask for review
- [ ] After merge: delete stale remote branches for that issue

**Release (maintainer only)**

- [ ] All sub-issues merged and tested on `develop`
- [ ] Wait for explicit approval before cut/tag/`gh release create`
