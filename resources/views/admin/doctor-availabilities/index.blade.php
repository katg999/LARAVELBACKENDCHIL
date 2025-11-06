@extends('layouts.base')

@section('content')
<div class="container-fluid px-3 py-2">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm bg-primary">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-white">
                            <h1 class="h3 mb-1 fw-bold">Doctor Availabilities Management</h1>
                            <p class="mb-0 opacity-85">Manage availability schedules for all doctors</p>
                        </div>
                        <div class="btn-group">
                            <a href="{{ route('admin.doctors.create') }}" class="btn btn-light btn-sm">
                                <i class="mdi mdi-plus me-2"></i>Add Doctor
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="avatar-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                        <i class="mdi mdi-doctor"></i>
                    </div>
                    <h4 class="fw-bold text-primary">{{ $doctors->total() }}</h4>
                    <p class="text-muted mb-0">Total Doctors</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="avatar-circle bg-success text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                        <i class="mdi mdi-calendar-check"></i>
                    </div>
                    <h4 class="fw-bold text-success">{{ $doctors->where('availabilities', '!=', collect())->count() }}</h4>
                    <p class="text-muted mb-0">With Availability Set</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="avatar-circle bg-info text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                        <i class="mdi mdi-calendar-today"></i>
                    </div>
                    <h4 class="fw-bold text-info">
                        @php
                            $availableToday = $doctors->filter(function($doctor) {
                                return $doctor->isAvailableOnDay(strtolower(now()->format('l')));
                            })->count();
                        @endphp
                        {{ $availableToday }}
                    </h4>
                    <p class="text-muted mb-0">Available Today</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="avatar-circle bg-warning text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                        <i class="mdi mdi-calendar-multiple"></i>
                    </div>
                    <h4 class="fw-bold text-warning">
                        @php
                            $totalSlots = $doctors->sum(function($doctor) {
                                return $doctor->availabilities->where('available', true)->sum('max_appointments');
                            });
                        @endphp
                        {{ $totalSlots }}
                    </h4>
                    <p class="text-muted mb-0">Total Available Slots</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-12 mb-2">
            <input type="text" id="admin-search" class="form-control" placeholder="Search by doctor name...">
        </div>
        <div class="col-md-3 col-sm-12 mb-2">
            <input type="text" id="specialization-search" class="form-control" placeholder="Search by specialization...">
        </div>
        <div class="col-md-3 col-sm-12 mb-2">
            <select id="day-filter" class="form-control">
                <option value="">All days</option>
                <option value="monday">Monday</option>
                <option value="tuesday">Tuesday</option>
                <option value="wednesday">Wednesday</option>
                <option value="thursday">Thursday</option>
                <option value="friday">Friday</option>
                <option value="saturday">Saturday</option>
                <option value="sunday">Sunday</option>
            </select>
        </div>
        <div class="col-md-3 col-sm-12 mb-2">
            <button type="button" class="btn btn-primary btn-sm w-100" onclick="clearFilters()">
                <i class="mdi mdi-refresh me-2"></i>Clear Filters
            </button>
        </div>
    </div>

    <!-- Success Message -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="mdi mdi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Doctors Availabilities Table -->
    <div class="card">
        <div class="card-header" style="background-color: #FF00FF;">
            <h5 class="card-title mb-0" style="color: white !important;">
                <i class="mdi mdi-calendar-clock me-2" style="color: white !important;"></i>Doctor Availabilities Overview
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="doctor-availabilities-table">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0">#</th>
                            <th class="border-0">Doctor</th>
                            <th class="border-0">Specialization</th>
                            <th class="border-0">Contact</th>
                            <th class="border-0">Mon</th>
                            <th class="border-0">Tue</th>
                            <th class="border-0">Wed</th>
                            <th class="border-0">Thu</th>
                            <th class="border-0">Fri</th>
                            <th class="border-0">Sat</th>
                            <th class="border-0">Sun</th>
                            <th class="border-0 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($doctors as $doctor)
                        <tr data-name="{{ strtolower($doctor->name ?? '') }}"
                            data-specialization="{{ strtolower($doctor->specialization ?? '') }}">
                            <td>
                                <strong>{{ $doctor->id }}</strong>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-3 bg-primary text-white d-flex align-items-center justify-content-center">
                                        {{ strtoupper(substr($doctor->name ?? 'D', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold">{{ $doctor->display_name ?? '-' }}</div>
                                        <small class="text-muted">ID: {{ $doctor->id }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($doctor->specialization)
                                    <span class="badge bg-info text-white">{{ $doctor->specialization }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($doctor->contact)
                                    <div>
                                        <i class="mdi mdi-phone text-success me-1"></i>
                                        <a href="tel:{{ $doctor->contact }}" class="text-decoration-none">{{ $doctor->contact }}</a>
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            @php
                                $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                            @endphp
                            @foreach($days as $day)
                                @php
                                    $availability = $doctor->availabilities->where('day', $day)->first();
                                @endphp
                                <td>
                                    @if($availability && $availability->available)
                                        <div class="text-center">
                                            <span class="badge bg-success text-white mb-1">
                                                <i class="mdi mdi-check-circle me-1"></i>Yes
                                            </span>
                                            <br>
                                            <small class="text-muted">{{ $availability->max_appointments }} slots</small>
                                        </div>
                                    @else
                                        <span class="badge bg-secondary text-white">
                                            <i class="mdi mdi-close-circle me-1"></i>No
                                        </span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('doctor.availability', $doctor->id) }}" class="btn btn-sm btn-outline-primary" title="Manage Availability">
                                        <i class="mdi mdi-calendar-edit"></i>
                                    </a>
                                    <a href="{{ route('profile.show', $doctor->id) }}" class="btn btn-sm btn-outline-info" title="View Details">
                                        <i class="mdi mdi-eye"></i>
                                    </a>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" title="More Actions">
                                            <i class="mdi mdi-dots-horizontal"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a href="{{ route('doctor.availability', $doctor->id) }}" class="dropdown-item" target="_blank">
                                                    <i class="mdi mdi-open-in-new me-2 text-info"></i>Manage Availability
                                                </a>
                                            </li>
                                            <li>
                                                <a href="{{ route('admin.doctors.edit', $doctor->id) }}" class="dropdown-item">
                                                    <i class="mdi mdi-pencil me-2 text-warning"></i>Edit Doctor
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('admin.doctors.destroy', $doctor->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="dropdown-item text-danger" type="submit" onclick="return confirm('Delete this doctor?')">
                                                        <i class="mdi mdi-delete me-2"></i>Delete
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
                            <td colspan="12" class="text-center py-5">
                                <i class="mdi mdi-calendar-clock text-muted mb-3" style="font-size: 3rem;"></i>
                                <h5 class="text-muted">No doctors found</h5>
                                <p class="text-muted">Try adjusting your filters or add a new doctor.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-4">{{ $doctors->links() }}</div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    const searchInput = document.getElementById('admin-search');
    const specializationInput = document.getElementById('specialization-search');
    const dayFilter = document.getElementById('day-filter');
    const tableRows = Array.from(document.querySelectorAll('#doctor-availabilities-table tbody tr'));

    function filterDoctors() {
        const nameQuery = (searchInput?.value || '').trim().toLowerCase();
        const specQuery = (specializationInput?.value || '').trim().toLowerCase();
        const dayQuery = (dayFilter?.value || '').trim().toLowerCase();

        tableRows.forEach(row => {
            // Skip empty state row
            if (row.querySelector('td[colspan]')) return;

            const name = row.getAttribute('data-name') || '';
            const specialization = row.getAttribute('data-specialization') || '';

            const matchesName = !nameQuery || name.includes(nameQuery);
            const matchesSpec = !specQuery || specialization.includes(specQuery);

            // For day filter, check if any availability matches
            let matchesDay = !dayQuery;
            if (dayQuery) {
                const dayCells = row.querySelectorAll('td');
                // Check the day columns (indices 4-10 for Mon-Sun)
                for (let i = 4; i <= 10; i++) {
                    const cell = dayCells[i];
                    if (cell && cell.textContent.toLowerCase().includes('yes')) {
                        matchesDay = true;
                        break;
                    }
                }
            }

            row.style.display = (matchesName && matchesSpec && matchesDay) ? '' : 'none';
        });
    }

    function clearFilters() {
        if (searchInput) searchInput.value = '';
        if (specializationInput) specializationInput.value = '';
        if (dayFilter) dayFilter.value = '';
        filterDoctors();
    }

    if (searchInput) searchInput.addEventListener('input', filterDoctors);
    if (specializationInput) specializationInput.addEventListener('input', filterDoctors);
    if (dayFilter) dayFilter.addEventListener('change', filterDoctors);

    // Make clearFilters function globally available
    window.clearFilters = clearFilters;
});
</script>
@endpush

@push('styles')
<style>
.doctor-availabilities-header-gradient {
    background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
}

.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    font-size: 1rem;
    font-weight: bold;
    border: 2px solid #e9ecef;
    margin-right: 1.5rem !important;
}

.table-responsive {
    border-radius: 0.375rem;
}

.table thead th {
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    color: #495057;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    vertical-align: middle;
}

.table tbody td {
    vertical-align: middle;
    padding: 1rem 0.75rem;
}

.table-hover tbody tr:hover {
    background-color: rgba(0,0,0,.075);
}

.btn-group .btn {
    margin-right: 0.25rem;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

.badge {
    font-size: 0.75rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }

    .avatar-circle {
        width: 35px;
        height: 35px;
        font-size: 0.9rem;
    }

    .btn-group .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }

    /* Stack day columns on mobile */
    .table-responsive .table {
        min-width: 1200px;
    }
}

/* Day availability badges */
.badge {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
}
</style>
@endpush