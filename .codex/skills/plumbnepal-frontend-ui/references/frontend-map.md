# Frontend Map

## App Layout

- Entry: `resources/js/app.tsx` and `AppComponent.tsx`.
- Authenticated shell: `resources/js/pages/DashboardPage.tsx`.
- Booking feature: `resources/js/features/booking`.
- AI feature: `resources/js/features/ai`.
- Shared map/address: `resources/js/components/MapPinAddress.tsx`.
- Shared API utilities: `resources/js/utils/api.ts`, `resources/js/utils/auth.ts`.

## Feature Patterns

- Keep domain components in `features/<domain>/components`.
- Keep network functions in `features/<domain>/services`.
- Keep reusable state and async orchestration in `features/<domain>/hooks`.
- Keep domain types in `features/<domain>/types`.

## Validation Notes

- Run `npm.cmd run build` on frontend changes.
- Use `npm.cmd test` or a targeted Vitest command when test imports are valid.
- PowerShell may block `npm`; use `npm.cmd`.
- Existing test `resources/js/pages/BookingPage.test.tsx` may need import path updates because the booking page now lives in `resources/js/features/booking/pages/BookingPage.tsx`.
