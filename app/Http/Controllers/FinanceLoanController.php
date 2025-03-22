<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FinanceLoanController extends Controller
{
    public function storeFinanceLoanData(Request $request)
    {
        // Validate the request
        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|email',
            'facility_name' => 'required|string',
            'facility_report' => 'required|file|mimes:pdf,doc,docx,jpg,png|max:2048', // 2MB max
            'license' => 'required|file|mimes:pdf,doc,docx,jpg,png|max:2048', // 2MB max
        ]);

        // Store the uploaded files
        $facilityReportPath = $request->file('facility_report')->store('facility_reports');
        $licensePath = $request->file('license')->store('licenses');

        // Save the data to the database
        $financeLoan = FinanceLoan::create([
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'phone' => $request->input('phone'),
            'email' => $request->input('email'),
            'facility_name' => $request->input('facility_name'),
            'facility_report_path' => $facilityReportPath,
            'license_path' => $licensePath,
        ]);

        // Return a response
        return response()->json([
            'message' => 'Finance Loan Data submitted successfully!',
            'data' => $financeLoan,
        ], 201);
    }
}
