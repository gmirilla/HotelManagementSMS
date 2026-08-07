# Recommendations & Implementation Status Tracker

**Purpose:** Track this codebase's status against the full "Enterprise Hotel Management Software (Laravel 13)" specification, and record concrete recommendations for closing gaps.

**Last reviewed:** 2026-08-04
**Legend:** ✅ Done · 🟡 Partial · ❌ Missing · 🔜 Not started

> This document is a living tracker. Update status and check off recommendations as work lands. Do not re-derive "what exists" from scratch each time — update this file instead, and use `git log`/`git blame` for change history.

---

## 0. Executive Summary

The codebase is a genuine Laravel 13 build using a **Domain-Driven Design layout** (`app/Domain/{Module}/{Actions,Enums,Support,...}`) rather than the classic Repository/Service/DTO layout implied by the prompt — this is an intentional, reasonable architectural substitution, not a gap, but is called out because the spec explicitly asks for Repository Pattern/DTOs. Almost every hotel business module (Reservations, Front Desk, Guests, Housekeeping, Maintenance, Restaurant/POS, Inventory, Procurement, Accounting, HR, CRM, Loyalty, Events, Reporting) has real models, migrations, actions, Livewire UI, policies, and feature tests — this is far beyond a prototype.

The biggest real gaps are **not UI/CRUD**, they are **cross-cutting infrastructure**: no async Jobs, no Notifications/Mailables, no Broadcasting, incomplete payment gateways (Stripe/PayPal/Flutterwave), unused Excel/PDF export packages, and API coverage limited to ~5 of ~20 modules.

| Area | Status |
|---|---|
| Core hotel operations modules | ✅ Strong |
| Auth & Authorization (RBAC, MFA, password policy) | ✅ Strong |
| Testing (Feature/Unit, coverage gate) | ✅ Strong |
| Database schema & seeders | ✅ Strong |
| CI & Docker | 🟡 Partial (CI yes, CD/deploy no) |
| Notifications (Email/SMS/WhatsApp/push/in-app) | ❌ Missing |
| Async jobs / queue usage | ❌ Missing (infra present, zero Job classes) |
| Broadcasting / real-time | ❌ Missing |
| REST API breadth | 🟡 Partial (5 of ~20 modules) |
| Payment gateways beyond Paystack | 🟡 Partial |
| Reporting exports (PDF/Excel/CSV) | 🟡 Partial (packages installed, unused) |
| Documentation (deployment/DR/admin/user guides) | 🟡 Partial |

---

## 1. Technology Stack

| Item | Status | Notes |
|---|---|---|
| Laravel 13 | ✅ | `laravel/framework ^13.8` |
| PHP 8.4+ | 🟡 | `composer.json` requires `^8.3`, not `^8.4` — spec asks for 8.4+ |
| Blade Templates | ✅ | |
| Livewire 4 | ✅ | `livewire/livewire ^4.3`, 43 components |
| Alpine.js | 🟡 | Not a standalone dependency; bundled via Livewire 4. Confirm this is sufficient or add explicitly if raw Alpine directives are used in Blade outside Livewire. |
| TailwindCSS | ✅ | `tailwindcss ^4.0` |
| MySQL 8+ | ✅ | CI uses MySQL 8.4 service |
| Redis | ✅ | `predis/predis`, configured for cache/queue in `.env.example` |
| Laravel Queue | 🟡 | Config + Horizon present; **zero `ShouldQueue` job classes exist** — nothing is actually queued |
| Laravel Scheduler | ❌ | No custom Artisan commands or `routes/console.php` schedule entries found |
| Laravel Events | ❌ | `app/Events/` and `app/Listeners/` do not exist |
| Laravel Notifications | ❌ | `app/Notifications/` does not exist |
| Laravel Policies | ✅ | 24 policy classes |
| Laravel Gates | 🟡 | Spatie permission-based checks used; confirm supplementary `Gate::define` usage where policies don't fit |
| Laravel Broadcasting | ❌ | `config/broadcasting.php` missing entirely; no Echo/Pusher/Reverb |
| Laravel Cashier | ❌ | Not installed; not needed unless SaaS subscription billing for hotel *tenants* is in scope (spec says "if online subscription required") |
| Laravel Sanctum | ✅ | Ability-scoped tokens used in API |
| Laravel Pint | ✅ | Configured, CI-enforced |
| Laravel Horizon | ✅ | Installed, provider present, Docker service defined |
| Laravel Telescope | ✅ | Installed, dev provider present |
| Laravel Debugbar | ✅ | Dev-only, correct |
| Spatie Permission | ✅ | 16 roles, granular permissions, seeded |
| Spatie Activity Log | ✅ | Wired into models (e.g. `Reservation`) |
| Spatie Media Library | 🟡 | Installed + migrated, but no confirmed model usage (e.g. room images, guest documents, menu item photos) |
| Laravel Excel | 🟡 | Installed, **zero Export/Import classes found** — unused |
| DomPDF | 🟡 | Installed, **zero `Pdf::` usage found** — unused |
| PHPUnit | ✅ | |
| PestPHP | ✅ | Pest 4 + laravel/browser/stressless plugins |

**Recommendations:**
- [ ] Bump `composer.json` PHP constraint to `^8.4` to match the spec and actually use PHP 8.4 features.
- [ ] Confirm Alpine.js is available for non-Livewire interactive Blade snippets, or document that Livewire's bundled Alpine is the intentional single source.
- [ ] Wire Spatie Media Library into at least Room images, Guest documents, and Menu item photos — migrations exist but usage is unconfirmed.

---

## 2. Software Architecture

| Principle/Pattern | Status | Notes |
|---|---|---|
| SOLID / DRY / KISS | ✅ (assessed structurally) | DDD folder layout with single-purpose Action classes supports this |
| Repository Pattern | ❌ | Not used — **Action classes substitute for repositories**. Decide and document this as the deliberate pattern rather than a gap; update `docs/architecture/coding-standards.md` to state it explicitly if not already there. |
| Service Layer | 🟡 | Present as `Domain/{X}/Support/*` calculators/resolvers rather than named `*Service` classes — equivalent in spirit |
| Action Classes | ✅ | Core pattern used throughout (Create/Cancel/Assign/Complete actions per domain) |
| Form Requests | 🟡 | Only 11 found, scoped to Auth + API v1 — most Livewire components likely validate inline via Livewire's `#[Validate]`, which is idiomatic for Livewire 4 but should be confirmed as the deliberate choice, not a gap |
| API Resources | ✅ | 6 present for API v1 |
| DTOs | 🟡 | Only `Domain/Payment/DataTransferObjects/` (3 files) — other domains don't use DTOs; acceptable if Actions take typed params/Eloquent models directly |
| Events & Listeners | ❌ | Entirely missing — see §11 Notifications, this is the same root gap |
| Observer Pattern | ❌ | No `App\Observers` found — check if model lifecycle hooks (e.g., auto-generating invoice numbers, activity logging side effects) are handled inline instead |
| Factory Pattern | ✅ | 66 model factories; also check for Payment Gateway factory (strategy selection) |
| Strategy Pattern | 🟡 | Payment gateways are structured for this (`Domain/Payment/Gateways/`) but only Paystack is implemented — the strategy "slot" exists, filling is the gap |
| Dependency Injection | ✅ (assumed from Laravel conventions) | |
| DDD concepts | ✅ | This *is* the primary architecture, exceeding the spec's "where practical" ask |

**Recommendations:**
- [ ] Document the Repository→Actions substitution decision explicitly in `docs/architecture/coding-standards.md` so future contributors don't re-litigate it.
- [ ] Add `App\Events` + `App\Listeners` for key domain events (ReservationCreated, CheckInCompleted, InvoicePaid, LowStockReached, MaintenanceRequestRaised) — this unlocks Notifications, activity triggers, and decouples side effects from Actions.
- [ ] Implement Stripe, PayPal, and Flutterwave gateway classes alongside `PaystackGateway` in `Domain/Payment/Gateways/` to complete the Strategy pattern already scaffolded.

---

## 3. UI/UX Requirements

| Item | Status | Notes |
|---|---|---|
| Responsive / mobile / tablet | 🟡 | TailwindCSS in use; needs manual verification across breakpoints, not confirmable from static audit |
| Dashboard driven | ✅ | `Livewire\Reporting\DashboardOverview` + `DashboardMetrics` |
| Cards / analytics / charts | ✅ | Chart.js installed, `stat-tile`/`chart` Blade components exist |
| Quick actions | 🟡 | Needs UI verification |
| Global search | ❌ | No evidence of a global/omnisearch component found in audit |
| Dark Mode / Light Mode | 🟡 | `app/Support/Theme/` exists (1 file) + `admin/appearance` route — needs verification it's a full dark/light toggle, not just brand color theming |
| Heroicons | 🟡 | Not confirmed in audit — check `resources/views` for icon usage |

**Recommendations:**
- [ ] Verify dark/light mode is a genuine user-toggleable theme (not just tenant brand color customization) — `app/Support/Theme/` needs inspection.
- [ ] Add a global search Livewire component (guests, reservations, rooms, invoices) if not present — high-value for front-desk staff.
- [ ] Confirm Heroicons is the icon set in use; standardize if mixed.

---

## 4. Authentication

| Item | Status | Notes |
|---|---|---|
| Login | ✅ | |
| Forgot Password | ✅ | |
| Email Verification | ✅ | Laravel default scaffolding present |
| MFA | ✅ | `pragmarx/google2fa`, full enable/disable/confirm/verify actions, recovery codes |
| Session Management | ✅ | Per-device session listing (DB session driver deliberately, per SRS FR-AUTH-009) |
| Account Lockout | ✅ | `config/security.php`, env-driven attempts/minutes |
| Password History | ✅ | `PasswordHistory` model + migration |
| Password Expiry | ✅ | `EnsurePasswordIsNotExpired` middleware |
| Remember Me | 🟡 | Standard Laravel feature, assumed present — not explicitly confirmed in audit |

**This section is essentially complete.** No action items beyond confirming Remember Me is wired on the login form.

---

## 5. Authorization (RBAC)

| Item | Status | Notes |
|---|---|---|
| Role-based access control | ✅ | Spatie Permission |
| All 18 specified roles | ✅ | Confirmed present in `RolePermissionSeeder`: Super Admin, Hotel Owner, GM, Branch Manager, Receptionist, Reservation Officer, Housekeeping Supervisor/Staff, Restaurant Manager, Waiter, Chef, Accountant, Cashier, Maintenance Officer, Security Officer, HR, Auditor, Guest |
| Configurable permissions | ✅ | Granular permission strings per role |
| Policies | ✅ | 24 policies covering nearly every model |

**This section is complete.** No action items.

---

## 6. Core Hotel Modules

All of the following exist with models, migrations, Action classes, Livewire UI, and (mostly) policies. Status reflects **completeness relative to the spec's feature list within each module**, not mere existence.

### 6.1 Dashboard — ✅ Strong
Occupancy, revenue, bookings, check-ins/outs, room status, KPIs present via `DashboardOverview` + `DashboardMetrics`.
- [ ] Verify all spec line items are surfaced: today's arrivals/departures, pending housekeeping, maintenance requests, restaurant sales, outstanding invoices — cross-check against `DashboardMetrics` fields.

### 6.2 Branch Management — ✅ Strong
Multi-tenant (`Tenant`) + multi-branch (`Branch`) models, `Admin\BranchManager` Livewire.
- [ ] Confirm each branch scopes rooms/employees/restaurant/inventory/accounting/reports correctly (tenant/branch scoping should be tested, not just modeled).

### 6.3 Room Management — ✅ Strong
RoomType, Room, RoomRate, Amenity, `RateResolver` (seasonal/weekend/holiday pricing logic).
- [ ] Confirm Floor/Building/Block concepts exist as fields or separate models (not explicitly confirmed in audit — spec lists these distinctly from Room).
- [ ] Confirm room image support is wired via Media Library (see §1).
- [ ] Confirm an Availability Calendar UI component exists (vs. just an availability-check API/action).

### 6.4 Reservation Management — ✅ Strong
Full lifecycle: create/cancel actions, status log, waitlist, `AvailabilityChecker`, `BookingWizard`.
- [ ] Confirm Corporate booking and Group booking are distinct flows (vs. generic reservation) — `CorporateAccount` model exists, verify it links into the booking wizard.
- [ ] Email/SMS booking confirmation notifications — blocked on §11 Notifications gap.

### 6.5 Front Desk — ✅ Strong
Check-in/out actions, Folio + charges, `FrontDesk\Dashboard`.
- [ ] Confirm room upgrade/transfer, key management, late checkout/early check-in are implemented as distinct actions (not fully enumerated in audit — spot-check `Domain/FrontDesk/Actions/`).

### 6.6 Guest Management — ✅ Strong
Guest, GuestContact, GuestDocument, GuestNote models; `GuestManager`/`GuestProfile` Livewire.
- [ ] Confirm blacklist/VIP flags, loyalty tie-in, and family/emergency-contact relationships are modeled (GuestContact may cover this — verify field-level).

### 6.7 Housekeeping — ✅ Strong
HousekeepingTask, LostFoundItem, `TaskBoard`, assign/complete/inspect actions.
- [ ] Confirm laundry requests are modeled distinctly or as a HousekeepingTask subtype.

### 6.8 Maintenance — ✅ Strong
Asset, MaintenanceWorkOrder, `WorkOrderManager`.
- [ ] Confirm preventive maintenance scheduling exists (recurring work orders) — likely needs Scheduler (§1 gap) to be functional, not just modeled.

### 6.9 Restaurant & POS — ✅ Strong
Outlets, Tables, Menu, Orders, `PosTerminal`, `KitchenDisplay`, `MenuManager`.
- [ ] Kitchen Display real-time updates likely rely on Livewire polling since Broadcasting is missing (§1) — consider whether this is acceptable UX or needs Reverb/Pusher for a true live KDS.
- [ ] Confirm split bills, discounts, and room-service-to-folio posting are implemented.

### 6.10 Inventory — ✅ Strong
InventoryItem, StockMovement, Warehouse, adjust/issue/receive/transfer actions.
- [ ] Confirm barcode support and expiry tracking fields exist on `InventoryItem` (spec explicitly lists these — not confirmed in audit).
- [ ] Confirm minimum-stock alerting triggers something (notification, dashboard flag) — depends on §11.

### 6.11 Procurement — ✅ Strong
PurchaseOrder(+Items), Supplier, GoodsReceipt, `PurchaseOrderManager`.
- [ ] Confirm RFQ and multi-step approval workflow exist distinctly, or whether PO approval is a single-step status field.

### 6.12 Accounting — ✅ Strong
Chart of accounts, journal entries, AR/AP, cashbook, tax rules, P&L/Trial Balance/Balance Sheet calculators.
- [ ] Confirm Bank Reconciliation exists as a distinct feature (not explicitly named in migration list — `cashbook_entries` may cover it, verify).
- [ ] Confirm Cash Flow statement calculator exists alongside P&L/Trial Balance/Balance Sheet.

### 6.13 Human Resources — ✅ Strong
Employee, Leave, Attendance, Payroll, Performance, Disciplinary, Recruitment.
- [ ] Confirm payroll actually computes deductions/taxes (vs. just storing `PayrollRun`/`Payslip` records) — worth a targeted code read.
- [ ] Confirm document storage for HR files uses Media Library.

### 6.14 CRM, Loyalty, Coupons — ✅ Strong
CorporateAccount, GuestFeedback, MarketingCampaign, LoyaltyAccount/Transaction, Coupon/Redemption.
- [ ] Confirm "Complaints" is tracked distinctly from general Feedback, per spec wording.
- [ ] Confirm membership-level/tier logic beyond point accrual (spec lists "Membership levels" separately from "Reward points").

### 6.15 Event Management — ✅ Strong
EventSpace, EventBooking(+Items), EventService, bill calculator.
- [ ] Confirm equipment rental is modeled as `EventService` line items or needs its own concept.

**Overall recommendation for §6:** These modules are functionally complete at the data/action layer. The main follow-up work is (a) targeted verification of the finer-grained spec bullet points noted above via direct code reads rather than a fresh audit, and (b) wiring notifications/async triggers so these modules communicate with guests/staff instead of just persisting state.

---

## 7. Reporting — 🟡 Partial

| Item | Status |
|---|---|
| Occupancy/Revenue/Room Performance reports | ✅ Calculators exist |
| Guest/Restaurant/Inventory/Accounting/Audit/Tax/Employee reports | 🟡 Needs verification per-type |
| Export to PDF | ❌ DomPDF installed, unused |
| Export to Excel | ❌ Laravel Excel installed, unused |
| Export to CSV | ❌ Not confirmed |

**Recommendations:**
- [ ] Build `App\Exports\*` classes (Laravel Excel `FromCollection`/`WithHeadings`) for at least: Occupancy, Revenue, Guest, Restaurant Sales, Inventory Valuation, Trial Balance/P&L/Balance Sheet.
- [ ] Build PDF export views for invoices/folios and financial statements using the already-installed DomPDF.
- [ ] Add CSV export as a lightweight fallback for report tables that don't need Excel formatting.

This is one of the highest-leverage remaining gaps: the packages are already in `composer.json`, so this is pure implementation work, no new dependencies needed.

---

## 8. Notifications — ❌ Missing

| Channel | Status |
|---|---|
| Email | ❌ No Mailables found |
| SMS | ❌ No SMS provider integration found |
| WhatsApp | ❌ Not found |
| In-app | ❌ Not found |
| Push | ❌ Not found |

**Recommendations (highest priority gap in the codebase):**
- [ ] Introduce `App\Notifications\*` classes for at minimum: `ReservationConfirmed`, `CheckInCompleted`, `InvoiceGenerated`, `PaymentReceived`, `MaintenanceRequestAssigned`, `LowStockAlert`.
- [ ] Add `App\Events` + `App\Listeners` (see §2) to trigger these notifications from domain Actions without coupling Actions directly to notification-sending.
- [ ] Wire `mail` channel first (lowest integration cost), then evaluate an SMS provider (Twilio/Vonage) and WhatsApp Business API per the spec.
- [ ] Add a `database` notification channel + a simple in-app notification bell Livewire component for staff-facing alerts (maintenance, low stock, no-shows).
- [ ] This gap blocks several items already flagged above (§6.4 booking confirmations, §6.10 low-stock alerts) — prioritize accordingly.

---

## 9. REST API — 🟡 Partial

| Module | API Coverage |
|---|---|
| Auth | ✅ |
| Guests | ✅ |
| Reservations | ✅ |
| Room Types (+ availability) | ✅ |
| Folios / Payments | ✅ |
| Reports (occupancy, revenue) | ✅ |
| Housekeeping | ❌ |
| Maintenance | ❌ |
| Restaurant/POS | ❌ |
| Inventory | ❌ |
| Procurement | ❌ |
| Accounting | ❌ |
| HR | ❌ |
| CRM | ❌ |
| Events | ❌ |

OpenAPI/Swagger infra (`l5-swagger`) is installed and published; unconfirmed whether `@OA\...` annotations exist on the controllers that do have API coverage.

**Recommendations:**
- [ ] Decide scope: does every module need a public REST API, or only guest/booking-facing ones (as currently built)? The spec asks for full API coverage across all listed resources — confirm with stakeholders whether back-office modules (HR, Accounting) genuinely need REST endpoints or are Livewire-only by design.
- [ ] If full coverage is required, extend `routes/api/v1.php` + matching Controllers/Resources/Requests for Housekeeping, Maintenance, Restaurant, Inventory modules first (most likely to have external integration needs — channel managers, POS hardware, IoT).
- [ ] Verify `@OA` annotations exist for currently-covered endpoints; add for any missing so Swagger UI is actually useful, not just installed.

---

## 10. Payment Integration — 🟡 Partial

| Gateway | Status |
|---|---|
| Paystack | ✅ Full implementation (initiate/confirm/refund + webhook) |
| Stripe | ❌ Env placeholder only |
| PayPal | ❌ Env placeholder only |
| Flutterwave | ❌ Env placeholder only |
| Cash / POS | 🟡 Likely handled as a manual payment method — verify |
| Bank Transfer | 🟡 Verify |
| Split Payments | 🟡 Verify against Folio/Payment model design |
| Refunds | ✅ (Paystack only) |

**Recommendations:**
- [ ] Implement `StripeGateway`, `PayPalGateway`, `FlutterwaveGateway` classes in `Domain/Payment/Gateways/` following the existing `PaystackGateway` contract — the Strategy pattern scaffolding is already there (see §2).
- [ ] Confirm split-payment support (multiple payment methods against one folio) is modeled, not just single-gateway-per-transaction.

---

## 11. Security — ✅ Strong

CSRF/XSS/SQLi protection are Laravel-framework defaults (assumed intact, no evidence of raw queries bypassing them). Confirmed present: rate limiting, audit logs (Spatie Activity Log), MFA, policies, dedicated security test suite (`tests/Feature/Security/` — AuthorizationBoundary, MassAssignment, RateLimiting, UserManagementBoundary).

**Recommendations:**
- [ ] Add explicit security headers middleware (CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, HSTS) if not already set via Nginx config — check `docker/nginx/default.conf`.
- [ ] Run `composer audit` (already in CI) and a manual dependency review before any production release.
- [ ] Consider a scheduled `/security-review` pass (skill available in this environment) before go-live.

---

## 12. Performance — 🟡 Partial

| Item | Status |
|---|---|
| Redis Cache | 🟡 Configured, usage not confirmed beyond config |
| Database Indexes | 🟡 Not verified — spot-check migrations for `->index()`/`->unique()` on FK and query-hot columns |
| Queues | ❌ Infra present, zero jobs (see §1) |
| N+1 prevention | ✅ `tests/Feature/Performance/QueryCountTest.php` exists |
| Pagination | 🟡 Assumed via Livewire's `WithPagination`, not explicitly confirmed |
| Image Optimization | 🟡 Depends on Media Library conversions being configured (see §1) |

**Recommendations:**
- [ ] Move at least report generation and export jobs (§7) to queued jobs once implemented — good first real use of the queue infra.
- [ ] Audit high-traffic queries (dashboard metrics, availability checker) for explicit Redis caching with sensible TTLs.
- [ ] Confirm Media Library conversions (thumbnails) are configured for room/menu images to avoid serving full-size originals.

---

## 13. Database — ✅ Strong

89 migrations, 66 factories, comprehensive domain coverage, soft deletes + activity logging on key models. ERD documented in `docs/architecture/erd.md` (751 lines).

**Recommendations:**
- [ ] Spot-check that foreign keys have `->constrained()->cascadeOnDelete()` or explicit `restrictOnDelete()` as appropriate per relationship (financial records should likely restrict, not cascade).
- [ ] Confirm audit fields (`created_by`/`updated_by`) exist where the spec implies accountability (financial transactions, approvals) — not explicitly confirmed in the audit.

---

## 14. Documentation — 🟡 Partial

| Doc | Status |
|---|---|
| SRS | ✅ `docs/SRS.md` (442 lines) |
| System Architecture | ✅ `docs/architecture/system-architecture.md` |
| Database Design / ERD | ✅ `docs/architecture/erd.md` |
| Coding Standards | ✅ `docs/architecture/coding-standards.md` |
| Installation Guide | 🟡 Partially covered in README |
| Deployment Guide | ❌ Missing |
| API Documentation | 🟡 Swagger UI installed, annotation completeness unconfirmed |
| User Manual | ❌ Missing |
| Administrator Guide | ❌ Missing |
| Developer Guide | 🟡 Coding standards doc partially covers this |
| Maintenance Guide | ❌ Missing |
| Backup & Restore Guide | ❌ Missing |
| Disaster Recovery Guide | ❌ Missing |
| Change Log / Release Notes | ❌ Missing |

**Recommendations:**
- [ ] Write `docs/deployment-guide.md` covering the existing Docker Compose setup (nginx/mysql/redis/queue/horizon/scheduler services already defined — just needs a runbook).
- [ ] Write `docs/backup-restore.md` and `docs/disaster-recovery.md` — critical for a hotel system handling financial/guest PII data, currently a compliance gap.
- [ ] Add `CHANGELOG.md` at repo root, start populating from next release onward.
- [ ] Write a brief Administrator Guide (role/permission management, branch setup, payment gateway configuration) — most of the underlying features already exist, this is pure documentation work.

---

## 15. Logging & Monitoring — ✅ Strong (mostly)

Telescope + Horizon both installed and configured. Structured logging assumed via Laravel defaults.

**Recommendations:**
- [x] ~~Confirm Telescope is disabled/gated in production~~ — **Found a real deploy-breaking bug (2026-08-07):** `laravel/telescope` is correctly in `require-dev`, but `bootstrap/providers.php` registered `TelescopeServiceProvider` unconditionally. A standard `composer install --no-dev` production deploy would fatal-error on every request since the parent class wouldn't exist in `vendor/`. Fixed with a `class_exists()` guard in `bootstrap/providers.php`. Also added `TELESCOPE_ENABLED=true` to `.env.example` with a comment that it must be set `false` in production — it is **not** tied to `APP_ENV`; the existing `TELESCOPE_ALLOWED_EMAILS` gate only controls dashboard *viewing*, not whether Telescope's watchers run at all.
- [ ] Consider an external exception tracker (Sentry/Flare) for production — not currently evidenced.

---

## 16. Code Quality — ✅ Strong

Pint, Larastan (PHPStan), Rector all installed with composer scripts; CI enforces Pint + PHPStan.

**Recommendations:**
- [ ] Confirm a pre-commit hook (e.g., via `captainhook` or Husky-equivalent) runs Pint/Larastan locally, not just in CI — spec explicitly asks for pre-commit hooks and none were found in the audit.
- [ ] Confirm PHPStan/Larastan level (README references level 8) is actually enforced at that level in `phpstan.neon`.

---

## 17. Testing — ✅ Strong

70 Feature tests + 2 Unit tests, Pest 4 with Laravel/Browser/Stressless plugins, CI coverage gate at `--min=95`.

| Test type | Status |
|---|---|
| Unit | 🟡 Only 2 files — thin for a system this size; most coverage is via Feature tests, which is a valid Pest-idiomatic choice but worth confirming intentional |
| Feature | ✅ Strong, 70 files across all domains |
| Browser (Dusk or Pest Browser) | 🟡 One smoke test under `tests/Feature/Browser/`, not a dedicated `tests/Browser/` suite — spec explicitly asks for Dusk; project uses Pest's browser plugin + Playwright instead (reasonable modern substitution) |
| API | ✅ `tests/Feature/Api/V1/` (6 files) |
| Performance | 🟡 One `QueryCountTest.php`; spec asks for stress/load/concurrent-booking tests — `pest-plugin-stressless` is installed but usage beyond this one file unconfirmed |
| Security | ✅ 4 dedicated files + 1 Auth policy test |
| Coverage threshold | ✅ 95% gate enforced in CI |

**Recommendations:**
- [ ] Expand browser test coverage beyond one smoke test — spec explicitly calls out Login, Reservation, Booking, Payments, Room Search, Guest Registration, Check-in, Check-out, Reports as required flows.
- [ ] Add a concurrent-booking stress test using `pest-plugin-stressless` (installed but seemingly unused) to validate no double-booking under race conditions — this is a real correctness risk for any hotel system, not just a checkbox.
- [ ] Once Notification/Job classes exist (§8, §1), add corresponding tests — currently there's nothing to test there.

---

## 18. CI/CD & DevOps — 🟡 Partial

CI (`.github/workflows/ci.yml`) runs Pint, PHPStan, migrations, Pest with coverage gate, and `composer audit`. Docker Compose covers app/nginx/mysql/redis/queue/horizon/scheduler.

**Missing:**
- [ ] No CD/deploy job in CI — add a deployment workflow (or document the manual deploy process) once a target environment is chosen.
- [ ] No pre-commit hook config found (see §16).
- [ ] No k8s manifests / IaC — likely out of scope unless multi-region/large-chain deployment is planned; note as a future consideration rather than a current gap.

---

## How to Use This Document

1. Before starting work on a gap, re-verify its status with a targeted `Grep`/`Read` — this document reflects a point-in-time audit (2026-08-04) and the codebase will move.
2. When closing an item, check its box and update the relevant status badge (❌→🟡→✅).
3. Add new rows if the audit missed something or scope changes; don't let this file silently go stale — update "Last reviewed" at the top whenever a fresh pass is done.
4. High-leverage next steps, in rough priority order:
   1. **Notifications + Events/Listeners** (§8, §2) — unblocks booking confirmations, alerts across multiple modules.
   2. **Reporting exports** (§7) — packages already installed, pure implementation.
   3. **Additional payment gateways** (§10) — scaffolding exists, follow the Paystack pattern.
   4. **API breadth decision + implementation** (§9) — needs a scope decision first, then mechanical extension.
   5. **Deployment/DR/backup documentation** (§14) — compliance-relevant, low implementation risk, high documentation effort.
