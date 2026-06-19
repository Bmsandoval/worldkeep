#!/usr/bin/env python3
"""Create milestones and labels for a new prototype repo."""
from __future__ import annotations

import json
import subprocess
import sys

REPO = "__GITHUB_REPO__"

LABELS = {
    "release": ("Parent issue for a version release", "1D76DB"),
    "poc": ("PoC v0.x work", "F9D0C4"),
    "enhancement": ("New feature", "a2eeef"),
    "documentation": ("Documentation", "0075ca"),
    "planning": ("Planning and workflow", "FBCA04"),
    "tdd": ("Expect test-first delivery", "C2E0C6"),
    "bug": ("Something broken", "d73a4a"),
}

MILESTONES = [
    ("v0.0.0", "Planning and repository shell"),
    ("v0.0.1", "PHP foundation, PHPUnit, CI"),
    ("v0.1.0", "First domain vertical slice"),
]


def gh(*args: str) -> str:
    r = subprocess.run(["gh", *args], capture_output=True, text=True)
    if r.returncode != 0:
        raise RuntimeError(f"gh failed: {r.stderr.strip()}")
    return (r.stdout or "").strip()


def ensure_labels() -> None:
    existing = {
        line.split("\t", 1)[0]
        for line in gh("label", "list", "--repo", REPO, "--limit", "100").splitlines()
        if line
    }
    for name, (desc, color) in LABELS.items():
        if name not in existing:
            subprocess.run(
                ["gh", "label", "create", name, "--repo", REPO, "--description", desc, "--color", color],
                check=True,
            )


def ensure_milestones() -> None:
    titles = {m["title"] for m in json.loads(gh("api", f"repos/{REPO}/milestones", "--paginate"))}
    for title, desc in MILESTONES:
        if title not in titles:
            gh("api", f"repos/{REPO}/milestones", "-f", f"title={title}", "-f", f"description={desc}")


def main() -> None:
    if REPO.startswith("__"):
        print("Set REPO in this script after scaffolding (or re-run scaffold with --github-repo).", file=sys.stderr)
        sys.exit(1)
    ensure_labels()
    ensure_milestones()
    print(f"Labels and milestones ready on {REPO}")


if __name__ == "__main__":
    main()
