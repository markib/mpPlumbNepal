---
name: plumbnepal-ai-intake
description: Work on PlumbNepal AI plumbing diagnosis, Laravel AI provider routing, diagnosis persistence, image or text intake, AI frontend components, booking prefill from diagnosis, and AI feature tests. Use when editing app/Services/AI, app/Ai, AiController, DiagnoseRequest, AiDiagnosis, or resources/js/features/ai.
---

# PlumbNepal AI Intake

## Overview

Use this skill for AI diagnosis and intake changes. The AI flow should help users describe plumbing issues, optionally upload evidence, receive urgency and summary, then prefill booking notes and emergency state.

## Workflow

1. Read the request validator, controller, AI service/router, provider, model, and frontend AI feature before editing.
2. Keep AI output normalized before storing or passing it into booking state.
3. Preserve fallback behavior when a provider is unavailable.
4. Avoid leaking secrets or provider credentials into frontend code.
5. Update `tests/Feature/AiIntakeTest.php` or nearby tests when backend behavior changes.

## Domain Rules

- Treat AI as assistive, not authoritative; preserve user-editable booking fields.
- Store diagnosis records through `AiDiagnosis` when the API returns a useful result.
- Booking integration uses `ai_diagnosis_id`, `service_notes`, and urgency-derived `is_emergency`.
- Image handling should use `AiStorageService` or existing upload/storage patterns.
- Provider routing lives under `app/Services/AI`; do not hard-code cloud provider details into controllers.

## Files To Check

- Backend: `app/Http/Controllers/Api/AiController.php`, `app/Http/Requests/DiagnoseRequest.php`, `app/Models/AiDiagnosis.php`.
- Services: `app/Services/AiService.php`, `app/Services/AI\AiStorageService.php`, `app/Services/AI`.
- Agent: `app/Ai/Agents/Diagnosis/PlumbingDiagnoser.php`.
- Frontend: `resources/js/features/ai`, `resources/js/components/AIRequestInput.tsx`.
- Tests: `tests/Feature/AiIntakeTest.php`.

## References

- Read `references/ai-map.md` for the compact AI file map and integration notes.
