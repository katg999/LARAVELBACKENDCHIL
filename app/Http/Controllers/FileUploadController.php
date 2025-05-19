<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use App\Models\Doctor;
use App\Models\School;

class FileUploadController extends Controller
{
    /**
     * Generate a pre-signed URL for direct upload to DigitalOcean Spaces
     */
    public function generatePresignedUrl(Request $request)
    {
        // Existing code - keep as fallback method
        $request->validate([
            'filename' => 'required|string',
            'upload_type' => 'sometimes|string|in:doctor,school',
            'content_type' => 'sometimes|string',
        ]);

        $fileName = $request->input('filename');
        $uploadType = $request->input('upload_type', 'documents');
        $contentType = $request->input('content_type', 'application/octet-stream');

        // Sanitize filename
        $cleanFileName = preg_replace('/[^a-zA-Z0-9\-\._]/', '', $fileName);
        $path = "{$uploadType}/" . Str::uuid() . "_" . $cleanFileName;

        // Get the S3 client directly from the disk configuration
        $client = Storage::disk('s3')->getClient();
        
        $command = $client->getCommand('PutObject', [
            'Bucket' => config('filesystems.disks.s3.bucket'),
            'Key' => $path,
            'ACL' => 'public-read',
            'ContentType' => $contentType
        ]);

        $expires = now()->addMinutes(15);
        $signedUrl = (string) $client->createPresignedRequest($command, $expires)->getUri();
        $publicUrl = Storage::disk('s3')->url($path);

        return response()->json([
            'upload_url' => $signedUrl,
            'public_url' => $publicUrl,
            'file_path' => $path
        ]);
    }

    /**
     * New server-side proxy method to handle direct file uploads
     * This avoids CORS issues by having the server perform the upload to DigitalOcean
     */
    public function proxyUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:51200', // 50MB max
            'upload_type' => 'required|string|in:doctor,school',
        ]);

        $file = $request->file('file');
        $uploadType = $request->input('upload_type');

        try {
            // 1. Upload to DigitalOcean Spaces directly from the server
            $path = "{$uploadType}/" . Str::uuid() . "_" . $file->getClientOriginalName();
            $uploaded = Storage::disk('s3')->put($path, file_get_contents($file->getRealPath()), [
                'visibility' => 'public',
                'ContentType' => $file->getMimeType(),
            ]);

            if (!$uploaded) {
                return response()->json(['error' => 'Failed to upload file to DigitalOcean'], 500);
            }

            // Get the public URL
            $publicUrl = Storage::disk('s3')->url($path);

            // 2. Also upload to tmpfiles.org for temporary access
            $tmpFileUrl = $this->uploadToTmpFilesFromServer($file);

            // 3. Update the model with both URLs
            $model = $uploadType === 'doctor'
                ? Doctor::latest()->first()
                : School::latest()->first();

            if ($model) {
                $model->update([
                    'permanent_file_url' => $publicUrl,
                    'temp_file_url' => $tmpFileUrl,
                    'file_uploaded_at' => now(),
                ]);
            }

            return response()->json([
                'message' => 'File uploaded successfully',
                'file_url' => $publicUrl,
                'temp_file_url' => $tmpFileUrl,
                'upload_type' => $uploadType
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Upload failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Private helper to upload to tmpfiles.org from the server
     */
    private function uploadToTmpFilesFromServer($file)
    {
        $client = new Client();
        $response = $client->post('https://tmpfiles.org/api/v1/upload', [
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => fopen($file->getRealPath(), 'r'),
                    'filename' => $file->getClientOriginalName()
                ]
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        return str_replace(
            'https://tmpfiles.org/',
            'https://tmpfiles.org/dl/',
            $data['data']['url']
        );
    }

    /**
     * Handle the final file URL storage after both uploads complete
     */
    public function storeFileUrls(Request $request)
    {
        // Existing code
        $request->validate([
            'permanent_url' => 'required|url',
            'temp_url' => 'required|url',
            'upload_type' => 'required|in:doctor,school',
        ]);

        // Determine which model to update based on upload_type
        $model = $request->upload_type === 'doctor'
            ? Doctor::latest()->first()
            : School::latest()->first();

        if (!$model) {
            return response()->json(['error' => 'No record found'], 404);
        }

        // Update the model with both URLs
        $model->update([
            'permanent_file_url' => $request->permanent_url,
            'temp_file_url' => $request->temp_url,
            'file_uploaded_at' => now(),
        ]);

        return response()->json([
            'message' => 'File URLs stored successfully',
            'data' => $model
        ]);
    }

    /**
     * Helper method to upload to tmpfiles.org (can be used if client-side fails)
     */
    public function uploadToTmpFiles(Request $request)
    {
        // Existing code
        $request->validate([
            'file' => 'required|file|max:51200', // 50MB max
        ]);

        $client = new Client();
        $response = $client->post('https://tmpfiles.org/api/v1/upload', [
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => fopen($request->file('file')->path(), 'r'),
                    'filename' => $request->file('file')->getClientOriginalName()
                ]
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        $downloadUrl = str_replace(
            'https://tmpfiles.org/',
            'https://tmpfiles.org/dl/',
            $data['data']['url']
        );

        return response()->json([
            'temp_url' => $downloadUrl,
            'expires_at' => now()->addHours(24) // tmpfiles.org typically keeps files for 24h
        ]);
    }
}