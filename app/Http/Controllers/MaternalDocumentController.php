<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\MaternalDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class MaternalDocumentController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'document_type' => 'nullable|in:ultrasound report,blood test results,urine analysis,prenatal screening'
        ]);

        try {
            $file = $request->file('document');
            
            // 1. Upload to DigitalOcean Spaces (pregnancy_docs disk)
            $path = $file->store(
                "patients/{$validated['patient_id']}/documents",
                'pregnancy_docs'  // Using the specific pregnancy docs configuration
            );
            
            // 2. Get the public URL
            $s3Path = Storage::disk('pregnancy_docs')->url($path);

            // 3. Save to database
            $document = MaternalDocument::create([
                'patient_id' => $validated['patient_id'],
                'original_filename' => $file->getClientOriginalName(),
                's3_path' => $s3Path,
                'document_type' => $validated['document_type'] ?? 'unclassified document',
                'confidence' => isset($validated['document_type']) ? 1.0 : 0.0
            ]);

            return response()->json([
                'success' => true,
                'document' => $document
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    protected function classifyDocument($file, $patientId)
    {
        try {
            // Send file directly to classifier endpoint
            $response = Http::attach(
                'file', 
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName()
            )->post('https://pregnancydocumentclassifier.onrender.com/classify/', [
                'patient_id' => 'patient_'.$patientId
            ]);
            
            if ($response->successful()) {
                return $response->json()['classification'];
            }
            
            return [
                'label' => 'unclassified document',
                'confidence' => 0.0,
                'status' => 'api_error'
            ];
            
        } catch (\Exception $e) {
            return [
                'label' => 'unclassified document',
                'confidence' => 0.0,
                'status' => 'error'
            ];
        }
    }
}