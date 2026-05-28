<?php
// app/Http/Controllers/Api/AiController.php
namespace App\Http\Controllers\Api;

use App\Ai\Agents\Diagnosis\DiagnosisValidator;
use App\Http\Controllers\Controller;
use App\Http\Requests\DiagnoseRequest;
use App\Services\AI\AiPipelineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AiController extends Controller
{
    public function __construct(protected DiagnosisValidator $diagnosisValidator) {}

    /**
     * Handles the AI Plumbing Diagnosis flow.
     * 1. Calls local Ollama via AiService.
     * 2. Validates AI Confidence.
     * 3. Persists successful hits for marketplace analytics.
     * 4. Returns JSON for the React frontend.
     */
    public function diagnose(DiagnoseRequest $request,  AiPipelineService $service): JsonResponse
    {
        $diagnosisId = null;
        
        try {

            $pipeline = $service->start(
                user: $request->user(),
                input: $request->validated(),
                image: [
                    'name' => $request->image_name,
                    'data' => $request->image_data,
                ]
            );
            $diagnosisId = $pipeline->id;
            // $analysis = $this->diagnosisValidator->handle($pipeline->result);

            Log::info("AI Pipeline started", [
                'pipeline_id' => $pipeline->id,
                'user_id' => $request->user()?->id,
            ]);

        
            return response()->json([
                'status' => 'processing',
                'pipeline_id' => $pipeline->id,
            ],202);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 502); // Bad Gateway - appropriate for downstream AI service failure
        }
    }
}
