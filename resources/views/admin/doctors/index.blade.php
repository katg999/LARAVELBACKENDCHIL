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
                            <h1 class="h3 mb-1 fw-bold">Doctors Management</h1>
                            <p class="mb-0 opacity-85">Manage healthcare professionals and their information</p>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#createDoctorModal">
                                <i class="fa fa-plus me-2"></i>Register
                            </button>
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#createDoctorModal">
                                <i class="mdi mdi-export me-2"></i> <span>Export</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="row mb-4">
        <div class="col-md-4 col-sm-12 mb-2">
            <input type="text" id="admin-search" class="form-control" placeholder="Search by name, email, or specialization...">
        </div>
        <div class="col-md-4 col-sm-12 mb-2">
            <select id="specialization-filter" class="form-select form-control">
                <option value="">All Specializations</option>
                @foreach($specializations->sort() as $specialization)
                    <option value="{{ strtolower($specialization) }}">{{ $specialization }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Success Message -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Doctors Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fa fa-user-md me-2"></i>Doctors List
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="doctors-table">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0">#</th>
                            <th class="border-0">Doctor</th>
                            <th class="border-0">Contact</th>
                            <th class="border-0">Specialization</th>
                            <th class="border-0">Affiliation</th>
                            <th class="border-0">Meeting Room</th>
                            <th class="border-0">Status</th>
                            <th class="border-0">Created</th>
                            <th class="border-0 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $doctor)
                        <tr data-name="{{ strtolower($doctor->name ?? '') }}"
                            data-email="{{ strtolower($doctor->email ?? '') }}"
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
                                        @if($doctor->specialization)
                                            <small class="text-muted">{{ $doctor->specialization }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($doctor->email)
                                    <div class="mb-1">
                                        <i class="fa fa-envelope text-primary me-1"></i>
                                        <a href="mailto:{{ $doctor->email }}" class="text-decoration-none">{{ $doctor->email }}</a>
                                    </div>
                                @endif
                                @if($doctor->contact)
                                    <div>
                                        <i class="fa fa-phone text-success me-1"></i>
                                        <a href="tel:{{ $doctor->contact }}" class="text-decoration-none">{{ $doctor->contact }}</a>
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($doctor->specialization)
                                    <span class="badge bg-primary text-white">{{ $doctor->specialization }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($doctor->school)
                                    <div class="mb-1">
                                        <i class="fa fa-school text-info me-1"></i>
                                        <span class="badge bg-info text-white">{{ substr($doctor->school->name, 0, 20) }}</span>
                                    </div>
                                @endif
                                @if($doctor->healthFacility)
                                    <div>
                                        <i class="fa fa-hospital text-warning me-1"></i>
                                        <span class="badge bg-warning text-dark">{{ substr($doctor->healthFacility->name, 0, 20) }}</span>
                                    </div>
                                @endif
                                @if(!$doctor->school && !$doctor->healthFacility)
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($doctor->meeting_slug)
                                    <div class="d-flex align-items-center">
                                        <code class="small text-primary me-2">{{ $doctor->meeting_slug }}</code>
                                        <button class="btn btn-sm btn-outline-primary copy-btn" onclick="navigator.clipboard.writeText('https://meet.jit.si/{{ $doctor->meeting_slug }}')" title="Copy Meeting URL">
                                            <i class="fa fa-copy"></i>
                                        </button>
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-success text-white">
                                    <i class="fa fa-circle me-1" style="font-size: 0.5rem;"></i>Active
                                </span>
                            </td>
                            <td>
                                <small class="text-muted">
                                    {{ optional($doctor->created_at)->format('M j, Y') ?? '-' }}
                                </small>
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('profile.show', $doctor->id) }}" class="btn btn-sm btn-outline-info" title="View Profile">
                                        <i class="mdi mdi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.doctors.edit', $doctor->id) }}" class="btn btn-outline-secondary btn-sm" title="Edit">
                                        <i class="mdi mdi-flag"></i>
                                    </a>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" title="More Actions">
                                            <i class="fa fa-ellipsis-h"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <form action="{{ route('admin.doctors.send-login', $doctor->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button class="dropdown-item" type="submit" onclick="return confirm('Send login OTP to this doctor?')">
                                                        <i class="fa fa-envelope me-2 text-primary"></i>Send Login Link
                                                    </button>
                                                </form>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('admin.doctors.destroy', $doctor->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="dropdown-item text-danger" type="submit" onclick="return confirm('Delete this doctor?')">
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
                                <i class="fa fa-user-md fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No doctors found</h5>
                                <p class="text-muted">Try adjusting your filters or create a new doctor.</p>
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

<!-- Create Doctor Modal -->
<div class="modal fade doctors-modal" id="createDoctorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Doctor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.model.store', 'doctors') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    @if(isset($errors) && $errors->any())
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
                            <label class="form-label">Specialization</label>
                            <input name="specialization" class="form-control" value="{{ old('specialization') }}">
                            @error('specialization')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact</label>
                            <input name="contact" class="form-control" value="{{ old('contact') }}">
                            @error('contact')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">School</label>
                            <select name="school_id" class="form-select">
                                <option value="">-- none --</option>
                                @foreach($schools as $id => $label)
                                    <option value="{{ $id }}" @if(old('school_id') == $id) selected @endif>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Health Facility</label>
                            <select name="health_facility_id" class="form-select">
                                <option value="">-- none --</option>
                                @foreach($hfs as $id => $label)
                                    <option value="{{ $id }}" @if(old('health_facility_id') == $id) selected @endif>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Profile Image</label>
                            <input name="file_url" type="file" accept="image/*" class="form-control">
                            @error('file_url')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Meeting Slug</label>
                            <input name="meeting_slug" class="form-control" value="{{ old('meeting_slug', $generatedSlug ?? '') }}" readonly>
                            <label class="form-label small mt-2">Meeting URL</label>
                            <div class="input-group">
                                <input id="modal-meeting-url" type="text" class="form-control" value="{{ 'https://meet.jit.si/' . (old('meeting_slug', $generatedSlug ?? '')) }}" readonly>
                                <button type="button" id="copy-modal-meeting-url" class="btn btn-outline-secondary btn-sm">Copy</button>
                            </div>
                            <div id="modal-copy-feedback" class="small text-success mt-1" style="display:none">Copied to clipboard</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button class="btn btn-primary btn-sm">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    const searchInput = document.getElementById('admin-search');
    const specializationFilter = document.getElementById('specialization-filter');
    const tableRows = Array.from(document.querySelectorAll('#doctors-table tbody tr'));

    function filterDoctors() {
        const q = (searchInput?.value || '').trim().toLowerCase();
        const selectedSpec = (specializationFilter?.value || '').toLowerCase();

        tableRows.forEach(row => {
            // Skip empty state row
            if (row.querySelector('td[colspan]')) return;

            const name = row.getAttribute('data-name') || '';
            const email = row.getAttribute('data-email') || '';
            const specialization = row.getAttribute('data-specialization') || '';

            const matchesSearch = !q || name.includes(q) || email.includes(q) || specialization.includes(q);
            const matchesSpec = !selectedSpec || specialization === selectedSpec;

            row.style.display = (matchesSearch && matchesSpec) ? '' : 'none';
        });
    }

    if (searchInput) searchInput.addEventListener('input', filterDoctors);
    if (specializationFilter) specializationFilter.addEventListener('change', filterDoctors);

    // Copy meeting URL functionality
    document.getElementById('copy-modal-meeting-url')?.addEventListener('click', function() {
        const urlInput = document.getElementById('modal-meeting-url');
        navigator.clipboard.writeText(urlInput.value).then(() => {
            const feedback = document.getElementById('modal-copy-feedback');
            feedback.style.display = 'block';
            setTimeout(() => feedback.style.display = 'none', 2000);
        });
    });
});
</script>
@endpush

@push('styles')
<style>
.doctors-header-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

.copy-btn {
    font-size: 0.7rem;
    padding: 0.2rem 0.5rem;
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