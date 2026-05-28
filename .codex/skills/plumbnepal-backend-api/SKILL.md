---
name: plumbnepal-backend-api
description: Work on PlumbNepal Laravel backend APIs, Eloquent models, migrations, seeders, validation, auth, payments, verification, booking proposals, contract workflow, pricing, and PHPUnit tests. Use when editing app/, routes/api.php, database/, tests/Feature, tests/Unit, or backend service code.
---

# PlumbNepal Backend API

## Overview

Use this skill for backend changes in the Laravel API. Keep the marketplace domain intact: customers create bookings, plumbers propose or receive work, contracts lock accepted work, payments are local-provider aware, and verification gates trust-sensitive actions.

## Workflow

1. Read `routes/api.php` to confirm the public or authenticated route surface.
2. Read the controller, model, migration, and nearest tests before editing.
3. Use Laravel validation for request boundaries and Eloquent relationships for response loading.
4. Add or update focused PHPUnit coverage for changed API behavior.
5. Run the nearest PHPUnit test or `composer test` when feasible.

## Domain Rules

- API routes are grouped under `/api/v1`.
- Sanctum-authenticated routes live inside `Route::middleware('auth:sanctum')`.
- Preserve role checks for `customer`, `plumber`, `service_provider`, `shop_keeper`, and `admin`.
- Booking fields include Nepal address data: `ward_number`, `tole_name`, `landmark`, `latitude`, `longitude`.
- Contract workflow statuses are `pending`, `proposed`, `contracted`, `in_progress`, and `completed`.
- Proposal acceptance and competing proposal expiry must happen inside a transaction.
- Verification and citizenship flags are trust gates for plumber-sensitive workflows.

## Files To Check

- Booking APIs: `app/Http/Controllers/Api/BookingController.php`, `app/Models/Booking.php`, `tests/Feature/BookingApiTest.php`.
- Proposals and contracts: `BookingProposalController.php`, `BookingProposal.php`, `docs/request-to-contract-workflow.md`.
- Payments: `PaymentController.php`, `Payment.php`.
- Verification: `VerificationController.php`, `VerificationDocument.php`.
- Pricing: `app/Services/PricingCalculator.php`, `tests/Unit/PricingCalculatorTest.php`.

## References

- Read `references/backend-map.md` for a compact file and domain map.
- Read `../../../docs/request-to-contract-workflow.md` before changing proposal or contract lifecycle behavior.
