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
    $request->validate([
        'patient_id' => 'required|exists:patients,id',
        'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
    ]);

    try {
        if (!$request->hasFile('document')) {
            throw new \Exception('No file was uploaded');
        }

        $file = $request->file('document');
        $patientId = $request->patient_id;
        
        // Generate more unique filename
        $filename = uniqid().'_'.preg_replace('/[^a-zA-Z0-9\.\-]/', '_', $file->getClientOriginalName());
        $path = "patients/{$patientId}/documents/{$filename}";

        // Stream the file instead of loading into memory
        Storage::disk('pregnancy_docs')->put($path, fopen($file->getRealPath(), 'r+'));
        
        $s3Path = Storage::disk('pregnancy_docs')->url($path);

        // Classify document if no type provided
        $documentType = $request->document_type;
        $confidence = 1.0;
        
        if (empty($documentType)) {
            $classification = $this->classifyDocument($file, $patientId);
            $documentType = $classification['label'];
            $confidence = $classification['confidence'];
        }

        $document = MaternalDocument::create([
            'patient_id' => $patientId,
            'original_filename' => $file->getClientOriginalName(),
            's3_path' => $s3Path,
            'document_type' => $documentType,
            'confidence' => $confidence
        ]);

        return response()->json([
            'success' => true,
            'document' => $document
        ]);

    } catch (\Exception $e) {
        \Log::error("Upload failed: " . $e->getMessage());
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