#!/usr/bin/env bash
# v1.5 handoff acceptance — runs automated playtest tests.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT/mcp"
go test ./internal/store/ -run 'TestHandoff|TestSessionFloor' -count=1
go test ./internal/mcp/ -run TestHandoffPlaytestScenario -count=1
echo "Handoff playtest passed."
