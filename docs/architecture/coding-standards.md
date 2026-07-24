# Coding Standards & Conventions

Applies to all PHP/Blade/Livewire code in this repository. Enforced in CI via Pint + PHPStan; violations block merge.

## PHP

- `declare(strict_types=1);` at the top of every PHP file.
- Full type hints on all method parameters, return types, and class properties (no implicit `mixed` unless genuinely polymorphic).
- Laravel Pint (`pint.json`, based on the `laravel` preset) is the single source of truth for formatting — never hand-format against it.
- PHPStan at `level: 8` (see `phpstan.neon`), run via `composer analyse`.
- Final classes by default; only remove `final` when a class is deliberately designed for extension.

## Architecture Layers

| Layer | Location | Responsibility |
|---|---|---|
| Controllers | `app/Http/Controllers/**` | Thin HTTP boundary: validate (via Form Request), call one Action/Service, return a response/resource. No business logic. |
| Form Requests | `app/Http/Requests/**` | All input validation and authorization gating for a request. |
| API Resources | `app/Http/Resources/**` | Shape outbound JSON; never expose Eloquent models directly from API/Livewire. |
| Actions | `app/Domain/<Module>/Actions/**` | Single-purpose, invokable classes for one business operation (e.g., `CheckInGuestAction`). Preferred over fat services for discrete operations. |
| Services | `app/Domain/<Module>/Services/**` | Coordinate multiple actions/repositories for a use case that doesn't fit a single Action. |
| Repositories | `app/Domain/<Module>/Repositories/**` | Query encapsulation for complex/reused queries. Not required for simple CRUD — don't wrap Eloquent for its own sake. |
| DTOs | `app/Domain/<Module>/DTOs/**` | Typed data carriers between layers (readonly classes) where passing an array or raw model would be ambiguous. |
| Models | `app/Models/**` | Eloquent models, relationships, scopes, casts. No business logic beyond model-intrinsic behavior (accessors/scopes). |
| Policies | `app/Policies/**` | One policy per model governing authorization. |
| Events/Listeners | `app/Domain/<Module>/Events`, `Listeners` | Cross-cutting side effects (notifications, audit hooks) decoupled from the triggering action. |
| Observers | `app/Observers/**` | Model lifecycle hooks (e.g., auto-generating a folio number) — used sparingly, only for model-intrinsic invariants. |

Modules (`Reservation`, `FrontDesk`, `Housekeeping`, `Maintenance`, `Restaurant`, `Inventory`, `Accounting`, `HR`, `CRM`, `Events`, `Reporting`) live under `app/Domain/<Module>/` so each module can be read, tested, and reasoned about independently. Shared/cross-module concerns (money, date ranges, audit) live under `app/Domain/Shared/`.

## Database

- One migration per schema change; migrations are never edited after being merged to main (a new migration corrects a prior one).
- Every table has `id` (unsigned big int), relevant foreign keys with `constrained()->cascadeOnDelete()` or `restrictOnDelete()` as semantically correct, and timestamps.
- Soft deletes (`deleted_at`) on any table where historical/audit integrity matters (reservations, invoices, guests) — not applied blanket to every table.
- Money columns are `unsignedBigInteger` storing minor units (cents), never `float`/`decimal` for currency math.
- Every foreign key and every column used in a `WHERE`/`ORDER BY` on a table expected to grow is indexed.

## Testing

- Pest is the primary testing syntax; PHPUnit remains available for compatibility.
- Every Action/Service has a Unit test; every user-facing flow has a Feature test covering happy path, validation failure, and authorization failure.
- Every Livewire component gets a full-page render test in both empty and populated states — Action/Policy unit tests alone don't catch a `#[Computed]` method whose return type doesn't match what it actually returns at runtime, which has caused real 500s in this project (see `RestaurantLivewireRenderTest.php`'s docblock for the original incident).
- Feature tests assert query counts on list endpoints to catch N+1 regressions (`countQueries()` helper in `tests/Pest.php`; see `tests/Feature/Performance/QueryCountTest.php`). Always warm caches (permissions/roles) with one throwaway call before the measured comparison — Spatie's permission cache being cold on the first call and warm on the second otherwise produces a false N+1 failure that has nothing to do with eager loading.
- `tests/Unit/ArchitectureTest.php` runs Pest's `php`/`security` presets plus project-specific layering rules (domain layer stays framework-agnostic, models don't touch HTTP, naming conventions) on every `php artisan test` run. Needs `memory_limit` above the CLI default of 128M to parse the full codebase — set to 512M in this project's `php.ini`.
- `tests/Feature/Security/` holds authorization-boundary regression tests: branch isolation, tenant isolation, mass-assignment protection, and rate limiting. When a Livewire component takes a foreign-key ID from a client-mutable property or method argument (a picker scoped to the active branch in the UI), always re-verify branch/tenant ownership server-side before mutating — the picker only *displays* a scoped list, it doesn't *enforce* one. This exact IDOR shape was found and fixed six times during the Deliverable 9 audit (`AuthorizationBoundaryTest.php`'s docblock has the full list); when adding a new picker-backed mutation, add a matching regression test.
- Real-browser smoke tests (`tests/Feature/Browser/`) use Pest v4 browser testing (Playwright-backed). They require the Chromium binary (`npx playwright install chromium`) and are wrapped in the `browserTest()` helper (`tests/Pest.php`) so the suite skips with a clear reason — rather than failing outright — wherever that binary or the underlying WebSocket bridge isn't available, instead of silently disappearing.
- Load tests against NFR-PERF-001's 500ms-at-p95 budget live in `tests/Performance/` (Pest Stressless, k6-backed) and are deliberately **not** part of the default suite — `phpunit.xml` doesn't discover that directory. Run them explicitly against a real running server: `vendor/bin/pest tests/Performance` (see the file's docblock for setup).
- **Known local-environment limitation (Windows dev machine, not CI):** PHPStan/Larastan crashes silently on this machine regardless of configuration (confirmed environment-specific, not a project config issue); Pest browser testing's Playwright server starts successfully as a standalone process but the WebSocket bridge PHP uses to connect to it fails to connect from within this specific sandboxed shell even with the Chromium binary fully installed; large one-time tool downloads (Playwright's ~180MB Chromium, Stressless's k6 binary) are unreliable over this environment's network and may need retrying outside it. None of these reflect a problem with the project's code or configuration — rely on CI (Linux) for static analysis and browser/load testing until resolved.

## Git

- Conventional commit-style subject lines (`feat:`, `fix:`, `chore:`, `test:`, `docs:`) recommended, not enforced by hook in v1.
- No direct commits of `.env`, credentials, or generated `vendor`/`node_modules`.

## Naming

- Singular, descriptive Action/Service class names as verbs (`CreateReservationAction`, not `ReservationManager`).
- Table names: snake_case plural. Model names: StudlyCase singular.
- Enum-like fixed value sets use native PHP `enum` (backed by string) under `app/Domain/<Module>/Enums/**`, not magic strings.
