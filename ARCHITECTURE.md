# Architecture

Aqdi is a Laravel 10 backend for Saudi rental-contract documentation (Ejar-related flows). This document describes the **feature-based modular architecture** the codebase is moving toward, and the rules for adding or migrating a feature.

Phase 0 (Shared Infrastructure) is in place. **Catalog** (Phase 1) lives in `app/Modules/Catalog/`. Remaining features stay in the legacy Laravel layout until they are migrated.

## Surfaces

One application serves four HTTP surfaces. URLs, HTTP methods, request fields, and JSON envelopes must stay stable unless a change is explicitly approved.

| Surface | Route prefix | Middleware | Auth |
|---|---|---|---|
| Public website (Blade) | `/` | `web` | Session `web` guard → `User` |
| Public/mobile API v1 | `api/` | `api` | Sanctum → `User` |
| Public/mobile API v2 | `api/v2/` | `api` | Sanctum → `User` |
| Admin panel API | `api/admin/` | `api` | Sanctum → `Employee` + `permission:` |

There is **no Filament** admin panel. Admin is a JSON API consumed by a separate SPA.

## Directory layout

```
app/
├── Modules/                      # One folder per business feature (added as features migrate)
│   └── FeatureName/
│       ├── Models/
│       ├── Controllers/
│       │   ├── Api/              # v1 adapters
│       │   ├── Api/V2/
│       │   ├── Admin/
│       │   └── Website/          # Blade adapters — keep existing URLs
│       ├── Requests/
│       ├── Resources/
│       ├── Services/
│       ├── Actions/
│       ├── Policies/
│       ├── Events/
│       ├── Listeners/
│       ├── Jobs/
│       ├── Notifications/
│       ├── Enums/
│       ├── Exceptions/
│       └── Routes/
│           ├── api.php
│           ├── api_v2.php
│           ├── admin.php
│           └── web.php
├── Shared/                       # Cross-feature infrastructure only
│   ├── Responses/                # JSON envelope (Responser) + encoding
│   ├── Helpers/                  # Global helpers + SaudiMobile
│   └── Routing/                  # ModuleRouteLoader
├── Http/                         # Legacy controllers/requests/resources until migrated
├── Models/                       # Legacy models until migrated
├── Services/                     # Legacy services until migrated
├── Support/                      # Domain helpers still tied to unmigrated features
└── Providers/
    ├── RouteServiceProvider.php  # Legacy route files + module loader
    └── ModuleServiceProvider.php
```

Create a subdirectory only when the feature actually has that kind of class. Do not scaffold empty folders.

## Responsibilities

| Layer | Owns | Must not own |
|---|---|---|
| **Controller** | Receive request, authorize, call Action/Service, return Resource/view | Validation rules, multi-model workflows, payment/SMS/Firebase calls, JSON shaping beyond Resource |
| **Form Request** | `rules()`, `authorize()`, input normalization | Persistence, notifications, queries |
| **Action** | One business operation (e.g. `CreateContractAction`) | HTTP responses |
| **Service** | Multi-step workflows and coordination | HTTP responses, becoming a god class |
| **API Resource** | Transform a model/array to JSON | Queries, writes, external APIs |
| **Policy** | Authorization for a model/feature | Business orchestration |
| **Model** | Relations, casts, scopes, accessors | External APIs, multi-aggregate workflows |
| **Module Routes** | Feature endpoints with the **same** URL as today | Changing paths or methods |

## Shared vs Modules

A feature may depend on `app/Shared/`.

`Shared` must **not** depend on a specific feature module. If a helper needs `WebsiteImage` or `Contract`, it belongs in that feature (or stay in legacy `app/Support` until that feature migrates).

Do not put catalog/auth/contract logic in Shared.

## JSON API contract

Do **not** replace the existing envelope. Clients depend on `App\Shared\Responses\Responser` (legacy alias: `App\Http\Traits\Responser`):

```json
{
  "message": "...",
  "code": 200,
  "success": true,
  "data": {}
}
```

Validation/errors use `success: false` plus `errors` where `errorResponse()` is used. Admin lists use `data.items` + `data.pagination`.

New API code should `use App\Shared\Responses\Responser`. Existing controllers may keep `App\Http\Traits\Responser` (it forwards to Shared).

## Routing

### Legacy files (source of truth until a feature migrates)

- `routes/web.php`
- `routes/api.php`
- `routes/api_v2.php`
- `routes/admin.php`

`RouteServiceProvider` still loads these first, with the same middleware and prefixes as before.

### Module loader

`App\Shared\Routing\ModuleRouteLoader` scans `app/Modules/*/Routes/` and loads, when present:

| File | Middleware | Prefix |
|---|---|---|
| `api.php` | `api` | `api` |
| `api_v2.php` | `api` | `api/v2` |
| `admin.php` | `api` | `api/admin` |
| `web.php` | `web` | (none) |

The loader is invoked from `RouteServiceProvider` inside `$this->routes()` so `php artisan route:cache` includes module routes.

**When moving a feature:** copy its routes into the module file with **identical** URIs, methods, names, and middleware, then **remove those routes from the legacy file** in the same change. Never register the same URL twice.

**Website:** do not delete, rename, or alter `web.php` routes unless explicitly approved. Unused-looking website routes stay.

## Catalog (migrated)

`app/Modules/Catalog/` owns lookup data used by contracts and the public/admin APIs:

- Models: City, Region, BankAccount, Paperwork, ContractPeriod, PaymentType, ReaEstatType, ReaEstatUsage, UnitType, UnitUsage, UsageUnit, ServicesPricing, TenantRole
- Public GETs under `api/` and `api/v2/` (cities, regions, bank-accounts, services-pricing, paperwork, real-estat-type, real-estat-usage, units-types, units-usage, payments-types, contract-periods)
- Public `api/v2/tenant-roles` (index is implemented; extra REST verbs stay registered)
- Admin CRUD under `api/admin/` (regions, cities, real-estate-types, real-estate-usages, unit-types, unit-usages, tenant-roles, contract-periods, paperworks, payment-types)

Legacy `App\Models\*` and `App\Http\Resources\*` class names remain as aliases. Website `get-cities` stays on the website contract controller (not Catalog). FAQ, settings, popups, instrument/contract types, and `real-estates` admin are not Catalog.

`TenantRoleIsInUseAction` and `PaymentTypeIsInUseAction` query `App\Models\Contract` until Contracts is migrated.

## Auth (migrated)

`app/Modules/Auth/` owns login, signup, verification, password reset, logout, and employee session (login / refresh / me / profile / fcm / logout):

- Public `api/` and `api/v2/` auth groups (same URIs, unnamed inner routes)
- Authenticated profile / password / fcm / notifications / deactivate live on **Users** `AccountController`
- Admin employee session routes (`employees.login`, `employees.refresh-token`, `employees.me`, `employees.profile`, `employees.fcm`, `employees.logout`); employee CRUD stays on `EmployeeController`
- Website login / signup / password / logout / verification (only routes that existed at migrate time)

`Employee`, `Admin`, `Seo`, Kernel middleware, and auth config stay in the shared app until Employees migrate. `User` lives in Users with a legacy alias.

## Users (migrated)

`app/Modules/Users/` owns client identity and account management:

- Model: `User` (legacy `App\Models\User` alias)
- Admin clients API under `api/admin/users` (list, export, show, block, delete, nested properties/units/deed, per-user coupons and custom discount)
- Public Sanctum account routes (`GET|POST /profile`, `POST /update/password`, `POST /fcm`, `GET /notifications`, `POST /user/deactivate`) on v1 and v2
- Website profile / photo / deactivate (`profile`, `update.profile`, `update.profile.photo`, `RemoveProfile`)

Auth keeps login/signup/OTP/reset/logout. Coupons catalog, analytics clients, and `UserCouponService` / `UserCustomDiscountService` stay outside Users; the admin Users controller calls those services. `GET /notifications` still reads `Offer` until Notifications migrates.

## Planned modules

Migrate in this order:

1. Catalog (cities, regions, types, usages, periods, paperwork, bank accounts, tenant roles) — **done**
2. Auth — **done**
3. Users — **done**
4. Employees
5. Settings
6. RealEstate
7. Contracts
8. Payments
9. Coupons
10. Notifications
11. Content (includes website Blade pages)
12. Marketing, Seo, Analytics, Finance

Website is a **channel**, not a module. Blade controllers live under `Modules/{Feature}/Controllers/Website`.

## Dependency rules

Dependencies point inward toward stable core:

```
Catalog, Settings, Users, Employees
        ↑
   RealEstate, Coupons, Notifications
        ↑
     Contracts  ←  Payments (interface / events, not controller imports)
        ↑
 Marketing, Seo, Analytics, Finance, Content
```

Avoid circular module imports. Break `Contracts ↔ Payments` with `PaymentGatewayInterface` and domain events when that feature is migrated.

## Naming

- Modules: singular business name (`Contracts`, `RealEstate`, `Catalog`)
- Actions: verb + entity (`CreateContractAction`, `SubmitContractStep1Action`)
- Keep public typo URLs (`realState`, `Coupon`) until a dedicated, approved rename

## How to add a new feature

1. Add `app/Modules/FeatureName/` with only the folders you need.
2. Put the Model, Actions/Services, Requests, Resources, and HTTP adapters in that module.
3. Register routes in `Modules/FeatureName/Routes/{api,api_v2,admin,web}.php` using the same prefixes the loader applies. Do not change existing public URLs.
4. If the admin SPA needs a gate, add the section to `config/permissions.php` first.
5. Bind feature services in `ModuleServiceProvider` only when a binding is required.
6. Add or update tests for the behavior you touch.
7. Run Pint, `composer dump-autoload`, `php artisan route:list`, and the relevant tests.

## How to migrate an existing feature

1. Identify models, controllers (Website / Api / Api V2 / Admin), requests, resources, services, jobs, tests, and routes.
2. Add tests for critical behavior if they do not exist.
3. Extract Actions/Services **without** changing rules; then move classes and update namespaces.
4. Move routes into the module and delete the copies from `routes/*.php` in the same change.
5. Grep for the old namespace. Fix every reference.
6. Verify: `optimize:clear`, `route:list`, tests, logs.

Do not change database schema for cleanliness. Do not drop API v1. Do not invent repositories, DTO layers, or interfaces unless there is a real abstraction need (the payment gateway interface already exists).

## Authorization

Admin authorization today is `permission:{section}.{action}` middleware driven by `config/permissions.php`. Keep those names. Policies can be added per feature without removing the middleware until the SPA contract is verified.

Catalog registers Laravel policies in `AuthServiceProvider` that mirror existing `permission:{section}.{action}` middleware (they do not replace the middleware). Other features still rely on middleware only.

## Testing

- PHPUnit (`tests/Unit`, `tests/Feature`). No in-memory SQLite by default — tests use the configured database.
- Prefer feature tests for HTTP contracts and unit tests for Actions/helpers.
- After each feature migration: `php artisan test` (or a focused filter), `vendor/bin/pint`, `php artisan route:list`.
- There is no Larastan/PHPStan in this project today.

## Backward compatibility

Preserve:

- API and website URLs, methods, and parameter names
- JSON envelope and pagination shape
- Auth guards and permission section names
- Database tables/columns
- Website Blade routes even if they look unused

Do not:

- Drop unused website routes
- Change the JSON envelope
- Rewrite production migrations
- Merge User and Employee auth
