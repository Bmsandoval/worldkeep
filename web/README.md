# WorldKeep

__APP_TAGLINE__

## Documentation & work queue

| | |
|--|--|
| **Vision** | [docs/planning/product-vision.md](./docs/planning/product-vision.md) |
| **Phases** | [docs/planning/product-phases.md](./docs/planning/product-phases.md) |
| **Build now** | [GitHub issues](https://github.com/__GITHUB_REPO__/issues) |
| **Agent rules** | [AGENTS.md](./AGENTS.md) |
| **All docs** | [docs/README.md](./docs/README.md) |
| **PoC → MVP** | [docs/backlog.md](./docs/backlog.md) |
| **Future** | [docs/icebox.md](./docs/icebox.md) |
| **Ideas** | [docs/ideas.md](./docs/ideas.md) |

## Stack (PoC)

**Laravel** (PHP), **SQLite** for PoC (MySQL at MVP), PHPUnit (TDD), Blade UI at `/app`.

Design system: Bootstrap 5, League Spartan, Phosphor icons, primary `#212447` / secondary `#68ADB7`.

## Quick start

```bash
make setup
make test
make serve    # → http://127.0.0.1:8000/app
```

`make help` lists all targets.

<details>
<summary>Manual setup (without Make)</summary>

```bash
cp .env.example .env
composer install
php artisan key:generate
touch database/database.sqlite
php artisan migrate
composer test
php artisan serve
```

</details>

## API (session cookie)

```bash
curl -c cookies.txt -X POST http://127.0.0.1:8000/register \
  -H 'Content-Type: application/json' \
  -d '{"name":"Alex","email":"alex@example.com","password":"password123"}'

curl -b cookies.txt http://127.0.0.1:8000/me
```

## License

TBD.
