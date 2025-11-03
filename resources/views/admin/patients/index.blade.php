@extends('layouts.base')

@push('styles')
<link href="{{ asset('css/admin-dashboard.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container-fluid px-3 py-2">
    <!-- Header Section with Gradient Background -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm doctors-header-gradient">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-white">
                            <h1 class="h3 mb-1 fw-bold text-white">Patients Management</h1>
                            <p class="mb-0 opacity-85">Manage patient information and records</p>
                        </div>
                        <div class="d-flex gap-3">
                            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#createPatientModal">
                                <i class="fa fa-plus me-3"></i> <span>Add Patient</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-12 mb-2">
            <input type="text" id="admin-search" class="form-control" placeholder="Search by name, ID, or contact...">
        </div>
        <div class="col-md-2 col-sm-12 mb-2">
            <select id="gender-filter" class="form-select form-control">
                <option value="">All Genders</option>
                @foreach($genders as $gender)
                    <option value="{{ $gender }}">{{ ucfirst($gender) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 col-sm-12 mb-2">
            <select id="school-filter" class="form-select form-control">
                <option value="">All Schools</option>
                @foreach($schools as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 col-sm-12 mb-2">
            <select id="facility-filter" class="form-select form-control">
                <option value="">All Facilities</option>
                @foreach($hfs as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-1 col-sm-12 mb-2">
            <input type="number" id="min-age" class="form-control" placeholder="Min Age" min="0">
        </div>
        <div class="col-md-1 col-sm-12 mb-2">
            <input type="number" id="max-age" class="form-control" placeholder="Max Age" min="0">
        </div>
        <div class="col-md-1 col-sm-12 mb-2">
            <button type="button" id="clear-filters" class="btn btn-outline-secondary w-100">Clear</button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Patients Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fa fa-users me-2"></i>Patients List
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="patients-table">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0">#</th>
                            <th class="border-0">Patient</th>
                            <th class="border-0">Patient ID</th>
                            <th class="border-0">Contact</th>
                            <th class="border-0">Gender</th>
                            <th class="border-0">Affiliation</th>
                            <th class="border-0">Appointments</th>
                            <th class="border-0">Created</th>
                            <th class="border-0 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $patient)
                        <tr data-name="{{ strtolower($patient->name ?? '') }}"
                            data-patient-id="{{ strtolower($patient->patient_id ?? '') }}"
                            data-contact="{{ strtolower($patient->contact_number ?? '') }}"
                            data-parent-contact="{{ strtolower($patient->parent_contact ?? '') }}"
                            data-gender="{{ $patient->gender }}"
                            data-school="{{ $patient->school_id }}"
                            data-facility="{{ $patient->health_facility_id }}"
                            data-age="{{ $patient->age }}">
                            <td>
                                <strong>{{ $patient->id }}</strong>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-4 bg-primary text-white d-flex align-items-center justify-content-center">
                                        {{ strtoupper(substr($patient->name ?? 'P', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold">{{ $patient->name ?? '-' }}</div>
                                        @if($patient->age)
                                            <small class="text-muted">{{ $patient->age }} years old</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <code class="small">{{ $patient->patient_id ?? '-' }}</code>
                            </td>
                            <td>
                                @if($patient->contact_number)
                                    <div class="mb-1">
                                        <i class="fa fa-phone text-primary me-1"></i>
                                        <a href="tel:{{ $patient->contact_number }}" class="text-decoration-none">{{ $patient->contact_number }}</a>
                                    </div>
                                @endif
                                @if($patient->parent_contact)
                                    <div>
                                        <i class="fa fa-user-friends text-success me-1"></i>
                                        <span class="small">{{ $patient->parent_contact }}</span>
                                    </div>
                                @endif
                                @if(!$patient->contact_number && !$patient->parent_contact)
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $genderColors = [
                                        'male' => 'primary',
                                        'female' => 'success',
                                        'other' => 'secondary'
                                    ];
                                    $genderColor = $genderColors[$patient->gender] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $genderColor }} text-white">
                                    <i class="fa fa-{{ $patient->gender === 'male' ? 'mars' : ($patient->gender === 'female' ? 'venus' : 'genderless') }} me-1"></i>{{ ucfirst($patient->gender ?? 'Unknown') }}
                                </span>
                            </td>
                            <td>
                                @if($patient->school)
                                    <div class="mb-1">
                                        <i class="fa fa-school text-info me-1"></i>
                                        <span class="badge bg-info text-white">{{ Str::limit($patient->school->name, 20) }}</span>
                                        @if($patient->grade)
                                            <small class="ms-1">Grade: {{ $patient->grade }}</small>
                                        @endif
                                    </div>
                                @endif
                                @if($patient->healthFacility)
                                    <div>
                                        <i class="fa fa-hospital text-warning me-1"></i>
                                        <span class="badge bg-warning text-dark">{{ Str::limit($patient->healthFacility->name, 20) }}</span>
                                    </div>
                                @endif
                                @if(!$patient->school && !$patient->healthFacility)
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($patient->appointments && $patient->appointments->count() > 0)
                                    <div class="small">
                                        <div class="fw-bold">{{ $patient->appointments->count() }} total</div>
                                        <div class="text-muted">
                                            {{ $patient->appointments->where('status', 'completed')->count() }} completed,
                                            {{ $patient->appointments->whereIn('status', ['pending', 'scheduled'])->count() }} upcoming
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">
                                    {{ optional($patient->created_at)->format('M j, Y') ?? '-' }}
                                </small>
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('patients.profile', $patient->id) }}" class="btn btn-sm btn-outline-info" title="View Profile" target="_blank">
                                        <i class="mdi mdi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.patients.edit', $patient->id) }}" class="btn btn-outline-secondary btn-sm" title="Edit">
                                        <i class="mdi mdi-pencil"></i>
                                    </a>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-toggle="dropdown" title="More Actions">
                                            <i class="fa fa-ellipsis-h"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <form action="{{ route('admin.patients.destroy', $patient->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="dropdown-item text-danger" type="submit" onclick="return confirm('Delete this patient?')">
                                                        <i class="fa fa-trash me-2"></i>Delete
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <i class="fa fa-users fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No patients found</h5>
                                <p class="text-muted">Try adjusting your filters or add a new patient.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-4">{{ $items->appends(request()->query())->links() }}</div>
</div>

<!-- Create Patient Modal (Placeholder) -->
<div class="modal fade doctors-modal" id="createPatientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Patient</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Patient creation form would go here. For now, use the generic form.</p>
                <a href="{{ route('admin.patients.create') }}" class="btn btn-primary btn-sm">Go to Create Form</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    const searchInput = document.getElementById('admin-search');
    const genderFilter = document.getElementById('gender-filter');
    const schoolFilter = document.getElementById('school-filter');
    const facilityFilter = document.getElementById('facility-filter');
    const minAgeInput = document.getElementById('min-age');
    const maxAgeInput = document.getElementById('max-age');
    const clearFiltersBtn = document.getElementById('clear-filters');
    const tableRows = Array.from(document.querySelectorAll('#patients-table tbody tr'));

    function filterPatients() {
        const q = (searchInput?.value || '').trim().toLowerCase();
        const selectedGender = (genderFilter?.value || '').toLowerCase();
        const selectedSchool = schoolFilter?.value || '';
        const selectedFacility = facilityFilter?.value || '';
        const minAge = minAgeInput?.value ? parseInt(minAgeInput.value) : null;
        const maxAge = maxAgeInput?.value ? parseInt(maxAgeInput.value) : null;

        tableRows.forEach(row => {
            // Skip empty state row
            if (row.querySelector('td[colspan]')) return;

            const name = row.getAttribute('data-name') || '';
            const patientId = row.getAttribute('data-patient-id') || '';
            const contact = row.getAttribute('data-contact') || '';
            const parentContact = row.getAttribute('data-parent-contact') || '';
            const gender = row.getAttribute('data-gender') || '';
            const school = row.getAttribute('data-school') || '';
            const facility = row.getAttribute('data-facility') || '';
            const age = row.getAttribute('data-age') ? parseInt(row.getAttribute('data-age')) : null;

            const matchesSearch = !q || name.includes(q) || patientId.includes(q) || contact.includes(q) || parentContact.includes(q);
            const matchesGender = !selectedGender || gender === selectedGender;
            const matchesSchool = !selectedSchool || school === selectedSchool;
            const matchesFacility = !selectedFacility || facility === selectedFacility;
            const matchesMinAge = !minAge || (age !== null && age >= minAge);
            const matchesMaxAge = !maxAge || (age !== null && age <= maxAge);

            row.style.display = (matchesSearch && matchesGender && matchesSchool && matchesFacility && matchesMinAge && matchesMaxAge) ? '' : 'none';
        });
    }

    function clearFilters() {
        if (searchInput) searchInput.value = '';
        if (genderFilter) genderFilter.value = '';
        if (schoolFilter) schoolFilter.value = '';
        if (facilityFilter) facilityFilter.value = '';
        if (minAgeInput) minAgeInput.value = '';
        if (maxAgeInput) maxAgeInput.value = '';
        filterPatients();
    }

    if (searchInput) searchInput.addEventListener('input', filterPatients);
    if (genderFilter) genderFilter.addEventListener('change', filterPatients);
    if (schoolFilter) schoolFilter.addEventListener('change', filterPatients);
    if (facilityFilter) facilityFilter.addEventListener('change', filterPatients);
    if (minAgeInput) minAgeInput.addEventListener('input', filterPatients);
    if (maxAgeInput) maxAgeInput.addEventListener('input', filterPatients);
    if (clearFiltersBtn) clearFiltersBtn.addEventListener('click', clearFilters);
});
</script>
@endpush