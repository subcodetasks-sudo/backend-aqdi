# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

"Aqdi" (أقدي) is a Laravel 10 backend for a Saudi real-estate rental contract platform (Ejar-related
documentation flows). It serves three distinct client surfaces from one codebase:

- **Public website** — server-rendered Blade views (`resources/views/website/**`), routed via
  `routes/web.php`. Sessions/CSRF, uses the `web` guard (`App\Models\User`).
- **Admin panel API** — a JSON API consumed by a separate frontend SPA, routed via `routes/admin.php`
  and registered under prefix `api/admin` with the `api` middleware group (see `RouteServiceProvider`).
  Uses Sanctum-authenticated `Employee`/`Admin` models, not the `admin` session guard despite the name.
- **Mobile/public API** — `routes/api.php` (v1, prefix `api`) and `routes/api_v2.php` (v2, prefix
  `api/v2`), consumed by the mobile app and possibly other web clients. New work should generally target
  `Api\V2` controllers unless matching existing v1 behavior.

Everything under `routes/admin.php` and `routes/api*.php` returns JSON; only `routes/web.php` renders
Blade templates.

## Common commands

```bash
php artisan serve                       # run the app locally
php artisan migrate                     # run migrations
php artisan migrate:fresh --seed        # rebuild DB from scratch with seeders

php artisan test                        # run the full test suite (PHPUnit)
php artisan test --filter=TestName      # run a single test by method/class name
php artisan test tests/Feature/Admin/SomeTest.php   # run a single test file
vendor/bin/phpunit                      # equivalent direct invocation

vendor/bin/pint                         # format PHP code (Laravel Pint)
vendor/bin/pint --test                  # check formatting without writing changes

npm run dev                             # Vite dev server (public website assets only)
npm run build                           # Vite production build
```

Tests live in `tests/Unit` and `tests/Feature` (see `phpunit.xml`); there is no dedicated `sqlite`
in-memory test DB configured by default — check `.env`/`phpunit.xml` before assuming test DB isolation.

## Route/controller/guard map

| Route file | URL prefix | Middleware group | Controllers namespace | Auth guard / model |
|---|---|---|---|---|
| `routes/web.php` | `/` | `web` | `App\Http\Controllers\Website` | `web` guard → `User`, custom `LoginWebsite` middleware |
| `routes/admin.php` | `api/admin` | `api` | `App\Http\Controllers\Admin` | Sanctum, `auth:sanctum` per-route → `Employee`/`Admin` |
| `routes/api.php` | `api` | `api` | `App\Http\Controllers\Api` | Sanctum → `User` |
| `routes/api_v2.php` | `api/v2` | `api` | `App\Http\Controllers\Api\V2` | Sanctum → `User` |

Auth guards are defined in `config/auth.php`: `web` (users), `admin` (admins), `employee` (employees),
`seo` (seos), and `api` (sanctum, users). Note the admin panel API mostly relies on `auth:sanctum` at
the route level rather than the `admin` session guard.

Routes inside each file are grouped by feature with `Route::prefix()->name()->controller()->group()`;
follow this pattern (kebab-case URL segments, dot-namespaced route names) when adding endpoints.

## Permission system (admin panel)

Permissions are dynamic, not hardcoded to fixed roles. `config/permissions.php` defines the matrix of
`sections` (e.g. `analytics`, `employees`, `employee_salaries`, `roles`, `settings`, ...) × `actions`
(`view`, `create`, `edit`, `delete`, `retrieve`). Each section×action pair maps to a row in the
`Permission` model (`name` = `"{section}.{action}"`).

`App\Services\Admin\RolePermissionResolver` turns a `permission_matrix` (section => [actions]) or a raw
list of permission IDs into permission IDs when creating/editing a `Role`/`TenantRole`, and can sync any
missing section×action permissions into the DB (`syncAllPermissionsFromConfig`).

When adding a new admin feature that needs its own gate, add the section to `config/permissions.php`
first, then check the specific `section.action` permission in the controller/middleware — follow the
pattern of existing gated sections like `employees` or `employee_salaries` (a separate, more sensitive
section from `employees` because salary data needs a stricter check) rather than reusing an unrelated
section out of convenience.

## Contract status state machine

Contracts move through a documentation workflow, modeled by `App\Support\ContractStatusCase` (an enum
resolving from a status id/name) and consumed by `App\Services\ContractStatusCaseService`, which
provides per-status validation rules and post-validation checks (e.g. `EJAR_AUTHENTICATION` requires a
deed number/type, `SEND_DRAFT` requires a draft number and a contact-number choice). `ContractStatus`,
`DraftContractStatus`, and `ContractStatusHistory` models track current/historical state; `ReceivedContract`
and `RefundableContract` model the "received" and "returned/refunded" side flows. When adding new statuses
or status-dependent fields, extend `ContractStatusCase` and the service's `match` arms rather than
branching ad hoc in controllers.

## Payment gateways

`App\Interfaces\PaymentGatewayInterface` defines the full contract for a hosted-payment gateway
(build redirect URL, process IPN/webhook, sync/confirm payment status, compute cart amount). Moyasar is
the current implementation (`App\Services\MoyasarPaymentService`); swap or add gateways by implementing
this interface and rebinding it in the container rather than branching on gateway name in controllers.
`ContractInvoiceService` computes/persists the `Invoice` for a contract and derives price/status display
info from the contract, payment, and `DocFee` support class.

## Multi-language content

Arabic/English pairs are handled per-model, not via Laravel's translation files, for most
admin-manageable content (see `config/permissions.php`'s `ar`/`en` label pairs, and `_en`/`_ar` suffixed
columns across models like `Blog`, `ContentPage`, `MessageAlertSection`). `App\Http\Middleware\SetLocale`
(web) and `App\Http\Middleware\ApiLocalization` (api) resolve the active locale per-request; `lang/`
holds framework/validation strings only.

## Response conventions (JSON APIs)

Controllers under `Admin`/`Api`/`Api\V2` use the `App\Http\Traits\Responser` trait for consistent JSON
shapes: `apiResponse($data, $msg, $code)`, `successMessage`, `errorMessage`, `errorResponse`, and
`paginatedApiResponse($paginator, $items, $message, $meta)` which returns `data.items` +
`data.pagination`. Use these helpers instead of ad hoc `response()->json()` calls in new endpoints, and
match the existing `paginate()` shape (current_page/last_page/from/to/total/etc.) for list endpoints.

## External integrations

- **Moyasar** — payment gateway (`MoyasarPaymentService`, `MOYASAR_*` env vars).
- **Firebase** — push notifications (`FirebaseNotificationService`, `FIREBASE_*` env vars, service
  account JSON referenced by `FIREBASE_CREDENTIALS`).
- **Twilio** / **Taqnyat** — SMS (`TwilioService`, `SmsLog` model, `TWILIO_*` env vars).
- **Google API client** (`google/apiclient`) and **Laravel Socialite** — social auth / Google
  integrations.
- **DomPDF** / **niklasravnsborg/laravel-pdf** — contract/paperwork PDF generation.
- **Laravel Telescope** — request/query debugging, gated by `TELESCOPE_ENABLED`.

## Postman collections

`postman/` and `tools/generate_*_postman_collection.php` scripts generate/maintain Postman collections
per feature area (admin full API, employee KPIs, operating expenses, contract units, etc.) directly from
route definitions. When adding or changing admin routes in a documented area, check whether the
corresponding `tools/generate_*.php` script needs updating and regenerate its collection.
