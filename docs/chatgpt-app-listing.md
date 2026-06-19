# ChatGPT app listing — copy & positioning

Public-facing text for the ChatGPT Apps directory and connector onboarding. **User-facing tone** — describe value to players/DMs, not MCP internals.

Technical setup: [chatgpt-mcp-setup.md](./chatgpt-mcp-setup.md) · Production URL: `https://worldkeep.bsandoval.dev/mcp`

---

## Directory copy (approved)

### Subtitle (≤30 characters)

```text
D&D Campaign canon for AI DM
```

(28 characters)

### Description

```text
Your campaign deserves a memory that doesn't quit.

WorldKeep is the continuity layer for AI-assisted tabletop play. It remembers the people you've met, the places you've walked, the threads still burning — and treats what happens in your story as real. If your AI DM said Finn lost an eye in a dockside fight, that's canon now. No recap doc. No "wait, didn't we already establish that?"

When you kill the wrong person, insult the wrong faction, or let a secret slip, the world can respond like it meant it. Grudges stick. Rumors spread. The story moves forward instead of resetting every time you open a new chat.

Built for long-running D&D and narrative campaigns — not a map or dice app, but the part that makes your table feel like one continuous world.
```

---

## Product positioning (public story)

These principles govern **marketing copy**, ChatGPT listing text, and the direction for future UX — even where older MVP features still exist in code.

| Principle | Public story |
| ----------- | ------------ |
| **Automatic canon** | What the AI narrates in play is treated as campaign truth. No separate "approve canon" step for players. |
| **Consequences persist** | Deaths, grudges, secrets, and plot beats carry forward across sessions and chats. |
| **Rules are not table-negotiated** | Mechanics come from established reference (e.g. SRD via Open5e), not a group vote on house rules in the app narrative. |
| **WorldKeep remembers; AI reasons** | Storage and retrieval of campaign facts — not a VTT, dice roller, or full rules engine. |

### Not in the public pitch

- MCP tool names, "continuity engine" jargon, or explaining behavior to GPT/OpenAI reviewers
- Canon **approval queues** or DM sign-off flows as a selling point
- House-ruling / `record_ruling` as "your group decides the rules"

### Legacy MVP (code may still exist)

The browser **Approvals** UI and propose/commit MCP tools shipped in v1.2–v1.8. They are **not** part of the ChatGPT app story above. Deprecating or hiding them is a follow-up product task — see [roadmap.md](./roadmap.md).

---

## Changelog

| Date | Change |
| ---- | ------ |
| 2026-06-19 | Adopted subtitle + primary description; documented auto-canon and non-negotiated rules positioning |
