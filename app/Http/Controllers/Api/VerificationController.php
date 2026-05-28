<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VerificationDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VerificationController extends Controller
{
    public function uploadDocument(Request $request)
    {
        $request->validate([
            'document_type' => 'required|in:citizenship,nagarpalika,skill_certificate',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $path = $request->file('file')->store('verification_documents', 'public');

        $document = VerificationDocument::create([
            'user_id' => $request->user()->id,
            'document_type' => $request->input('document_type'),
            'file_path' => $path,
            'status' => 'pending',
        ]);

        return response()->json([ 'document' => $document ], 201);
    }

    public function submitForReview(Request $request)
    {
        $request->validate([
            'profile_id' => 'nullable|integer',
            'mobile_number' => 'nullable|string|max:20',
        ]);

        $user = $request->user();
        if ($user->role !== 'plumber') {
            return response()->json([ 'message' => 'Only plumbers may submit verification' ], 403);
        }

        $user->verification_status = 'submitted';
        $user->verification_notes = 'Awaiting admin review.';
        $user->save();

        return response()->json([ 'message' => 'Verification workflow started' ]);
    }

    public function status(Request $request)
    {
        return response()->json([
            'verification_status' => $request->user()->verification_status,
            'documents' => $request->user()->verificationDocuments,
        ]);
    }
}
