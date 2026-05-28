---
name: plumbnepal-frontend-ui
description: Work on PlumbNepal React TypeScript frontend, Tailwind UI, booking pages, plumber and customer dashboards, map/address components, i18n strings, frontend API services, and Vitest tests. Use when editing resources/js, resources/css, Vite, or frontend test files.
---

# PlumbNepal Frontend UI

## Overview

Use this skill for the React/Vite frontend. Favor functional, operational screens over marketing pages: booking, dispatch, proposals, verification, map selection, and status tracking should be fast to scan and usable on low bandwidth.

## Workflow

1. Locate the feature under `resources/js/features` first; use `resources/js/pages` for route-level composition.
2. Read the component, hook, API service, and type file for the feature before editing.
3. Keep state and network logic in hooks/services when that pattern already exists.
4. Make loading, empty, success, error, disabled, and mobile states explicit.
5. Run `npm.cmd run build`; run targeted Vitest tests when their imports are valid.

## UI Rules

- Keep service workflows visible on the first screen; do not add a landing page unless asked.
- Use restrained SaaS-style UI: organized forms, tables/lists, clear status chips, and compact controls.
- Preserve bilingual readiness with `react-i18next`; do not hard-code new user-facing strings where an existing i18n pattern is nearby.
- Maps use Leaflet and `react-leaflet`.
- Booking form fields must preserve location and Nepal address fields.
- Toasts and alerts should be accessible with `role="status"` or `role="alert"` as appropriate.
- Avoid layout shifts in maps, dashboards, repeated list cards, and submit buttons.

## Files To Check

- Booking feature: `resources/js/features/booking`.
- AI intake feature: `resources/js/features/ai`.
- Dashboard shell: `resources/js/pages/DashboardPage.tsx`.
- Plumber dashboard: `resources/js/pages/PlumberDashboard.tsx`.
- Customer proposals: `resources/js/pages/CustomerProposalList.tsx`.
- Shared utilities: `resources/js/utils/api.ts`, `resources/js/utils/auth.ts`.
- Tests: `resources/js/pages/BookingPage.test.tsx`.

## References

- Read `references/frontend-map.md` for component and validation notes.
