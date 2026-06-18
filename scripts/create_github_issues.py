#!/usr/bin/env python3
"""Create Worldkeep milestones and POC-aligned issues on a fresh repo.

If issues already exist from the obsolete bootstrap, run realign_github_issues.py instead.
"""
from __future__ import annotations

import subprocess
import sys
from pathlib import Path

REPO = "Bmsandoval/worldkeep"


def main() -> None:
    open_count = subprocess.run(
        ["gh", "issue", "list", "--repo", REPO, "--state", "open", "--limit", "1",
         "--json", "number", "-q", "length"],
        capture_output=True, text=True, check=True,
    ).stdout.strip()
    if open_count != "0":
        print(
            "Repo already has open issues — run scripts/realign_github_issues.py "
            "to close stale bootstrap issues and recreate the POC queue.",
            file=sys.stderr,
        )
        sys.exit(1)

    sys.path.insert(0, str(Path(__file__).resolve().parent))
    from realign_github_issues import create_all  # noqa: PLC0415

    create_all()
    print("\nDone. Update HANDOFF.md with new issue numbers.")


if __name__ == "__main__":
    main()
