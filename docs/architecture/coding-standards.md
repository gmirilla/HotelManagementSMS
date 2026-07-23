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
- Feature tests assert query counts on list endpoints to catch N+1 regressions.

## Git

- Conventional commit-style subject lines (`feat:`, `fix:`, `chore:`, `test:`, `docs:`) recommended, not enforced by hook in v1.
- No direct commits of `.env`, credentials, or generated `vendor`/`node_modules`.

## Naming

- Singular, descriptive Action/Service class names as verbs (`CreateReservationAction`, not `ReservationManager`).
- Table names: snake_case plural. Model names: StudlyCase singular.
- Enum-like fixed value sets use native PHP `enum` (backed by string) under `app/Domain/<Module>/Enums/**`, not magic strings.
