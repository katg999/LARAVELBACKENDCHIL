<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HealthFacility;
use Illuminate\Support\Facades\Log;
use App\Models\Message;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Doctor;


class HealthFacilityController extends Controller
{
    /**
     * Register a new health facility
     */
    public function registerHealthFacility(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:health_facilities',
            'contact' => 'required|string|max:20',
            'file_url' => 'nullable|string'
        ]);

        $healthFacility = HealthFacility::create($validated);

        return response()->json([
            'message' => 'Health facility registered successfully',
            'health_facility' => $healthFacility
        ], 201);
    }

    /**
     * Get all health facilities
     */
   public function getHealthFacilities()
{
    $healthFacilities = HealthFacility::all();
    
    return response()->json([
        'success' => true,
        'data' => $healthFacilities
    ]);
}

    /**
     * Update file URL for most recently created health facility
     */
    public function updateLatestHealthFacilityFile(Request $request)
    {
        $healthFacility = HealthFacility::latest()->first();

        if (!$healthFacility) {
            return response()->json([
                'message' => 'No health facility records found to update'
            ], 404);
        }

        $validated = $request->validate([
            'file_url' => 'required|string|url'
        ]);

        $healthFacility->update(['file_url' => $validated['file_url']]);

        Log::info('Latest health facility file URL updated', [
            'health_facility_id' => $healthFacility->id,
            'file_url' => $validated['file_url']
        ]);

        return response()->json([
            'message' => 'File URL updated for most recent health facility',
            'health_facility_id' => $healthFacility->id,
            'file_url' => $healthFacility->file_url
        ]);
    }

    /**
     * Update specific health facility by ID
     */
    public function updateHealthFacility(Request $request, $id)
    {
        $healthFacility = HealthFacility::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:health_facilities,email,'.$healthFacility->id,
            'contact' => 'sometimes|string|max:20',
            'file_url' => 'nullable|string'
        ]);

        $healthFacility->update($validated);

        return response()->json([
            'message' => 'Health facility updated successfully',
            'health_facility' => $healthFacility
        ]);
    }

public function showDashboard($id)
{

    $doctors = Doctor::all();
    $healthFacility = HealthFacility::findOrFail($id);
     $notifications = collect(); 
     $allDoctors = Doctor::where('health_facility_id', $id)->get();


    // Get unread messages count for this health facility
    $unreadMessages = Message::where('health_facility_id', $id)
                             ->where('is_read', false)
                             ->count();

    $appointments = Appointment::where('health_facility_id', $id)
                           ->latest()
                           ->take(10)
                           ->get();


    $availableDoctors = Doctor::where('health_facility_id', $id)
                          ->whereHas('availabilities', function ($query) {
                              $query->where('available', true);
                          })
                          ->get();

       // Fetch the messages for this health facility
    $messages = Message::where('health_facility_id', $id)
                       ->orderBy('created_at', 'desc')  // Optional: you can order them by creation date
                       ->get();

      // Fetch the patients for this health facility
    $patients = Patient::where('health_facility_id', $id)
                       ->get();



 
    // Pass the data to the view
    return view('Health-Facility-Instance', [
    'healthFacility' => $healthFacility,
    'unreadMessages' => $unreadMessages,
    'allDoctors' => $allDoctors,
    'appointments' => $appointments,
    'availableDoctors' => $availableDoctors,
    'patients' => $patients, 
    'messages' => $messages,
    'stats' => null // or an empty array []
]);
}



    
}