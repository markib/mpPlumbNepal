# AGENTS.md

PlumbNepal — on-demand plumbing marketplace for Nepal.  
Backend: Laravel 13 (PHP 8.3+, PostgreSQL + PostGIS)  
Frontend: React 18 + TypeScript + Vite + Tailwind CSS + Leaflet  
Auth: Laravel Sanctum | Real-time: Laravel Reverb (Pusher-compatible)  
Payments: eSewa, Khalti, IME Pay, COD (stubs) | i18n: react-i18next (Nepali/English)

## Architecture

- API-first monolith. All JSON under `/api/v1`. No Blade beyond welcome page.
- Thin controllers → business logic in `app/Services/`
- Geospatial: PostGIS `geography(POINT,4326)` with Haversine fallback for non-PgSQL
- Booking lifecycle: `pending → proposed → contracted → in_progress → completed`
  - 4-digit OTP (`contract_start_code`) verified at job start
- Frontend: feature modules under `resources/js/features/` (booking/, ai/)
- AI: Laravel AI pipeline (`app/Ai/`, `app/Services/AI/`) with local Ollama fallback

## Commands

| Target | Command |
|---|---|
| Full quality gate | `composer quality` (lint → stan → test, in order) |
| Backend tests | `composer test` / `vendor/bin/phpunit` |
| Unit-only tests | `composer test:unit` |
| CI backend tests | `vendor/bin/phpunit --configuration phpunit.ci.xml` |
| Frontend tests | `npm test` (Vitest) |
| Frontend build | `npm run build` |
| Dev servers | `php artisan serve` + `npm run dev` + `php artisan reverb:start` + `php artisan queue:work` |
| Migrations | `php artisan migrate` / `migrate:fresh` |
| Seed | `php artisan db:seed` |
| Lint (check) | `composer lint` |
| Lint (auto-fix) | `composer lint-fix` |
| Static analysis | `composer stan` (PHPStan level 8) |

**Windows:** Use `npm.cmd` if `npm.ps1` blocked by execution policy.

## Testing

- Two PHPUnit configs: `phpunit.xml` (SQLite in-memory local), `phpunit.ci.xml` (PostgreSQL CI)
- Tests use `RefreshDatabase` trait
- Vitest: `pool: threads`, `maxThreads: 1`, `isolate: true` — single-threaded
- Frontend tests in `resources/js/**/*.test.{ts,tsx}`
- Verify both PostGIS and Haversine fallback paths for dispatch/location changes

## Domain gotchas

- Nepal address: `ward_number`, `tole_name`, `landmark` + lat/lng — preserve everywhere
- Plumber search radius default: 15km (`.env` `PLUMBER_SEARCH_RADIUS`)
- `citizenship_verified = true` required before plumber can accept jobs
- Payment: `initiate` + `callback` endpoints; stubs only

## Coding conventions

- Laravel Pint style (`pint.json`: Laravel preset, ordered imports, strict types, concat spaces)
- Form Requests for validation, Policies for authorization, API Resources for responses
- Constructor property promotion, readonly properties, enums over magic strings
- Transactions for multi-row status transitions (proposal accept/expire, contract creation)
- DI over facades in domain services. No `save()` in loops. Prefer eager loading, scopes, typed properties.
- Before writing code: read existing impl → search similar patterns → reuse existing Services/DTOs/Actions/Form Requests/Policies. Never duplicate abstractions.

## Completion gate

Before marking any task done:
```
composer quality
```
Pint passes + PHPStan passes + all tests pass.

## Existing docs

- `docs/request-to-contract-workflow.md` — proposal/contract state machine deep-dive
- `.github/copilot-instructions.md` — additional coding standards
- `boost.json` — Copilot agent + skill config
