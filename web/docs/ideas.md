# Ideas — suggestions, pivots, experiments

Informal list. **Not** the work queue — see GitHub issues. Promote winners to issues or [backlog.md](./backlog.md).

**Active direction:** [planning/product-vision.md](./planning/product-vision.md)

## Product ideas

| Idea | Notes |
|------|--------|
| **Domain placeholder dashboard** | Replace `/app` hero with 3 cards tied to vision (metrics TBD) |
| **Feature flag stub** | `config/features.php` + blade `@feature` — no vendor yet |
| **Audit log table** | `activity_log` migration only; wire on first real model |

## Pivots to watch

| Pivot | When to consider |
|-------|------------------|
| **API-first from day one** | JSON `/api/*` beside Blade if mobile client planned |
| **Single-tenant only** | Drop teams/RBAC from scaffold script for simpler PoCs |
| **Boring CRUD vertical** | Pick one noun (invoices, assets) and rename kit via script |
