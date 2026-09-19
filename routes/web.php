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
use App\Http\Controllers\PatientVisitController;
use App\Http\Controllers\InsuranceController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\PatientAuthController;
use App\Http\Controllers\VisitPaymentController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\EmployerController;
use App\Http\Controllers\AdoptionMetricsController;
use App\Http\Controllers\UssdController;
use App\Http\Controllers\PharmacyController;
use App\Http\Controllers\ClaimsController;
use App\Http\Controllers\PatientInsuranceController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Controllers\PatientPrescriptionController;



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
     ->middleware('admin')
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

Route::middleware('session.auth:school')->group(function () {
    Route::get('/school-dashboard', function (Request $request) {
        $authenticatedUser = $request->current_user;
        $school = \App\Models\School::findOrFail($authenticatedUser['id']);

        // Get current week data (Monday to Sunday)
        $startOfWeek = now()->startOfWeek(); // Monday
        $endOfWeek = now()->endOfWeek(); // Sunday

        // Weekly appointments data
        $weeklyAppointments = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $count = $school->appointments()
                ->whereDate('appointment_time', $date)
                ->count();
            $weeklyAppointments[] = $count;
        }

        // Weekly lab tests data
        $weeklyLabTests = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $count = $school->labTests()
                ->whereDate('created_at', $date)
                ->count();
            $weeklyLabTests[] = $count;
        }

        return view('school.school-dashboard', [
            'school' => $school,
            'studentsCount' => $school->students()->count(),
            'appointmentsCount' => $school->appointments()->count(),
            'labTestsCount' => $school->labTests()->count(),
            'doctorsCount' => $school->doctors()->count(),
            'students' => $school->students()->latest()->get(),
            'appointments' => $school->appointments()->with(['patient', 'doctor', 'duration'])->latest()->get(),
            'labTests' => $school->labTests()->with('patient')->latest()->get(),
            'doctors' => Doctor::latest()->get(),
            'weeklyAppointments' => $weeklyAppointments,
            'weeklyLabTests' => $weeklyLabTests
        ]);
    })->name('school.dashboard');

    Route::get('/students', function (Request $request) {
        $authenticatedUser = $request->current_user;
        $school = \App\Models\School::findOrFail($authenticatedUser['id']);

        $query = $school->students();

        // Apply filters
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('grade')) {
            $query->where('grade', $request->grade);
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        if ($request->filled('min_age')) {
            $minBirthDate = now()->subYears($request->min_age + 1)->addDay();
            $query->where('birth_date', '<=', $minBirthDate);
        }

        if ($request->filled('max_age')) {
            $maxBirthDate = now()->subYears($request->max_age);
            $query->where('birth_date', '>=', $maxBirthDate);
        }

        $students = $query->latest()->paginate(15);

        return view('school.students', [
            'school' => $school,
            'students' => $students
        ]);
    })->name('students');

    Route::delete('/students/{student}/delete', function (Request $request, $studentId) {
        $authenticatedUser = $request->current_user;
        $school = \App\Models\School::findOrFail($authenticatedUser['id']);
        $student = App\Models\Patient::findOrFail($studentId);

        // Verify the student belongs to the school
        if ($student->school_id !== $school->id) {
            abort(403);
        }

        // Check if student has any appointments
        if ($student->appointments()->count() > 0) {
            return redirect()->route('students')
                ->with('error', 'Cannot delete student with existing appointments.');
        }

        // Deleting the student will cascade and remove related appointments (handled in Student model)
        $student->delete();

        return redirect()->route('students')->with('success', 'Student deleted successfully.');
    })->name('students.delete');

    Route::post('/students/create', function (Request $request) {
        $authenticatedUser = $request->current_user;
        $school = \App\Models\School::findOrFail($authenticatedUser['id']);

        try {
            $validated = $request->validate([
                'patient_type' => 'required|in:new,existing',
            ]);

            if ($validated['patient_type'] === 'existing') {
                // Handle existing patient
                $existingValidation = $request->validate([
                    'patient_id' => 'required|string|exists:patients,patient_id',
                ]);

                $patient = App\Models\Patient::where('patient_id', $existingValidation['patient_id'])->first();

                // Check if patient is already associated with this school
                if ($patient->school_id == $school->id) {
                    return redirect()->route('students')
                        ->with('error', 'Patient is already associated with this school.');
                }

                // Update patient with school association
                $patient->update([
                    'school_id' => $school->id,
                    'grade' => $request->input('grade'), // Optional grade for existing patients
                ]);

                return redirect()->route('students')
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
                    'school_id' => $school->id,
                    'grade' => $newValidation['grade'],
                ]);

                return redirect()->route('students')
                    ->with('success', 'Student created successfully.');
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        }
    })->name('students.create');

    Route::get('/lab-tests', function (Request $request) {
        $authenticatedUser = $request->current_user;
        $school = \App\Models\School::findOrFail($authenticatedUser['id']);

        // Paginate lab tests for the school (15 per page)
        $labTests = $school->labTests()->with('patient')->latest()->paginate(15);

        return view('lab.lab-tests', [
            'school' => $school,
            'labTests' => $labTests,
            'students' => $school->students()->latest()->get()
        ]);
    })->name('lab-tests');

    // Handle lab test form submissions from web forms (redirect back to lab-tests page)
    Route::post('/lab-tests', function (Illuminate\Http\Request $request) {
        $authenticatedUser = $request->current_user;
        $school = \App\Models\School::findOrFail($authenticatedUser['id']);

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'test_type' => 'required|string',
            'notes' => 'nullable|string'
        ]);

        $labTest = App\Models\LabTest::create(array_merge($validated, [
            'school_id' => $school->id,
            'status' => 'pending'
        ]));

        return redirect()->route('lab-tests')->with('success', 'Lab test requested successfully.');
    })->name('lab-tests.store');

    // Delete a lab test (web)
    Route::delete('/lab-tests/{labTest}', function (Request $request, App\Models\LabTest $labTest) {
        $authenticatedUser = $request->current_user;
        $school = \App\Models\School::findOrFail($authenticatedUser['id']);

        // Verify the lab test belongs to the authenticated school
        if ($labTest->school_id !== $school->id) {
            abort(403);
        }

        $labTest->delete();
        return redirect()->route('lab-tests')->with('success', 'Lab test deleted');
    })->name('lab-tests.destroy');

    // Mark a lab test as completed (web)
    Route::post('/lab-tests/{labTest}/complete', function (Request $request, App\Models\LabTest $labTest) {
        $authenticatedUser = $request->current_user;
        $school = \App\Models\School::findOrFail($authenticatedUser['id']);

        // Verify the lab test belongs to the authenticated school
        if ($labTest->school_id !== $school->id) {
            abort(403);
        }

        $labTest->update(['status' => 'completed']);
        return redirect()->route('lab-tests')->with('success', 'Lab test marked completed');
    })->name('lab-tests.complete');

    Route::get('/book-doctor', function (Request $request) {
        $authenticatedUser = $request->current_user;
        $school = \App\Models\School::findOrFail($authenticatedUser['id']);

        return view('booking.book-doctor', [
            'school' => $school,
            'appointments' => $school->appointments()->with(['patient', 'doctor', 'duration'])->latest()->get(),
            'patients' => $school->students()->latest()->get(),
            'doctors' => Doctor::latest()->get()
        ]);
    })->name('book-doctor');

    Route::get('/transactions', function (Request $request) {
        $authenticatedUser = $request->current_user;
        $school = \App\Models\School::findOrFail($authenticatedUser['id']);

        // Get transactions/payments related to this school
        // For now, we'll show appointments with payment status
        $appointments = $school->appointments()->with(['patient', 'doctor', 'duration'])->latest()->paginate(15);
        return view('school.school-transactions', compact('school', 'appointments'));
    })->name('school.transactions');
});



Route::get('/students/{school}', function (Request $request, App\Models\School $school) {
    $query = $school->students();

    // Apply filters
    if ($request->filled('search')) {
        $query->where('name', 'like', '%' . $request->search . '%');
    }

    if ($request->filled('grade')) {
        $query->where('grade', $request->grade);
    }

    if ($request->filled('gender')) {
        $query->where('gender', $request->gender);
    }

    if ($request->filled('min_age')) {
        $minBirthDate = now()->subYears($request->min_age + 1)->addDay();
        $query->where('birth_date', '<=', $minBirthDate);
    }

    if ($request->filled('max_age')) {
        $maxBirthDate = now()->subYears($request->max_age);
        $query->where('birth_date', '>=', $maxBirthDate);
    }

    $students = $query->latest()->paginate(15);

    return view('school.students', [
        'school' => $school,
        'students' => $students
    ]);
})->name('students');


Route::delete('/students/{school}/{student}/delete', function ($schoolId, $studentId) {
    $school = App\Models\School::findOrFail($schoolId);
    $student = App\Models\Patient::findOrFail($studentId);

    // Verify the student belongs to the school
    if ($student->school_id !== $school->id) {
        abort(403);
    }

    // Check if student has any appointments
    if ($student->appointments()->count() > 0) {
        return redirect()->route('students', ['school' => $school->id])
            ->with('error', 'Cannot delete student with existing appointments.');
    }

    // Deleting the student will cascade and remove related appointments (handled in Student model)
    $student->delete();

    return redirect()->route('students', ['school' => $school->id])->with('success', 'Student deleted successfully.');
})->name('students.delete');

Route::post('/students/create', function (Request $request) {
    // Get school from form data instead of session
    $school = App\Models\School::findOrFail($request->input('school_id'));

    try {
        $validated = $request->validate([
            'patient_type' => 'required|in:new,existing',
        ]);

        if ($validated['patient_type'] === 'existing') {
            // Handle existing patient
            $existingValidation = $request->validate([
                'patient_id' => 'required|string|exists:patients,patient_id',
            ]);

            $patient = App\Models\Patient::where('patient_id', $existingValidation['patient_id'])->first();

            // Check if patient is already associated with this school
            if ($patient->school_id == $school->id) {
                return redirect()->route('students', ['school' => $school->id])
                    ->with('error', 'Patient is already associated with this school.');
            }

            // Update patient with school association
            $patient->update([
                'school_id' => $school->id,
                'grade' => $request->input('grade'), // Optional grade for existing patients
            ]);

            return redirect()->route('students', ['school' => $school->id])
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
                'school_id' => $school->id,
                'grade' => $newValidation['grade'],
            ]);

            return redirect()->route('students', ['school' => $school->id])
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

    return view('lab.lab-tests', [
        'school' => $school,
        'labTests' => $labTests,
        'students' => $school->students()->latest()->get()
    ]);
})->name('lab-tests');

// Handle lab test form submissions from web forms (redirect back to lab-tests page)
Route::post('/lab-tests', function (Illuminate\Http\Request $request) {
    // Get school from form data instead of session
    $school = App\Models\School::findOrFail($request->input('school_id'));

    $validated = $request->validate([
        'student_id' => 'required|exists:students,id',
        'test_type' => 'required|string',
        'notes' => 'nullable|string'
    ]);

    $labTest = App\Models\LabTest::create(array_merge($validated, [
        'school_id' => $school->id,
        'status' => 'pending'
    ]));

    return redirect()->route('lab-tests', ['school' => $school->id])->with('success', 'Lab test requested successfully.');
})->name('lab-tests.store');

// Delete a lab test (web)
Route::delete('/lab-tests/{school}/{labTest}', function (App\Models\School $school, App\Models\LabTest $labTest) {
    // Verify the lab test belongs to the authenticated school
    if ($labTest->school_id !== $school->id) {
        abort(403);
    }

    $labTest->delete();
    return redirect()->route('lab-tests', ['school' => $school->id])->with('success', 'Lab test deleted');
})->name('lab-tests.destroy');

// Mark a lab test as completed (web)
Route::post('/lab-tests/{school}/{labTest}/complete', function (App\Models\School $school, App\Models\LabTest $labTest) {
    // Verify the lab test belongs to the authenticated school
    if ($labTest->school_id !== $school->id) {
        abort(403);
    }

    $labTest->update(['status' => 'completed']);
    return redirect()->route('lab-tests', ['school' => $school->id])->with('success', 'Lab test marked completed');
})->name('lab-tests.complete');


Route::get('/book-doctor/{school}', function (App\Models\School $school) {
    return view('booking.book-doctor', [
        'school' => $school,
        'appointments' => $school->appointments()->with(['patient', 'doctor', 'duration'])->latest()->get(),
        'patients' => $school->students()->latest()->get(),
        'doctors' => Doctor::latest()->get()
    ]);
})->name('book-doctor');

Route::get('/transactions/{school}', function (App\Models\School $school) {
    // Get transactions/payments related to this school
    // For now, we'll show appointments with payment status
    $appointments = $school->appointments()->with(['patient', 'doctor', 'duration'])->latest()->paginate(15);
    return view('school.school-transactions', compact('school', 'appointments'));
})->name('school.transactions');


// Appointment actions
Route::post('/appointments/validate', [\App\Http\Controllers\AppointmentController::class, 'validateAppointment'])->name('appointments.validate');
Route::patch('/appointments/{appointment}/cancel', [\App\Http\Controllers\AppointmentController::class, 'cancel'])->name('appointments.cancel');
Route::patch('/appointments/{appointment}/complete', [\App\Http\Controllers\AppointmentController::class, 'complete'])->name('appointments.complete');
Route::patch('/appointments/{appointment}/approve', [\App\Http\Controllers\AppointmentController::class, 'approve'])->name('appointments.approve');
Route::delete('/appointments/{appointment}', [\App\Http\Controllers\AppointmentController::class, 'destroy'])->name('appointments.destroy');

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
// Ensure the 'web' middleware is applied so session/cookie middleware run
// (EncryptCookies, StartSession, ShareErrorsFromSession, VerifyCsrfToken)
Route::prefix('admin')->middleware(['web', 'admin'])->group(function(){
    Route::get('/', [AdminController::class, 'index'])->name('admin.index');

    // Individual model routes
    Route::get('/doctors', [AdminModelController::class, 'index'])->name('admin.doctors.index');
    Route::get('/doctors/create', [AdminModelController::class, 'create'])->name('admin.doctors.create');
    Route::post('/doctors', [AdminModelController::class, 'store'])->name('admin.doctors.store');
    Route::get('/doctors/{id}/edit', [AdminModelController::class, 'edit'])->name('admin.doctors.edit');
    Route::put('/doctors/{id}', [AdminModelController::class, 'update'])->name('admin.doctors.update');
    Route::delete('/doctors/{id}', [AdminModelController::class, 'destroy'])->name('admin.doctors.destroy');

    Route::post('/users/send-invite', [AdminModelController::class, 'sendAdminInvite'])->name('admin.users.send-invite');

    Route::get('/users', [AdminModelController::class, 'index'])->name('admin.users.index');
    Route::get('/users/create', [AdminModelController::class, 'create'])->name('admin.users.create');
    Route::post('/users', [AdminModelController::class, 'store'])->name('admin.users.store');
    Route::get('/users/{id}/edit', [AdminModelController::class, 'edit'])->name('admin.users.edit');
    Route::put('/users/{id}', [AdminModelController::class, 'update'])->name('admin.users.update');
    Route::delete('/users/{id}', [AdminModelController::class, 'destroy'])->name('admin.users.destroy');

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

    Route::get('/transactions', [AdminModelController::class, 'index'])->name('admin.transactions.index');
    Route::get('/transactions/create', [AdminModelController::class, 'create'])->name('admin.transactions.create');
    Route::post('/transactions', [AdminModelController::class, 'store'])->name('admin.transactions.store');
    Route::get('/transactions/{id}/edit', [AdminModelController::class, 'edit'])->name('admin.transactions.edit');
    Route::put('/transactions/{id}', [AdminModelController::class, 'update'])->name('admin.transactions.update');
    Route::delete('/transactions/{id}', [AdminModelController::class, 'destroy'])->name('admin.transactions.destroy');

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

    Route::get('/doctor-availabilities', [AdminModelController::class, 'index'])->name('admin.doctor-availabilities.index');

    Route::get('/durations', [AdminModelController::class, 'index'])->name('admin.durations.index');
    Route::get('/durations/create', [AdminModelController::class, 'create'])->name('admin.durations.create');
    Route::post('/durations', [AdminModelController::class, 'store'])->name('admin.durations.store');
    Route::get('/durations/delete/{id}', [AdminModelController::class, 'destroy'])->name('admin.durations.delete');
    Route::get('/durations/{id}/edit', [AdminModelController::class, 'edit'])->name('admin.durations.edit');
    Route::get('/durations/{id}', [AdminModelController::class, 'show'])->name('admin.durations.show');
    Route::post('/durations/seed', [AdminModelController::class, 'seedDurations'])->name('admin.durations.seed');
    Route::put('/durations/{id}', [AdminModelController::class, 'update'])->name('admin.durations.update');
    Route::delete('/durations/{id}', [AdminModelController::class, 'destroy'])->name('admin.durations.destroy');

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
// Web route to update a doctor's meeting link from the web form (keeps session & CSRF)
Route::post('/doctor/update-meeting-link', [DoctorController::class, 'updateMeetingLink'])
    ->name('doctor.update-meeting-link');

// Web route to update doctor availability (form submissions)
Route::post('/doctor/availability', [\App\Http\Controllers\DoctorAvailabilityController::class, 'update'])
    ->name('doctor.update-availability');

Route::get('/doctor-availabilities', [DoctorController::class, 'allAvailabilities'])->middleware('admin')->name('doctor.all-availabilities');


// Doctor Dashboard Route
Route::get('/doctors-dashboard', [DoctorController::class, 'dashboard']);


Route::middleware('session.auth:doctor')->group(function () {
    Route::get('/doctor/dashboard', [DoctorController::class, 'authDashboard'])->name('doctor.dashboard');
    Route::get('/doctor/availability', [DoctorController::class, 'availability'])->name('doctor.availability');
    Route::get('/doctor/appointments', [DoctorController::class, 'getDoctorAppointments'])->name('doctor.appointments');
    Route::get('/doctor/meeting-link', [DoctorController::class, 'meetingLink'])->name('doctor.meeting-link');
    Route::get('/doctor/profile', [App\Http\Controllers\ProfileController::class, 'show'])->name('doctor.profile');
    Route::get('/doctor/edit-profile', [DoctorController::class, 'editProfile'])->name('doctor.edit-profile');
    Route::put('/doctor/update-profile', [DoctorController::class, 'updateProfile'])->name('doctor.update-profile');
    Route::post('/doctor/send-link', [DoctorController::class, 'sendLink'])->name('doctor.send-link');
});

// One-time login link consume route (public)
Route::get('/auth/login/{token}', [\App\Http\Controllers\OneTimeLoginController::class, 'consume'])->name('auth.login.token');

// Centralized profile route (optional doctor id to preserve doctor context)
Route::get('/doctors/{doctor}', [App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');

// Current user profile route
Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'show'])->name('user.profile');

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
    return view('health-facility.health_facilities', [
        'healthFacilities' => $healthFacilities ?? [],
        'error' => $error ?? null
    ]);
});


Route::middleware('session.auth:health_facility')->group(function () {
    Route::get('/health-facility/dashboard', function (Request $request) {
        $authenticatedUser = $request->current_user;
        return app(HealthFacilityController::class)->showDashboard($request, $authenticatedUser['id']);
    })->name('health-facility.dashboard');

    // Health Facility section routes
    Route::get('/health-facility/patients', function (Request $request) {
        $authenticatedUser = $request->current_user;
        return app(HealthFacilityController::class)->patients($request, $authenticatedUser['id']);
    })->name('health-facility.patients');

    Route::match(['post', 'delete'], '/health-facility/patients', function (Request $request) {
        $authenticatedUser = $request->current_user;
        return app(HealthFacilityController::class)->destroyAllPatients($request, $authenticatedUser['id']);
    })->name('health-facility.patients.destroy-all');

    Route::get('/health-facility/patients/create', function (Request $request) {
        $authenticatedUser = $request->current_user;
        return app(HealthFacilityController::class)->createPatient($request, $authenticatedUser['id']);
    })->name('health-facility.patients.create');

    Route::delete('/health-facility/patients/{patientId}', function (Request $request, $patientId) {
        $authenticatedUser = $request->current_user;
        return app(HealthFacilityController::class)->destroyPatient($request, $authenticatedUser['id'], $patientId);
    })->name('health-facility.patients.destroy');

    Route::get('/health-facility/book-doctor', function (Request $request) {
        $authenticatedUser = $request->current_user;
        return app(HealthFacilityController::class)->bookDoctor($request, $authenticatedUser['id']);
    })->name('health-facility.book-doctor');

    Route::get('/health-facility/lab-tests', function (Request $request) {
        $authenticatedUser = $request->current_user;
        return app(HealthFacilityController::class)->labTests($request, $authenticatedUser['id']);
    })->name('health-facility.lab-tests');

    Route::get('/health-facility/transactions', function (Request $request) {
        $authenticatedUser = $request->current_user;
        return app(HealthFacilityController::class)->transactions($request, $authenticatedUser['id']);
    })->name('health-facility.transactions');

    Route::get('/health-facility/staff', function (Request $request) {
        $authenticatedUser = $request->current_user;
        return app(HealthFacilityController::class)->staff($request, $authenticatedUser['id']);
    })->name('health-facility.staff');
});

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
Route::get('/patients/{patient}/medical-history/{medicalHistory}', [PatientController::class, 'showMedicalHistory'])->name('patients.medical-history.show');
Route::post('/patients/{patient}/medical-history', [PatientController::class, 'storeMedicalHistory'])->name('patients.medical-history.store');
Route::put('/patients/{patient}/medical-history/{medicalHistory}', [PatientController::class, 'updateMedicalHistory'])->name('patients.medical-history.update');
    
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

            // Update patient with health facility association
            $patient->update([
                'health_facility_id' => $validated['health_facility_id'],
            ]);

            return redirect()->route('health-facility.patients')
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

            return redirect()->route('health-facility.patients')
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
Route::get('/appointment/payment-status/{appointment}', [PaymentController::class, 'checkAppointmentPaymentStatus'])->name('payment.appointment.status');
Route::get('/appointment/cancel/{appointment}', [PaymentController::class, 'appointmentCancel'])->name('payment.appointment.cancel');

// Dummy payment confirmation route for testing
Route::post('/payment/appointment/confirm-dummy/{appointment}', [PaymentController::class, 'confirmDummyPayment'])->name('payment.appointment.confirm-dummy');

// MarzPay API Routes (public webhook endpoint)
Route::post('/marzpay/webhook', [PaymentController::class, 'handleCallback'])->name('marzpay.webhook');

// MarzPay Payment Routes (authenticated)
Route::prefix('api/payments')->middleware('auth')->group(function () {
    Route::post('/collect', [PaymentController::class, 'requestPayment'])->name('api.payments.collect');
    Route::post('/send', [PaymentController::class, 'sendPayment'])->name('api.payments.send');
    Route::get('/status/{referenceId}', [PaymentController::class, 'paymentStatus'])->name('api.payments.status');
    Route::get('/balance', [PaymentController::class, 'accountBalance'])->name('api.payments.balance');
});

// MarzPay Test Routes (no auth for testing)
Route::prefix('test/payments')->group(function () {
    Route::post('/collect', [PaymentController::class, 'requestPayment'])->name('test.payments.collect');
    Route::post('/send', [PaymentController::class, 'sendPayment'])->name('test.payments.send');
    Route::get('/status/{referenceId}', [PaymentController::class, 'paymentStatus'])->name('test.payments.status');
    Route::get('/balance', [PaymentController::class, 'accountBalance'])->name('test.payments.balance');
});

// Patient pages reached by signed links sent over SMS (no password needed)
Route::get('/visit/{appointment}', [PatientVisitController::class, 'show'])->middleware('signed')->name('visit.show');
Route::post('/visit/{appointment}/join', [PatientVisitController::class, 'join'])->middleware('signed')->name('visit.join');
Route::get('/my-visits/{patient}', [PatientVisitController::class, 'visits'])->middleware('signed')->name('patient.visits');

// Insurance handling for clinic and doctor staff
Route::middleware('session.auth')->prefix('insurance')->group(function () {
    Route::get('/insurers', [InsuranceController::class, 'insurers'])->name('insurance.insurers');
    Route::post('/patients/{patient}/policies', [InsuranceController::class, 'addPolicy'])->name('insurance.policies.store');
    Route::post('/appointments/{appointment}/verify', [InsuranceController::class, 'verify'])->name('insurance.verify');
    Route::get('/visit-records.csv', [InsuranceController::class, 'exportCsv'])->name('insurance.export');
    Route::post('/visit-records/submitted', [InsuranceController::class, 'markSubmitted'])->name('insurance.submitted');
});

// Prescriptions and medicine delivery
Route::post('/my-visits/{patient}/prescriptions', [PatientPrescriptionController::class, 'upload'])->middleware('signed')->name('patient.prescriptions.upload');
Route::post('/my-visits/{patient}/prescriptions/{prescription}/delivery', [PatientPrescriptionController::class, 'requestDelivery'])->middleware('signed')->name('patient.prescriptions.delivery');
Route::middleware('session.auth')->prefix('care')->group(function () {
    Route::post('/appointments/{appointment}/prescriptions', [PrescriptionController::class, 'issue'])->name('care.prescriptions.issue');
    Route::get('/prescriptions', [PrescriptionController::class, 'queue'])->name('care.prescriptions.queue');
    Route::post('/prescriptions/{prescription}/review', [PrescriptionController::class, 'review'])->name('care.prescriptions.review');
    Route::post('/prescriptions/{prescription}/delivery', [PrescriptionController::class, 'updateDelivery'])->name('care.prescriptions.delivery');
    Route::get('/prescriptions/{prescription}/image', [PrescriptionController::class, 'image'])->name('care.prescriptions.image');
});

// Patient login by phone and one-time code, then their own records
Route::get('/patient/login', [PatientAuthController::class, 'showLogin'])->name('patient.login');
Route::post('/patient/login/code', [PatientAuthController::class, 'requestCode'])->middleware('throttle:10,1')->name('patient.login.code');
Route::post('/patient/login/verify', [PatientAuthController::class, 'verify'])->middleware('throttle:10,1')->name('patient.login.verify');
Route::get('/patient/records', [PatientAuthController::class, 'records'])->name('patient.records');
Route::post('/patient/logout', [PatientAuthController::class, 'logout'])->name('patient.logout');

// Paying from the patient's visit link, and wallet / pay-link tools for staff
Route::post('/visit/{appointment}/pay/mobile-money', [VisitPaymentController::class, 'mobileMoney'])->middleware(['signed', 'throttle:10,1'])->name('visit.pay.momo');
Route::post('/visit/{appointment}/pay/wallet', [VisitPaymentController::class, 'wallet'])->middleware('signed')->name('visit.pay.wallet');
Route::middleware('session.auth')->prefix('care')->group(function () {
    Route::get('/patients/{patient}/wallet', [WalletController::class, 'show'])->name('care.wallet.show');
    Route::post('/patients/{patient}/wallet/credit', [WalletController::class, 'credit'])->name('care.wallet.credit');
    Route::post('/appointments/{appointment}/pay-link', [WalletController::class, 'sendPayLink'])->name('care.paylink');
});

// Employer accounts (admin only)
// (not under /admin/ because /admin/{modelKey} is a catch-all registered earlier)
Route::middleware(['auth', 'admin'])->prefix('manage/employers')->group(function () {
    Route::post('/', [EmployerController::class, 'store'])->name('admin.employers.store');
    Route::post('/{employer}/members', [EmployerController::class, 'addMember'])->name('admin.employers.members');
    Route::get('/{employer}/invoice.csv', [EmployerController::class, 'invoice'])->name('admin.employers.invoice');
});

// Adoption numbers for the pilot
Route::get('/metrics/adoption', [AdoptionMetricsController::class, 'mine'])->middleware('session.auth')->name('metrics.adoption');
Route::get('/manage/metrics/adoption', [AdoptionMetricsController::class, 'all'])->middleware(['auth', 'admin'])->name('metrics.adoption.admin');

// Channels: USSD for feature phones, WhatsApp inbound
Route::post('/ussd', [UssdController::class, 'handle'])->middleware('throttle:60,1')->name('ussd');
Route::get('/whatsapp/webhook', [WhatsAppWebhookController::class, 'verify'])->name('whatsapp.verify');
Route::post('/whatsapp/webhook', [WhatsAppWebhookController::class, 'receive'])->middleware('throttle:120,1')->name('whatsapp.receive');

// Medicine pricing and insurance (pharmacy desk), patient payment for medicine, and patient-sent insurance details
Route::middleware('session.auth')->prefix('care')->group(function () {
    Route::post('/prescriptions/{prescription}/price', [PharmacyController::class, 'price'])->name('care.pharmacy.price');
    Route::post('/prescriptions/{prescription}/insurer-decision', [PharmacyController::class, 'insurerDecision'])->name('care.pharmacy.insurer');
    Route::get('/pharmacy/claims.csv', [PharmacyController::class, 'claimsCsv'])->name('care.pharmacy.claims');
    Route::post('/pharmacy/claims/submitted', [PharmacyController::class, 'claimsSubmitted'])->name('care.pharmacy.submitted');
});
Route::middleware('session.auth')->prefix('insurance')->group(function () {
    Route::get('/policies/pending', [InsuranceController::class, 'pendingPolicies'])->name('insurance.policies.pending');
    Route::post('/policies/{policy}/review', [InsuranceController::class, 'reviewPolicy'])->name('insurance.policies.review');
    Route::get('/policies/{policy}/card', [InsuranceController::class, 'policyCard'])->name('insurance.policies.card');
});
Route::post('/my-visits/{patient}/prescriptions/{prescription}/pay/mobile-money', [PatientPrescriptionController::class, 'payMobileMoney'])->middleware(['signed', 'throttle:10,1'])->name('patient.prescriptions.pay.momo');
Route::post('/my-visits/{patient}/prescriptions/{prescription}/pay/wallet', [PatientPrescriptionController::class, 'payWallet'])->middleware('signed')->name('patient.prescriptions.pay.wallet');
Route::post('/my-visits/{patient}/insurance', [PatientInsuranceController::class, 'store'])->middleware(['signed', 'throttle:10,1'])->name('patient.policies.store');
Route::post('/patient/insurance/{patient}', [PatientInsuranceController::class, 'portalStore'])->middleware('throttle:10,1')->name('patient.portal.policies.store');

// Follow claims after they go to the insurer
Route::middleware('session.auth')->prefix('insurance')->group(function () {
    Route::get('/claims', [ClaimsController::class, 'index'])->name('insurance.claims');
    Route::post('/appointments/{appointment}/claim-response', [ClaimsController::class, 'visitResponse'])->name('insurance.claims.visit');
    Route::post('/prescriptions/{prescription}/claim-response', [ClaimsController::class, 'medicineResponse'])->name('insurance.claims.medicine');
});
