# Guru Traders ERP

Export trading management system for **Guru Traders** — an export-only trading
company that sources garments from external jobbers and ships them to overseas
buyers with a full export document set.

```
Buyer (+ Agent) → Guru Traders → Jobber (+ Agent) → Guru Traders → Export → Buyer

Inquiry → Quotation → OC → Sample Approval → PO → Material Issue
       → Inward → QC → Packing → Shipment → Export Docs → Payment
```

Guru Traders does not manufacture, does not sell domestically, and keeps no ready
stock — goods are made only after a buyer order is confirmed.

---

## Build status

Only **User Management** is functional. Everything else is designed and
permission-mapped, but the screens are not built yet — they appear greyed out in
the sidebar.

| Phase | Module | Status |
|---|---|---|
| 0 | Roles, Permissions & Users | ✅ **Done** |
| 1 | CRUD skeleton + Category reference module | ⬜ Next |
| 2 | Lookups screen (units, ports, currencies, HSN, sizes, …) | ⬜ |
| 3 | Agent & Category | ⬜ *blocked — Agent field list not supplied* |
| 4 | Product (+ incentives, rates, packing specs) | ⬜ |
| 5 | Buyer (+ carton markings) | ⬜ |
| 6 | Supplier / Jobber (+ qty-slab rates) | ⬜ |
| — | PO Format · Contract · Markup | ⬜ *blocked — scope not defined* |
| 7 | Inquiry → Quotation → OC | ⬜ |
| 8 | Sample → PO → Material Issue | ⬜ |
| 9 | Inward → QC → Debit Note | ⬜ |
| 10 | Packing → Shipment | ⬜ |
| 11 | Export Documents | ⬜ |
| 12 | Accounts & Payments | ⬜ |
| 13 | Reports & Dashboard | ⬜ |
| 14 | Jobworker Portal | ⬜ |

**Masters (client-confirmed):** Supplier · Buyer · Agent · Product · Category ·
PO Format · Contract · Markup

Full breakdown and checklist: **[docs/PROJECT_PLAN.md](docs/PROJECT_PLAN.md)**

---

## Documentation

| Document | What's in it |
|---|---|
| **[docs/SETUP.md](docs/SETUP.md)** | Install, run, verify, troubleshoot, deploy checklist |
| **[docs/PROJECT_PLAN.md](docs/PROJECT_PLAN.md)** | Build phases, per-phase checklist, sidebar structure, open blockers |
| **[docs/DATABASE_SCHEMA.md](docs/DATABASE_SCHEMA.md)** | ~45 tables, column-level, with the reasoning behind the tricky ones |

---

## Tech stack

Laravel 12 · PHP 8.2+ · MySQL 8 · Blade · Bootstrap 5 · AdminLTE 4 ·
[spatie/laravel-permission](https://spatie.be/docs/laravel-permission) 8 · Vite

---

## Quick start

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate
# set DB_DATABASE / DB_USERNAME / DB_PASSWORD in .env

mysql -u root -p -e "CREATE DATABASE guru_traders CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE DATABASE guru_traders_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

php artisan migrate:fresh --seed
npm run dev          # keep running
php artisan serve
```

Open <http://localhost:8000> — login `admin@gurutraders.com` / `Guru@123`.

Verify with `php artisan test` (expect **40 passed**).

Detailed steps, prerequisites and troubleshooting: **[docs/SETUP.md](docs/SETUP.md)**.

---

## Access control

Permissions are declared in **`config/permissions.php`** — that file is the single
source of truth. The seeder, the artisan command and the role permission matrix
all read from it.

Permissions are deliberately **not creatable through the UI**: a hand-typed name
matches no `@can()` check in the code, so it would grant nothing and fail
silently, with the only symptom being a menu item that never appears.

```bash
php artisan permission:sync            # create newly declared permissions
php artisan permission:sync --roles    # also re-apply system role mappings
php artisan permission:sync --prune    # delete permissions dropped from config
```

### Roles

| Role | Permissions | Scope |
|---|---:|---|
| Super Admin | all (132) | Bypasses every check via `Gate::before()` |
| Admin | 127 | Full operations; cannot edit roles |
| Merchandising & Manufacturing | 50 | Inquiry → PO, products, suppliers |
| Accounts | 36 | Bills, payments, commission, outstanding |
| Export Documentation & Foreign Payment | 23 | Shipment, export docs, foreign receipts |
| Quality Checker | 12 | Inward inspection |
| Packing | 11 | Packing lists and cartons |
| Jobworker | 5 | External jobber — own POs only |

> ⚠️ **Do not assign the Jobworker role yet.** Per-jobber data scoping needs
> `users.supplier_id`, which arrives with the `suppliers` table in Phase 6.
> Until then an unscoped `purchase-order.view` would expose every jobber's rates
> to every other jobber.

### Roles vs masters

A jobber exists in two separate places, and they are not interchangeable:

- **`suppliers` master** (Phase 6) — the business record: company, GST, bank,
  rate slabs. Referenced by every PO, bill and payment.
- **`Jobworker` role** (done) — a login for the jobber portal, linked to a
  supplier row. Optional; only jobbers who need portal access get a user account.

Agents are master records only — they have no login role. If agents need a
portal, that is a new role, not a change to an existing one.

---

## Project structure

```
app/
├── Console/Commands/SyncPermissions.php
├── Http/
│   ├── Controllers/UserManagement/     one folder per functional area
│   └── Requests/UserManagement/        validation lives here, not in controllers
├── Models/
├── Providers/AppServiceProvider.php    Gate::before — Super Admin bypass
├── Services/                           business logic; controllers stay thin
└── Support/PermissionRegistry.php      reads config/permissions.php

config/permissions.php                  SOURCE OF TRUTH for roles + permissions

database/seeders/                       PermissionsSeeder → RolesSeeder → SuperAdminSeeder

resources/views/
├── components/ui/                      card, field, status-badge, delete-form, empty-state
├── layouts/                            app, header, sidebar
└── user-management/                    users, roles, permissions

docs/                                   SETUP · PROJECT_PLAN · DATABASE_SCHEMA
tests/Feature/UserManagementTest.php
```

---

## Adding a module

Six steps, in order — this is what keeps permissions and code from drifting
apart. Expanded version in [docs/SETUP.md § 11](docs/SETUP.md).

1. Declare the permission in `config/permissions.php`
2. `php artisan permission:sync`
3. Grant it to roles (config + `--roles`, or the Roles UI)
4. Guard the controller with `HasMiddleware` + `Middleware('permission:…')`
5. Gate the sidebar entry with `@can`
6. Gate the action buttons with `@can`

> Laravel 12's base `Controller` is an empty abstract class — `$this->middleware()`
> does not exist. Use the `HasMiddleware` interface.

---

## Testing

```bash
php artisan test                                # full suite — 40 tests
php artisan test --filter=UserManagementTest    # one class
```

The suite runs against **MySQL** (`guru_traders_test`), not the Laravel default
of `sqlite::memory:` — testing on the same engine as production catches enum,
generated-column and index-length behaviour that sqlite accepts silently.

---

## Open questions

Ten items need client confirmation before their phase can start. The two urgent
ones block Phase 3, which in turn blocks Buyer and Supplier:

1. **Agent Master field list** — no specification sheet was supplied
2. **Unit Master list** — the sheet is marked "on hold"

Full list with the phase each one blocks: [docs/PROJECT_PLAN.md § 8](docs/PROJECT_PLAN.md).
