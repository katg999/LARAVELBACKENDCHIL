<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HealthFacility;
use Illuminate\Support\Facades\Log;

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
        return response()->json(HealthFacility::all());
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
}