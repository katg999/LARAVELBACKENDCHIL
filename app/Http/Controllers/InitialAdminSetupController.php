<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\School;
use App\Models\HealthFacility;
use App\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class InitialAdminSetupController extends Controller
{
    /**
     * Show the initial admin setup form for health facilities
     */
    public function showHealthFacilityForm($facilityId)
    {
        $healthFacility = HealthFacility::findOrFail($facilityId);

        // Check if this health facility already has an admin
        $existingAdmin = User::where('health_facility_id', $healthFacility->id)
            ->whereHas('roles', function($query) {
                $query->where('slug', 'health-facility-admin');
            })
            ->first();

        if ($existingAdmin) {
            return redirect()->route('login')->with('info', 'This health facility already has an admin. Please login with your credentials.');
        }

        return view('initial-admin-setup.health-facility', compact('healthFacility'));
    }

    /**
     * Create the initial admin user for a health facility
     */
    public function createHealthFacilityAdmin(Request $request, $facilityId)
    {
        $healthFacility = HealthFacility::findOrFail($facilityId);

        // Verify entity email ownership first (should be in session from VoiceFlow)
        // For now, we'll proceed directly, but in production you should verify VoiceFlow OTP was completed

        // Validate the request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Create the admin user with personal email
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'], // Personal email, NOT facility email
            'password' => Hash::make($validated['password']),
            'health_facility_id' => $healthFacility->id,
        ]);

        // Assign admin role
        $user->assignRole('health-facility-admin');

        \Log::info('Created initial admin for health facility', [
            'health_facility_id' => $healthFacility->id,
            'user_id' => $user->id,
            'personal_email' => $user->email
        ]);

        // Log the user in
        auth()->login($user);

        return redirect()->route('health-facility.dashboard')->with('success', 'Welcome! Your admin account has been created.');
    }

    /**
     * Show the initial admin setup form for schools
     */
    public function showSchoolForm($schoolId)
    {
        $school = School::findOrFail($schoolId);

        // Check if this school already has an admin
        $existingAdmin = User::where('school_id', $school->id)
            ->whereHas('roles', function($query) {
                $query->where('slug', 'school-admin');
            })
            ->first();

        if ($existingAdmin) {
            return redirect()->route('login')->with('info', 'This school already has an admin. Please login with your credentials.');
        }

        return view('initial-admin-setup.school', compact('school'));
    }

    /**
     * Create the initial admin user for a school
     */
    public function createSchoolAdmin(Request $request, $schoolId)
    {
        $school = School::findOrFail($schoolId);

        // Validate the request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Create the admin user with personal email
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'], // Personal email, NOT school email
            'password' => Hash::make($validated['password']),
            'school_id' => $school->id,
        ]);

        // Assign admin role
        $user->assignRole('school-admin');

        \Log::info('Created initial admin for school', [
            'school_id' => $school->id,
            'user_id' => $user->id,
            'personal_email' => $user->email
        ]);

        // Log the user in
        auth()->login($user);

        return redirect()->route('school.dashboard')->with('success', 'Welcome! Your admin account has been created.');
    }
}
