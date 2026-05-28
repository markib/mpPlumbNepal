# PlumbNepal

On-demand plumbing marketplace for Nepal with Laravel 13 backend, PostgreSQL + PostGIS, and React TypeScript frontend.

## Implementation status
- Backend scaffolded with core models, migrations, services, and API controllers
- Geospatial plumbing dispatch using PostGIS `geography(POINT,4326)`
- Booking status lifecycle from Pending -> Accepted -> En Route -> Job Started -> Completed
- Local payment gateway support placeholders for eSewa, Khalti, IME Pay, and COD
- Plumber verification workflow with citizenship, nagarpalika, and skill uploads
- Nepali/English bilingual-ready front-end structure planned

## Next steps
1. Install Laravel dependencies and configure `.env`
2. Enable PostgreSQL/PostGIS and run migrations
3. Seed service types and booking statuses if needed
4. Build React frontend and connect to `/api/v1` endpoints

## Notes
- Security: use hashed passwords, guarded uploads, RBAC, and signed payment callbacks
- Use pin-on-map and landmark fields for Nepal-specific address capture
- Add low-bandwidth fallback with ward/tole selection and SMS notification support
