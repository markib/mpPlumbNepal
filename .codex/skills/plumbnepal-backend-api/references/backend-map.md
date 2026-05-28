# Backend Map

## Main API Surface

- Routes: `routes/api.php`, all API endpoints under `/api/v1`.
- Auth: `AuthController`, Sanctum token helpers in frontend `utils/auth.ts`.
- Booking: `BookingController`, `Booking`, `BookingStatus`, `ServiceType`, `PricingCalculator`.
- Proposals: `BookingProposalController`, `BookingProposal`.
- Dispatch: `DispatchController`, `PlumberProfile`, location events.
- Verification: `VerificationController`, `VerificationDocument`.
- Payments: `PaymentController`, `Payment`.
- AI: `AiController`, `AiDiagnosis`, `app/Services/AI`.

## Data Notes

- `bookings.user_id` can be nullable for guest booking behavior.
- `bookings.accepted_by_id` points to `plumber_profiles`.
- `contract_terms` and `job_order_json` are structured arrays in the model.
- `payment_method` supports `esewa`, `khalti`, `ime_pay`, and `cod`.
- Plumber profiles use service type IDs and availability/verification flags.

## Testing Notes

- Backend feature tests live in `tests/Feature`.
- Pricing tests live in `tests/Unit/PricingCalculatorTest.php`.
- Prefer focused tests for validation errors, role authorization, state transitions, and JSON response shape.
