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

**Stack:** Laravel 12 · PHP 8.2+ · MySQL 8 · Blade · Bootstrap 5 · AdminLTE 4 ·
[spatie/laravel-permission](https://spatie.be/docs/laravel-permission) 8 · Vite 7

---

## 1. Deployment

### Prerequisites

| Requirement | Version | Verify with |
|---|---|---|
| PHP | 8.2 or newer | `php -v` |
| Composer | 2.x | `composer -V` |
| Node.js | 20 or newer | `node -v` |
| npm | 10 or newer | `npm -v` |
| MySQL | 8.0 | `mysql --version` |

Required PHP extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`,
`xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `curl`.

> The app runs on **MySQL**, not sqlite. `pdo_sqlite` is not required and is not
> installed on the target servers. Note that `.env.example` still ships Laravel's
> stock `DB_CONNECTION=sqlite` — the steps below overwrite it, do not skip them.

---

### 1.1 Local / development deployment

Run these in order from the project root. Every line is required on a fresh clone.

```bash
# ── 1. Get the code ──────────────────────────────────────────────────────────
git clone <repo-url> guru-traders-erp
cd guru-traders-erp

# ── 2. Install dependencies ──────────────────────────────────────────────────
composer install
npm install

# ── 3. Environment file ──────────────────────────────────────────────────────
cp .env.example .env
php artisan key:generate

# ── 4. Create both databases (app + test) ────────────────────────────────────
mysql -u root -p -e "CREATE DATABASE guru_traders      CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE DATABASE guru_traders_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# ── 5. Point .env at MySQL ───────────────────────────────────────────────────
#    Edit .env and set:
#      DB_CONNECTION=mysql
#      DB_HOST=127.0.0.1
#      DB_PORT=3306
#      DB_DATABASE=guru_traders
#      DB_USERNAME=root
#      DB_PASSWORD=<your password>

# ── 6. Migrate + seed  ⬅ THE IMPORTANT STEP ──────────────────────────────────
php artisan migrate --seed

# ── 7. Build front-end assets ────────────────────────────────────────────────
npm run dev            # dev server, keep this terminal running

# ── 8. Serve ─────────────────────────────────────────────────────────────────
php artisan serve      # in a second terminal
```

Open <http://localhost:8000> and log in with the seeded Super Admin:

| | |
|---|---|
| Email | `admin@gurutraders.com` |
| Password | `Guru@123` |

Both are read from `SUPER_ADMIN_EMAIL` / `SUPER_ADMIN_PASSWORD` in `.env`.
**Change them before deploying anywhere but local.**

---

### 1.2 What `php artisan migrate --seed` actually does

This is the single command that turns an empty database into a working one. It
runs **19 migrations**, then **8 seeders in dependency order** — the order is not
cosmetic, each seeder needs the rows the previous one created.

```bash
php artisan migrate --seed
```

**Migrations (19)** — creates 45 tables:

| Group | Tables |
|---|---|
| Framework | `users`, `cache`, `jobs`, `sessions` |
| Permissions | `permissions`, `roles`, `model_has_roles`, `role_has_permissions`, … |
| Reference | lookup tables, `number_series`, `countries` / `states` / `cities` |
| Masters | `categories`, `products`, `product_incentives`, `agents`, `agent_commissions`, `buyers`, `buyer_carton_markings`, `suppliers`, `supplier_contacts`, `category_format` |

**Seeders (8)** — run automatically by `DatabaseSeeder`:

| # | Seeder | Creates | Why this position |
|---|---|---|---|
| 1 | `PermissionsSeeder` | 107 permissions from `config/permissions.php` | Roles cannot be granted permissions that don't exist |
| 2 | `RolesSeeder` | 8 system roles + their permission matrix | Needs permissions to exist |
| 3 | `SuperAdminSeeder` | The `admin@gurutraders.com` login | Needs the Super Admin role to exist |
| 4 | `NumberSeriesSeeder` | Auto-code counters | The code generator has no counter until this runs — the first Category save fails without it |
| 5 | `LookupSeeder` | Units, ports, currencies, HSN, incoterms, payment terms, countries, … | Feeds every master dropdown |
| 6 | `SupplierLookupSeeder` | Supplier types, designations | Kept separate so Supplier can be reseeded without touching buyer/product lists |
| 7 | `GeoSeeder` | States and cities | Needs countries from `LookupSeeder` |
| 8 | `AgentSeeder` | Sample agents | Gives the Buyer form's agent dropdown data to show |

Useful variants:

```bash
php artisan migrate --seed          # first deploy — migrate, then seed
php artisan migrate:fresh --seed    # DROP everything and rebuild (destroys data)
php artisan db:seed                 # re-run seeders only
php artisan db:seed --class=LookupSeeder   # re-run one seeder
```

> `migrate:fresh` drops every table. Never run it on a database that has real
> client data in it.

---

### 1.3 Production deployment

```bash
# ── 1. Pull and install without dev packages ─────────────────────────────────
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci

# ── 2. Environment ───────────────────────────────────────────────────────────
#    In .env set:
#      APP_ENV=production
#      APP_DEBUG=false
#      APP_URL=https://<your-domain>
#      DB_* → production credentials
#      SUPER_ADMIN_PASSWORD → a real password, not Guru@123
php artisan key:generate --force      # first deploy only — this invalidates
                                      # existing sessions and encrypted data

# ── 3. Database ──────────────────────────────────────────────────────────────
php artisan migrate --force           # --force is required outside local;
                                      # migrate refuses to run in production
                                      # without it
php artisan db:seed --force           # FIRST DEPLOY ONLY (see note below)

# ── 4. Sync permissions on every subsequent deploy ───────────────────────────
php artisan permission:sync --roles

# ── 5. Build assets ──────────────────────────────────────────────────────────
npm run build

# ── 6. Cache for speed ───────────────────────────────────────────────────────
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ── 7. Permissions on writable directories ───────────────────────────────────
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

> **`db:seed --force` on the first deploy only.** The seeders are idempotent for
> permissions and roles, but `AgentSeeder` inserts sample agents — re-running it
> on a live database adds demo rows a client will see. On later deploys use
> `php artisan permission:sync --roles` instead, which is the safe subset.

**Redeploy (no schema change):**

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan permission:sync --roles
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

**Web server document root** must point at `public/`, not the project root.

---

### 1.4 Troubleshooting deployment

| Symptom | Cause | Fix |
|---|---|---|
| `could not find driver` | `pdo_mysql` missing | `sudo apt install php8.3-mysql && sudo systemctl restart php8.3-fpm` |
| `Access denied for user` | `.env` credentials wrong | Check `DB_USERNAME` / `DB_PASSWORD`, then `php artisan config:clear` |
| `Unknown database 'guru_traders'` | Step 4 skipped | Create the database, re-run `php artisan migrate --seed` |
| Menu items missing after deploy | Permissions not synced | `php artisan permission:sync --roles` |
| Blank page / stale CSS | Assets not built, or cached config | `npm run build` and `php artisan optimize:clear` |
| `No application encryption key` | Step 3 skipped | `php artisan key:generate` |
| Config changes ignored | Cached config | `php artisan config:clear` |
| Tests fail with `Unknown database` | Test DB missing | Create `guru_traders_test` (step 4) |

---

## 2. Modules completed

**8 modules are live**, plus authentication. The 5 masters and the Users and
Roles screens each have list, create, view, edit, soft delete and an
activate/deactivate toggle, all guarded by per-action permissions. Permissions is
deliberately read-only (see below).

### Masters — 5 of 7 complete

| Module | Route | Status | Notes |
|---|---|---|---|
| Category | `/masters/categories` | ✅ Complete | Auto-code, PO format links |
| Product | `/masters/products` | ✅ Complete | Incentives, HSN & drawback, price band, GST rate, fabric specs |
| Buyer | `/masters/buyers` | ✅ Complete | Carton markings, cascading Country → State → City, commission |
| Supplier / Jobber | `/masters/suppliers` | ✅ Complete | Contacts, categories supplied, agent cascade, GST/PAN/MSME, bank, discount & credit days |
| Agent | `/masters/agents` | ✅ Complete | Commission structure, party-type filtering |
| PO Format | — | ⬜ Not started | Scope not defined by client |
| Markup | — | ⬜ Not started | Scope not defined by client |

### User Management — 3 of 3 complete

| Module | Route | Status |
|---|---|---|
| Users | `/user-management/users` | ✅ Complete |
| Roles | `/user-management/roles` | ✅ Complete |
| Permissions | `/user-management/permissions` | ✅ Complete (read-only + sync) |

### Platform — complete

| Module | Status | Notes |
|---|---|---|
| Authentication & Profile | ✅ Complete | Laravel Breeze — login, password reset, profile |
| Access control | ✅ Complete | 107 permissions · 8 roles · `Gate::before()` Super Admin bypass |
| Reference data | ✅ Complete | Lookups, number series, Country/State/City cascade endpoints |
| Sidebar navigation | ✅ Complete | Permission-gated, collapsible |

### Access control detail

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

| Role | Permissions | Scope |
|---|---:|---|
| Super Admin | 107 (all) | Bypasses every check via `Gate::before()` |
| Admin | 102 | Full operations; cannot edit roles |
| Merchandising & Manufacturing | 36 | Inquiry → PO, products, suppliers |
| Accounts | 35 | Bills, payments, commission, outstanding |
| Export Documentation & Foreign Payment | 20 | Shipment, export docs, foreign receipts |
| Packing | 11 | Packing lists and cartons |
| Quality Checker | 9 | Inward inspection |
| Jobworker | 3 | External jobber — own POs only |

> ⚠️ **Do not assign the Jobworker role yet.** Per-jobber data scoping needs
> `users.supplier_id`. The `suppliers` table now exists, but the scoping column
> and the query filters do not — until they land, an unscoped
> `purchase-order.view` would expose every jobber's rates to every other jobber.

**Roles vs masters.** A jobber exists in two separate places, and they are not
interchangeable:

- **`suppliers` master** (done) — the business record: company, GST, bank, rate
  slabs. Referenced by every PO, bill and payment.
- **`Jobworker` role** (done) — a login for the jobber portal, linked to a
  supplier row. Optional; only jobbers who need portal access get a user account.

Agents are master records only — they have no login role. If agents need a
portal, that is a new role, not a change to an existing one.

---

## 3. Project structure

```
guru-traders-erp/
│
├── app/
│   ├── Console/Commands/
│   │   └── SyncPermissions.php          php artisan permission:sync
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/                    Breeze — login, reset, verify
│   │   │   ├── Masters/                 Category, Product, Buyer,
│   │   │   │                            Supplier, Agent, Geo
│   │   │   └── UserManagement/          User, Role, Permission
│   │   │
│   │   └── Requests/                    validation lives here, never in
│   │       ├── Masters/                 controllers — Store*/Update* pairs
│   │       └── UserManagement/          per module
│   │
│   ├── Models/
│   │   ├── Concerns/
│   │   │   ├── Filterable.php           shared index-page filtering
│   │   │   └── HasAuditColumns.php      created_by / updated_by, automatic
│   │   ├── Agent.php  Buyer.php  Category.php  Product.php  Supplier.php
│   │   └── …lookup models (Currency, Port, Incoterm, State, City, …)
│   │
│   ├── Providers/
│   │   └── AppServiceProvider.php       Gate::before — Super Admin bypass
│   │
│   ├── Services/                        business logic; controllers stay thin
│   │   ├── Masters/                     one service per master
│   │   ├── NumberSeriesService.php      auto-code generation
│   │   ├── RoleService.php
│   │   └── UserService.php
│   │
│   └── Support/
│       └── PermissionRegistry.php       reads config/permissions.php
│
├── config/
│   └── permissions.php                  ⭐ SOURCE OF TRUTH — roles + permissions
│
├── database/
│   ├── migrations/                      19 migrations, 45 tables
│   ├── seeders/                         8 seeders, run in dependency order
│   └── factories/
│
├── resources/views/
│   ├── components/ui/                   card · field · select · textarea ·
│   │                                    form-section · status-badge ·
│   │                                    delete-form · empty-state
│   ├── layouts/                         app · header · sidebar · guest
│   ├── masters/                         one folder per master, each with
│   │                                    index · create · edit · show · _form
│   ├── user-management/                 users · roles · permissions
│   └── auth/  profile/
│
├── routes/
│   ├── web.php                          app routes — masters + user management
│   ├── auth.php                         Breeze auth routes
│   └── console.php
│
├── tests/
│   ├── Feature/Masters/                 Agent · Buyer · Category ·
│   │                                    Product · Supplier
│   ├── Feature/Auth/                    Breeze auth coverage
│   └── Feature/UserManagementTest.php
│
└── public/                              ⬅ web server document root
```

### Conventions worth knowing

- **Controllers stay thin.** Business logic goes in `app/Services/`, validation
  in `app/Http/Requests/`.
- **Permissions live in the controller**, not the route file — each controller
  declares them via `HasMiddleware` + `Middleware('permission:…')`, so a new
  action cannot be added without also deciding its permission.
- **Laravel 12's base `Controller` is an empty abstract class** — `$this->middleware()`
  does not exist. Use the `HasMiddleware` interface.
- **Route ordering matters.** Static routes such as `products/check-code` are
  declared *before* `Route::resource`, or `products/{product}` swallows them.
- **All masters soft delete.** Records are never physically removed.

### Adding a module

Six steps, in order — this is what keeps permissions and code from drifting apart.

1. Declare the permission in `config/permissions.php`
2. `php artisan permission:sync`
3. Grant it to roles (config + `--roles`, or the Roles UI)
4. Guard the controller with `HasMiddleware` + `Middleware('permission:…')`
5. Gate the sidebar entry with `@can`
6. Gate the action buttons with `@can`

---

## 4. Testing

```bash
php artisan test                                  # full suite
php artisan test --filter=Masters                 # all master modules
php artisan test --filter=SupplierTest            # one class
php artisan test tests/Feature/Masters/BuyerTest.php
```

### Test environment

The suite runs against **MySQL** (`guru_traders_test`), not the Laravel default
of `sqlite::memory:` — testing on the same engine as production catches enum,
generated-column and index-length behaviour that sqlite accepts silently. The
connection is set in `phpunit.xml`; the database must be created once by hand
(step 4 of deployment).

### Coverage

| Suite | Tests |
|---|---|
| `Masters/` — Agent, Buyer, Category, Product, Supplier | Field-level create/update, code uniqueness, cascades, soft delete, in-use guards |
| `UserManagementTest` | Users, roles, permission gating |
| `Auth/` | Login, registration, password reset/update/confirm, email verification |
| `ProfileTest` | Profile edit and account deletion |

### Current status — 175 passing, 13 failing

Run on `feature/master-corrections` (~4½ min):

```
Tests:    13 failed, 175 passed (573 assertions)
```

The 13 failures are **known and confined to two files** — they are fallout from
the recent Buyer/Category corrections, not a broken deployment:

| File | Failing | Cause |
|---|---:|---|
| `Feature/Masters/BuyerTest.php` | 12 | `UrlGenerationException` and `ErrorException` — the test fixtures still build routes and payloads against the pre-correction Buyer schema |
| `Feature/Masters/CategoryTest.php` | 1 | `po_format_id` comes back `null` — the `category_format` pivot is not populated on create |

Everything else — all Agent, Product and Supplier tests, all user management,
all auth — passes. Fix these before merging to `main`.

---

## 5. Features

### Built and working

- **Role-based access control** — 107 permissions across 8 roles, config-driven,
  with a Super Admin bypass and a UI-safe sync command.
- **Five master modules** — Category, Product, Buyer, Supplier, Agent, each with
  full CRUD, soft delete, status toggle and audit columns.
- **Auto-generated codes** — number-series driven, with a live "is this code
  free?" check on the create forms.
- **Cascading geography** — Country → State → City dropdowns backed by seeded
  reference data and dedicated JSON endpoints.
- **Nested master data** — product incentives, buyer carton markings with live
  preview, supplier contacts, agent commission structures.
- **Reusable Blade UI kit** — card, field, select, textarea, form-section,
  status-badge, delete-form, empty-state, so every screen looks the same.
- **Permission-gated collapsible sidebar** — menu items appear only when the
  signed-in user actually holds the permission.
- **Audit trail on every master** — `created_by` / `updated_by` filled
  automatically by the `HasAuditColumns` concern.

### Planned

Ordered roughly by build sequence. Sidebar entries already exist and are
permission-mapped for all of these; the screens are what's missing.

| Area | Modules |
|---|---|
| Remaining masters | PO Format · Contract · Markup — *blocked, client has not defined scope* |
| Reference screens | Lookups UI · Number Series UI · Company profile · Activity log |
| Sales cycle | Inquiry → Quotation → Order Confirmation |
| Production cycle | Sample Approval → Purchase Order → Material Issue |
| Inward cycle | Inward Entry → QC → Debit Note |
| Dispatch | Packing List → Shipment |
| Export | Export Document set |
| Accounts | Purchase Bill · Payments · Foreign Payment · Agent Commission · Outstanding |
| Reporting | Reports · Dashboard |
| Portal | Jobworker portal — *needs `users.supplier_id` scoping first* |

### Open questions

Items still needing client confirmation before their module can start:

1. **PO Format** — field list and template structure not supplied
2. **Contract** — scope not defined
3. **Markup** — calculation rules not defined
4. **Unit Master list** — the sheet is still marked "on hold"

---

## Quick reference

```bash
# Setup
php artisan migrate --seed              # migrate + seed (first deploy)
php artisan migrate:fresh --seed        # rebuild from scratch (destroys data)

# Permissions
php artisan permission:sync             # add newly declared permissions
php artisan permission:sync --roles     # also re-apply role mappings
php artisan permission:sync --prune     # remove permissions dropped from config

# Run
npm run dev                             # asset dev server
php artisan serve                       # app on :8000
composer dev                            # server + queue + logs + vite together

# Test
php artisan test

# Cache
php artisan optimize:clear              # clear config, route, view caches
```

| | |
|---|---|
| Local URL | <http://localhost:8000> |
| Login | `admin@gurutraders.com` / `Guru@123` |
| App database | `guru_traders` |
| Test database | `guru_traders_test` |
| Permission source of truth | `config/permissions.php` |
