# Dispatch And Location Map

## Backend Files

- `app/Http/Controllers/Api/DispatchController.php`
- `app/Models/PlumberProfile.php`
- `app/Events/BookingAssigned.php`
- `app/Events/PlumberLocationUpdate.php`
- PostGIS migration: `database/migrations/2026_04_14_000000_create_postgis_extension.php`
- Profile/location migrations under `database/migrations`.

## Frontend Files

- `resources/js/features/booking/hooks/usePlumberTracking.ts`
- `resources/js/features/booking/components/PlumberTrackingCard.tsx`
- `resources/js/features/booking/components/NearbyPlumbersList.tsx`
- `resources/js/components/MapPinAddress.tsx`

## Coordinate Rules

- UI and API payloads use `latitude` and `longitude`.
- WKT uses `POINT(longitude latitude)`.
- Leaflet positions use `[latitude, longitude]`.
- Validate latitude between `-90` and `90`, longitude between `-180` and `180`.

## Query Rules

- Production path: PostGIS `ST_DWithin` and distance ordering.
- Local fallback: Haversine distance over stored latitude/longitude when not PostgreSQL.
- Keep service type filtering and verification filtering in the query path.
