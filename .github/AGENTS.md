# AGENTS.md

## Project Overview
PlumbNepal is an on-demand plumbing marketplace designed for the Nepalese market. It connects customers with qualified plumbers efficiently using real-time dispatching.
- Backend: Laravel (API-first architecture)
- Frontend: React + TypeScript (Vite)
- AI: Hybrid (Cloud provider + Local fallback via Ollama)
- Real-time: WebSockets (Laravel WebSockets / Pusher-compatible)
- Database: PostgreSQL + PostGIS (location-based services)

### Key Technologies
- **Backend**: Laravel 13 (PHP 8.3)
- **Frontend**: React (TypeScript) with Tailwind CSS
- **Database**: PostgreSQL with PostGIS extension for geospatial queries
- **Build/Tooling**: Vite, Vitest, PHPUnit

### Architecture
- Traditional Laravel monolith with an API-first approach serving a React frontend.
- Geospatial features are handled by PostGIS.
- Follow **Service Layer Architecture**
- Controllers must be thin (no business logic)
- Business logic must live in `app/Services`
- Use feature-based structure in frontend
- APIs must return JSON only (no HTML)

## Development Workflow
### Build and Test Commands
- **Backend**: `composer test` or `php artisan test`
- **Frontend**: `npm run test`

### Coding Standards
- Follow Laravel/PSR conventions for backend development.
- Use React/TypeScript best practices for frontend development.

## Key Concepts
### Geospatial Dispatch
- Uses PostGIS `geography(POINT, 4326)` for proximity-based plumber assignment.

### Booking Lifecycle
- Pending -> Accepted -> En Route -> Job Started -> Completed.