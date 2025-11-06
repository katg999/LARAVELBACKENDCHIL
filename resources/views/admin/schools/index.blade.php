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
                            <h1 class="h3 mb-1 fw-bold text-white">Schools Management</h1>
                            <p class="mb-0 opacity-85">Manage school information and records</p>
                        </div>
                        <div class="d-flex gap-3">
                            <button type="button" class="btn btn-light btn-sm" data-toggle="modal" data-target="#createSchoolModal">
                                <i class="fa fa-plus me-3"></i> <span>Add School</span>
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
            <input type="text" id="admin-search" class="form-control" placeholder="Search by name, email, or contact...">
        </div>
        <div class="col-md-2 col-sm-12 mb-2">
            <select id="sort-filter" class="form-select form-control">
                <option value="">Sort by...</option>
                <option value="name_asc">Name (A-Z)</option>
                <option value="name_desc">Name (Z-A)</option>
                <option value="student_count_desc">Most Students</option>
                <option value="student_count_asc">Least Students</option>
                <option value="created_at_desc">Newest First</option>
                <option value="created_at_asc">Oldest First</option>
            </select>
        </div>
        <div class="col-md-2 col-sm-12 mb-2">
            <button type="button" id="clear-filters" class="btn btn-outline-secondary w-100">Clear</button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="mdi mdi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true"><i class="mdi mdi-close"></i></span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="mdi mdi-alert-circle me-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true"><i class="mdi mdi-close"></i></span>
            </button>
        </div>
    @endif

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

    <!-- Schools Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fa fa-school me-2"></i>Schools List
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="schools-table">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0">#</th>
                            <th class="border-0">School</th>
                            <th class="border-0">Contact</th>
                            <th class="border-0">Students</th>
                            <th class="border-0">Doctors</th>
                            <th class="border-0">Address</th>
                            <th class="border-0">Status</th>
                            <th class="border-0">Created</th>
                            <th class="border-0 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $school)
                        <tr data-name="{{ strtolower($school->name ?? '') }}"
                            data-email="{{ strtolower($school->email ?? '') }}"
                            data-contact="{{ strtolower($school->contact ?? '') }}">
                            <td>
                                <strong>{{ $school->id }}</strong>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-4 bg-info text-white d-flex align-items-center justify-content-center">
                                        {{ strtoupper(substr($school->name ?? 'S', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold">{{ $school->name ?? '-' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($school->email)
                                    <div class="mb-1">
                                        <i class="fa fa-envelope text-primary me-1"></i>
                                        <a href="mailto:{{ $school->email }}" class="text-decoration-none">{{ $school->email }}</a>
                                    </div>
                                @endif
                                @if($school->contact)
                                    <div>
                                        <i class="fa fa-phone text-success me-1"></i>
                                        <a href="tel:{{ $school->contact }}" class="text-decoration-none">{{ $school->contact }}</a>
                                    </div>
                                @endif
                                @if(!$school->email && !$school->contact)
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="text-center">
                                    <div class="fw-bold text-primary">{{ $school->students->count() }}</div>
                                    <small class="text-muted">enrolled</small>
                                </div>
                            </td>
                            <td>
                                <div class="text-center">
                                    <div class="fw-bold text-info">{{ $school->doctors->count() }}</div>
                                    <small class="text-muted">assigned</small>
                                </div>
                            </td>
                            <td>
                                @if($school->address)
                                    <span class="small">{{ Str::limit($school->address, 30) }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-success text-white">
                                    <i class="fa fa-check-circle me-1"></i>Active
                                </span>
                            </td>
                            <td>
                                <small class="text-muted">
                                    {{ optional($school->created_at)->format('M j, Y') ?? '-' }}
                                </small>
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('admin.schools.edit', $school->id) }}" class="btn btn-sm btn-outline-info" title="View Profile">
                                        <i class="mdi mdi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.schools.edit', $school->id) }}" class="btn btn-outline-secondary btn-sm" title="Edit">
                                        <i class="mdi mdi-pencil"></i>
                                    </a>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-toggle="dropdown" title="More Actions">
                                            <i class="fa fa-ellipsis-h"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a href="{{ route('admin.schools.edit', $school->id) }}" class="dropdown-item" target="_blank">
                                                    <i class="fa fa-external-link me-2 text-info"></i>View Full Details
                                                </a>
                                            </li>
                                            <li>
                                                <a href="#" class="dropdown-item" data-toggle="modal" data-target="#inviteAdminModal{{ $school->id }}">
                                                    <i class="fa fa-envelope me-2 text-success"></i>Send Admin Invitation
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('admin.schools.destroy', $school->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="dropdown-item text-danger" type="submit" onclick="return confirm('Delete this school?')">
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
                                <i class="fa fa-school fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No schools found</h5>
                                <p class="text-muted">Try adjusting your filters or add a new school.</p>
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

<!-- Create School Modal (Placeholder) -->
<div class="modal fade doctors-modal" id="createSchoolModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New School</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">School creation form would go here. For now, use the generic form.</p>
                <a href="{{ route('admin.schools.create') }}" class="btn btn-primary btn-sm">Go to Create Form</a>
            </div>
        </div>
    </div>
</div>

<!-- Invitation Modals (one per school) -->
@foreach($items as $school)
<div class="modal fade" id="inviteAdminModal{{ $school->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fa fa-envelope me-2"></i>Invite Admin for {{ $school->name }}
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('school.staff.invite') }}">
                @csrf
                <input type="hidden" name="school_id" value="{{ $school->id }}">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle me-2"></i>
                        <strong>Important:</strong> The invitation will be sent to the person's <strong>personal email</strong>, not the school email ({{ $school->email }}).
                    </div>

                    <div class="mb-3">
                        <label for="email{{ $school->id }}" class="form-label">
                            Personal Email <span class="text-danger">*</span>
                        </label>
                        <input type="email" 
                               class="form-control" 
                               id="email{{ $school->id }}" 
                               name="email" 
                               required 
                               placeholder="john.doe@gmail.com">
                        <small class="text-muted">
                            The admin will register using this personal email address.
                        </small>
                    </div>

                    <div class="mb-3">
                        <label for="role{{ $school->id }}" class="form-label">
                            Role <span class="text-danger">*</span>
                        </label>
                        <select class="form-control" id="role{{ $school->id }}" name="role" required>
                            <option value="school-admin">School Admin</option>
                            <option value="school-staff">School Staff</option>
                        </select>
                        <small class="text-muted">
                            Admins can manage staff and send invitations. Staff have limited access.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-send me-2"></i>Send Invitation
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
    const sortFilter = document.getElementById('sort-filter');
    const clearFiltersBtn = document.getElementById('clear-filters');
    const tableRows = Array.from(document.querySelectorAll('#schools-table tbody tr'));

    function filterSchools() {
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

    function sortSchools() {
        const sortValue = sortFilter?.value || '';
        if (!sortValue) return;

        const tbody = document.querySelector('#schools-table tbody');
        const rowsArray = Array.from(tableRows).filter(row => !row.querySelector('td[colspan]'));

        rowsArray.sort((a, b) => {
            let aVal, bVal;

            switch(sortValue) {
                case 'name_asc':
                    aVal = a.getAttribute('data-name') || '';
                    bVal = b.getAttribute('data-name') || '';
                    return aVal.localeCompare(bVal);
                case 'name_desc':
                    aVal = a.getAttribute('data-name') || '';
                    bVal = b.getAttribute('data-name') || '';
                    return bVal.localeCompare(aVal);
                case 'student_count_asc':
                    aVal = parseInt(a.querySelector('.fw-bold.text-primary')?.textContent || '0');
                    bVal = parseInt(b.querySelector('.fw-bold.text-primary')?.textContent || '0');
                    return aVal - bVal;
                case 'student_count_desc':
                    aVal = parseInt(a.querySelector('.fw-bold.text-primary')?.textContent || '0');
                    bVal = parseInt(b.querySelector('.fw-bold.text-primary')?.textContent || '0');
                    return bVal - aVal;
                default:
                    return 0;
            }
        });

        // Re-append sorted rows
        rowsArray.forEach(row => tbody.appendChild(row));
    }

    function clearFilters() {
        if (searchInput) searchInput.value = '';
        if (sortFilter) sortFilter.value = '';
        filterSchools();
        // Reset to original order (you might want to store original order)
    }

    if (searchInput) searchInput.addEventListener('input', filterSchools);
    if (sortFilter) sortFilter.addEventListener('change', function() {
        filterSchools();
        sortSchools();
    });
    if (clearFiltersBtn) clearFiltersBtn.addEventListener('click', clearFilters);
});
</script>
@endpush