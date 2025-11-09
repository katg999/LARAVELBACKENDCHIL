@extends('layouts.base')

@section('content')
<div class="container-fluid px-3 py-2">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm bg-success">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-white">
                            <h1 class="h3 mb-1 fw-bold">Health Facilities Management</h1>
                            <p class="mb-0 opacity-85">Manage healthcare facilities and their information</p>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-light btn-sm" data-toggle="modal" data-target="#createHealthFacilityModal">
                                <i class="mdi mdi-plus me-2"></i>Add Facility
                            </button>
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
                    <div class="avatar-circle bg-success text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                        <i class="mdi mdi-hospital-building"></i>
                    </div>
                    <h4 class="fw-bold text-success">{{ $items->total() }}</h4>
                    <p class="text-muted mb-0">Total Facilities</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="avatar-circle bg-info text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                        <i class="mdi mdi-account-heart"></i>
                    </div>
                    <h4 class="fw-bold text-info">{{ \App\Models\Patient::whereNotNull('health_facility_id')->count() }}</h4>
                    <p class="text-muted mb-0">Total Patients</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="avatar-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                        <i class="mdi mdi-doctor"></i>
                    </div>
                    <h4 class="fw-bold text-primary">{{ \App\Models\Doctor::whereNotNull('health_facility_id')->count() }}</h4>
                    <p class="text-muted mb-0">Total Doctors</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="avatar-circle bg-warning text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                        <i class="mdi mdi-calendar-check"></i>
                    </div>
                    <h4 class="fw-bold text-warning">{{ \App\Models\Appointment::whereHas('patient', function($q) { $q->whereNotNull('health_facility_id'); })->count() }}</h4>
                    <p class="text-muted mb-0">Total Appointments</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="row mb-4">
        <div class="col-md-4 col-sm-12 mb-2">
            <input type="text" id="admin-search" class="form-control" placeholder="Search by name, email, or contact...">
        </div>
        <div class="col-md-4 col-sm-12 mb-2">
            <form method="GET" class="d-flex">
                <select name="sort" class="form-control">
                    <option value="created_at_desc" {{ request('sort') == 'created_at_desc' ? 'selected' : '' }}>Newest First</option>
                    <option value="created_at_asc" {{ request('sort') == 'created_at_asc' ? 'selected' : '' }}>Oldest First</option>
                    <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Name A-Z</option>
                    <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>Name Z-A</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm" style="margin-left: 1rem;">Sort</button>
            </form>
        </div>
    </div>

    <!-- Success Message -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="mdi mdi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true"><i class="mdi mdi-close"></i></span>
            </button>
        </div>
    @endif

    <!-- Error Message -->
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="mdi mdi-alert-circle me-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true"><i class="mdi mdi-close"></i></span>
            </button>
        </div>
    @endif

    <!-- Validation Errors -->
    @if($errors->any())
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="mdi mdi-alert me-2"></i>
            <strong>Validation Error:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true"><i class="mdi mdi-close"></i></span>
            </button>
        </div>
    @endif

    <!-- Health Facilities Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="mdi mdi-hospital-building me-2"></i>Health Facilities List
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="health-facilities-table">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0">#</th>
                            <th class="border-0">Facility</th>
                            <th class="border-0">Contact</th>
                            <th class="border-0">Patients</th>
                            <th class="border-0">Staff</th>
                            <th class="border-0">Address</th>
                            <th class="border-0">Status</th>
                            <th class="border-0">Created</th>
                            <th class="border-0 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $facility)
                        <tr data-name="{{ strtolower($facility->name ?? '') }}"
                            data-email="{{ strtolower($facility->email ?? '') }}"
                            data-contact="{{ strtolower($facility->contact ?? '') }}">
                            <td>
                                <strong>{{ $facility->id }}</strong>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-3 bg-success text-white d-flex align-items-center justify-content-center">
                                        {{ strtoupper(substr($facility->name ?? 'H', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold">{{ $facility->name ?? '-' }}</div>
                                        <small class="text-muted">ID: {{ $facility->id }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($facility->email)
                                    <div class="mb-1">
                                        <i class="mdi mdi-email text-primary me-1"></i>
                                        <a href="mailto:{{ $facility->email }}" class="text-decoration-none">{{ $facility->email }}</a>
                                    </div>
                                @endif
                                @if($facility->contact)
                                    <div>
                                        <i class="mdi mdi-phone text-success me-1"></i>
                                        <a href="tel:{{ $facility->contact }}" class="text-decoration-none">{{ $facility->contact }}</a>
                                    </div>
                                @endif
                                @if(!$facility->email && !$facility->contact)
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-info text-white me-2">0</span>
                                    <small class="text-muted">patients</small>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-primary text-white me-2">0</span>
                                    <small class="text-muted">staff</small>
                                </div>
                            </td>
                            <td>
                                @if($facility->address)
                                    <span class="small">{{ strlen($facility->address) > 40 ? substr($facility->address, 0, 40) . '...' : $facility->address }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-success text-white">
                                    <i class="mdi mdi-check-circle me-1"></i>Active
                                </span>
                            </td>
                            <td>
                                <small class="text-muted">
                                    {{ optional($facility->created_at)->format('M j, Y') ?? '-' }}
                                </small>
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('admin.health-facilities.edit', $facility->id) }}" class="btn btn-sm btn-outline-info" title="View Details">
                                        <i class="mdi mdi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.health-facilities.edit', $facility->id) }}" class="btn btn-outline-secondary btn-sm" title="Edit">
                                        <i class="mdi mdi-pencil"></i>
                                    </a>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" title="More Actions">
                                            <i class="mdi mdi-dots-horizontal"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a href="{{ route('admin.health-facilities.edit', $facility->id) }}" class="dropdown-item" target="_blank">
                                                    <i class="mdi mdi-open-in-new me-2 text-info"></i>View Full Details
                                                </a>
                                            </li>
                                            <li>
                                                <a href="#" class="dropdown-item" data-toggle="modal" data-target="#inviteAdminModal{{ $facility->id }}">
                                                    <i class="mdi mdi-email-send me-2 text-success"></i>Send Admin Invitation
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('admin.health-facilities.destroy', $facility->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="dropdown-item text-danger" type="submit" onclick="return confirm('Delete this health facility?')">
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
                            <td colspan="9" class="text-center py-5">
                                <i class="mdi mdi-hospital-building fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No health facilities found</h5>
                                <p class="text-muted">Try adjusting your filters or add a new health facility.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-4">{{ $items->links() }}</div>
</div>

<!-- Create Health Facility Modal -->
<div class="modal fade health-facilities-modal" id="createHealthFacilityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Health Facility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.health-facilities.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input name="name" class="form-control" required value="{{ old('name') }}">
                            @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input name="email" type="email" class="form-control" value="{{ old('email') }}">
                            @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact</label>
                            <input name="contact" class="form-control" value="{{ old('contact') }}">
                            @error('contact')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="3" placeholder="Full address of the health facility">{{ old('address') }}</textarea>
                            @error('address')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Logo</label>
                            <input name="logo" type="file" accept="image/*" class="form-control">
                            @error('logo')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                    <button class="btn btn-primary btn-sm">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Invitation Modals (one per facility) -->
@foreach($items as $facility)
<div class="modal fade" id="inviteAdminModal{{ $facility->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="mdi mdi-email-send me-2"></i>Invite Admin for {{ $facility->name }}
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('health-facility.staff.invite') }}">
                @csrf
                <input type="hidden" name="health_facility_id" value="{{ $facility->id }}">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="mdi mdi-information me-2"></i>
                        <strong>Important:</strong> The invitation will be sent to the person's <strong>personal email</strong>, not the facility email ({{ $facility->email }}).
                    </div>

                    <div class="mb-3">
                        <label for="email{{ $facility->id }}" class="form-label">
                            Personal Email <span class="text-danger">*</span>
                        </label>
                        <input type="email" 
                               class="form-control" 
                               id="email{{ $facility->id }}" 
                               name="email" 
                               required 
                               placeholder="john.doe@gmail.com">
                        <small class="text-muted">
                            The admin will register using this personal email address.
                        </small>
                    </div>

                    <div class="mb-3">
                        <label for="role{{ $facility->id }}" class="form-label">
                            Role <span class="text-danger">*</span>
                        </label>
                        <select class="form-control" id="role{{ $facility->id }}" name="role" required>
                            <option value="health-facility-admin">Health Facility Admin</option>
                            <option value="health-facility-medical-personnel">Medical Personnel</option>
                        </select>
                        <small class="text-muted">
                            Admins can manage staff and send invitations. Medical personnel have limited access.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="mdi mdi-send me-2"></i>Send Invitation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    const searchInput = document.getElementById('admin-search');
    const tableRows = Array.from(document.querySelectorAll('#health-facilities-table tbody tr'));

    function filterFacilities() {
        const q = (searchInput?.value || '').trim().toLowerCase();

        tableRows.forEach(row => {
            // Skip empty state row
            if (row.querySelector('td[colspan]')) return;

            const name = row.getAttribute('data-name') || '';
            const email = row.getAttribute('data-email') || '';
            const contact = row.getAttribute('data-contact') || '';

            const matchesSearch = !q || name.includes(q) || email.includes(q) || contact.includes(q);

            row.style.display = matchesSearch ? '' : 'none';
        });
    }

    if (searchInput) searchInput.addEventListener('input', filterFacilities);
});
</script>
@endpush

@push('styles')
<style>
.health-facilities-header-gradient {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
}
</style>
@endpush