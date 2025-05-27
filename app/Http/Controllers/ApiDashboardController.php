<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class ApiDashboardController extends Controller
{
    public function index()
    {
        try {
            // Fetch data from the API endpoint
            $response = Http::get('https://laravelbackendchil.onrender.com/api/schools');
            
            // Check if the request was successful
            if ($response->successful()) {
                // Decode the JSON response
                $schools = $response->json();
                
                // Additional check to ensure $schools is an array
                if (!is_array($schools)) {
                    $schools = [];
                }
            } else {
                // Request failed, set empty array
                $schools = [];
            }
        } catch (\Exception $e) {
            // Handle exceptions (network errors, etc.)
            $schools = [];
        }
        
        // Pass the data to the view
        return view('api-dashboard', [
            'schools' => $schools,
            'error' => empty($schools) ? 'Failed to fetch data from API' : null
        ]);
    }


}