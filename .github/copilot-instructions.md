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