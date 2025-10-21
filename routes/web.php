<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PatientController; 
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\FinanceDashboardController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminModelController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HealthFacilityController;
use App\Http\Controllers\ApiDashboardController;
use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\OtpController;
use Illuminate\Http\Request; 
use App\Models\Doctor;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WalletController;



/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
| These routes are loaded by the RouteServiceProvider within a group
| which contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect('https://ketiai.com');
});

// Home Route (Fixed Controller Reference)
Route::get('/home', [HomeController::class, 'index'])->name('home');

// API Dashboard Route
Route::get('/api-dashboard', [ApiDashboardController::class, 'index'])->name('api-dashboard');

// Finance Dashboard Route
Route::get('/finance-dashboard', [FinanceDashboardController::class, 'index'])->name('finance-dashboard');


// If you need a web view for admin purposes
//Route::get('/admin/contact-submissions', function () {
  //  return view('contact-submissions');
//});


Route::get('/admin/contact-submissions', [ContactController::class, 'index'])
     ->name('admin.contact-submissions');


// Newsletter verification (web - HTML)
Route::get('/verify-newsletter/{token}', [NewsletterController::class, 'verify'])->name('newsletter.verify.web');

Route::post('/send-otp', [App\Http\Controllers\OtpController::class, 'sendOtp']);

// routes/web.php
// Route::get('/school-dashboard/{school}', function (App\Models\School $school) {
//     return view('school-dashboard', [
//         'school' => $school,
//         'students' => $school->students()->with(['appointments', 'labTests'])->latest()->get(),
//         'appointments' => $school->appointments()->with(['student', 'doctor'])->latest()->get(),
//         'labTests' => $school->labTests()->with('student')->latest()->get(),
//         'doctors' => $school->doctors()->latest()->get()
//     ]);
// })->name('school.dashboard');

use App\Http\Controllers\SchoolController;

Route::get('/school-dashboard/{school}', function (App\Models\School $school) {
    return view('school-dashboard', [
        'school' => $school,
        'studentsCount' => $school->students()->count(),
        'appointmentsCount' => $school->appointments()->count(),
        'labTestsCount' => $school->labTests()->count(),
        'doctorsCount' => $school->doctors()->count(),
        'students' => $school->students()->latest()->get(),
        'appointments' => $school->appointments()->with(['patient', 'doctor', 'duration'])->latest()->get(),
        'labTests' => $school->labTests()->with('patient')->latest()->get(),
        'doctors' => Doctor::latest()->get()
    ]);
})->name('school.dashboard');


Route::get('/students/{school}', function (App\Models\School $school) {
    return view('students', [
        'school' => $school,
        'students' => $school->students()->latest()->get()
    ]);
})->name('students');


Route::delete('/students/{student}/delete', function ($studentId) {
    $student = App\Models\Patient::findOrFail($studentId);
    $schoolId = $student->school_id;

    // Check if student has any appointments
    if ($student->appointments()->count() > 0) {
        return redirect()->route('students', ['school' => $schoolId])
            ->with('error', 'Cannot delete student with existing appointments.');
    }

    // Deleting the student will cascade and remove related appointments (handled in Student model)
    $student->delete();

    return redirect()->route('students', ['school' => $schoolId])->with('success', 'Student deleted successfully.');
})->name('students.delete');

Route::post('/students/create', function (Request $request) {
    try {
        $validated = $request->validate([
            'patient_type' => 'required|in:new,existing',
            'patient_type' => 'required|in:new,existing',
            'school_id' => 'required',
        ]);

        if ($validated['patient_type'] === 'existing') {
            // Handle existing patient
            $existingValidation = $request->validate([
                'patient_id' => 'required|string|exists:patients,patient_id',
            ]);

            $patient = App\Models\Patient::where('patient_id', $existingValidation['patient_id'])->first();

            // Check if patient is already associated with this school
            if ($patient->school_id == $validated['school_id']) {
                return redirect()->route('students', ['school' => $validated['school_id']])
                    ->with('error', 'Patient is already associated with this school.');
            }

            // Update patient with school association
            $patient->update([
                'school_id' => $validated['school_id'],
                'grade' => $request->input('grade'), // Optional grade for existing patients
            ]);

            return redirect()->route('students', ['school' => $validated['school_id']])
                ->with('success', 'Existing patient associated with school successfully.');
        } else {
            // Handle new patient
            $newValidation = $request->validate([
                'name' => 'required',
                'gender' => 'required|in:male,female,other',
                'grade' => 'required',
                'parent_contact' => 'required',
                'birth_date' => 'required|date',
            ]);

            // Use findOrCreate to check if student exists or create new one
            $patient = App\Models\Patient::findOrCreate([
                'name' => $newValidation['name'],
                'birth_date' => $newValidation['birth_date'],
                'gender' => $newValidation['gender'],
                'parent_contact' => $newValidation['parent_contact'],
            ], [
                'school_id' => $validated['school_id'],
                'grade' => $newValidation['grade'],
            ]);

            return redirect()->route('students', ['school' => $patient->school_id])
                ->with('success', 'Student created successfully.');
        }
    } catch (\Illuminate\Validation\ValidationException $e) {
        return redirect()->back()
            ->withErrors($e->errors())
            ->withInput();
    }
})->name('students.create');


Route::get('/lab-tests/{school}', function (App\Models\School $school) {
    // Paginate lab tests for the school (15 per page)
    $labTests = $school->labTests()->with('patient')->latest()->paginate(15);

    return view('lab-tests', [
        'school' => $school,
        'labTests' => $labTests,
        'students' => $school->students()->latest()->get()
    ]);
})->name('lab-tests');

// Handle lab test form submissions from web forms (redirect back to lab-tests page)
Route::post('/lab-tests', function (Illuminate\Http\Request $request) {
    $validated = $request->validate([
        'school_id' => 'required|exists:schools,id',
        'student_id' => 'required|exists:students,id',
        'test_type' => 'required|string',
        'notes' => 'nullable|string'
    ]);

    $labTest = App\Models\LabTest::create(array_merge($validated, ['status' => 'pending']));

    return redirect()->route('lab-tests', ['school' => $validated['school_id']])->with('success', 'Lab test requested successfully.');
})->name('lab-tests.store');

// Delete a lab test (web)
Route::delete('/lab-tests/{labTest}', function (App\Models\LabTest $labTest) {
    $schoolId = $labTest->school_id;
    $labTest->delete();
    return redirect()->route('lab-tests', ['school' => $schoolId])->with('success', 'Lab test deleted');
})->name('lab-tests.destroy');

// Mark a lab test as completed (web)
Route::post('/lab-tests/{labTest}/complete', function (App\Models\LabTest $labTest) {
    $labTest->update(['status' => 'completed']);
    return redirect()->route('lab-tests', ['school' => $labTest->school_id])->with('success', 'Lab test marked completed');
})->name('lab-tests.complete');


Route::get('/book-doctor/{school}/', function (App\Models\School $school) {
    return view('book-doctor', [
        'school' => $school,
        'appointments' => $school->appointments()->with(['patient', 'doctor', 'duration'])->latest()->get(),
        'patients' => $school->students()->latest()->get(),
        'doctors' => Doctor::latest()->get()
    ]);
})->name('book-doctor');


Route::get('/doctor/{doctorId}/appointments', [DoctorController::class, 'getDoctorAppointments'])->name('doctor.appointments');
// Appointment actions
Route::patch('/appointments/{appointment}/cancel', [\App\Http\Controllers\AppointmentController::class, 'cancel'])->name('appointments.cancel');
Route::patch('/appointments/{appointment}/complete', [\App\Http\Controllers\AppointmentController::class, 'complete'])->name('appointments.complete');

// Authentication routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Registration routes
Route::get('register', [App\Http\Controllers\Auth\RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('register', [App\Http\Controllers\Auth\RegisterController::class, 'register']);

// Admin registration invite route
Route::get('/admin/register/{token}', [App\Http\Controllers\Auth\RegisterController::class, 'showRegistrationForm'])->name('admin.register');

// Password Reset Routes
Route::get('password/reset', 'App\Http\Controllers\Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
Route::post('password/email', 'App\Http\Controllers\Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
Route::get('password/reset/{token}', 'App\Http\Controllers\Auth\ResetPasswordController@showResetForm')->name('password.reset');
Route::post('password/reset', 'App\Http\Controllers\Auth\ResetPasswordController@reset')->name('password.update');

// Simple admin area (protected)
Route::prefix('admin')->middleware(['auth', 'can:admin'])->group(function(){
    Route::get('/', [AdminController::class, 'index'])->name('admin.index');

    // Individual model routes
    Route::get('/doctors', [AdminModelController::class, 'index'])->name('admin.doctors.index');
    Route::get('/doctors/create', [AdminModelController::class, 'create'])->name('admin.doctors.create');
    Route::post('/doctors', [AdminModelController::class, 'store'])->name('admin.doctors.store');
    Route::get('/doctors/{id}/edit', [AdminModelController::class, 'edit'])->name('admin.doctors.edit');
    Route::put('/doctors/{id}', [AdminModelController::class, 'update'])->name('admin.doctors.update');
    Route::delete('/doctors/{id}', [AdminModelController::class, 'destroy'])->name('admin.doctors.destroy');

    Route::get('/appointments', [AdminModelController::class, 'index'])->name('admin.appointments.index');
    Route::get('/appointments/create', [AdminModelController::class, 'create'])->name('admin.appointments.create');
    Route::post('/appointments', [AdminModelController::class, 'store'])->name('admin.appointments.store');
    Route::get('/appointments/{id}/edit', [AdminModelController::class, 'edit'])->name('admin.appointments.edit');
    Route::put('/appointments/{id}', [AdminModelController::class, 'update'])->name('admin.appointments.update');
    Route::delete('/appointments/{id}', [AdminModelController::class, 'destroy'])->name('admin.appointments.destroy');

    Route::get('/payments', [AdminModelController::class, 'index'])->name('admin.payments.index');
    Route::get('/payments/create', [AdminModelController::class, 'create'])->name('admin.payments.create');
    Route::post('/payments', [AdminModelController::class, 'store'])->name('admin.payments.store');
    Route::get('/payments/{id}/edit', [AdminModelController::class, 'edit'])->name('admin.payments.edit');
    Route::put('/payments/{id}', [AdminModelController::class, 'update'])->name('admin.payments.update');
    Route::delete('/payments/{id}', [AdminModelController::class, 'destroy'])->name('admin.payments.destroy');

    Route::get('/patients', [AdminModelController::class, 'index'])->name('admin.patients.index');
    Route::get('/patients/create', [AdminModelController::class, 'create'])->name('admin.patients.create');
    Route::post('/patients', [AdminModelController::class, 'store'])->name('admin.patients.store');
    Route::get('/patients/{id}/edit', [AdminModelController::class, 'edit'])->name('admin.patients.edit');
    Route::put('/patients/{id}', [AdminModelController::class, 'update'])->name('admin.patients.update');
    Route::delete('/patients/{id}', [AdminModelController::class, 'destroy'])->name('admin.patients.destroy');

    Route::get('/schools', [AdminModelController::class, 'index'])->name('admin.schools.index');
    Route::get('/schools/create', [AdminModelController::class, 'create'])->name('admin.schools.create');
    Route::post('/schools', [AdminModelController::class, 'store'])->name('admin.schools.store');
    Route::get('/schools/{id}/edit', [AdminModelController::class, 'edit'])->name('admin.schools.edit');
    Route::put('/schools/{id}', [AdminModelController::class, 'update'])->name('admin.schools.update');
    Route::delete('/schools/{id}', [AdminModelController::class, 'destroy'])->name('admin.schools.destroy');

    Route::get('/health-facilities', [AdminModelController::class, 'index'])->name('admin.health-facilities.index');
    Route::get('/health-facilities/create', [AdminModelController::class, 'create'])->name('admin.health-facilities.create');
    Route::post('/health-facilities', [AdminModelController::class, 'store'])->name('admin.health-facilities.store');
    Route::get('/health-facilities/{id}/edit', [AdminModelController::class, 'edit'])->name('admin.health-facilities.edit');
    Route::put('/health-facilities/{id}', [AdminModelController::class, 'update'])->name('admin.health-facilities.update');
    Route::delete('/health-facilities/{id}', [AdminModelController::class, 'destroy'])->name('admin.health-facilities.destroy');

    // Generic model routes (fallback)
    Route::get('/{modelKey}', [AdminModelController::class, 'index'])->name('admin.model.index');
    Route::get('/{modelKey}/create', [AdminModelController::class, 'create'])->name('admin.model.create');
    Route::post('/{modelKey}', [AdminModelController::class, 'store'])->name('admin.model.store');
    // Appointments extras
    Route::post('/appointments/bulk', [AdminModelController::class, 'bulkUpdateAppointments'])->name('admin.appointments.bulk');
    Route::get('/appointments/export', [AdminModelController::class, 'exportAppointmentsCsv'])->name('admin.appointments.export');
    // Payments extras
    Route::post('/payments/bulk', [AdminModelController::class, 'bulkUpdatePayments'])->name('admin.payments.bulk');
    Route::get('/{modelKey}/{id}/edit', [AdminModelController::class, 'edit'])->name('admin.model.edit');
    Route::get('/doctors/{id}', [AdminModelController::class, 'showDoctor'])->name('admin.doctors.show');
    Route::post('/doctors/{id}/send-login-link', [AdminModelController::class, 'sendLoginLinkToDoctor'])->name('admin.doctors.send-login');
    Route::put('/{modelKey}/{id}', [AdminModelController::class, 'update'])->name('admin.model.update');
    Route::delete('/{modelKey}/{id}', [AdminModelController::class, 'destroy'])->name('admin.model.destroy');
});
Route::get('doctor/{doctorId}/meeting-link/', function ($doctorId) {
    $doctor = Doctor::findOrFail($doctorId);
    return view('meeting-link', [
        'appointments' => $doctor->appointments()->with(['patient', 'school', 'healthFacility', 'duration'])->latest()->get(),
        'doctor' => $doctor
    ]);
})->name('doctor.meeting-link');

// Web route to update a doctor's meeting link from the web form (keeps session & CSRF)
Route::post('/doctor/{id}/update-meeting-link', [DoctorController::class, 'updateMeetingLink'])
    ->name('doctor.update-meeting-link');

// Web route to send meeting link to an email (school/health facility)
Route::post('/doctor/{doctor}/send-link', [\App\Http\Controllers\DoctorController::class, 'sendLink'])
    ->name('doctor.send-link');

// Web route to update doctor availability (form submissions)
Route::post('/doctor/{doctor}/availability', [\App\Http\Controllers\DoctorAvailabilityController::class, 'update'])
    ->name('doctor.update-availability');

Route::get('/doctor-dashboard/{doctorId}/availability', [DoctorController::class, 'availability'])->name('doctor.availability');

Route::get('/doctor-availabilities', [DoctorController::class, 'allAvailabilities'])->middleware('admin')->name('doctor.all-availabilities');


// Doctor Dashboard Route
Route::get('/doctors-dashboard', [DoctorController::class, 'dashboard']);


Route::middleware(['auth:doctor'])->group(function () {
    Route::get('/doctor/dashboard', [DoctorController::class, 'authDashboard'])->name('doctor.dashboard');
    // Add routes for other methods if not already defined
});


Route::get('/doctor-dashboard', function () {
    return view('doctor-dashboard'); // points to resources/views/doctor-dashboard.blade.php
});


Route::get('/doctor-dashboard/{doctorId}', [DoctorController::class, 'showDoctorDashboard'])->name('doctor.dashboard');

// One-time login link consume route (public)
Route::get('/one-time-login/{token}', [\App\Http\Controllers\OneTimeLoginController::class, 'consume'])->name('one-time-login.consume');

// Centralized profile route (optional doctor id to preserve doctor context)
Route::get('/profile/{doctor?}', [App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');


// In your web.php routes file, add this route

Route::get('/health-facilities-dashboard', function () {
    try {
        // Fetch data from the API endpoint
        $response = Http::get('https://laravelbackendchil.onrender.com/api/health-facilities');
        
        // Check if the request was successful
        if ($response->successful()) {
            // Get the data from the response
            $data = $response->json();
            
            // Check if we have health facilities data
            $healthFacilities = $data['data'] ?? [];
            
            // If data is not in expected format, check if it's an array at root level
            if (empty($healthFacilities) && is_array($data)) {
                $healthFacilities = $data;
            }
        } else {
            // Request failed, set empty array
            $healthFacilities = [];
            $error = 'Failed to fetch data from API: ' . ($response->json()['message'] ?? 'Unknown error');
        }
    } catch (\Exception $e) {
        // Handle exceptions (network errors, etc.)
        $healthFacilities = [];
        $error = 'Exception occurred: ' . $e->getMessage();
    }
    
    // Pass the data to the view
    return view('health_facilities', [
        'healthFacilities' => $healthFacilities ?? [],
        'error' => $error ?? null
    ]);
});


Route::get('/health-facility/dashboard/{id}', [HealthFacilityController::class, 'showDashboard'])->name('health-facility.dashboard');

// Health Facility section routes
Route::get('/health-facility/{id}/patients', [HealthFacilityController::class, 'patients'])->name('health-facility.patients');
Route::get('/health-facility/{id}/patients/create', [HealthFacilityController::class, 'createPatient'])->name('health-facility.patients.create');
Route::get('/health-facility/{id}/book-doctor', [HealthFacilityController::class, 'bookDoctor'])->name('health-facility.book-doctor');
Route::get('/health-facility/{id}/lab-tests', [HealthFacilityController::class, 'labTests'])->name('health-facility.lab-tests');
Route::get('/health-facility/{id}/transactions', [HealthFacilityController::class, 'transactions'])->name('health-facility.transactions');
Route::get('/health-facility/{id}/staff', [HealthFacilityController::class, 'staff'])->name('health-facility.staff');

Route::put('/health-facilities/{id}', [HealthFacilityController::class, 'updateHealthFacility'])->name('health-facilities.update');
Route::post('/health-facilities/{id}/change-password', [HealthFacilityController::class, 'changePassword'])->name('health-facilities.change-password');
Route::post('/health-facilities/{id}/upload-logo', [HealthFacilityController::class, 'uploadLogo'])->name('health-facilities.upload-logo');
Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
Route::post('/patients', [PatientController::class, 'store'])->name('patients.store');
Route::delete('/patients/{patient}/delete', [PatientController::class, 'destroy'])->name('patients.delete');

Route::get('/patients/create', [PatientController::class, 'createGeneral'])->name('patients.general.create');
Route::post('/patients/general', [PatientController::class, 'storeGeneral'])->name('patients.general.store');


Route::get('/patients/{patient}/maternal', [PatientController::class, 'maternalDocuments'])
    ->name('patient.maternal');

Route::get('/patients/{patient}/profile', [PatientController::class, 'show'])->name('patients.profile');
    
Route::post('/patients/create', function (Request $request) {
    try {
        $validated = $request->validate([
            'patient_type' => 'required|in:new,existing',
            'health_facility_id' => 'required|exists:health_facilities,id',
        ]);

        if ($validated['patient_type'] === 'existing') {
            // Handle existing patient
            $existingValidation = $request->validate([
                'patient_id' => 'required|string|exists:patients,patient_id',
            ]);

            $patient = App\Models\Patient::where('patient_id', $existingValidation['patient_id'])->first();

            // Check if patient is already associated with this health facility
            if ($patient->health_facility_id == $validated['health_facility_id']) {
                return redirect()->route('health-facility.patients', ['id' => $validated['health_facility_id']])
                    ->with('error', 'Patient is already associated with this health facility.');
            }

            // Update patient with health facility association
            $patient->update([
                'health_facility_id' => $validated['health_facility_id'],
            ]);

            return redirect()->route('health-facility.patients', ['id' => $validated['health_facility_id']])
                ->with('success', 'Existing patient associated with health facility successfully.');
        } else {
            // Handle new patient
            $newValidation = $request->validate([
                'name' => 'required|string|max:255',
                'gender' => 'required|string|in:male,female,other',
                'birth_date' => 'required|date',
                'contact_number' => 'nullable|string'
            ]);

            $patient = App\Models\Patient::create(array_merge($newValidation, [
                'health_facility_id' => $validated['health_facility_id']
            ]));

            return redirect()->route('health-facility.patients', ['id' => $validated['health_facility_id']])
                ->with('success', 'Patient created successfully.');
        }
    } catch (\Illuminate\Validation\ValidationException $e) {
        return redirect()->back()
            ->withErrors($e->errors())
            ->withInput();
    }
})->name('patients.create');

   
Route::get('/payment', [PaymentController::class, 'index'])->name('payment.form');
Route::post('/checkout', [PaymentController::class, 'checkout'])->name('payment.checkout');
Route::get('/success', function () {
        return "Payment Successful!";
    })->name('payment.success');
    Route::get('/cancel', function () {
        return "Payment Canceled!";
    })->name('payment.cancel');


// ...existing code...

// New appointment payment routes
Route::get('/appointment/pay/{appointment}', [PaymentController::class, 'showAppointmentPayForm'])->name('payment.appointment.pay');
Route::post('/appointment/checkout', [PaymentController::class, 'createAppointmentCheckout'])->name('payment.appointment.checkout');
Route::get('/appointment/success/{appointment}', [PaymentController::class, 'appointmentSuccess'])->name('payment.appointment.success');
Route::get('/appointment/cancel/{appointment}', [PaymentController::class, 'appointmentCancel'])->name('payment.appointment.cancel');

// Wallet Routes
Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
Route::post('/wallet/deposit', [WalletController::class, 'deposit'])->name('wallet.deposit');
Route::post('/wallet/withdraw', [WalletController::class, 'withdraw'])->name('wallet.withdraw');
Route::get('/wallet/balance', [WalletController::class, 'balance'])->name('wallet.balance');