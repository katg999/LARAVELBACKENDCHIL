@extends('layouts.base')

@section('content')
<div class="container-fluid">
    <!-- Patient Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center">
                            <div class="patient-avatar">
                                <i class="mdi mdi-account icon-xl text-primary"></i>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h2 class="mb-1">{{ $patient->name }}</h2>
                            <p class="text-muted mb-2">
                                <strong>Patient ID:</strong> {{ $patient->patient_id }}
                                <button class="btn btn-sm btn-outline-secondary ml-2 copy-btn" data-clipboard-text="{{ $patient->patient_id }}" title="Copy Patient ID">
                                    <i class="typcn typcn-copy"></i>
                                </button>
                            </p>
                            <div class="d-flex flex-wrap">
                                <span class="badge badge-primary" style="margin-right: 8px;">{{ ucfirst($patient->gender) }}</span>
                                @if($patient->birth_date)
                                <span class="badge badge-info" style="margin-right: 8px;">{{ $patient->birth_date->age }} years old</span>
                                @endif
                                @if($patient->school)
                                <span class="badge badge-success">Student</span>
                                @elseif($patient->healthFacility)
                                <span class="badge badge-warning">Health Facility Patient</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4 text-right">
                            <div class="btn-group" role="group">
                                <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
                                    <i class="typcn typcn-arrow-left"></i> Back
                                </a>
                                <button class="btn btn-outline-primary" onclick="window.print()">
                                    <i class="typcn typcn-printer"></i> Print Profile
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-2">
            <div class="card">
                <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
                    <div>
                        <div class="stat-label mb-2">Total Appointments</div>
                        <h5 class="mb-0">{{ $patient->appointments->count() }}</h5>
                    </div>
                    <i class="mdi mdi-calendar icon-xl text-primary"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-2">
            <div class="card">
                <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
                    <div>
                        <div class="stat-label mb-2">Lab Tests</div>
                        <h5 class="mb-0">{{ $patient->labTests->count() }}</h5>
                    </div>
                    <i class="mdi mdi-flask icon-xl text-success"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-2">
            <div class="card">
                <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
                    <div>
                        <div class="stat-label mb-2">Maternal Documents</div>
                        <h5 class="mb-0">{{ $patient->maternalDocuments->count() }}</h5>
                    </div>
                    <i class="mdi mdi-file-document icon-xl text-warning"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-2">
            <div class="card">
                <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
                    <div>
                        <div class="stat-label mb-2">Completed Appointments</div>
                        <h5 class="mb-0">{{ $patient->appointments->where('status', 'completed')->count() }}</h5>
                    </div>
                    <i class="mdi mdi-check-circle icon-xl text-info"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Patient Information -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="typcn typcn-user mr-2"></i>Patient Information
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-5">Full Name:</dt>
                                <dd class="col-sm-7">{{ $patient->name }}</dd>

                                <dt class="col-sm-5">Gender:</dt>
                                <dd class="col-sm-7">{{ ucfirst($patient->gender) }}</dd>

                                <dt class="col-sm-5">Date of Birth:</dt>
                                <dd class="col-sm-7">{{ $patient->birth_date ? $patient->birth_date->format('M d, Y') : 'Not provided' }}</dd>

                                <dt class="col-sm-5">Age:</dt>
                                <dd class="col-sm-7">{{ $patient->birth_date ? $patient->birth_date->age . ' years' : 'Not available' }}</dd>

                                <dt class="col-sm-5">Contact:</dt>
                                <dd class="col-sm-7">{{ $patient->contact_number ?: 'Not provided' }}</dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row">
                                @if($patient->parent_contact)
                                <dt class="col-sm-5">Parent Contact:</dt>
                                <dd class="col-sm-7">{{ $patient->parent_contact }}</dd>
                                @endif

                                @if($patient->grade)
                                <dt class="col-sm-5">Grade:</dt>
                                <dd class="col-sm-7">{{ $patient->grade }}</dd>
                                @endif

                                @if($patient->healthFacility)
                                <dt class="col-sm-5">Health Facility:</dt>
                                <dd class="col-sm-7">
                                    <span class="badge badge-primary">{{ $patient->healthFacility->name }}</span>
                                </dd>
                                @endif

                                @if($patient->school)
                                <dt class="col-sm-5">School:</dt>
                                <dd class="col-sm-7">
                                    <span class="badge badge-success">{{ $patient->school->name }}</span>
                                </dd>
                                @endif
                            </dl>
                        </div>
                    </div>

                    @if($patient->medicalHistories->count() > 0)
                    <div class="mt-4">
                        <h5 class="mb-3"><i class="typcn typcn-time mr-2"></i>Medical History</h5>
                        <div class="timeline">
                            @foreach($patient->medicalHistories->sortByDesc('recorded_date') as $history)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-info">
                                    <i class="typcn typcn-user"></i>
                                </div>
                                <div class="timeline-content">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong>{{ $history->doctor->name }}</strong>
                                            <small class="text-muted d-block">{{ $history->doctor->specialization ?? 'General' }}</small>
                                        </div>
                                        <small class="text-muted">
                                            {{ $history->recorded_date ? $history->recorded_date->format('M d, Y') : $history->created_at->format('M d, Y') }}
                                        </small>
                                    </div>
                                    <div class="mt-2">
                                        <p class="mb-2">{{ $history->content }}</p>
                                        <div class="d-flex justify-content-end">
                                            <button class="btn btn-sm btn-outline-primary btn-edit-medical-note"
                                                    data-id="{{ $history->id }}"
                                                    onclick="editMedicalNote({{ $history->id }})"
                                                    title="Edit Medical Note">
                                                <i class="typcn typcn-edit mr-1"></i>Edit
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @else
                    <div class="mt-4">
                        <h5 class="mb-3"><i class="typcn typcn-time mr-2"></i>Medical History</h5>
                        <div class="text-center py-4">
                            <i class="typcn typcn-document-text fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">No medical history records found for this patient.</p>
                            <small class="text-muted">Medical history will appear here when doctors add notes during appointments.</small>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Lab Tests Section -->
            @if($patient->labTests->count() > 0)
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="typcn typcn-beaker mr-2"></i>Lab Test History
                    </h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><i class="fa fa-vial mr-1"></i>Test Type</th>
                                <th><i class="fa fa-calendar-plus mr-1"></i>Requested Date</th>
                                <th><i class="fa fa-tasks mr-1"></i>Status</th>
                                <th><i class="fa fa-clipboard-check mr-1"></i>Results</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($patient->labTests->sortByDesc('created_at') as $labTest)
                            <tr>
                                <td><strong>{{ $labTest->test_type ?: 'N/A' }}</strong></td>
                                <td>{{ $labTest->created_at->format('M d, Y') }}</td>
                                <td>
                                    <span class="badge badge-{{ $labTest->status === 'completed' ? 'success' : 'warning' }}">
                                        <i class="fa fa-{{ $labTest->status === 'completed' ? 'check-circle' : 'clock' }} mr-1"></i>
                                        {{ ucfirst($labTest->status) }}
                                    </span>
                                </td>
                                <td>
                                    @if($labTest->results)
                                        <span class="text-success">{{ $labTest->results }}</span>
                                    @else
                                        <span class="text-muted">Pending results</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @else
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="typcn typcn-beaker mr-2"></i>Lab Test History
                    </h3>
                </div>
                <div class="card-body">
                    <div class="text-center py-4">
                        <i class="typcn typcn-beaker fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">No lab tests found for this patient.</p>
                        <small class="text-muted">Lab test results will appear here when tests are ordered and completed.</small>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="typcn typcn-flash mr-2"></i>Quick Actions
                    </h3>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary mb-2" data-toggle="modal" data-target="#scheduleAppointmentModal">
                            <i class="typcn typcn-calendar mr-2"></i>Schedule Appointment
                        </button>
                        <button class="btn btn-success mb-2" disabled>
                            <i class="typcn typcn-beaker mr-2"></i>Order Lab Test
                        </button>
                        <button class="btn btn-info mb-2" onclick="createMedicalNote()">
                            <i class="typcn typcn-document mr-2"></i>Add Medical Note
                        </button>
                        <button class="btn btn-warning mb-2" disabled>
                            <i class="typcn typcn-info mr-2"></i>Test Edit Modal
                        </button>
                        <button class="btn btn-warning mb-2" disabled>
                            <i class="typcn typcn-edit mr-2"></i>Update Profile
                        </button>
                    </div>
                </div>
            </div>

            <!-- Maternal Documents Section -->
            @if($patient->maternalDocuments->count() > 0)
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="typcn typcn-heart mr-2"></i>Maternal Health Documents
                    </h3>
                </div>
                <div class="card-body">
                    @foreach($patient->maternalDocuments->sortByDesc('created_at') as $document)
                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                        <div>
                            <strong>{{ ucfirst(str_replace('_', ' ', $document->document_type)) }}</strong><br>
                            <small class="text-muted">{{ $document->created_at->format('M d, Y') }}</small>
                        </div>
                        <div>
                            @if($document->file_path)
                            <a href="{{ asset('storage/' . $document->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="typcn typcn-eye"></i> View
                            </a>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Full Width Appointment History -->
    @if($patient->appointments->count() > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="typcn typcn-calendar mr-2"></i>Appointment History
                    </h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><i class="typcn typcn-calendar mr-1"></i>Date & Time</th>
                                <th><i class="typcn typcn-user mr-1"></i>Doctor</th>
                                <th><i class="typcn typcn-info mr-1"></i>Status</th>
                                <th><i class="typcn typcn-chat mr-1"></i>Notes</th>
                                <th><i class="typcn typcn-cog mr-1"></i>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($patient->appointments->sortByDesc('appointment_date') as $appointment)
                            <tr>
                                <td>
                                    @if($appointment->appointment_date)
                                        <div><strong>{{ $appointment->appointment_date->format('M d, Y') }}</strong></div>
                                        <div class="text-muted small">{{ $appointment->appointment_date->format('H:i') }}</div>
                                    @else
                                        <div><strong>{{ $appointment->created_at->format('M d, Y') }}</strong></div>
                                        <div class="text-muted small">{{ $appointment->created_at->format('H:i') }} (Created)</div>
                                    @endif
                                </td>
                                <td>
                                    @if($appointment->doctor)
                                        <strong>{{ $appointment->doctor->name }}</strong><br>
                                        <small class="text-muted">{{ $appointment->doctor->specialization ?? 'General' }}</small>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-{{ $appointment->status === 'completed' ? 'success' : ($appointment->status === 'cancelled' ? 'danger' : ($appointment->status === 'awaiting_payment' ? 'warning' : 'secondary')) }}">
                                        <i class="typcn typcn-{{ $appointment->status === 'completed' ? 'tick' : ($appointment->status === 'cancelled' ? 'times' : ($appointment->status === 'awaiting_payment' ? 'credit-card' : 'time')) }} mr-1"></i>
                                        {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
                                    </span>
                                </td>
                                <td>{{ $appointment->notes ?: 'No notes' }}</td>
                                <td>
                                    @if($appointment->status === 'awaiting_payment')
                                        <a href="{{ route('payment.appointment.pay', $appointment) }}" class="btn btn-sm btn-success">
                                            <i class="typcn typcn-credit-card mr-1"></i>Pay Now
                                        </a>
                                    @elseif($appointment->payment_status !== 'completed' && $appointment->status !== 'cancelled')
                                        <button class="btn btn-sm btn-danger btn-cancel-appointment" data-id="{{ $appointment->id }}" title="Cancel Appointment">
                                            <i class="typcn typcn-times mr-1"></i>Cancel
                                        </button>
                                    @elseif($appointment->status === 'cancelled')
                                        <button class="btn btn-sm btn-outline-danger btn-delete-appointment" data-id="{{ $appointment->id }}" title="Delete Appointment">
                                            <i class="typcn typcn-trash mr-1"></i>Delete
                                        </button>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Full Width Lab Tests Section -->
    @if($patient->labTests->count() > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="typcn typcn-beaker mr-2"></i>Lab Test History
                    </h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><i class="fa fa-vial mr-1"></i>Test Type</th>
                                <th><i class="fa fa-calendar-plus mr-1"></i>Requested Date</th>
                                <th><i class="fa fa-tasks mr-1"></i>Status</th>
                                <th><i class="fa fa-clipboard-check mr-1"></i>Results</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($patient->labTests->sortByDesc('created_at') as $labTest)
                            <tr>
                                <td><strong>{{ $labTest->test_type ?: 'N/A' }}</strong></td>
                                <td>{{ $labTest->created_at->format('M d, Y') }}</td>
                                <td>
                                    <span class="badge badge-{{ $labTest->status === 'completed' ? 'success' : 'warning' }}">
                                        <i class="fa fa-{{ $labTest->status === 'completed' ? 'check-circle' : 'clock' }} mr-1"></i>
                                        {{ ucfirst($labTest->status) }}
                                    </span>
                                </td>
                                <td>
                                    @if($labTest->results)
                                        <span class="text-success">{{ $labTest->results }}</span>
                                    @else
                                        <span class="text-muted">Pending results</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="typcn typcn-beaker mr-2"></i>Lab Test History
                    </h3>
                </div>
                <div class="card-body">
                    <div class="text-center py-4">
                        <i class="typcn typcn-beaker fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">No lab tests found for this patient.</p>
                        <small class="text-muted">Lab test results will appear here when tests are ordered and completed.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Schedule Appointment Modal -->
<div class="modal fade" id="scheduleAppointmentModal" tabindex="-1" role="dialog" aria-labelledby="scheduleAppointmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scheduleAppointmentModalLabel">
                    <i class="typcn typcn-calendar mr-2"></i>Schedule Appointment for {{ $patient->name }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="appointmentForm" method="POST" action="{{ route('appointments.store') }}">
                @csrf
                <div class="modal-body">
                    <!-- Hidden fields for patient and institution -->
                    <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                    @if($patient->school)
                        <input type="hidden" name="school_id" value="{{ $patient->school->id }}">
                    @elseif($patient->healthFacility)
                        <input type="hidden" name="health_facility_id" value="{{ $patient->healthFacility->id }}">
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="doctor_id" class="form-label">
                                    <i class="typcn typcn-user mr-1"></i>Doctor <span class="text-danger">*</span>
                                </label>
                                <select name="doctor_id" id="doctor_id" class="js-example-basic-single w-100 form-control" required>
                                    <option value="">Select Doctor</option>
                                    @php
                                        $doctors = \App\Models\Doctor::all();
                                    @endphp
                                    @foreach($doctors as $doctor)
                                        <option value="{{ $doctor->id }}">
                                            Dr. {{ $doctor->name }} - {{ $doctor->specialization ?? 'General' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="duration_id" class="form-label">
                                    <i class="typcn typcn-time mr-1"></i>Duration <span class="text-danger">*</span>
                                </label>
                                <select name="duration_id" id="duration_id" class="form-control" required>
                                    <option value="">Select Duration</option>
                                    @foreach(\App\Models\Duration::active()->get() as $duration)
                                        <option value="{{ $duration->id }}">{{ $duration->minutes }} minutes - {{ ucfirst($duration->duration_type) }}: UGX {{ number_format($duration->price, 0) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="appointment_date" class="form-label">
                                    <i class="typcn typcn-calendar mr-1"></i>Appointment Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="appointment_date" id="appointment_date" class="form-control" required
                                       min="{{ \Carbon\Carbon::tomorrow()->format('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="appointment_time" class="form-label">
                                    <i class="typcn typcn-time mr-1"></i>Appointment Time <span class="text-danger">*</span>
                                </label>
                                <input type="time" name="appointment_time" id="appointment_time" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reason" class="form-label">
                            <i class="typcn typcn-chat mr-1"></i>Reason for Visit <span class="text-danger">*</span>
                        </label>
                        <textarea name="reason" id="reason" class="form-control" rows="3" required
                                  placeholder="Please describe the reason for this appointment..."></textarea>
                    </div>

                    <!-- Patient Info Summary -->
                    <div class="alert alert-info text-dark">
                        <h6><i class="typcn typcn-info mr-1"></i>Appointment Details</h6>
                        <p class="mb-1"><strong>Patient:</strong> {{ $patient->name }} (ID: {{ $patient->patient_id }})</p>
                        @if($patient->school)
                            <p class="mb-1"><strong>Institution:</strong> {{ $patient->school->name }} (School)</p>
                        @elseif($patient->healthFacility)
                            <p class="mb-1"><strong>Institution:</strong> {{ $patient->healthFacility->name }} (Health Facility)</p>
                        @endif
                        <p class="mb-0"><strong>Age:</strong> {{ $patient->birth_date ? $patient->birth_date->age . ' years' : 'Not specified' }}</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="typcn typcn-times mr-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="typcn typcn-calendar mr-1"></i>Schedule Appointment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Medical Note Modal -->
<div class="modal fade" id="medicalNoteModal" tabindex="-1" role="dialog" aria-labelledby="addMedicalNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addMedicalNoteModalLabel">
                    <i class="typcn typcn-document mr-2"></i><span id="modal-title-text">Add Medical Note</span> for {{ $patient->name }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="medical_note_form" method="POST">
                @csrf
                <input type="hidden" id="medical-note-id" name="medical_history_id" value="">
                <input type="hidden" id="medical-note-method" name="_method" value="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="medical_note_content" class="form-label">
                            <i class="typcn typcn-edit mr-1"></i>Medical Note <span class="text-danger">*</span>
                        </label>
                        <textarea name="content" id="medical_note_content" class="form-control" rows="5" required
                                  placeholder="Enter detailed medical notes, observations, diagnosis, treatment plan, or any other relevant medical information..."></textarea>
                        <small class="form-text text-muted">Maximum 1000 characters</small>
                    </div>

                    <div class="form-group" style="display: none;">
                        <input type="hidden" name="recorded_date" id="medical_note_date" value="{{ date('Y-m-d') }}">
                    </div>

                    <!-- Patient Info Summary -->
                    <div class="alert alert-info text-dark">
                        <h6><i class="typcn typcn-info mr-1"></i>Note Details</h6>
                        <p class="mb-1"><strong>Patient:</strong> {{ $patient->name }} (ID: {{ $patient->patient_id }})</p>
                        <p class="mb-1"><strong>Doctor:</strong> {{ auth()->user()->name ?? 'Unknown' }}</p>
                        <p class="mb-0"><strong>Date:</strong> <span id="note-date-display">{{ date('M d, Y') }}</span></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="typcn typcn-times mr-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="medical-note-submit-btn">
                        <i class="typcn typcn-plus mr-1"></i><span id="submit-btn-text">Save Medical Note</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function createMedicalNote() {
    // Reset form for new note
    $('#medical_note_form')[0].reset();
    $('#medical-note-id').val('');
    $('#medical-note-method').val('POST');
    $('#modal-title-text').text('Add Medical Note');
    $('#submit-btn-text').text('Save Medical Note');
    $('#medicalNoteModal').modal('show');
}

function editMedicalNote(id) {
    // Load existing note data for editing
    $.ajax({
        url: '{{ route("patients.medical-history.show", [$patient->id, ":id"]) }}'.replace(':id', id),
        type: 'GET',
        success: function(data) {
            $('#medical_note_content').val(data.content);
            $('#medical-note-id').val(data.id);
            $('#medical-note-method').val('PUT');
            $('#modal-title-text').text('Edit Medical Note');
            $('#submit-btn-text').text('Update Medical Note');
            $('#medicalNoteModal').modal('show');
        },
        error: function() {
            alert('Error loading medical note data.');
        }
    });
}

$(document).ready(function() {
    // Handle medical note form submission
    $('#medical_note_form').on('submit', function(e) {
        e.preventDefault();

        var formData = new FormData(this);
        var method = $('#medical-note-method').val();
        var url = method === 'PUT'
            ? '{{ route("patients.medical-history.update", [$patient->id, ":id"]) }}'.replace(':id', $('#medical-note-id').val())
            : '{{ route("patients.medical-history.store", $patient->id) }}';

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-HTTP-Method-Override': method
            },
            success: function(response) {
                $('#medicalNoteModal').modal('hide');
                location.reload(); // Reload page to show updated data
            },
            error: function(xhr) {
                alert('Error saving medical note: ' + xhr.responseJSON?.message || 'Unknown error');
            }
        });
    });
});
</script>
@endpush