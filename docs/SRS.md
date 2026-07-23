# Software Requirements Specification (SRS)
## Enterprise Hotel Management System (HMS)

| | |
|---|---|
| **Document Version** | 1.0.0 |
| **Date** | 2026-07-23 |
| **Status** | Draft for review |
| **Target Stack** | Laravel 13, PHP 8.3+ (8.4+ recommended), Livewire 4, Alpine.js, TailwindCSS, MySQL 8+, Redis |

---

## 1. Introduction

### 1.1 Purpose

This document specifies the functional and non-functional requirements for the Enterprise Hotel Management System (HMS), a multi-branch, multi-tenant-capable hotel operations platform covering the full lifecycle of hotel operations: reservations, front desk, housekeeping, maintenance, food & beverage/POS, inventory & procurement, accounting, human resources, CRM/loyalty, event management, and reporting.

It is written to be actionable by engineering (as the basis for architecture, database design, and sprint planning), QA (as the basis for test plans), and stakeholders (as the basis for acceptance).

### 1.2 Scope

The system supports:

- A **hotel group (tenant)** operating **one or more branches (properties)**, each with independent rooms, staff, restaurant, inventory, and accounting, rolled up into group-level reporting.
- Role-based staff operations (front desk, housekeeping, restaurant, maintenance, accounting, HR, security, management).
- Guest-facing capabilities (online booking, guest portal/profile, loyalty) delivered through the same Laravel application (Blade/Livewire), with a REST API for future mobile/OTA integrations.
- Financial operations sufficient for property-level bookkeeping (AR/AP, GL, tax, cash/bank reconciliation) — not a replacement for a full corporate ERP/GL system, but sufficient for hotel-level accounting and exportable to one.

Out of scope for v1 (documented explicitly so they are not silently dropped, and can be prioritized later):

- Channel manager / OTA two-way rate & inventory sync (Booking.com, Expedia, etc.) — the API is designed to make this addable later.
- Native mobile apps (a documented REST API is provided for future clients).
- Full corporate double-entry ERP (multi-currency consolidation, complex cost-center allocation) beyond property-level accounting.

### 1.3 Definitions, Acronyms, Abbreviations

| Term | Meaning |
|---|---|
| HMS | Hotel Management System (this product) |
| Tenant / Group | A hotel company that may own multiple branches |
| Branch / Property | A single physical hotel location |
| POS | Point of Sale (restaurant/bar/room-service billing) |
| RBAC | Role-Based Access Control |
| ADR | Average Daily Rate |
| RevPAR | Revenue Per Available Room |
| OTA | Online Travel Agency |
| GRN | Goods Receipt Note |
| RFQ | Request for Quotation |
| KOT | Kitchen Order Ticket |
| GL | General Ledger |
| SRS | This document |
| MFA | Multi-Factor Authentication |

### 1.4 References

- Laravel 13 documentation, Livewire 4 documentation, Spatie `laravel-permission` / `laravel-medialibrary` / `laravel-activitylog` documentation.
- PCI-DSS SAQ guidance (for payment card handling — HMS delegates card capture to PCI-compliant gateways/hosted fields and never stores raw PAN/CVV).

### 1.5 Document Conventions

Each functional requirement has a stable ID of the form `FR-<MODULE>-<NNN>` so it can be traced from spec → migration → test. Non-functional requirements use `NFR-<CATEGORY>-<NNN>`. Priority: **M**ust / **S**hould / **C**ould (MoSCoW), used to sequence delivery.

---

## 2. Overall Description

### 2.1 Product Perspective

HMS is a new, standalone, self-hosted web application. It is multi-branch by design: every operational record (room, reservation, invoice, employee, stock item) belongs to exactly one `branch_id`, and every branch belongs to one `tenant/group`. Group-level users (Super Administrator, Hotel Owner, General Manager, Auditor) can see across branches per their role; branch-level staff are scoped to their assigned branch(es) via policies, not just UI filtering.

### 2.2 Actors / User Roles

Roles are implemented via Spatie `laravel-permission` as configurable, seedable roles with granular permissions (roles are a named permission bundle, not hardcoded logic). Default seeded roles:

| Role | Typical Scope |
|---|---|
| Super Administrator | Full system, all tenants/branches, system configuration |
| Hotel Owner | All branches within their tenant, financial visibility, no low-level ops |
| General Manager | All branches within their tenant, operational + financial |
| Branch Manager | Single branch, full operational authority |
| Receptionist | Single branch, front desk operations |
| Reservation Officer | Single branch (or group for central reservations), booking management |
| Housekeeping Supervisor | Single branch, housekeeping assignment & inspection |
| Housekeeping Staff | Single branch, assigned tasks only |
| Restaurant Manager | Single branch, restaurant/POS management |
| Waiter | Single branch, order taking |
| Chef | Single branch, kitchen display/orders |
| Accountant | Single branch (or group), full accounting module |
| Cashier | Single branch, payments/POS/front-desk billing |
| Maintenance Officer | Single branch, work orders/assets |
| Security Officer | Single branch, incident logs, guest ID verification aid |
| HR | Tenant or branch, employee/attendance/payroll |
| Auditor | Read-only, tenant-wide, audit logs & reports |
| Guest | Self-service: own profile, own reservations, own invoices |

Permissions are individually assignable so any role's permission set can be customized per deployment without code changes (`FR-AUTHZ-*`).

### 2.3 Operating Environment

- Server: Linux container (Docker), PHP 8.3+ (target 8.4+), Nginx/PHP-FPM, MySQL 8+, Redis 7+.
- Client: Evergreen desktop and mobile browsers (Chrome, Edge, Safari, Firefox — last 2 major versions). Responsive down to 360px width for handheld use by housekeeping/waitstaff.

### 2.4 Design & Implementation Constraints

- Must follow Laravel 13 conventions (see `docs/architecture/coding-standards.md`).
- All monetary values stored as integer minor units (cents) to avoid floating-point rounding errors; currency is per-branch configurable.
- All timestamps stored UTC; displayed in branch-configured timezone.
- Multi-branch data isolation enforced at the query/policy layer (global scopes + policies), never solely at the UI layer.

### 2.5 Assumptions & Dependencies

- Payment card data is never persisted by HMS; gateways (Stripe/PayPal/Flutterwave/Paystack) are used via tokenization/hosted fields.
- SMS/WhatsApp delivery depends on a configured third-party provider (e.g., Twilio/WhatsApp Business API); the system defines the abstraction, deployment supplies credentials.

---

## 3. Functional Requirements

Each module below lists representative, testable requirements. This is the authoritative functional baseline; the database schema (Deliverable 3) and module implementation (Deliverables 6–7) must trace back to these IDs.

### 3.1 Authentication & Account Security (`AUTH`)

| ID | Requirement | Priority |
|---|---|---|
| FR-AUTH-001 | Users authenticate with email + password; failed attempts are rate-limited per account and per IP. | M |
| FR-AUTH-002 | System supports "Forgot Password" via signed, time-limited email link (Laravel's built-in password broker). | M |
| FR-AUTH-003 | New accounts require email verification before accessing non-public areas. | M |
| FR-AUTH-004 | System supports optional TOTP-based MFA per user, enforceable per role (e.g., mandatory for Accountant/Admin roles). | S |
| FR-AUTH-005 | "Remember me" issues a long-lived, rotating remember token distinct from the session cookie. | M |
| FR-AUTH-006 | Accounts lock after N configurable consecutive failed logins, with automatic or admin-assisted unlock and notification to the user. | M |
| FR-AUTH-007 | Password policy enforces minimum complexity and prevents reuse of the last N passwords (password history). | M |
| FR-AUTH-008 | Passwords expire after a configurable interval for elevated roles, prompting forced rotation. | S |
| FR-AUTH-009 | Active sessions are listed per user with device/IP/last-active metadata and can be revoked individually or entirely ("log out everywhere"). | S |
| FR-AUTH-010 | All authentication events (login, logout, failed login, lockout, password change, MFA change) are written to the audit log. | M |

### 3.2 Authorization (`AUTHZ`)

| ID | Requirement | Priority |
|---|---|---|
| FR-AUTHZ-001 | Access control is role- and permission-based (Spatie permissions), evaluated via Laravel Policies/Gates at the controller/action boundary, not just hidden UI. | M |
| FR-AUTHZ-002 | Roles and permissions are manageable from an admin UI without code deployment. | M |
| FR-AUTHZ-003 | Every branch-scoped resource enforces that the acting user is assigned to that branch (or holds a tenant/group-level role) before allowing read/write. | M |
| FR-AUTHZ-004 | Guests (role: Guest) can only access their own profile, reservations, and invoices. | M |
| FR-AUTHZ-005 | Sensitive actions (refunds, rate overrides, void invoices, permission changes) require a distinct elevated permission and are logged with actor, reason, and timestamp. | M |

### 3.3 Branch / Group Management (`BR`)

| ID | Requirement | Priority |
|---|---|---|
| FR-BR-001 | Super Administrator can create/edit/deactivate tenants and branches. | M |
| FR-BR-002 | Each branch has its own address, timezone, currency, tax profile, contact info, logo/media, and operational configuration (check-in/out times, cancellation policy defaults). | M |
| FR-BR-003 | Users (staff) can be assigned to one or more branches with a role per branch. | M |
| FR-BR-004 | Group-level dashboards aggregate KPIs across branches; branch dashboards scope to one branch. | S |

### 3.4 Room Management (`ROOM`)

| ID | Requirement | Priority |
|---|---|---|
| FR-ROOM-001 | System manages Buildings → Floors/Blocks → Rooms hierarchy per branch. | M |
| FR-ROOM-002 | Room Types (e.g., Standard, Deluxe, Suite) define base capacity, bed configuration, default rate, and amenities. | M |
| FR-ROOM-003 | Individual rooms belong to a room type and carry room-specific overrides (floor, view, accessibility, images). | M |
| FR-ROOM-004 | Rooms have a current **status** (Vacant Clean, Vacant Dirty, Occupied, Out of Order, Out of Service) and a separate **housekeeping status**, both visible on a live room/availability board. | M |
| FR-ROOM-005 | Room pricing supports base rate, seasonal rate rules, weekend rate rules, and holiday/date-specific overrides, with a defined precedence order. | M |
| FR-ROOM-006 | An availability calendar shows, per room/room-type and date range, availability, rate, and restrictions (min-stay, closed-to-arrival). | M |
| FR-ROOM-007 | Rooms under maintenance are automatically excluded from bookable availability. | M |
| FR-ROOM-008 | Amenities are a managed, reusable taxonomy assignable to room types/rooms. | S |

### 3.5 Reservation Management (`RES`)

| ID | Requirement | Priority |
|---|---|---|
| FR-RES-001 | Staff can create reservations for walk-in, phone, corporate, and group bookings; guests can create online bookings through the same engine. | M |
| FR-RES-002 | Reservation creation checks real-time room-type availability and prevents overbooking beyond configured overbooking tolerance. | M |
| FR-RES-003 | Group bookings allow multiple rooms under one reservation reference with a designated group contact/billing. | M |
| FR-RES-004 | Reservations can be modified (dates, room type, rate, occupants) subject to availability and the applicable rate/cancellation rules. | M |
| FR-RES-005 | Reservations can be cancelled, with cancellation-fee rules applied based on the branch's cancellation policy and timing. | M |
| FR-RES-006 | Guests who fail to arrive by a configurable cutoff are flagged **No-Show** automatically (scheduled job) and the room released per policy. | M |
| FR-RES-007 | When a room type is fully booked, new requests can join a **waitlist** and are notified automatically if availability opens. | S |
| FR-RES-008 | Every reservation has a full, immutable audit trail of state changes (created, modified, confirmed, cancelled, no-show, checked-in, checked-out). | M |
| FR-RES-009 | Booking confirmation is sent by email (and SMS/WhatsApp if configured) on creation and on material change. | M |
| FR-RES-010 | Corporate accounts can have negotiated rates and direct-billing terms attached to their bookings. | S |

### 3.6 Front Desk Operations (`FD`)

| ID | Requirement | Priority |
|---|---|---|
| FR-FD-001 | Check-in captures/validates guest identity (passport/national ID/visa fields), assigns a specific physical room, and records key issuance. | M |
| FR-FD-002 | Check-in supports early check-in with configurable fee/approval. | S |
| FR-FD-003 | Check-out settles the folio (room + incidental charges), supports late check-out fees, and releases the room to housekeeping "dirty" status. | M |
| FR-FD-004 | Front desk can perform room upgrades/transfers mid-stay, adjusting the folio and room status accordingly. | M |
| FR-FD-005 | Front desk can attach free-text guest notes/flags (e.g., allergy, VIP request) visible to relevant staff. | S |
| FR-FD-006 | A live arrivals/departures board shows today's expected check-ins/check-outs with status. | M |

### 3.7 Guest Management (`GUEST`)

| ID | Requirement | Priority |
|---|---|---|
| FR-GUEST-001 | Guest profiles store contact info, ID documents (passport/national ID/visa), nationality, and preferences. | M |
| FR-GUEST-002 | Guest profiles show full stay history and folio history across branches within the tenant. | M |
| FR-GUEST-003 | Guests can be flagged VIP, Corporate, or Blacklisted, each affecting booking workflow (VIP alerts, blacklist blocks new bookings with override permission). | M |
| FR-GUEST-004 | Guest profiles can record linked family members/companions and an emergency contact. | S |
| FR-GUEST-005 | Guest loyalty tier, points balance, and membership number are visible on the profile. | S |
| FR-GUEST-006 | Guest-identifying documents are stored via the media library with access restricted by permission, and are covered by data-retention configuration. | M |

### 3.8 Housekeeping (`HK`)

| ID | Requirement | Priority |
|---|---|---|
| FR-HK-001 | Daily cleaning schedules are generated from occupancy/checkout data and can be manually adjusted. | M |
| FR-HK-002 | Rooms are assigned to housekeeping staff, who update status via a mobile-friendly checklist interface. | M |
| FR-HK-003 | Completed cleanings can be routed for supervisor inspection before the room returns to "Vacant Clean/Ready". | S |
| FR-HK-004 | Lost & found items are logged with room, finder, date, description, photo, and resolution/return status. | S |
| FR-HK-005 | Laundry requests (guest or internal linen) are tracked with status and turnaround. | C |
| FR-HK-006 | Housekeeping staff can raise a maintenance request directly from a room inspection. | M |

### 3.9 Maintenance (`MAINT`)

| ID | Requirement | Priority |
|---|---|---|
| FR-MAINT-001 | Work orders are created (manually or from housekeeping/guest reports), assigned to a technician, and tracked through status (Open → In Progress → Completed → Verified). | M |
| FR-MAINT-002 | Assets/equipment are registered per branch with warranty/service history. | S |
| FR-MAINT-003 | Preventive maintenance schedules generate recurring work orders automatically (via scheduler). | S |
| FR-MAINT-004 | Work orders capture parts/labor cost for repair-cost tracking and reporting. | S |
| FR-MAINT-005 | A room under active "Out of Order" maintenance is excluded from availability until the work order is verified complete. | M |

### 3.10 Restaurant & POS (`POS`)

| ID | Requirement | Priority |
|---|---|---|
| FR-POS-001 | Tables are managed per restaurant outlet with status (free/occupied/reserved) on a floor-plan or list view. | M |
| FR-POS-002 | Menus (categories, items, modifiers, pricing, tax class) are configurable per outlet. | M |
| FR-POS-003 | Orders taken at table, counter, or as room service generate a Kitchen Order Ticket routed to a kitchen display. | M |
| FR-POS-004 | POS supports split bills (by item or by guest), discounts (percentage/fixed, with approval threshold), and applicable taxes. | M |
| FR-POS-005 | Room-service orders can be posted directly to the guest's room folio instead of settled at the outlet. | M |
| FR-POS-006 | Completing an order deducts recipe-linked ingredients from inventory automatically. | S |
| FR-POS-007 | Restaurant sales reporting is available by outlet, item, category, and time period. | M |

### 3.11 Inventory (`INV`)

| ID | Requirement | Priority |
|---|---|---|
| FR-INV-001 | Items are tracked per warehouse/store with unit of measure, reorder point, and barcode. | M |
| FR-INV-002 | Stock movements (receipt, issue, transfer, adjustment, wastage) are recorded with full audit trail and running balance. | M |
| FR-INV-003 | Items below minimum stock trigger a low-stock alert/notification. | M |
| FR-INV-004 | Perishable items support expiry-date tracking and near-expiry alerts. | S |
| FR-INV-005 | Inventory valuation is available (at minimum: weighted average cost method). | S |
| FR-INV-006 | Suppliers are managed with contact/terms; Purchase Orders reference a supplier and line items. | M |
| FR-INV-007 | Goods Receipt against a PO updates stock and flags quantity/price discrepancies. | M |

### 3.12 Procurement (`PROC`)

| ID | Requirement | Priority |
|---|---|---|
| FR-PROC-001 | Purchase Requests can be raised by department staff and routed through a configurable approval chain. | S |
| FR-PROC-002 | Approved requests can generate an RFQ to one or more vendors. | C |
| FR-PROC-003 | Purchase Orders are generated from approved requests/RFQ responses and tracked to receipt. | M |
| FR-PROC-004 | Vendor invoices are matched against PO + GRN (3-way match) before payment approval. | S |

### 3.13 Accounting (`ACC`)

| ID | Requirement | Priority |
|---|---|---|
| FR-ACC-001 | A configurable Chart of Accounts exists per branch/tenant. | M |
| FR-ACC-002 | All revenue/expense-affecting transactions (folio postings, POS sales, purchases, payroll) generate corresponding journal entries in the GL (double-entry). | M |
| FR-ACC-003 | Accounts Receivable tracks guest/corporate direct-billing balances and aging. | M |
| FR-ACC-004 | Accounts Payable tracks vendor bills and payment status/aging. | M |
| FR-ACC-005 | A cashbook records daily cash movements per cashier/shift with an end-of-shift reconciliation. | M |
| FR-ACC-006 | Bank reconciliation matches bank statement lines against recorded transactions. | S |
| FR-ACC-007 | Tax rules (rate, applicability) are configurable per branch/jurisdiction and applied automatically to relevant transactions. | M |
| FR-ACC-008 | System produces Trial Balance, Profit & Loss, Balance Sheet, and Cash Flow statements for a selected period. | M |

### 3.14 Human Resources (`HR`)

| ID | Requirement | Priority |
|---|---|---|
| FR-HR-001 | Employee records include personal info, employment history, documents, and branch/role assignment. | M |
| FR-HR-002 | Attendance is captured (clock in/out or manual entry) and summarized per pay period. | M |
| FR-HR-003 | Leave requests follow a request → approval workflow with configurable leave types/balances. | M |
| FR-HR-004 | Payroll calculates pay based on attendance, leave, and configured salary structure, producing payslips. | S |
| FR-HR-005 | Performance reviews and disciplinary records are stored per employee with restricted visibility. | C |
| FR-HR-006 | Recruitment tracks open positions and candidates through a pipeline. | C |

### 3.15 CRM, Loyalty & Marketing (`CRM`)

| ID | Requirement | Priority |
|---|---|---|
| FR-CRM-001 | Corporate clients and travel agents are managed as account types with negotiated rates/commission terms. | S |
| FR-CRM-002 | Guest feedback and complaints are logged, assigned for resolution, and tracked to closure. | M |
| FR-CRM-003 | Loyalty program awards points on qualifying spend and tracks tier progression (e.g., Silver/Gold/Platinum) with tier-based benefits. | S |
| FR-CRM-004 | Coupons/discount codes/promotions are definable with validity window, usage limits, and applicable scope (room/restaurant/event). | S |
| FR-CRM-005 | Marketing campaigns can target guest segments (e.g., by loyalty tier, last-stay date) for email/SMS communication. | C |

### 3.16 Event Management (`EVT`)

| ID | Requirement | Priority |
|---|---|---|
| FR-EVT-001 | Conference halls/meeting rooms are managed as bookable resources distinct from guest rooms, with capacity and layout options. | S |
| FR-EVT-002 | Event bookings (conferences, weddings, banquets) capture date/time, resource, catering, and equipment rental requirements. | S |
| FR-EVT-003 | Event bookings generate a consolidated bill (venue + catering + equipment + services). | S |

### 3.17 Reporting & Analytics (`RPT`)

| ID | Requirement | Priority |
|---|---|---|
| FR-RPT-001 | Standard reports are available for occupancy, revenue (incl. RevPAR/ADR), room performance, guest statistics, restaurant sales, inventory, accounting, tax, and employee attendance. | M |
| FR-RPT-002 | Reports support date-range, branch, and relevant dimensional filters. | M |
| FR-RPT-003 | Reports export to PDF, Excel, and CSV. | M |
| FR-RPT-004 | An audit report shows who changed what, when, across sensitive modules (uses the activity log). | M |
| FR-RPT-005 | The dashboard surfaces real-time KPIs: occupancy rate, today's arrivals/departures, room status breakdown, pending housekeeping, open maintenance requests, restaurant sales today, outstanding invoices, and revenue trend chart. | M |

### 3.18 Notifications (`NOTIF`)

| ID | Requirement | Priority |
|---|---|---|
| FR-NOTIF-001 | Notifications are delivered via configurable channels: email, SMS, WhatsApp, in-app, and (optionally) web push, using Laravel's Notification system so channels are added without rewriting business logic. | M |
| FR-NOTIF-002 | Users can manage their own notification preferences per event type/channel where the event is not safety/compliance-critical. | S |
| FR-NOTIF-003 | In-app notifications are shown with unread state and mark-as-read, delivered in real time via broadcasting where infrastructure supports it. | S |

### 3.19 REST API (`API`)

| ID | Requirement | Priority |
|---|---|---|
| FR-API-001 | A versioned REST API (`/api/v1/...`) exposes authentication, bookings, guests, rooms, invoices, payments, and reports, authenticated via Sanctum tokens. | M |
| FR-API-002 | Every endpoint is documented via OpenAPI/Swagger, including request/response schemas and error shapes. | M |
| FR-API-003 | API responses use consistent API Resource transformers and a consistent error envelope. | M |
| FR-API-004 | API endpoints are rate-limited and scoped by token ability/role. | M |

### 3.20 Payments (`PAY`)

| ID | Requirement | Priority |
|---|---|---|
| FR-PAY-001 | Payments can be recorded via Stripe, PayPal, Flutterwave, Paystack, cash, POS terminal, or bank transfer, behind a common payment-gateway abstraction (Strategy pattern). | M |
| FR-PAY-002 | A single invoice can be settled by multiple payments/methods (split payment). | M |
| FR-PAY-003 | Refunds are supported per payment method where the gateway allows it, requiring an elevated permission and reason code. | M |
| FR-PAY-004 | Card data is never stored by HMS directly; gateway tokenization/hosted fields are used exclusively. | M |

---

## 4. Non-Functional Requirements

### 4.1 Performance (`NFR-PERF`)

| ID | Requirement |
|---|---|
| NFR-PERF-001 | Dashboard and room-availability views respond within 500ms server-side at p95 under nominal load (defined in performance test plan), aided by Redis caching of read-heavy aggregates. |
| NFR-PERF-002 | Reservation creation correctly serializes concurrent bookings for the same room/date range (DB-level locking/unique constraints), preventing double-booking under concurrent load. |
| NFR-PERF-003 | List endpoints/views are paginated; N+1 queries are prevented via eager loading, enforced in CI via a query-count assertion in feature tests. |
| NFR-PERF-004 | Long-running or bulk operations (reports, bulk emails, payroll runs) execute via queued jobs, not synchronously in the request cycle. |

### 4.2 Security (`NFR-SEC`)

| ID | Requirement |
|---|---|
| NFR-SEC-001 | All input is validated via Form Requests; all output is escaped by default (Blade) to prevent XSS. |
| NFR-SEC-002 | All state-changing requests are CSRF-protected; API endpoints use token auth instead of session/CSRF. |
| NFR-SEC-003 | All database access uses the query builder/Eloquent (parameter binding); no raw string-interpolated SQL. |
| NFR-SEC-004 | Authentication endpoints and other sensitive endpoints (password reset, MFA) are rate-limited. |
| NFR-SEC-005 | All security-relevant actions are recorded in an immutable audit/activity log (Spatie activitylog), including actor, IP, and timestamp. |
| NFR-SEC-006 | Sensitive data at rest (e.g., ID document numbers) is encrypted using Laravel's encryption; passwords use bcrypt/argon2 hashing. |
| NFR-SEC-007 | HTTP responses set standard security headers (CSP, X-Content-Type-Options, X-Frame-Options/frame-ancestors, Referrer-Policy, HSTS in production). |
| NFR-SEC-008 | Authorization is checked server-side on every request via Policies/Gates; client-side hiding of controls is never the sole control. |
| NFR-SEC-009 | Mass assignment is prevented via explicit `$fillable`/Form Request `validated()` usage — never blanket `$guarded = []` on user-writable models. |

### 4.3 Availability & Reliability (`NFR-AVAIL`)

| ID | Requirement |
|---|---|
| NFR-AVAIL-001 | The system targets 99.5% uptime for a single-node production deployment; the architecture must not preclude horizontal scaling later. |
| NFR-AVAIL-002 | Documented backup and disaster-recovery procedures exist for the database and uploaded media, with defined RPO/RTO targets. |

### 4.4 Usability & Accessibility (`NFR-UX`)

| ID | Requirement |
|---|---|
| NFR-UX-001 | UI is responsive from 360px (mobile housekeeping/waitstaff use) through desktop widths. |
| NFR-UX-002 | UI supports light and dark modes with a persisted user preference. |
| NFR-UX-003 | Core workflows (check-in, POS order entry) are usable on a tablet with touch targets ≥ 44px. |

### 4.5 Maintainability (`NFR-MAINT`)

| ID | Requirement |
|---|---|
| NFR-MAINT-001 | Code adheres to the project coding standards (Pint, PHPStan, strict types) enforced in CI. |
| NFR-MAINT-002 | Business logic is covered by automated tests; the project targets ≥95% coverage on non-generated code, tracked in CI. |
| NFR-MAINT-003 | Modules are organized to minimize cross-module coupling (see architecture doc), so a module can be modified/tested in isolation. |

### 4.6 Internationalization

| ID | Requirement |
|---|---|
| NFR-I18N-001 | Currency, timezone, and date/number formatting are branch-configurable. |
| NFR-I18N-002 | UI strings are routed through Laravel's localization layer even if only English is shipped initially, so additional locales are addable without code changes. |

---

## 5. External Interface Requirements

- **Payment Gateways:** Stripe, PayPal, Flutterwave, Paystack — via server-side SDKs behind a common `PaymentGateway` contract (Strategy pattern), never exposing secret keys client-side.
- **SMS/WhatsApp:** Abstracted `NotificationChannel` drivers; concrete provider (e.g., Twilio) configured per deployment.
- **Email:** Standard Laravel Mail (SMTP or a transactional provider) using queued Mailables.

---

## 6. Data Requirements (Summary)

Full ERD and migrations are Deliverable 3 (`docs/architecture/erd.md` and `database/migrations/*`). Key entity families implied by this SRS: `tenants`, `branches`, `users`/`roles`/`permissions`, `rooms`/`room_types`/`room_rates`, `reservations`/`reservation_rooms`, `guests`, `folios`/`folio_charges`, `housekeeping_tasks`, `maintenance_work_orders`, `restaurant_outlets`/`tables`/`menu_items`/`orders`, `inventory_items`/`stock_movements`/`purchase_orders`, `chart_of_accounts`/`journal_entries`, `employees`/`attendance`/`leave`/`payroll_runs`, `loyalty_accounts`/`coupons`, `events`/`event_bookings`, `payments`/`invoices`, `activity_log` (Spatie), `media` (Spatie).

---

## 7. Compliance & Audit

- All financial and identity-related actions must be traceable to a user, timestamp, and (where applicable) reason — supports both internal audit and external hotel-industry/tax audit requirements.
- ID document handling must support a data-retention/erasure policy (configurable retention period per branch jurisdiction) — flagged as a required configuration point, not hardcoded.

---

## 8. Acceptance Criteria (Definition of Done, per module)

A module is considered done when:

1. All **Must**-priority FRs for that module are implemented and demoed against this document.
2. Feature tests cover the happy path, validation failures, and authorization boundaries for every FR in the module.
3. The module's API endpoints (if any) are documented in the OpenAPI spec.
4. Pint/PHPStan pass with zero errors on changed code.
5. Relevant seeders/factories produce realistic demo data for the module.

---

## 9. Open Questions for Stakeholder Sign-off

These are explicitly flagged rather than silently assumed:

1. Which payment gateways are required for v1 vs. later phases (all four, or a prioritized subset)?
2. Is MFA mandatory for any role at launch, or fully optional?
3. Target scale (rooms per branch, branches per tenant, concurrent users) — needed to size performance test targets in `NFR-PERF`.
4. Which SMS/WhatsApp provider will be used in production (affects the notification driver to build first)?
5. Jurisdiction(s) for tax rules and ID-retention policy at launch.

---

*This SRS is the authoritative baseline for subsequent deliverables. Changes to scope should be reflected here first, with a version bump, before implementation.*
