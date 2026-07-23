# Enterprise Hotel Management System (HMS)

A multi-branch, production-grade Hotel Management System built on Laravel 13 — reservations, front desk, housekeeping, maintenance, restaurant/POS, inventory, accounting, HR, CRM/loyalty, events, and reporting, with a documented REST API.

This repository is being built incrementally. See [`docs/SRS.md`](docs/SRS.md) for the full functional/non-functional specification and [`docs/architecture/coding-standards.md`](docs/architecture/coding-standards.md) for the architecture and conventions every module follows.

## Stack

Laravel 13 · PHP 8.3+ (8.4+ recommended) · Livewire 4 · Alpine.js · TailwindCSS · MySQL 8+ · Redis · Laravel Horizon/Telescope · Spatie Permission/ActivityLog/MediaLibrary · Laravel Excel · DomPDF · Pest · Pint · Larastan · Rector.

## Getting Started

### Option A — Docker (recommended, matches production)

```bash
cp .env.example .env
docker compose build
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

The app is served at `http://localhost:8080` (see `APP_PORT` in `.env`). This starts `app` (PHP-FPM), `nginx`, `mysql`, `redis`, a `queue` worker, `horizon`, and the `scheduler`.

### Option B — Local PHP (Windows/macOS/Linux, no Docker)

Requires PHP 8.3+, Composer, and a MySQL 8+/Redis instance you provide (or point `DB_*`/`REDIS_*` in `.env` at a remote/dev instance; a local SQLite fallback also works for quick smoke-testing).

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
composer dev   # runs the app server, queue listener, log tail, and Vite together
```

> Note: `laravel/horizon` requires the `pcntl`/`posix` PHP extensions, which are POSIX-only. On Windows, use `php artisan queue:work` for local development; Horizon itself is intended to run in the Linux container (`docker compose up horizon`) or in production.

## Quality Gates

```bash
composer pint-test   # code style (Laravel Pint)
composer analyse      # static analysis (PHPStan/Larastan, level 8)
composer rector-dry    # refactoring suggestions (dry-run)
vendor/bin/pest --coverage --min=95   # test suite with coverage gate
```

All four run in CI on every push/PR — see [`.github/workflows/ci.yml`](.github/workflows/ci.yml).

## Project Documentation

| Document | Purpose |
|---|---|
| [`docs/SRS.md`](docs/SRS.md) | Functional & non-functional requirements (the authoritative spec) |
| [`docs/architecture/coding-standards.md`](docs/architecture/coding-standards.md) | Layering, naming, testing, and database conventions |

Further deliverables (ERD, API/OpenAPI docs, deployment guide, disaster recovery, etc.) are added incrementally as those modules are built — each will be linked here as it lands.

## Admin/Monitoring Dashboards

Telescope (`/telescope`) and Horizon (`/horizon`) are open to everyone in `local`; in any other environment, access is restricted to emails listed in `TELESCOPE_ALLOWED_EMAILS` / `HORIZON_ALLOWED_EMAILS` in `.env`.
