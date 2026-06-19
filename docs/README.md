# WorldKeep documentation

**Source of truth for product direction, architecture, and phased delivery.**

These docs supersede the initial bootstrap planning (`docs/planning/`, June 2026). GitHub issues were realigned 2026-06-17 to match [poc.md](./poc.md) §14 — see [HANDOFF.md](../HANDOFF.md) for issue numbers.

---

## Core documents

| Document | Purpose |
| -------- | ------- |
| [product-thesis.md](./product-thesis.md) | Why WorldKeep exists |
| [roadmap.md](./roadmap.md) | Active roadmap, backlog, icebox |
| [entity-model.md](./entity-model.md) | Canonical data model |
| [mcp.md](./mcp.md) | MCP interface |
| [session-lifecycle.md](./session-lifecycle.md) | Session workflow |
| [context-pipeline.md](./context-pipeline.md) | Context retrieval pipeline |

## Roadmap phases

| Document | Phase |
| -------- | ----- |
| [poc.md](./poc.md) | Proof of concept — **build this first** |
| [mvp.md](./mvp.md) | Campaign operating system |
| [web-ui.md](./web-ui.md) | Browser UI delivery plan (Phase 2.5) |
| [rest-api.md](./rest-api.md) | REST API routes (v1.1.0) |
| [deploy.md](./deploy.md) | Unified Docker / single-service hosting |
| [playtest-handoff.md](./playtest-handoff.md) | v1.5 seat handoff playtest |
| [participant-handoff.md](./participant-handoff.md) | Human ↔ AI seat cycling |
| [dndbeyond-integration.md](./dndbeyond-integration.md) | D&D Beyond MCP — characters, maps, optional sync |
| [open5e-integration.md](./open5e-integration.md) | **Open5e API — primary SRD rules reference** |
| [owlbear-integration.md](./owlbear-integration.md) | Owlbear VTT + extension — tactical maps (backlog) |
| [party-system.md](./party-system.md) | Persistent companions |
| [world-intel.md](./world-intel.md) | Campaign intelligence |
| [ruleset-engine.md](./ruleset-engine.md) | Custom rulesets (backlog) |
| [narrative-optimization.md](./narrative-optimization.md) | Player intelligence (icebox) |
| [future.md](./future.md) | Living world vision (backlog) |

## Process (not product spec)

| Document | Purpose |
| -------- | ------- |
| [workflow/prototype-workflow.md](./workflow/prototype-workflow.md) | Issue-driven dev process |
| [workflow/issue-pr-workflow.md](./workflow/issue-pr-workflow.md) | Issue and PR templates |

---

## Core principle

AI should reason. WorldKeep should remember. Neither should attempt to do both.
