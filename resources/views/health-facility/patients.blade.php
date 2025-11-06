@extends('layouts.base')

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h3 class="m-0">Patients</h3>
        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addPatientModal">
            <i class="mdi mdi-account-plus me-2"></i> Add Patient
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-light text-dark">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($patients->count())
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped text-dark">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Gender</th>
                            <th>Age</th>
                            <th>Contact</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($patients as $patient)
                        <tr>
                            <td>{{ $patient->id }}</td>
                            <td>{{ $patient->name }}</td>
                            <td>{{ ucfirst($patient->gender) }}</td>
                            <td>{{ \Carbon\Carbon::parse($patient->birth_date)->age }}</td>
                            <td>{{ $patient->contact_number ?? 'N/A' }}</td>
                            <td class="text-end">
                                <a href="{{ route('patients.profile', ['patient' => $patient->id]) }}" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="mdi mdi-account"></i> Profile
                                </a>
                                <form method="POST" action="{{ route('health-facility.patients.destroy', ['id' => $healthFacility->id, 'patientId' => $patient->id]) }}" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete {{ $patient->name }}?')">
                                        <i class="mdi mdi-delete"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    Showing {{ $patients->firstItem() ?? 0 }} to {{ $patients->lastItem() ?? 0 }} of {{ $patients->total() }} patients
                </div>
                <div>
                    {{ $patients->links() }}
                </div>
            </div>
        </div>
    </div>
    @else
        <div class="alert alert-info">No patients yet.</div>
    @endif
</div>
{{-- Add Patient Modal --}}
<div class="modal fade" id="addPatientModal" tabindex="-1" role="dialog" aria-labelledby="addPatientModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPatientModalLabel">Add Patient</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('patients.create') }}" id="patientForm">
                @csrf
                <input type="hidden" name="health_facility_id" value="{{ $healthFacility->id }}">
                <div class="modal-body">
                    <!-- Patient Type Selection -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Patient Type</label>
                        <div class="d-flex gap-3 align-items-start">
                            <div class="form-check flex-fill">
                                <input class="form-check-input" type="radio" name="patient_type" id="newPatient" value="new" checked>
                                <label class="form-check-label" for="newPatient" style="word-wrap: break-word; hyphens: auto;">
                                    <strong>New Patient</strong><br>
                                    <small class="text-muted">Create a new patient record</small>
                                </label>
                            </div>
                            <div class="form-check flex-fill">
                                <input class="form-check-input" type="radio" name="patient_type" id="existingPatient" value="existing">
                                <label class="form-check-label" for="existingPatient" style="word-wrap: break-word; hyphens: auto;">
                                    <strong>Existing Patient</strong><br>
                                    <small class="text-muted">Associate existing patient by Patient ID</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Existing Patient Section -->
                    <div id="existingPatientSection" class="d-none">
                        <div class="border rounded p-3 mb-3 bg-light">
                            <h6 class="mb-3">Associate Existing Patient</h6>
                            <div class="mb-3">
                                <label class="form-label">Patient ID <span class="text-danger">*</span></label>
                                <input type="text" name="patient_id" class="form-control" placeholder="Enter Patient ID">
                                <small class="form-text text-muted">Enter the unique Patient ID to associate with this health facility</small>
                            </div>
                        </div>
                    </div>

                    <!-- New Patient Section -->
                    <div id="newPatientSection">
                        <div class="border rounded p-3 mb-3">
                            <h6 class="mb-3">Create New Patient</h6>
                            <div class="mb-3">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Gender <span class="text-danger">*</span></label>
                                    <select name="gender" class="form-select form-control" required>
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Birth Date <span class="text-danger">*</span></label>
                                    <input type="date" name="birth_date" class="form-control" required>
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Contact Number</label>
                                <input type="text" name="contact_number" class="form-control" placeholder="e.g., +256 711 111 111">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save Patient</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection