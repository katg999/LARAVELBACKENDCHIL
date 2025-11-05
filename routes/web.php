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
use Illuminate\Support\Facades\Auth;



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
    if (Auth::check()) {
        return redirect('/admin');
    }
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

Route::middleware(['auth', 'role:school-admin,school-staff,admin'])->group(function () {
    Route::get('/school-dashboard', [SchoolController::class, 'showDashboard'])
    ->name('school.dashboard');

    Route::get('/students', function (Request $request) {
        $user = Auth::user();
        $school = \App\Models\School::findOrFail($user->school_id);

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
        $user = Auth::user();
        $school = \App\Models\School::findOrFail($user->school_id);
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
        $user = Auth::user();
        $school = \App\Models\School::findOrFail($user->school_id);

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
        $user = Auth::user();
        $school = \App\Models\School::findOrFail($user->school_id);

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
        $user = Auth::user();
        $school = \App\Models\School::findOrFail($user->school_id);

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
        $user = Auth::user();
        $school = \App\Models\School::findOrFail($user->school_id);

        // Verify the lab test belongs to the authenticated school
        if ($labTest->school_id !== $school->id) {
            abort(403);
        }

        $labTest->delete();
        return redirect()->route('lab-tests')->with('success', 'Lab test deleted');
    })->name('lab-tests.destroy');

    // Mark a lab test as completed (web)
    Route::post('/lab-tests/{labTest}/complete', function (Request $request, App\Models\LabTest $labTest) {
        $user = Auth::user();
        $school = \App\Models\School::findOrFail($user->school_id);

        // Verify the lab test belongs to the authenticated school
        if ($labTest->school_id !== $school->id) {
            abort(403);
        }

        $labTest->update(['status' => 'completed']);
        return redirect()->route('lab-tests')->with('success', 'Lab test marked completed');
    })->name('lab-tests.complete');

    Route::get('/book-doctor', function (Request $request) {
        $user = Auth::user();
        $school = \App\Models\School::findOrFail($user->school_id);

        return view('booking.book-doctor', [
            'school' => $school,
            'appointments' => $school->appointments()->with(['patient', 'doctor', 'duration'])->latest()->get(),
            'patients' => $school->students()->latest()->get(),
            'doctors' => Doctor::latest()->get()
        ]);
    })->name('book-doctor');

    Route::get('/transactions', function (Request $request) {
        $user = Auth::user();
        $school = \App\Models\School::findOrFail($user->school_id);

        // Get transactions/payments related to this school
        // For now, we'll show appointments with payment status
        $appointments = $school->appointments()->with(['patient', 'doctor', 'duration'])->latest()->paginate(15);
        return view('school.school-transactions', compact('school', 'appointments'));
    })->name('school.transactions');

    Route::get('/staff', [App\Http\Controllers\SchoolInvitationController::class, 'index'])->name('school.staff.index');
    Route::get('/staff/invitations', [App\Http\Controllers\SchoolInvitationController::class, 'invitations'])->name('school.staff.invitations');
    Route::post('/staff/invite', [App\Http\Controllers\SchoolInvitationController::class, 'sendInvitation'])->name('school.staff.invite');
    Route::get('/staff/remove/{user}', [App\Http\Controllers\SchoolInvitationController::class, 'removeStaffMember'])->name('school.staff.remove');
    Route::delete('/staff/invitations/{invitation}/revoke', [App\Http\Controllers\SchoolInvitationController::class, 'revokeInvitation'])->name('school.staff.invitation.revoke');
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
})->name('school.students');


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
})->name('school.students.delete');

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
})->name('school.lab-tests');

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
})->name('school.lab-tests.destroy');

// Mark a lab test as completed (web)
Route::post('/lab-tests/{school}/{labTest}/complete', function (App\Models\School $school, App\Models\LabTest $labTest) {
    // Verify the lab test belongs to the authenticated school
    if ($labTest->school_id !== $school->id) {
        abort(403);
    }

    $labTest->update(['status' => 'completed']);
    return redirect()->route('lab-tests', ['school' => $school->id])->with('success', 'Lab test marked completed');
})->name('school.lab-tests.complete');


Route::get('/book-doctor/{school}', function (App\Models\School $school) {
    return view('booking.book-doctor', [
        'school' => $school,
        'appointments' => $school->appointments()->with(['patient', 'doctor', 'duration'])->latest()->get(),
        'patients' => $school->students()->latest()->get(),
        'doctors' => Doctor::latest()->get()
    ]);
})->name('school.book-doctor');

Route::get('/transactions/{school}', function (App\Models\School $school) {
    // Get transactions/payments related to this school
    // For now, we'll show appointments with payment status
    $appointments = $school->appointments()->with(['patient', 'doctor', 'duration'])->latest()->paginate(15);
    return view('school.school-transactions', compact('school', 'appointments'));
})->name('school.transactions.view');


// Appointment actions
Route::post('/appointments/validate', [\App\Http\Controllers\AppointmentController::class, 'validateAppointment'])->name('appointments.validate');
Route::patch('/appointments/{appointment}/cancel', [\App\Http\Controllers\AppointmentController::class, 'cancel'])->name('appointments.cancel');
Route::patch('/appointments/{appointment}/complete', [\App\Http\Controllers\AppointmentController::class, 'complete'])->name('appointments.complete');
Route::delete('/appointments/{appointment}', [\App\Http\Controllers\AppointmentController::class, 'destroy'])->name('appointments.destroy');

// Authentication routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');

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
Route::prefix('admin')->middleware(['web', 'auth', 'role:admin'])->group(function(){
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

    // Invitation Management Routes
    Route::get('/invitations', [AdminController::class, 'invitations'])->name('admin.invitations.index');
    Route::post('/invitations/{type}/{id}/resend', [AdminController::class, 'resendInvitation'])->name('admin.invitations.resend');
    Route::delete('/invitations/{type}/{id}', [AdminController::class, 'revokeInvitation'])->name('admin.invitations.revoke');

    // School Invitation Routes for Admin
    Route::get('/schools/{school}/invitations', [App\Http\Controllers\SchoolInvitationController::class, 'invitations'])->name('admin.schools.invitations');
    Route::post('/schools/{school}/invitations/send', [App\Http\Controllers\SchoolInvitationController::class, 'sendInvitation'])->name('admin.schools.invitations.send');
    Route::delete('/schools/{school}/invitations/{invitation}/revoke', [App\Http\Controllers\SchoolInvitationController::class, 'revokeInvitation'])->name('admin.schools.invitations.revoke');

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


Route::middleware(['auth', 'role:health-facility-staff,health-facility-admin,health-facility-medical-personnel'])->group(function () {
    Route::get('/health-facility/dashboard', function (Request $request) {
        $user = Auth::user();
        return app(HealthFacilityController::class)->showDashboard($request, $user->health_facility_id);
    })->name('health-facility.dashboard');

    // Health Facility section routes
    Route::get('/health-facility/patients', function (Request $request) {
        $user = Auth::user();
        return app(HealthFacilityController::class)->patients($request, $user->health_facility_id);
    })->name('health-facility.patients');

    Route::match(['post', 'delete'], '/health-facility/patients', function (Request $request) {
        $user = Auth::user();
        return app(HealthFacilityController::class)->destroyAllPatients($request, $user->health_facility_id);
    })->name('health-facility.patients.destroy-all');

    Route::get('/health-facility/patients/create', function (Request $request) {
        $user = Auth::user();
        return app(HealthFacilityController::class)->createPatient($request, $user->health_facility_id);
    })->name('health-facility.patients.create');

    Route::delete('/health-facility/patients/{patientId}', function (Request $request, $patientId) {
        $user = Auth::user();
        return app(HealthFacilityController::class)->destroyPatient($request, $user->health_facility_id, $patientId);
    })->name('health-facility.patients.destroy');

    Route::get('/health-facility/book-doctor', function (Request $request) {
        $user = Auth::user();
        return app(HealthFacilityController::class)->bookDoctor($request, $user->health_facility_id);
    })->name('health-facility.book-doctor');

    Route::get('/health-facility/lab-tests', function (Request $request) {
        $user = Auth::user();
        return app(HealthFacilityController::class)->labTests($request, $user->health_facility_id);
    })->name('health-facility.lab-tests');

    Route::get('/health-facility/transactions', function (Request $request) {
        $user = Auth::user();
        return app(HealthFacilityController::class)->transactions($request, $user->health_facility_id);
    })->name('health-facility.transactions');

    Route::get('/health-facility/staff', function (Request $request) {
        $user = Auth::user();
        return app(HealthFacilityController::class)->staff($request, $user->health_facility_id);
    })->name('health-facility.staff');
});

// Initial Admin Setup Routes (Public - accessed after VoiceFlow verification)
Route::get('/initial-admin-setup/health-facility/{facilityId}', [\App\Http\Controllers\InitialAdminSetupController::class, 'showHealthFacilityForm'])->name('initial-admin-setup.health-facility');
Route::post('/initial-admin-setup/health-facility/{facilityId}', [\App\Http\Controllers\InitialAdminSetupController::class, 'createHealthFacilityAdmin'])->name('initial-admin-setup.health-facility.store');
Route::get('/initial-admin-setup/school/{schoolId}', [\App\Http\Controllers\InitialAdminSetupController::class, 'showSchoolForm'])->name('initial-admin-setup.school');
Route::post('/initial-admin-setup/school/{schoolId}', [\App\Http\Controllers\InitialAdminSetupController::class, 'createSchoolAdmin'])->name('initial-admin-setup.school.store');

// School Invitation Routes (Public) - Redirect to Auth
Route::get('/school/invitation/accept/{token}', function ($token) {
    return redirect()->route('login', [
        'invitation_token' => $token,
        'invitation_type' => 'school'
    ]);
})->name('school.invitation.accept');

// Health Facility Invitation Routes (Public) - Redirect to Auth
Route::get('/health-facility/invitation/accept/{token}', function ($token) {
    return redirect()->route('login', [
        'invitation_token' => $token,
        'invitation_type' => 'health-facility'
    ]);
})->name('health-facility.invitation.accept');

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