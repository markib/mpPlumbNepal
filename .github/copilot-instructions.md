# Copilot Instructions  – AI Plumbing Service Platform


## Build and Test Commands
- To run backend tests, use `composer test` or `php artisan test`.
- To run frontend tests, use `npm run test`.

## Coding Standards
- Follow Laravel/PSR conventions for backend development.
- Use React/TypeScript best practices for frontend development.

## Development Workflow
- API development: Controllers under `app/Http/Controllers` handle requests and return JSON responses.
- Frontend development: React components are located in `resources/`. Use TypeScript for type safety.
- Database: Always update migrations for schema changes. Ensure PostGIS types are utilized for location-based data.

# AI Coding Rules

## Environment

- PHP 8.4+
- Laravel 13
- Pest
- PHPUnit
- Laravel Pint
- PHPStan Level 8
- MySQL
- Redis

## Before Writing Code

Always:

1. Read existing implementation.
2. Search for similar patterns in project.
3. Reuse existing services.
4. Reuse existing DTOs.
5. Reuse existing Actions.
6. Reuse existing Form Requests.
7. Reuse existing Policies.

Never create duplicate abstractions.

---

## Architecture Rules

Follow:

Controller
→ Action / Service
→ Repository (if exists)
→ Model

Controllers must remain thin.

Maximum controller method length:

50 lines

Business logic belongs in:

- Actions
- Services
- Domain classes

Never place business logic directly in controllers.

---

## Laravel Rules

Use:

- Form Requests
- Policies
- API Resources
- Events
- Queued Jobs

Avoid:

- Facades inside domain services
- Static helpers
- Global functions

Prefer dependency injection.

---

## Eloquent Rules

Always:

- eager load relationships
- avoid N+1 queries
- use query scopes when reusable
- use transactions for multi-table updates

Never:

- call save() inside loops
- use raw SQL when Eloquent is sufficient

---

## Validation Rules

All request validation must use Form Requests.

Never validate inside controllers.

---

## Testing Rules

Every feature must include tests.

Required:

- Happy path
- Validation failure
- Authorization failure
- Edge case

Use Pest syntax.

Prefer factories over manual setup.

Never disable tests.

---

## Static Analysis Rules

All generated code must pass:

composer lint
composer stan
composer test

No code should be considered complete until all pass.

---

## PHPStan Rules

Target:

Level 8

Never:

- use mixed
- suppress errors
- add ignores without explanation

Prefer:

- typed properties
- typed return values
- typed collections

---

## Code Style

Follow Laravel Pint.

Always:

- use constructor property promotion
- use readonly where appropriate
- use enums instead of magic strings
- use value objects for complex data

Avoid:

- deeply nested conditionals
- methods > 50 lines
- classes > 500 lines

---

## Database Rules

Migration requirements:

- foreign keys
- indexes
- cascading behavior

Never modify production data in migrations.

---

## Security Rules

Never:

- trust request input
- expose stack traces
- store secrets in code

Always:

- authorize actions
- validate input
- escape output where required

---

## Pull Request Checklist

Before completing any task:

1. Run Pint
2. Run PHPStan
3. Run Tests
4. Remove dead code
5. Remove debugging statements
6. Verify imports
7. Verify type safety

Task is not complete until all checks pass.