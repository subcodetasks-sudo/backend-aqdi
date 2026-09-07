# Database migrations

## Why they were consolidated

The development branch had **173** Laravel-loaded migration files (plus one unused nested file). Most were `add_*` / `change_*` / `rename_*` / `drop_*` patches on tables that already had a CREATE migration. That history was hard to read and could not be rebuilt from scratch on MariaDB 10.4 without workarounds (row-size 1118, `RENAME COLUMN`).

This is a **local/development** squash. The goal is one CREATE per table representing the **final** schema, not a smaller file count at any cost.

## Old vs new structure

| | Before | After |
|---|---|---|
| Laravel-loaded files | 173 | **81** |
| Nested unused file | 1 (`database/migrations/add_to/…`) | removed |
| Pattern | CREATE + many ALTERs | CREATE (final schema) in FK order |

Kept unchanged:

- `0001_01_01_000001_create_cache_table.php` (cache + cache_locks)
- `0001_01_01_000002_create_jobs_table.php` (jobs + job_batches + failed_jobs)
- `2018_08_08_100000_create_telescope_entries_table.php`
- `2019_12_14_000001_create_personal_access_tokens_table.php` (Sanctum; no later ALTERs)

All other tables: one `2026_01_01_xxxxxx_create_{table}_table.php` file, ordered so referenced tables exist before foreign keys.

## What was merged

Schema-only ALTERs were folded into the matching CREATE:

- added columns, indexes, unique constraints, foreign keys
- type / nullable / default changes
- renames (final name only, e.g. `tenant_dob`, `number_of_units_in_realestate`)
- dropped columns omitted (e.g. `contracts.property_owner_dob_hijri` / `_gregorian` → `property_owner_dob`)
- enum expansions (`instrument_type` 13 values, `authorization_type` 3 values)
- English lookup columns nullable (`name_en`, `note_en`, …)
- `users.email` unique **and** nullable; `deleted_at` (soft deletes); UTM indexes
- `coupon_usages` business columns + FKs (they were commented out in the old CREATE)
- SEO crawl tables created with final **TEXT** columns (no follow-up widen migration)
- contract file-path columns as **TEXT** so a fresh utf8mb4 migrate does not hit MySQL error 1118

No-ops / duplicates removed rather than merged:

- empty `add_dob_type` follow-up
- empty `add_sort_to_admin_table` (wrong table name `admin`)
- no-op contracts marketing TEXT migration
- `ensure_ad_spend_dailies_table` (duplicate CREATE)
- nested `add_to/…contracts_schema_delta_from_baseline.php` (not loaded by Laravel)

## What was kept separate

| Kind | Decision |
|---|---|
| Telescope | Original file unchanged |
| Cache / jobs | Laravel grouped CREATEs unchanged |
| Sanctum tokens | Original CREATE unchanged |
| Data backfills | **Not kept as migrations.** Empty-DB no-ops; seeders own catalog data. |
| Permission sync / grant | **Not kept.** `database/seeders/PermissionSeeder.php` already calls `RolePermissionResolver`. |
| Journey contract statuses | **Not kept.** `ContractStatusSeeder` already inserts those rows. |
| Default rows (`general_settings`, `app_versions`) | **Not in CREATE** (option C). Use seeders / app config. |

`contracts.tenant_role_id` has **no foreign key** in either the old fresh-migrate result or the new set. The old ALTER tried to add the FK before `tenant_roles` existed (`Schema::hasTable` skipped it). Putting that FK on the CREATE (or in a later FK-only migration) would make Schema B *stricter* than Schema A, so it was **not** added. The column remains unsigned bigint nullable with no constraint.

There is currently **no seeder** for `app_versions` or `general_settings` default rows. Option C removed those inserts from migrations; add seeders later if the app needs those rows on a fresh database. `settings`, permissions, roles, and contract statuses already have seeders.

## Verification

Performed on an isolated MariaDB 10.4 instance (port **3307**, fresh datadir). **Not** the project database `aqdi-version3` and not the broken XAMPP datadir.

1. **Schema A:** original 173-file set on `aqdi_migrate_schema_a`. Two capture workarounds (same intended end state):
   - `RENAME COLUMN` on MariaDB 10.4 replaced with equivalent `CHANGE` for `real_estates.contract_ownership`
   - contract path `VARCHAR` columns converted to `TEXT` so later ALTERs were not blocked by error 1118
2. **Schema B:** consolidated 81-file set on `aqdi_migrate_schema_b` (clean `migrate`, no workarounds).
3. Compared `information_schema` for tables, columns (type, nullable, default, extra), indexes, unique indexes, foreign keys (name, refs, onDelete/onUpdate), and JSON `CHECK` clauses.

**Result: equivalent.** 86 application tables each side (excluding `migrations`). Zero remaining diffs after five CREATE fixes (`double(8,2)` / `float(8,2)`, `contract_status_histories` not using `morphs()`, sessions primary key, `expires_at` timestamp extras).

## How to rebuild from scratch

```bash
php artisan migrate
php artisan db:seed
```

Do not expect permission rows, contract statuses, `app_versions` seed rows, or `general_settings` rows from migrations. Those come from seeders (and related app config).

## Rules for future migrations

1. **New table:** add `database/migrations/YYYY_MM_DD_HHMMSS_create_{table}_table.php` with the full intended schema (indexes + FKs). One table per file unless it is a Laravel framework pair (cache/jobs) or Telescope.
2. **Change an existing table that is already deployed:** add a new ALTER migration. Do not rewrite old CREATE files on a database that already ran them.
3. **This development squash is not a pattern for production history.** If this tree is deployed, later schema changes must be forward ALTERs.
4. **Data vs schema:** inserts, backfills, and `RolePermissionResolver` belong in seeders (or a dedicated data migration), never in CREATE.
5. **`contracts` row size:** prefer `text()` for file-path columns. Avoid adding many new `varchar(255)` columns on `contracts`.
6. **FK order:** referenced table CREATE must run first. If that is impossible, use a later FK-only migration and document why.

## Counts

- Before: 173 loaded (+ 1 unused nested)
- After: 81
- Removed: 92 loaded files + 1 nested
- Consolidated into CREATEs: schema ALTERs for all mutated tables (largest: `contracts`, `real_estates`, `settings`, `real_units`, `users`)
- Intentionally kept separate: Telescope, cache, jobs, Sanctum tokens
- Data / permission migrations retained: **0** (seeders)
