# GitHub setup notes

## Milestones

Create milestones `v0.0.0`, `v0.0.1`, `v0.1.0`, … when filing release parents:

```bash
cd ~/projects/prototyper/prototypes/__APP_NAME__
python3 scripts/create_github_issues.py   # labels + milestones only (no issues by default)
```

Or create milestones manually in GitHub repo Settings.

## Branch default

Use **`develop`** as integration branch:

```bash
git checkout -b develop
git push -u origin develop
```

Set default branch to `develop` in GitHub repo Settings.

## First PR pattern

1. Parent issue `v0.0.0` — planning docs + `AGENTS.md`
2. Sub-issue — repo shell
3. `gh issue develop <N> --name issue-<N>-<slug> --checkout --base develop`
