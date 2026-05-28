# Project Guide: PlumbNepal

## 1. Project Overview
PlumbNepal is an on-demand plumbing marketplace designed for the Nepalese market.

*   **Purpose:** Connecting customers with qualified plumbers efficiently using real-time dispatching.
*   **Key Technologies:**
    *   **Backend:** Laravel 13 (PHP 8.3)
    *   **Frontend:** React (TypeScript) with Tailwind CSS
    *   **Database:** PostgreSQL with PostGIS extension for geospatial queries.
    *   **Build/Tooling:** Vite, Vitest, PHPUnit.
*   **High-level Architecture:** Traditional Laravel monolith with an API-first approach serving a React frontend. Geospatial features are handled by PostGIS.

## 2. Getting Started
### Prerequisites
*   PHP 8.3+
*   Composer
*   Node.js 18+ & npm
*   PostgreSQL with PostGIS extension enabled

### Installation
1.  Clone the repository and install dependencies:
    ```bash
    composer install
    npm install
    ```
2.  Copy environment file:
    ```bash
    cp .env.example .env
    ```
3.  Configure `.env` with database credentials and application keys.
4.  Run migrations:
    ```bash
    php artisan migrate
    ```

### Running the App
*   **Backend (Laravel):** `php artisan serve`
*   **Frontend (Dev mode):** `npm run dev`

### Running Tests
*   **PHP (Backend):** `composer test` or `php artisan test`
*   **JS/TS (Frontend):** `npm run test`

## 3. Project Structure
*   `app/`: Core Laravel application logic (Models, Controllers, Services).
*   `database/`: Migrations, seeders, and factories.
*   `resources/`: React frontend source files.
*   `routes/`: API and web routes.
*   `tests/`: Unit and feature tests.

## 4. Development Workflow
*   **API Development:** Controllers under `app/Http/Controllers` handle requests and return JSON responses.
*   **Frontend Development:** React components are located in `resources/`. Use TypeScript for type safety.
*   **Database:** Always update migrations for schema changes. Ensure PostGIS types are utilized for location-based data.
*   **Coding Standards:** Follow standard Laravel/PSR conventions and React/TypeScript best practices.

## 5. Key Concepts
*   **Geospatial Dispatch:** Uses PostGIS `geography(POINT, 4326)` for proximity-based plumber assignment.
*   **Booking Lifecycle:** Pending -> Accepted -> En Route -> Job Started -> Completed.
*   **Address Capture:** Supports pin-on-map combined with landmark/ward/tole fields specific to Nepal.

## 6. Common Tasks
*   **Add a new API endpoint:** Define route in `routes/api.php`, create controller.
*   **Run migrations:** `php artisan migrate`.
*   **Run tests:** Use `composer test` or `npm run test`.

## 7. Troubleshooting
*   **Database Issues:** Ensure PostGIS is enabled in your Postgres database: `CREATE EXTENSION postgis;`.
*   **Vite/Asset issues:** Try deleting `node_modules` and re-running `npm install`.

## 8. References
*   [Laravel Documentation](https://laravel.com/docs)
*   [PostGIS Documentation](https://postgis.net/documentation/)
*   [React Documentation](https://react.dev/)
