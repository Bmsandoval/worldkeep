# Product phases — __APP_TITLE__

| Phase | Versioning | Goal |
|-------|------------|------|
| **Shell** | `v0.0.0` | Docs, `AGENTS.md`, repo conventions |
| **Foundation** | `v0.0.1` | CI, PHPUnit, health, auth |
| **PoC** | `v0.1.0`+ | First vertical slice of your domain |
| **MVP** | `v1.x` | Shippable product |
| **Beyond** | `v2+` | See [icebox.md](../icebox.md) |

**North star:** [product-vision.md](./product-vision.md)

**Naming:** Call spike work **PoC** (`poc` label). Reserve **MVP** for `v1.x`.

## PoC complete when

Your `v0.x` release epics are done and [product-vision.md](./product-vision.md) success criteria are met.

## MVP complete when

Items in [backlog.md](../backlog.md) are delivered under `v1.x`.

## Database strategy

| Phase | Engine | Purpose |
|-------|--------|---------|
| **PoC** (`v0.x`) | **SQLite** | Zero-setup local dev; tests use in-memory SQLite |
| **MVP** (`v1.x`) | **MySQL** | Production; `DB_CONNECTION=mysql` in `.env` |

**Rules:**

- Laravel migrations + Eloquent only — one path for both engines.
- Keep migrations portable (avoid engine-specific SQL).
- Run migrations and tests against **MySQL** at least once before MVP ships.
