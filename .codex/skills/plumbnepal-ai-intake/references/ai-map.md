# AI Intake Map

## Backend Files

- `app/Http/Controllers/Api/AiController.php`
- `app/Http/Requests/DiagnoseRequest.php`
- `app/Models/AiDiagnosis.php`
- `app/Services/AiService.php`
- `app/Services/AI/AiStorageService.php`
- `app/Services/AI/AiRouter.php`
- `app/Services/AI/Contracts/AiProviderContract.php`
- `app/Services/AI/Providers/OllamaProvider.php`
- `app/Ai/Agents/Diagnosis/PlumbingDiagnoser.php`

## Frontend Files

- `resources/js/features/ai/components/AIRequestInput.tsx`
- `resources/js/features/ai/components/AiDiagnosisCard.tsx`
- `resources/js/features/ai/hooks/useAiDiagnosis.ts`
- `resources/js/features/ai/services/aiApi.ts`
- `resources/js/features/ai/types/ai.ts`
- Booking integration: `resources/js/features/booking/pages/BookingPage.tsx`

## Integration Notes

- Diagnosis results can set `service_notes`, `is_emergency`, and `ai_diagnosis_id` on booking state.
- Urgency values that mean high risk should map to emergency booking state.
- Keep user edits possible after AI prefill.
- Keep provider secrets server-side.
