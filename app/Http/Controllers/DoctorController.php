<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Doctor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;


class DoctorController extends Controller
{
    /**
     * Display doctors dashboard
     */
    public function dashboard()
    {
        try {
            // Try fetching from API first
            $response = Http::get('https://laravelbackendchil.onrender.com/api/doctors');
            
            if ($response->successful()) {
                $doctors = $response->json();
                $error = null;
            } else {
                // Fallback to local database if API fails
                $doctors = Doctor::all()->toArray();
                $error = 'Using local data: ' . $response->status();
            }
            
        } catch (\Exception $e) {
            // Final fallback to empty data with error message
            $doctors = [];
            $error = 'Error: ' . $e->getMessage();
        }

        return view('api-dashboard-doctors', [
            'doctors' => $doctors,
            'error' => $error
        ]);
    }

    /**
     * Register a new doctor
     */
    public function registerDoctor(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:doctors',
            'contact' => 'required|string|max:20',
            'specialization' => 'nullable|string|max:255',
            'file_url' => 'nullable|string'
        ]);

        $doctor = Doctor::create($validated);

        return response()->json([
            'message' => 'Doctor registered successfully',
            'doctor' => $doctor
        ], 201);
    }

    /**
     * Get all doctors (API endpoint)
     */
    public function getDoctors()
    {
        return response()->json(Doctor::all());
    }

    /**
     * Update file URL for most recently created doctor
     */
   public function updateLatestDoctorFile(Request $request)
{
    Log::info('Doctor file update method called');
    
    // Get the latest doctor by created_at timestamp
    $doctor = Doctor::latest()->first();
    
    // Also get the highest ID doctor for comparison
    $doctorByHighestId = Doctor::orderBy('id', 'desc')->first();
    
    Log::info('Doctor retrieved for update', [
        'latest_by_timestamp' => [
            'doctor_id' => $doctor ? $doctor->id : null,
            'created_at' => $doctor ? $doctor->created_at : null
        ],
        'latest_by_id' => [
            'doctor_id' => $doctorByHighestId ? $doctorByHighestId->id : null,
            'created_at' => $doctorByHighestId ? $doctorByHighestId->created_at : null
        ]
    ]);
    
    if (!$doctor) {
        Log::warning('No doctor found to update');
        return response()->json([
            'message' => 'No doctor records found to update'
        ], 404);
    }
    
    $validated = $request->validate([
        'file_url' => 'required|string|url'
    ]);
    
    $doctor->file_url = $validated['file_url'];
    $doctor->save();
    
    Log::info('Doctor file updated', [
        'doctor_id' => $doctor->id,
        'file_url' => $validated['file_url']
    ]);
    
    return response()->json([
        'message' => 'File URL updated for most recent doctor',
        'doctor_id' => $doctor->id,
        'file_url' => $doctor->file_url
    ]);
}

    /**
     * Update specific doctor by ID
     */
    public function updateDoctor(Request $request, $id)
    {
        $doctor = Doctor::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:doctors,email,'.$doctor->id,
            'contact' => 'sometimes|string|max:20',
            'specialization' => 'nullable|string|max:255',
            'file_url' => 'nullable|string'
        ]);

        $doctor->update($validated);

        return response()->json([
            'message' => 'Doctor updated successfully',
            'doctor' => $doctor
        ]);
    }


    public function showDashboard($id)
{
    $doctor = Doctor::findOrFail($id); // fetch doctor by ID

    return view('doctor-dashboard', [
        'doctor' => $doctor
    ]);
}


public function showDoctorDashboard($doctorId)
{
    $doctor = Doctor::with([
        'appointments.student',
        'appointments.school',
        'availabilities' // ✅ Include availabilities here
    ])->findOrFail($doctorId);

    // All appointments
    $appointments = $doctor->appointments()->latest()->get();

    // Upcoming appointments
    $upcomingAppointments = $doctor->appointments()
        ->where('appointment_time', '>', now())
        ->orderBy('appointment_time')
        ->get();

    // Calculate stats
    $totalAppointments = $appointments->count();
    $completedAppointments = $appointments->where('status', 'completed')->count();
    $upcomingAppointmentCount = $upcomingAppointments->count();

    $stats = [
        'total_appointments' => $totalAppointments,
        'completed_appointments' => $completedAppointments,
        'upcoming_appointments' => $upcomingAppointmentCount,
    ];

    // Compute availability for the next 7 days
    $today = now();
    $nextSevenDays = [];

    foreach (range(0, 6) as $i) {
        $date = $today->copy()->addDays($i);
        $dayName = strtolower($date->format('l')); // e.g., "monday"

        $availability = $doctor->availabilities
            ->where('day', $dayName)
            ->first();

        $nextSevenDays[] = [
            'date' => $date,
            'available' => $availability ? $availability->available : false,
        ];
    }

    return view('doctor-dashboard', [
        'doctor' => $doctor,
        'appointments' => $appointments,
        'upcomingAppointments' => $upcomingAppointments,
        'stats' => $stats,
        'nextSevenDays' => $nextSevenDays // ✅ Pass to view
    ]);
}


public function update(Request $request, Doctor $doctor)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:doctors,email,' . $doctor->id,
        // Add more validation as needed
    ]);

    $doctor->update($validated);

    return redirect()->back()->with('success', 'Profile updated successfully.');
}



public function updateMeetingLink(Request $request, $id)
{
    $doctor = Doctor::findOrFail($id);

    $request->validate([
        'meeting_slug' => 'required|string|alpha_dash|unique:doctors,meeting_slug,' . $doctor->id,
    ]);

    $doctor->meeting_slug = $request->input('meeting_slug');
    $doctor->save();

    return redirect()->back()->with('success', 'Meeting link updated!');
}

public function updateAvailability(Request $request, $id)
{
    $doctor = Doctor::findOrFail($id);

    $request->validate([
        'availability' => 'required|string|max:255' // or adjust based on your availability format
    ]);

    $doctor->availability = $request->input('availability');
    $doctor->save();

    return redirect()->back()->with('success', 'Availability updated successfully.');
}


public function changePassword(Request $request, Doctor $doctor)
{
    $request->validate([
        'current_password' => 'required',
        'new_password' => 'required|min:8|confirmed',
    ]);

    if (!Hash::check($request->current_password, $doctor->password)) {
        return back()->withErrors(['current_password' => 'Current password is incorrect']);
    }

    $doctor->password = Hash::make($request->new_password);
    $doctor->save();

    return back()->with('success', 'Password updated successfully');
}

public function updateNotifications(Request $request, Doctor $doctor)
{
    $doctor->email_notifications = $request->has('email_notifications');
    $doctor->sms_notifications = $request->has('sms_notifications');
    $doctor->save();

    return back()->with('success', 'Notification preferences updated.');
}


public function updatePayment(Request $request, Doctor $doctor)
{
    $doctor->momo_number = $request->input('momo_number');
    $doctor->bank_name = $request->input('bank_name');
    $doctor->account_number = $request->input('account_number');
    $doctor->save();

    return back()->with('success', 'Payment information updated.');
}

public function uploadImage(Request $request, Doctor $doctor)
{
    $request->validate([
        'profile_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
    ]);

    if ($request->hasFile('profile_image')) {
        $imagePath = $request->file('profile_image')->store('public/profile_images');

        // Save the filename or URL to the doctor record
        $doctor->profile_image = basename($imagePath);
        $doctor->save();

        return back()->with('success', 'Profile image uploaded successfully.');
    }

    return back()->with('error', 'Image upload failed.');
}


public function sendLink(Request $request, Doctor $doctor)
{
    $request->validate([
        'recipient_email' => 'required|email',
        'message' => 'nullable|string',
    ]);

    $link = 'https://meet.ketiai.com/' . ($doctor->meeting_slug ?? 'dr-' . strtolower(str_replace(' ', '-', $doctor->name)));
    $messageContent = $request->input('message') ?? "Here is my meeting link: $link";

    Mail::raw($messageContent, function ($message) use ($request) {
        $message->to($request->recipient_email)
                ->subject('Meeting Link from Your Doctor');
    });

    return back()->with('success', 'Meeting link sent successfully.');
}

public function updateOnlineStatus(Request $request, Doctor $doctor)
{
    // Validate the request data
    $request->validate([
        'is_online' => 'required|boolean',
    ]);

    // Update the doctor's online status
    $doctor->is_online = $request->is_online;
    $doctor->save();

    return response()->json(['status' => 'success', 'is_online' => $doctor->is_online]);
}

}