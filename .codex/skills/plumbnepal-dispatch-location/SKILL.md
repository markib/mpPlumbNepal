---
name: plumbnepal-dispatch-location
description: Work on PlumbNepal plumber dispatch, PostGIS nearby search, online and availability status, assignment, live plumber location updates, tracking APIs, Leaflet tracking UI, and geospatial tests. Use for DispatchController, PlumberProfile, booking assignment, and map tracking changes.
---

# PlumbNepal Dispatch Location

## Overview

Use this skill when changes affect how customers find plumbers, how plumbers publish availability/location, or how assigned plumber locations are tracked.

## Workflow

1. Read `DispatchController.php`, `PlumberProfile.php`, relevant migrations, and frontend tracking components.
2. Identify whether the change affects PostgreSQL/PostGIS, non-PostgreSQL fallback, or both.
3. Preserve coordinate order: PostGIS WKT points are `POINT(longitude latitude)`.
4. Keep radius, verification, service type, availability, and online filters explicit.
5. Validate with backend feature tests and frontend build/tests when tracking UI changes.

## Dispatch Rules

- Nearby plumber search should filter by `is_available`, `verified`, service type support, and radius.
- Use PostGIS geography functions for production PostgreSQL paths.
- Keep a Haversine fallback for local non-PostgreSQL development.
- Location update routes are plumber-only and require authenticated users.
- Active tracking should only expose assigned plumber location for active booking states.
- Broadcasting events are `BookingAssigned` and `PlumberLocationUpdate`; do not assume a websocket server is already configured.

## Files To Check

- Backend: `app/Http/Controllers/Api/DispatchController.php`, `app/Models/PlumberProfile.php`, `app/Events`.
- Database: PostGIS extension migration, plumber profile migrations, booking coordinate migrations.
- Frontend: `resources/js/features/booking/hooks/usePlumberTracking.ts`, `PlumberTrackingCard.tsx`, `NearbyPlumbersList.tsx`.
- Shared map component: `resources/js/components/MapPinAddress.tsx`.

## References

- Read `references/dispatch-map.md` for geospatial implementation notes.
- Read `../../../docs/request-to-contract-workflow.md` when assignment overlaps contract lifecycle.
