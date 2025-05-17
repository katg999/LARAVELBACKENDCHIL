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
     * Handle the final file URL storage after both uploads complete
     */
    public function storeFileUrls(Request $request)
    {
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
        $request->validate([
            'file' => 'required|file|max:51200', // 50MB max
        ]);

        $client = new Client();
        $response = $client->post(env('TMPFILES_API'), [
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
            env('TMPFILES_DOWNLOAD_BASE'),
            $data['data']['url']
        );

        return response()->json([
            'temp_url' => $downloadUrl,
            'expires_at' => now()->addHours(24) // tmpfiles.org typically keeps files for 24h
        ]);
    }
}