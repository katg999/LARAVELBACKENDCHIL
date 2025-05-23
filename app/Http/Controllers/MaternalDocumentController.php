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
        $file = $request->file('document');
        $patientId = $request->patient_id;

        // 1. First send to classifier
        $classification = $this->classifyDocument($file, $patientId);
        
        // 2. Upload to DigitalOcean Spaces
        $filename = uniqid().'_'.preg_replace('/[^a-zA-Z0-9\.\-]/', '_', $file->getClientOriginalName());
        $path = "patients/{$patientId}/documents/{$filename}";
        
        Storage::disk('pregnancy_docs')->put($path, fopen($file->getRealPath(), 'r+'));
        $s3Path = Storage::disk('pregnancy_docs')->url($path);

        // 3. Save to database
        $document = MaternalDocument::create([
            'patient_id' => $patientId,
            'original_filename' => $file->getClientOriginalName(),
            's3_path' => $s3Path,
            'document_type' => $classification['label'],
            'confidence' => $classification['confidence'],
            'classification_status' => $classification['status']
        ]);

        return response()->json([
            'success' => true,
            'document' => $document,
            'classification' => $classification
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
        $response = Http::timeout(30)
            ->attach(
                'file', 
                fopen($file->getRealPath(), 'r'),
                $file->getClientOriginalName()
            )
            ->post('https://pregnancydocumentclassifier.onrender.com/classify/', [
                'patient_id' => 'patient_'.$patientId
            ]);
        
        if ($response->successful()) {
            return $response->json()['classification'];
        }
        
        throw new \Exception('Classification API error: '.$response->body());
        
    } catch (\Exception $e) {
        \Log::error("Classification failed: " . $e->getMessage());
        return [
            'label' => 'unclassified document',
            'confidence' => 0.0,
            'status' => 'error'
        ];
    }
}
}