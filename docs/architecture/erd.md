# Entity-Relationship Design

Companion to [`system-architecture.md`](system-architecture.md). This is the authoritative data-model reference; `database/migrations/*` must match it (if they ever diverge, this document is wrong and should be corrected in the same PR that changes the migration).

Conventions used throughout (see `coding-standards.md` for the full rationale):

- All tables: `id` (unsigned bigint PK), `created_at`, `updated_at`; `deleted_at` (soft delete) called out explicitly where used.
- All monetary columns are integer **minor units** (cents) — suffixed `_cents` — never `float`.
- Every table below the tenancy level carries `branch_id`; tenancy-level tables carry `tenant_id`.
- `snake_case` table names, plural; FK columns named `<singular>_id`.

## 1. Tenancy, Branch & Identity

```mermaid
erDiagram
    TENANTS ||--o{ BRANCHES : owns
    TENANTS ||--o{ USERS : employs
    BRANCHES ||--o{ BRANCH_USER : "staffed by"
    USERS ||--o{ BRANCH_USER : "assigned to"
    USERS }o--o{ ROLES : "has (spatie)"
    ROLES }o--o{ PERMISSIONS : grants

    TENANTS {
        bigint id PK
        string name
        string slug UK
        string default_currency
        string default_timezone
        boolean is_active
    }
    BRANCHES {
        bigint id PK
        bigint tenant_id FK
        string name
        string code UK "short branch code, e.g. NYC-01"
        string currency
        string timezone
        string address_line1
        string city
        string country
        time check_in_time
        time check_out_time
        json cancellation_policy
        boolean is_active
    }
    USERS {
        bigint id PK
        bigint tenant_id FK "nullable for role=Guest across tenants"
        string name
        string email UK
        timestamp email_verified_at
        string password
        boolean mfa_enabled
        string mfa_secret "encrypted"
        timestamp password_changed_at
        int failed_login_attempts
        timestamp locked_until
        bigint current_branch_id FK "nullable, active branch in session"
        timestamp deleted_at
    }
    BRANCH_USER {
        bigint id PK
        bigint branch_id FK
        bigint user_id FK
        bigint role_id FK "spatie roles.id, role held at this branch"
        boolean is_primary
    }
```

Spatie `laravel-permission`'s own tables (`roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`) are used as-is (global roles, per the architecture decision in §3 of the system architecture doc); `BRANCH_USER.role_id` references `roles.id` to record which role a user holds *at a specific branch*, independent of any tenant-wide role also assigned directly on `model_has_roles`.

## 2. Room Management

```mermaid
erDiagram
    BRANCHES ||--o{ ROOM_TYPES : defines
    BRANCHES ||--o{ ROOMS : contains
    ROOM_TYPES ||--o{ ROOMS : categorizes
    ROOM_TYPES ||--o{ ROOM_RATES : "priced by"
    ROOMS ||--o{ ROOM_STATUS_LOGS : tracks
    ROOM_TYPES }o--o{ AMENITIES : offers

    ROOM_TYPES {
        bigint id PK
        bigint branch_id FK
        string name
        string slug
        int base_capacity_adults
        int base_capacity_children
        bigint base_rate_cents
        text description
        boolean is_active
    }
    ROOMS {
        bigint id PK
        bigint branch_id FK
        bigint room_type_id FK
        string room_number UK "unique per branch"
        string building
        string floor
        enum status "vacant_clean|vacant_dirty|occupied|out_of_order|out_of_service"
        enum housekeeping_status "clean|dirty|inspected|in_progress"
        boolean is_active
        timestamp deleted_at
    }
    ROOM_RATES {
        bigint id PK
        bigint room_type_id FK
        enum rate_type "base|seasonal|weekend|holiday|override"
        date starts_on "nullable for base"
        date ends_on "nullable for base"
        json days_of_week "nullable, for weekend rules"
        bigint rate_cents
        int priority "resolves overlapping rules; higher wins"
    }
    AMENITIES {
        bigint id PK
        string name
        string icon
    }
    ROOM_STATUS_LOGS {
        bigint id PK
        bigint room_id FK
        string from_status
        string to_status
        bigint changed_by_user_id FK
        string reason
    }
```

Room images use Spatie MediaLibrary's `media` table against the `ROOMS` and `ROOM_TYPES` models (collection `room-images`) rather than a bespoke table.

## 3. Guest Management

```mermaid
erDiagram
    TENANTS ||--o{ GUESTS : "known to"
    GUESTS ||--o{ GUEST_DOCUMENTS : has
    GUESTS ||--o{ GUEST_CONTACTS : has
    GUESTS ||--o{ GUEST_NOTES : has
    GUESTS ||--o| LOYALTY_ACCOUNTS : has

    GUESTS {
        bigint id PK
        bigint tenant_id FK
        string first_name
        string last_name
        string email
        string phone
        date date_of_birth
        string nationality
        enum guest_type "individual|corporate|travel_agent"
        enum flag "none|vip|blacklisted"
        string blacklist_reason "nullable"
        json preferences
        timestamp deleted_at
    }
    GUEST_DOCUMENTS {
        bigint id PK
        bigint guest_id FK
        enum document_type "passport|national_id|visa|other"
        string document_number "encrypted"
        string issuing_country
        date expires_on
    }
    GUEST_CONTACTS {
        bigint id PK
        bigint guest_id FK
        enum relation_type "family|emergency|companion"
        string name
        string phone
        string relationship
    }
    GUEST_NOTES {
        bigint id PK
        bigint guest_id FK
        bigint created_by_user_id FK
        text note
        boolean is_alert "surfaced prominently at front desk, e.g. allergy"
    }
```

Guest document numbers use Laravel's `encrypted` cast (NFR-SEC-006); the `GUEST_DOCUMENTS` table itself is additionally covered by the media-retention scheduled purge described in the architecture doc where a scanned copy is attached via MediaLibrary (`guest-documents` collection).

## 4. Reservation Management

```mermaid
erDiagram
    GUESTS ||--o{ RESERVATIONS : books
    BRANCHES ||--o{ RESERVATIONS : hosts
    RESERVATIONS ||--o{ RESERVATION_ROOMS : includes
    ROOM_TYPES ||--o{ RESERVATION_ROOMS : "requested as"
    ROOMS ||--o{ RESERVATION_ROOMS : "assigned to (nullable until check-in)"
    RESERVATIONS ||--o{ RESERVATION_STATUS_LOGS : tracks
    RESERVATIONS ||--o| WAITLIST_ENTRIES : "may originate from"
    CORPORATE_ACCOUNTS ||--o{ RESERVATIONS : "bills to (nullable)"

    RESERVATIONS {
        bigint id PK
        bigint branch_id FK
        bigint guest_id FK "primary/booking guest"
        bigint corporate_account_id FK "nullable"
        string confirmation_code UK
        enum source "walk_in|phone|online|corporate|group|ota"
        enum status "pending|confirmed|checked_in|checked_out|cancelled|no_show"
        date arrival_date
        date departure_date
        int adults
        int children
        text special_requests
        bigint cancellation_fee_cents "nullable"
        timestamp cancelled_at
        timestamp deleted_at
    }
    RESERVATION_ROOMS {
        bigint id PK
        bigint reservation_id FK
        bigint room_type_id FK
        bigint room_id FK "nullable until assigned"
        bigint rate_cents "locked-in nightly rate at booking time"
        int occupants_adults
        int occupants_children
    }
    RESERVATION_STATUS_LOGS {
        bigint id PK
        bigint reservation_id FK
        string from_status
        string to_status
        bigint changed_by_user_id FK "nullable, system for automated no-show"
        string reason
    }
    WAITLIST_ENTRIES {
        bigint id PK
        bigint branch_id FK
        bigint guest_id FK
        bigint room_type_id FK
        date desired_arrival
        date desired_departure
        enum status "waiting|notified|converted|expired"
    }
    CORPORATE_ACCOUNTS {
        bigint id PK
        bigint tenant_id FK
        string company_name
        string billing_email
        bigint negotiated_rate_cents "nullable"
        boolean direct_billing_enabled
    }
```

A unique partial constraint (enforced at the application/service layer via a locking query, documented in `system-architecture.md` §3 NFR-PERF-002) prevents two `RESERVATION_ROOMS` from double-booking the same `room_id` over an overlapping date range once a physical room is assigned.

## 5. Front Desk & Folio (Billing)

```mermaid
erDiagram
    RESERVATIONS ||--o| FOLIOS : generates
    GUESTS ||--o{ FOLIOS : "billed to"
    FOLIOS ||--o{ FOLIO_CHARGES : contains
    FOLIOS ||--o{ PAYMENTS : "settled by"
    FOLIO_CHARGES ||--o| RESTAURANT_ORDERS : "sourced from (nullable)"

    FOLIOS {
        bigint id PK
        bigint branch_id FK
        bigint reservation_id FK
        bigint guest_id FK
        enum status "open|closed|voided"
        bigint balance_cents "denormalized running balance"
        timestamp closed_at
    }
    FOLIO_CHARGES {
        bigint id PK
        bigint folio_id FK
        enum charge_type "room|tax|service_fee|restaurant|late_checkout|early_checkin|misc|discount"
        string description
        bigint amount_cents
        date charge_date
        bigint posted_by_user_id FK "nullable, system for automated night-audit room charge"
    }
    PAYMENTS {
        bigint id PK
        bigint branch_id FK
        bigint folio_id FK "nullable — POS-only payments settle against an order instead"
        enum method "stripe|paypal|flutterwave|paystack|cash|pos_terminal|bank_transfer"
        bigint amount_cents
        string currency
        enum status "pending|completed|failed|refunded|partially_refunded"
        string gateway_reference "nullable"
        bigint received_by_user_id FK "nullable, null for gateway webhooks"
        text refund_reason "nullable"
    }
```

Room-upgrade/transfer (FR-FD-004) is modeled as: update the relevant `RESERVATION_ROOMS.room_id`, log a `ROOM_STATUS_LOGS` entry for both rooms, and post an offsetting `FOLIO_CHARGES` pair (credit old rate, charge new rate) rather than a bespoke "transfer" table — this keeps the folio the single source of truth for what the guest owes.

## 6. Housekeeping & Maintenance

```mermaid
erDiagram
    ROOMS ||--o{ HOUSEKEEPING_TASKS : scheduled
    USERS ||--o{ HOUSEKEEPING_TASKS : "assigned to"
    ROOMS ||--o{ LOST_FOUND_ITEMS : "found in"
    ROOMS ||--o{ MAINTENANCE_WORK_ORDERS : "raised for"
    ASSETS ||--o{ MAINTENANCE_WORK_ORDERS : "raised for (nullable, non-room asset)"
    USERS ||--o{ MAINTENANCE_WORK_ORDERS : "assigned to"
    BRANCHES ||--o{ ASSETS : owns

    HOUSEKEEPING_TASKS {
        bigint id PK
        bigint branch_id FK
        bigint room_id FK
        bigint assigned_to_user_id FK "nullable until assigned"
        bigint inspected_by_user_id FK "nullable"
        enum task_type "checkout_clean|stayover_clean|deep_clean|inspection"
        enum status "pending|in_progress|awaiting_inspection|completed|failed_inspection"
        date scheduled_date
        timestamp started_at
        timestamp completed_at
        json checklist "item => bool"
    }
    LOST_FOUND_ITEMS {
        bigint id PK
        bigint branch_id FK
        bigint room_id FK "nullable"
        string description
        bigint found_by_user_id FK
        date found_on
        enum status "held|returned|disposed"
        bigint returned_to_guest_id FK "nullable"
    }
    ASSETS {
        bigint id PK
        bigint branch_id FK
        string name
        string serial_number
        date purchased_on
        date warranty_expires_on
        string location
    }
    MAINTENANCE_WORK_ORDERS {
        bigint id PK
        bigint branch_id FK
        bigint room_id FK "nullable"
        bigint asset_id FK "nullable"
        bigint reported_by_user_id FK
        bigint assigned_to_user_id FK "nullable"
        enum priority "low|medium|high|urgent"
        enum status "open|in_progress|completed|verified"
        text description
        bigint parts_cost_cents
        bigint labor_cost_cents
        boolean is_preventive
        bigint recurrence_days "nullable, for preventive schedules"
        timestamp completed_at
        timestamp verified_at
    }
```

A room with an open `MAINTENANCE_WORK_ORDERS` row flagged `is_blocking = true` (derived from priority, not a stored column) is excluded from availability by the Reservation module's availability query (FR-MAINT-005) — implemented as a scope joining this table, not a denormalized flag on `ROOMS`, so the two never drift out of sync.

## 7. Restaurant & POS

```mermaid
erDiagram
    BRANCHES ||--o{ RESTAURANT_OUTLETS : operates
    RESTAURANT_OUTLETS ||--o{ RESTAURANT_TABLES : has
    RESTAURANT_OUTLETS ||--o{ MENU_CATEGORIES : has
    MENU_CATEGORIES ||--o{ MENU_ITEMS : contains
    MENU_ITEMS ||--o{ MENU_ITEM_INGREDIENTS : "consumes (recipe)"
    RESTAURANT_TABLES ||--o{ RESTAURANT_ORDERS : hosts
    GUESTS ||--o{ RESTAURANT_ORDERS : "placed by (nullable, walk-in diners)"
    RESTAURANT_ORDERS ||--o{ RESTAURANT_ORDER_ITEMS : contains

    RESTAURANT_OUTLETS {
        bigint id PK
        bigint branch_id FK
        string name
        enum outlet_type "restaurant|bar|room_service|banquet"
    }
    RESTAURANT_TABLES {
        bigint id PK
        bigint outlet_id FK
        string label
        int seats
        enum status "free|occupied|reserved"
    }
    MENU_CATEGORIES {
        bigint id PK
        bigint outlet_id FK
        string name
        int display_order
    }
    MENU_ITEMS {
        bigint id PK
        bigint menu_category_id FK
        string name
        bigint price_cents
        string tax_class
        boolean is_available
    }
    MENU_ITEM_INGREDIENTS {
        bigint id PK
        bigint menu_item_id FK
        bigint inventory_item_id FK
        decimal quantity
        string unit
    }
    RESTAURANT_ORDERS {
        bigint id PK
        bigint branch_id FK
        bigint outlet_id FK
        bigint table_id FK "nullable for room service/counter"
        bigint guest_id FK "nullable"
        bigint folio_id FK "nullable, set when posted to room"
        enum order_type "dine_in|room_service|takeaway"
        enum status "open|sent_to_kitchen|served|closed|voided"
        bigint discount_cents
        bigint tax_cents
        bigint total_cents
        bigint opened_by_user_id FK
    }
    RESTAURANT_ORDER_ITEMS {
        bigint id PK
        bigint order_id FK
        bigint menu_item_id FK
        int quantity
        bigint unit_price_cents
        json modifiers
        enum kitchen_status "queued|preparing|ready|served"
        bigint split_group "nullable, groups items for split-bill-by-guest"
    }
```

Completing a `RESTAURANT_ORDER_ITEMS` line (`kitchen_status = served` and the order closes) dispatches an event consumed by the Inventory module to deduct `MENU_ITEM_INGREDIENTS.quantity × RESTAURANT_ORDER_ITEMS.quantity` from stock (FR-POS-006) — inventory is never decremented directly by POS code.

## 8. Inventory & Procurement

```mermaid
erDiagram
    BRANCHES ||--o{ WAREHOUSES : has
    WAREHOUSES ||--o{ INVENTORY_ITEMS : stores
    INVENTORY_ITEMS ||--o{ STOCK_MOVEMENTS : tracks
    SUPPLIERS ||--o{ PURCHASE_ORDERS : fulfills
    PURCHASE_ORDERS ||--o{ PURCHASE_ORDER_ITEMS : contains
    INVENTORY_ITEMS ||--o{ PURCHASE_ORDER_ITEMS : "ordered as"
    PURCHASE_ORDERS ||--o{ GOODS_RECEIPTS : "received via"
    PURCHASE_REQUESTS ||--o| PURCHASE_ORDERS : "approved into (nullable)"

    WAREHOUSES {
        bigint id PK
        bigint branch_id FK
        string name
        enum type "main_store|kitchen|bar|housekeeping"
    }
    INVENTORY_ITEMS {
        bigint id PK
        bigint warehouse_id FK
        string sku UK
        string name
        string unit_of_measure
        string barcode
        int reorder_point
        int quantity_on_hand "denormalized, derived from stock_movements"
        bigint average_cost_cents
        date expires_on "nullable, perishables"
        boolean is_perishable
    }
    STOCK_MOVEMENTS {
        bigint id PK
        bigint inventory_item_id FK
        enum movement_type "receipt|issue|transfer|adjustment|wastage"
        int quantity "signed: positive in, negative out"
        bigint unit_cost_cents
        string reference_type "polymorphic: PurchaseOrder, RestaurantOrder, manual"
        bigint reference_id
        bigint recorded_by_user_id FK
    }
    SUPPLIERS {
        bigint id PK
        bigint tenant_id FK
        string name
        string contact_email
        string payment_terms
    }
    PURCHASE_REQUESTS {
        bigint id PK
        bigint branch_id FK
        bigint requested_by_user_id FK
        enum status "pending|approved|rejected|converted"
        text justification
    }
    PURCHASE_ORDERS {
        bigint id PK
        bigint branch_id FK
        bigint supplier_id FK
        string po_number UK
        enum status "draft|sent|partially_received|received|closed|cancelled"
        bigint total_cents
    }
    PURCHASE_ORDER_ITEMS {
        bigint id PK
        bigint purchase_order_id FK
        bigint inventory_item_id FK
        int quantity_ordered
        int quantity_received
        bigint unit_cost_cents
    }
    GOODS_RECEIPTS {
        bigint id PK
        bigint purchase_order_id FK
        bigint received_by_user_id FK
        date received_on
        boolean has_discrepancy
        text discrepancy_notes
    }
```

Inventory valuation (FR-INV-005) is computed from `STOCK_MOVEMENTS` using weighted-average cost, recalculated incrementally on each `receipt` movement and stored denormalized on `INVENTORY_ITEMS.average_cost_cents` for fast reads — `STOCK_MOVEMENTS` remains the append-only source of truth.

## 9. Accounting

```mermaid
erDiagram
    BRANCHES ||--o{ ACCOUNTS : "charts of accounts"
    ACCOUNTS ||--o{ JOURNAL_LINES : posted
    JOURNAL_ENTRIES ||--o{ JOURNAL_LINES : contains
    BRANCHES ||--o{ JOURNAL_ENTRIES : records
    CORPORATE_ACCOUNTS ||--o{ AR_ENTRIES : owes
    SUPPLIERS ||--o{ AP_ENTRIES : "owed by branch"
    BRANCHES ||--o{ CASHBOOK_ENTRIES : records
    BRANCHES ||--o{ TAX_RULES : defines

    ACCOUNTS {
        bigint id PK
        bigint branch_id FK
        string code UK
        string name
        enum account_type "asset|liability|equity|revenue|expense"
        bigint parent_account_id FK "nullable, for hierarchy"
    }
    JOURNAL_ENTRIES {
        bigint id PK
        bigint branch_id FK
        date entry_date
        string reference_type "polymorphic source: Folio, RestaurantOrder, PurchaseOrder, PayrollRun"
        bigint reference_id
        string memo
        bigint created_by_user_id FK "nullable, system-generated"
    }
    JOURNAL_LINES {
        bigint id PK
        bigint journal_entry_id FK
        bigint account_id FK
        enum side "debit|credit"
        bigint amount_cents
    }
    AR_ENTRIES {
        bigint id PK
        bigint branch_id FK
        bigint corporate_account_id FK
        bigint folio_id FK "nullable"
        bigint amount_cents
        date due_date
        enum status "open|partially_paid|paid|written_off"
    }
    AP_ENTRIES {
        bigint id PK
        bigint branch_id FK
        bigint supplier_id FK
        bigint purchase_order_id FK "nullable"
        bigint amount_cents
        date due_date
        enum status "open|partially_paid|paid|disputed"
    }
    CASHBOOK_ENTRIES {
        bigint id PK
        bigint branch_id FK
        bigint cashier_user_id FK
        enum entry_type "cash_in|cash_out"
        bigint amount_cents
        string reason
        date shift_date
        boolean reconciled
    }
    TAX_RULES {
        bigint id PK
        bigint branch_id FK
        string name
        decimal rate_percent
        enum applies_to "room|restaurant|event|all"
    }
```

Journal entries always balance (`SUM(debit) = SUM(credit)` per `journal_entry_id`), enforced at the Action layer (`PostJournalEntryAction`) rather than a DB constraint, since MySQL cannot express a cross-row balance check declaratively — this is covered by a dedicated Unit test per `coding-standards.md`'s testing section.

## 10. Human Resources

```mermaid
erDiagram
    BRANCHES ||--o{ EMPLOYEES : employs
    USERS ||--o| EMPLOYEES : "is (nullable — not every employee has system access)"
    EMPLOYEES ||--o{ ATTENDANCE_RECORDS : logs
    EMPLOYEES ||--o{ LEAVE_REQUESTS : submits
    EMPLOYEES ||--o{ PAYROLL_LINES : "paid via"
    PAYROLL_RUNS ||--o{ PAYROLL_LINES : contains
    BRANCHES ||--o{ PAYROLL_RUNS : runs

    EMPLOYEES {
        bigint id PK
        bigint branch_id FK
        bigint user_id FK "nullable"
        string employee_number UK
        string first_name
        string last_name
        string position
        date hired_on
        date terminated_on "nullable"
        bigint base_salary_cents
        enum pay_frequency "monthly|biweekly|weekly"
    }
    ATTENDANCE_RECORDS {
        bigint id PK
        bigint employee_id FK
        date work_date
        timestamp clock_in
        timestamp clock_out
        enum source "biometric|manual|self_service"
    }
    LEAVE_REQUESTS {
        bigint id PK
        bigint employee_id FK
        enum leave_type "annual|sick|unpaid|other"
        date starts_on
        date ends_on
        enum status "pending|approved|rejected"
        bigint approved_by_user_id FK "nullable"
    }
    PAYROLL_RUNS {
        bigint id PK
        bigint branch_id FK
        date period_start
        date period_end
        enum status "draft|processed|paid"
    }
    PAYROLL_LINES {
        bigint id PK
        bigint payroll_run_id FK
        bigint employee_id FK
        bigint gross_cents
        bigint deductions_cents
        bigint net_cents
        json breakdown
    }
```

## 11. CRM, Loyalty & Events

```mermaid
erDiagram
    GUESTS ||--o| LOYALTY_ACCOUNTS : has
    LOYALTY_ACCOUNTS ||--o{ LOYALTY_TRANSACTIONS : logs
    BRANCHES ||--o{ COUPONS : issues
    GUESTS ||--o{ FEEDBACK_ENTRIES : submits
    BRANCHES ||--o{ EVENT_SPACES : has
    EVENT_SPACES ||--o{ EVENT_BOOKINGS : "booked as"
    GUESTS ||--o{ EVENT_BOOKINGS : "booked by (nullable, corporate contact instead)"
    EVENT_BOOKINGS ||--o{ EVENT_BOOKING_ITEMS : contains

    LOYALTY_ACCOUNTS {
        bigint id PK
        bigint guest_id FK
        string membership_number UK
        enum tier "silver|gold|platinum"
        int points_balance
    }
    LOYALTY_TRANSACTIONS {
        bigint id PK
        bigint loyalty_account_id FK
        enum transaction_type "earn|redeem|expire|adjust"
        int points
        string reference_type "polymorphic: Folio, RestaurantOrder"
        bigint reference_id
    }
    COUPONS {
        bigint id PK
        bigint branch_id FK
        string code UK
        enum discount_type "percent|fixed"
        decimal discount_value
        date valid_from
        date valid_until
        int max_uses
        int used_count
        enum scope "room|restaurant|event|all"
    }
    FEEDBACK_ENTRIES {
        bigint id PK
        bigint branch_id FK
        bigint guest_id FK
        enum category "compliment|complaint|suggestion"
        text message
        enum status "open|in_progress|resolved"
        bigint assigned_to_user_id FK "nullable"
    }
    EVENT_SPACES {
        bigint id PK
        bigint branch_id FK
        string name
        int capacity
        enum space_type "conference_hall|meeting_room|banquet_hall"
    }
    EVENT_BOOKINGS {
        bigint id PK
        bigint branch_id FK
        bigint event_space_id FK
        bigint guest_id FK "nullable"
        bigint corporate_account_id FK "nullable"
        string event_name
        enum event_type "conference|wedding|banquet|meeting"
        timestamp starts_at
        timestamp ends_at
        enum status "tentative|confirmed|completed|cancelled"
        bigint total_cents
    }
    EVENT_BOOKING_ITEMS {
        bigint id PK
        bigint event_booking_id FK
        enum item_type "venue|catering|equipment|service"
        string description
        bigint amount_cents
    }
```

## 12. Cross-Cutting: Audit & Media

Handled entirely by first-party packages, not bespoke tables:

- **`activity_log`** (Spatie `laravel-activitylog`) — every model that implements `LogsActivity` (all financially/identity-sensitive models per NFR-SEC-005: `RESERVATIONS`, `FOLIOS`, `PAYMENTS`, `GUEST_DOCUMENTS`, `JOURNAL_ENTRIES`, role/permission changes) logs create/update/delete with a diff, the causer, and a batch UUID for grouping multi-model operations.
- **`media`** (Spatie `laravel-medialibrary`) — polymorphic attachment for `room-images`, `guest-documents`, `branch-assets`, `lost-found` collections (§9 of the architecture doc).

## 13. Indexing Notes

Beyond FK indexes (automatic with `constrained()`), the following composite indexes are required from the first migration that creates each table, since these are the queries the dashboard and availability engine run continuously:

- `rooms (branch_id, status)` — room-status board.
- `reservation_rooms (room_id, room_type_id)` plus a query-level date-range check against `reservations (arrival_date, departure_date)` — availability search.
- `reservations (branch_id, status, arrival_date)` — arrivals/departures board.
- `folios (branch_id, status)` — outstanding invoices dashboard tile.
- `stock_movements (inventory_item_id, created_at)` — running balance / valuation queries.
- `activity_log (subject_type, subject_id)` — already indexed by the package; verified, not re-implemented.
