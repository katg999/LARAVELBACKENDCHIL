<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Doctor;
use App\Models\Appointment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\MeetingLinkMail;
use Illuminate\Support\Facades\Auth;


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

        return view('api.api-dashboard-doctors', [
            'doctors' => $doctors,
            'error' => $error
        ]);
    }

    /**
     * Authenticated doctor dashboard (uses session auth)
     */
    public function authDashboard(Request $request)
    {
        $authenticatedUser = $request->current_user;
        $doctor = Doctor::findOrFail($authenticatedUser['id']);

        // Fetch appointments with related data (exclude cancelled)
        $appointments = Appointment::where('doctor_id', $doctor->id)
            ->where('status', '!=', 'cancelled')
            ->with(['patient', 'duration'])
            ->get();

        // Get upcoming appointments (next 7 days, exclude cancelled)
        $upcomingAppointments = Appointment::where('doctor_id', $doctor->id)
            ->where('status', '!=', 'cancelled')
            ->with(['patient'])
            ->where('appointment_time', '>', now())
            ->where('appointment_time', '<=', now()->addDays(7))
            ->orderBy('appointment_time')
            ->get();

        // Calculate stats
        $totalAppointments = $appointments->count();
        $completedAppointments = $appointments->where('status', 'completed')->count();
        $uniquePatients = $appointments->pluck('patient')->filter()->unique('id')->count();

        // Calculate revenue
        $revenue = 0;
        foreach ($appointments as $appt) {
            if ($appt->status === 'completed' && $appt->duration) {
                $revenue += $appt->duration->getPrice();
            }
        }

        $stats = [
            'total_appointments' => $totalAppointments,
            'completed_appointments' => $completedAppointments,
            'patients' => $uniquePatients,
            'revenue' => $revenue
        ];

        return view('doctor.doctor-dashboard', [
            'doctor' => $doctor,
            'appointments' => $appointments,
            'upcomingAppointments' => $upcomingAppointments,
            'stats' => $stats
        ]);
    }

    /**
     * Register a new doctor
     */
    public function registerDoctor(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:doctors',
                'contact' => 'required|string|max:20',
                'specialization' => 'required|string|max:255',
                'file_url' => 'nullable|string'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed for doctor registration', [
                'errors' => $e->errors()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        $doctor = Doctor::create($validated);

        return response()->json([
            'message' => 'Doctor registered successfully',
            'doctor' => $doctor
        ], 201);
    }

    /**
     * Get all appointments for a doctor (session-authenticated)
     */
    public function getDoctorAppointments(Request $request)
    {
        $authenticatedUser = $request->current_user;
        $doctor = Doctor::findOrFail($authenticatedUser['id']);

        $appointments = Appointment::where('doctor_id', $doctor->id)
            ->whereIn('status', ['confirmed', 'completed']) // Only show confirmed and completed appointments
            ->with(['patient', 'school', 'healthFacility', 'duration'])
            ->latest()
            ->paginate(10);

        return view('doctor.doctor-appointments', [
            'appointments' => $appointments,
            'doctor' => $doctor
        ]);
    }

    /**
     * Get all doctors (API endpoint)
     */
    public function getDoctors()
    {
        return response()->json(Doctor::all());
    }

    /**
     * Get available doctors for a specific day
     */
    public function getAvailableDoctors(Request $request)
    {
        $day = $request->query('day');

        if (!$day) {
            return response()->json([
                'success' => false,
                'message' => 'Day parameter is required'
            ], 400);
        }

        $doctors = Doctor::availableOnDay($day)->get();

        return response()->json([
            'success' => true,
            'doctors' => $doctors
        ]);
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

    return view('doctor.doctor-dashboard', [
        'doctor' => $doctor
    ]);
}


    public function showDoctorDashboard(Request $request)
    {
        try {
            $authenticatedUser = $request->current_user;
            $doctor = Doctor::findOrFail($authenticatedUser['id']);

            // Fetch appointments with related data (only confirmed and completed)
            $appointments = Appointment::where('doctor_id', $doctor->id)
                ->whereIn('status', ['confirmed', 'completed'])
                ->with(['patient', 'duration'])
                ->get();

            // Get upcoming appointments (next 7 days, only confirmed and completed)
            $upcomingAppointments = Appointment::where('doctor_id', $doctor->id)
                ->whereIn('status', ['confirmed', 'completed'])
                ->with(['patient'])
                ->where('appointment_time', '>', now())
                ->where('appointment_time', '<=', now()->addDays(7))
                ->orderBy('appointment_time')
                ->get();

            // Calculate stats
            $totalAppointments = $appointments->count();
            $completedAppointments = $appointments->where('status', 'completed')->count();
            $uniquePatients = $appointments->pluck('patient')->filter()->unique('id')->count();

            // Calculate revenue
            $revenue = 0;
            foreach ($appointments as $appt) {
                if ($appt->status === 'completed' && $appt->duration) {
                    $revenue += $appt->duration->getPrice();
                }
            }

            $stats = [
                'total_appointments' => $totalAppointments,
                'completed_appointments' => $completedAppointments,
                'patients' => $uniquePatients,
                'revenue' => $revenue
            ];

            return view('doctor.doctor-dashboard', [
                'doctor' => $doctor,
                'appointments' => $appointments,
                'upcomingAppointments' => $upcomingAppointments,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }    /**
     * Show availability management page for all doctors with filtering
     */
    public function allAvailabilities(Request $request)
    {
        $query = Doctor::with('availabilities');

        // Filter by specialization
        if ($request->filled('specialization')) {
            $query->where('specialization', 'like', '%' . $request->specialization . '%');
        }

        // Filter by name
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        // Filter by availability on specific day
        if ($request->filled('day')) {
            $query->availableOnDay($request->day);
        }

        $doctors = $query->paginate(10);

        return view('doctor.doctor-availabilities', [
            'doctors' => $doctors,
            'filters' => $request->only(['specialization', 'name', 'day'])
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



public function updateMeetingLink(Request $request)
{
    $authenticatedUser = $request->current_user;
    $doctor = Doctor::findOrFail($authenticatedUser['id']);

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


    public function sendLink(Request $request)
    {
        $authenticatedUser = $request->current_user;
        $doctor = Doctor::findOrFail($authenticatedUser['id']);

        $request->validate([
            'recipient_email' => 'required|email',
            'message' => 'nullable|string',
        ]);

        // Use Jitsi Meet as the meeting provider
        $link = 'https://meet.jit.si/' . ($doctor->meeting_slug ?? 'dr-' . strtolower(str_replace(' ', '-', $doctor->name)));
            $messageContent = $request->input('message') ?? "You have a meeting invitation. Click the button below to join.";

            // Use a Mailable with a nice HTML template
            Mail::to($request->recipient_email)
                ->send(new MeetingLinkMail($doctor, $messageContent, $link));

        // Check if this is an AJAX request
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Meeting link sent successfully!'
            ]);
        }

        return back()->with('success', 'Meeting link sent successfully.');
    }public function updateOnlineStatus(Request $request, Doctor $doctor)
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

    /**
     * Show availability management page for a specific doctor
     */
    public function availability(Request $request)
    {
        $authenticatedUser = $request->current_user;
        $doctor = Doctor::findOrFail($authenticatedUser['id']);
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        return view('doctor.doctor-availability', [
            'doctor' => $doctor,
            'days' => $days
        ]);
    }

    /**
     * Show meeting link management page for a specific doctor
     */
    public function meetingLink(Request $request)
    {
        $authenticatedUser = $request->current_user;
        $doctor = Doctor::findOrFail($authenticatedUser['id']);

        return view('doctor.meeting-link', [
            'doctor' => $doctor
        ]);
    }

    /**
     * Show edit profile page for a specific doctor
     */
    public function editProfile(Request $request)
    {
        $authenticatedUser = $request->current_user;
        $doctor = Doctor::findOrFail($authenticatedUser['id']);

        return view('doctor.doctor-edit-profile', [
            'doctor' => $doctor
        ]);
    }

    /**
     * Update doctor profile
     */
    public function updateProfile(Request $request)
    {
        $authenticatedUser = $request->current_user;
        $doctor = Doctor::findOrFail($authenticatedUser['id']);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:doctors,email,' . $doctor->id,
            'contact' => 'nullable|string|max:20',
            'specialization' => 'nullable|string|max:255',
            'meeting_slug' => 'nullable|string|alpha_dash|unique:doctors,meeting_slug,' . $doctor->id,
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            // Delete old image if exists
            if ($doctor->file_url && Storage::exists('public/profile_images/' . basename($doctor->file_url))) {
                Storage::delete('public/profile_images/' . basename($doctor->file_url));
            }

            // Store new image
            $imagePath = $request->file('profile_image')->store('public/profile_images');
            $validated['file_url'] = asset('storage/' . basename($imagePath));
        }

        $doctor->update($validated);

        return redirect()->route('doctor.profile')->with('success', 'Profile updated successfully!');
    }

}