<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FinanceDashboardController extends Controller
{
    public function index()
    {
        // Fetch data from the API endpoint
        $response = Http::get('https://laravelbackendchil.onrender.com/api/finance-loan-data');

        // Check if the request was successful
        if ($response->successful()) {
            // Decode the JSON response
            $data = $response->json();

            // Pass the data to the view
            return view('finance-dashboard', ['loans' => $data['data']]);
        } else {
            // Handle the error (e.g., show an error message)
            return view('finance-dashboard', ['loans' => [], 'error' => 'Failed to fetch data from the API.']);
        }
    }
}


