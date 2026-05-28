# PlumbNepal Agent Guide

Use this guide when working in this repository. PlumbNepal is an on-demand plumbing marketplace for Nepal with a Laravel 13 API, PostgreSQL/PostGIS dispatch logic, React TypeScript frontend, Tailwind CSS, Laravel Sanctum auth, and Laravel AI diagnosis intake.

## Project Shape

- Backend: `app/`, `routes/api.php`, `database/migrations/`, `database/seeders/`, `tests/Feature`, `tests/Unit`.
- Frontend: `resources/js`, `resources/css/app.css`, Vite, React 18, TypeScript, Tailwind, Leaflet.
- Domain docs: `docs/request-to-contract-workflow.md`.
- Public API base: `/api/v1`.
- Important flows: booking creation, nearby plumber search, proposal/contract acceptance, live plumber tracking, payments, verification uploads, and AI diagnosis.

## Working Rules

- Prefer existing project patterns over new abstractions.
- Keep Laravel controllers thin when possible; put reusable domain logic in `app/Services`.
- Preserve Nepal-specific address fields: `ward_number`, `tole_name`, `landmark`, latitude, longitude.
- Treat PostGIS paths and non-PostgreSQL fallback paths as separate behavior that both need to keep working.
- Use transactions for proposal acceptance, contract creation, and any multi-row status transition.
- Keep frontend feature code under `resources/js/features` when it belongs to a domain.
- Use accessible UI states: loading, disabled, error, success, empty, and low-bandwidth friendly fallbacks.
- Do not commit generated cache/session/log files from `storage/`, or Vite output unless the task explicitly asks for production assets.

## Common Commands

- Frontend build: `npm.cmd run build`
- Frontend tests: `npm.cmd test`
- Laravel tests: `composer test`
- Unit-only Laravel tests: `composer test:unit`
- Run migrations locally: `php artisan migrate`
- Seed data: `php artisan db:seed`

On Windows PowerShell, use `npm.cmd` instead of `npm` if script execution policy blocks `npm.ps1`.

## Validation Expectations

- For React changes, run `npm.cmd run build` and targeted Vitest tests when the test path is valid.
- For Laravel API/model/service changes, run the nearest PHPUnit test file or suite.
- For dispatch/location changes, test both PostgreSQL/PostGIS behavior when possible and fallback distance behavior.
- If a command cannot run because of local environment or sandbox permissions, report the exact blocker.

## Local Skills

Project skills live in `.codex/skills`:

- `$plumbnepal-backend-api` for Laravel API, models, migrations, validation, payments, verification, proposals, and PHPUnit work.
- `$plumbnepal-frontend-ui` for React, TypeScript, Tailwind, booking UI, dashboards, maps, i18n, and Vitest work.
- `$plumbnepal-dispatch-location` for PostGIS nearby search, plumber availability, assignment, and live tracking.
- `$plumbnepal-ai-intake` for Laravel AI diagnosis, AI storage, AI request UI, and booking integration.
