@extends('layouts.base')

@push('styles')
<link href="{{ asset('css/admin-dashboard.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container-fluid px-3 py-2">
    <!-- Header Section with Gradient Background -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm bg-primary">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-white">
                            <h1 class="h3 mb-1 fw-bold text-white">Durations Management</h1>
                            <p class="mb-0 opacity-85">Manage appointment duration settings and pricing</p>
                        </div>
                        <div class="d-flex gap-3">
                            <button type="button" class="btn btn-light btn-sm" data-toggle="modal" data-target="#createDurationModal">
                                <i class="mdi mdi-plus me-3"></i> <span>Add Duration</span>
                            </button>
                            <form action="{{ route('admin.durations.seed') }}" method="POST" class="d-inline" id="seedForm">
                                @csrf
                                <button type="submit" class="btn btn-info btn-sm" id="seedDurationsBtn">
                                    <i class="mdi mdi-seed me-2"></i>
                                    <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                    <span>Seed Defaults</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-12 mb-2">
            <input type="text" id="admin-search" class="form-control" placeholder="Search by minutes, type or price...">
        </div>
        <div class="col-md-2 col-sm-12 mb-2">
            <select id="status-filter" class="form-select form-control">
                <option value="">All Status</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
        </div>
        <div class="col-md-2 col-sm-12 mb-2">
            <select id="sort-filter" class="form-select form-control">
                <option value="">Sort by...</option>
                <option value="minutes_asc">Minutes (Low to High)</option>
                <option value="minutes_desc">Minutes (High to Low)</option>
                <option value="price_asc">Price (Low to High)</option>
                <option value="price_desc">Price (High to Low)</option>
                <option value="created_at_desc">Newest First</option>
                <option value="created_at_asc">Oldest First</option>
            </select>
        </div>
        <div class="col-md-3 col-sm-12 mb-2">
            <button type="button" id="clear-filters" class="btn btn-outline-secondary w-100">Clear</button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="mdi mdi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <i class="typcn typcn-times"></i>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="mdi mdi-alert-circle me-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <i class="typcn typcn-times"></i>
            </button>
        </div>
    @endif

    <!-- Durations Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="mdi mdi-timer me-2"></i>Durations List
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="durations-table">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0">#</th>
                            <th class="border-0">Duration</th>
                            <th class="border-0">Type</th>
                            <th class="border-0">Price</th>
                            <th class="border-0">Status</th>
                            <th class="border-0">Created</th>
                            <th class="border-0 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                        <tr data-minutes="{{ $item->minutes }}"
                            data-duration-type="{{ $item->duration_type }}"
                            data-price="{{ $item->price }}"
                            data-is-active="{{ $item->is_active ? '1' : '0' }}">
                            <td>
                                <strong>{{ $item->id }}</strong>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-3 bg-primary text-white d-flex align-items-center justify-content-center">
                                        <i class="mdi mdi-timer"></i>
                                    </div>
                                    <div class="fw-bold">{{ $item->minutes }} minutes</div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $item->duration_type === 'general' ? 'info' : 'warning' }} text-white">
                                    {{ ucfirst($item->duration_type) }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-success">UGX {{ number_format($item->price, 0) }}</div>
                            </td>
                            <td>
                                @if($item->is_active)
                                    <span class="badge bg-success text-white">
                                        <i class="fa fa-check-circle me-1"></i>Active
                                    </span>
                                @else
                                    <span class="badge bg-secondary text-white">
                                        <i class="fa fa-pause-circle me-1"></i>Inactive
                                    </span>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">
                                    {{ optional($item->created_at)->format('M j, Y') ?? '-' }}
                                </small>
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('admin.durations.edit', $item->id) }}" class="btn btn-outline-secondary btn-sm" title="Edit">
                                        <i class="mdi mdi-pencil"></i>
                                    </a>
                                    <button class="btn btn-outline-danger btn-sm" type="button"
                                            data-toggle="modal"
                                            data-target="#deleteDurationModal"
                                            data-duration-id="{{ $item->id }}"
                                            data-duration-name="{{ $item->minutes }} minutes ({{ ucfirst($item->duration_type) }})"
                                            title="Delete">
                                        <i class="mdi mdi-delete"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="mdi mdi-timer fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No durations found</h5>
                                <p class="text-muted">Try adjusting your filters or add a new duration.</p>
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

<!-- Create Duration Modal -->
<div class="modal fade doctors-modal" id="createDurationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Duration</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.durations.store') }}" id="createDurationForm" novalidate>
                @csrf
                <div class="modal-body">
                    <div id="createDurationErrors" class="alert alert-danger" style="display: none;"></div>
                    <div id="createDurationSuccess" class="alert alert-success" style="display: none;"></div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="modal_minutes">Minutes <span class="text-danger">*</span></label>
                                <input type="number" name="minutes" id="modal_minutes" class="form-control" min="1" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="modal_duration_type">Duration Type <span class="text-danger">*</span></label>
                                <select name="duration_type" id="modal_duration_type" class="form-control" required>
                                    <option value="general">General</option>
                                    <option value="specialist">Specialist</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="modal_duration_type">Duration Type <span class="text-danger">*</span></label>
                                <select name="duration_type" id="modal_duration_type" class="form-control @error('duration_type') is-invalid @enderror" required>
                                    <option value="general" {{ old('duration_type', 'general') === 'general' ? 'selected' : '' }}>General</option>
                                    <option value="specialist" {{ old('duration_type') === 'specialist' ? 'selected' : '' }}>Specialist</option>
                                </select>
                                @error('duration_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="modal_price">Price (UGX) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">UGX</span>
                                    </div>
<<<<<<< HEAD
                                    <input type="number" name="price" id="modal_price" class="form-control @error('price') is-invalid @enderror"
                                           value="{{ old('price') }}" step="0.01" min="0" required>
                                </div>
                                @error('price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
=======
                                    <input type="number" name="price" id="modal_price" class="form-control" step="0.01" min="0" required>
                                </div>
                                <div class="invalid-feedback"></div>
>>>>>>> 5574e2083b71cb54484a5dd6692cf825691a4835
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="custom-control-input" id="modal_is_active" checked>
                            <label class="custom-control-label" for="modal_is_active">Active</label>
                        </div>
                        <small class="form-text text-muted">Inactive durations won't be available for new appointments</small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="createDurationBtn">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" style="display: none;"></span>
                        <i class="fas fa-save"></i> Create Duration
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Duration Modal -->
<div class="modal fade doctors-modal" id="deleteDurationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="mdi mdi-alert-circle me-2"></i>Delete Duration
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <i class="mdi mdi-timer text-danger" style="font-size: 4rem;"></i>
                </div>
                <h5 class="text-center mb-3">Are you sure you want to delete this duration?</h5>
                <p class="text-muted text-center mb-0">
                    <strong id="deleteDurationName"></strong>
                </p>
                <div class="alert alert-warning mt-4">
                    <i class="mdi mdi-alert me-2"></i>
                    <strong>Warning:</strong> This action cannot be undone. Any appointments using this duration may be affected.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="mdi mdi-close"></i> Cancel
                </button>
                <a href="#" id="deleteDurationLink" class="btn btn-danger">
                    <i class="mdi mdi-delete"></i> Yes, Delete
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    const searchInput = document.getElementById('admin-search');
    const statusFilter = document.getElementById('status-filter');
    const sortFilter = document.getElementById('sort-filter');
    const clearFiltersBtn = document.getElementById('clear-filters');
    const tableRows = Array.from(document.querySelectorAll('#durations-table tbody tr'));

    // Seed Durations Functionality
    const seedForm = document.getElementById('seedForm');
    const seedDurationsBtn = document.getElementById('seedDurationsBtn');

    if (seedForm && seedDurationsBtn) {
        seedForm.addEventListener('submit', function(e) {
            const spinner = seedDurationsBtn.querySelector('.spinner-border');
            const icon = seedDurationsBtn.querySelector('.mdi-seed');
            const text = seedDurationsBtn.querySelector('span:not(.spinner-border)');

            // Show spinner and disable button
            spinner.classList.remove('d-none');
            icon.classList.add('d-none');
            text.textContent = 'Seeding...';
            seedDurationsBtn.disabled = true;

            // Let the form submit normally - the spinner will show during the request
            // The page will reload with success/error message from the controller
        });
    }

    function filterDurations() {
        const q = (searchInput?.value || '').trim().toLowerCase();
        const status = statusFilter?.value || '';

        tableRows.forEach(row => {
            // Skip empty state row
            if (row.querySelector('td[colspan]')) return;

            const minutes = row.getAttribute('data-minutes') || '';
            const durationType = row.getAttribute('data-duration-type') || '';
            const price = row.getAttribute('data-price') || '';
            const isActive = row.getAttribute('data-is-active') || '';

            const matchesSearch = !q ||
                minutes.includes(q) ||
                durationType.includes(q) ||
                price.includes(q);

            const matchesStatus = !status || isActive === status;

            row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
        });
    }

    function sortDurations() {
        const sortValue = sortFilter?.value || '';
        if (!sortValue) return;

        const tbody = document.querySelector('#durations-table tbody');
        const rowsArray = Array.from(tableRows).filter(row => !row.querySelector('td[colspan]'));

        rowsArray.sort((a, b) => {
            let aVal, bVal;

            switch(sortValue) {
                case 'minutes_asc':
                    aVal = parseInt(a.getAttribute('data-minutes') || '0');
                    bVal = parseInt(b.getAttribute('data-minutes') || '0');
                    return aVal - bVal;
                case 'minutes_desc':
                    aVal = parseInt(a.getAttribute('data-minutes') || '0');
                    bVal = parseInt(b.getAttribute('data-minutes') || '0');
                    return bVal - aVal;
                case 'price_asc':
                    aVal = parseFloat(a.getAttribute('data-price') || '0');
                    bVal = parseFloat(b.getAttribute('data-price') || '0');
                    return aVal - bVal;
                case 'price_desc':
                    aVal = parseFloat(a.getAttribute('data-price') || '0');
                    bVal = parseFloat(b.getAttribute('data-price') || '0');
                    return bVal - aVal;
                case 'created_at_desc':
                    // For simplicity, assume newer items have higher IDs
                    aVal = parseInt(a.querySelector('strong')?.textContent || '0');
                    bVal = parseInt(b.querySelector('strong')?.textContent || '0');
                    return bVal - aVal;
                case 'created_at_asc':
                    aVal = parseInt(a.querySelector('strong')?.textContent || '0');
                    bVal = parseInt(b.querySelector('strong')?.textContent || '0');
                    return aVal - bVal;
                default:
                    return 0;
            }
        });

        // Re-append sorted rows
        rowsArray.forEach(row => tbody.appendChild(row));
    }

    function clearFilters() {
        if (searchInput) searchInput.value = '';
        if (statusFilter) statusFilter.value = '';
        if (sortFilter) sortFilter.value = '';
        filterDurations();
        // Reset to original order
    }

    if (searchInput) searchInput.addEventListener('input', filterDurations);
    if (statusFilter) statusFilter.addEventListener('change', filterDurations);
    if (sortFilter) sortFilter.addEventListener('change', function() {
        filterDurations();
        sortDurations();
    });
    if (clearFiltersBtn) clearFiltersBtn.addEventListener('click', clearFilters);

    // Delete Duration Modal Functionality
    $('#deleteDurationModal').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const durationId = button.data('duration-id');
        const durationName = button.data('duration-name');

        const modal = $(this);
        modal.find('#deleteDurationName').text(durationName);
        modal.find('#deleteDurationLink').attr('href', '/admin/durations/delete/' + durationId);
    });

    // Create Duration AJAX Functionality
    const createDurationForm = document.getElementById('createDurationForm');
    const createDurationBtn = document.getElementById('createDurationBtn');
    const createDurationErrors = document.getElementById('createDurationErrors');
    const createDurationSuccess = document.getElementById('createDurationSuccess');

    if (createDurationForm) {
        console.log('Create duration form found');
        createDurationForm.addEventListener('submit', function(e) {
            try {
                console.log('Form submit event triggered');
                e.preventDefault();

                // Clear previous messages
                if (createDurationErrors) createDurationErrors.style.display = 'none';
                if (createDurationSuccess) createDurationSuccess.style.display = 'none';

            // Show loading state
            const spinner = createDurationBtn.querySelector('.spinner-border');
            const icon = createDurationBtn.querySelector('.fas');
            const text = createDurationBtn.lastChild;

            if (spinner) spinner.style.display = 'inline-block';
            if (icon) icon.style.display = 'none';
            if (text) text.textContent = 'Creating...';
            createDurationBtn.disabled = true;                // Clear previous validation errors
                createDurationForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                createDurationForm.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');

                // Prepare form data
                const formData = new FormData(createDurationForm);
                const csrfToken = document.querySelector('input[name="_token"]').value;
                console.log('CSRF token found:', csrfToken ? 'Yes' : 'No');
                console.log('CSRF token value:', csrfToken);

                // Send AJAX request
                fetch('/admin/durations', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    credentials: 'same-origin'
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        return response.text().then(text => {
                            console.log('Error response:', text);
                            try {
                                const err = JSON.parse(text);
                                throw new Error(err.message || `HTTP ${response.status}: ${response.statusText}`);
                            } catch (e) {
                                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                            }
                        });
                    }
                    return response.json();
                })
                .then(data => {
                if (data.success) {
                    // Success - show message and update table
                    if (createDurationSuccess) {
                        createDurationSuccess.textContent = data.message || 'Duration created successfully!';
                        createDurationSuccess.style.display = 'block';
                    }

                    // Add new row to table
                    addDurationToTable(data.data);

                    // Reset form and close modal after delay
                    setTimeout(() => {
                        $('#createDurationModal').modal('hide');
                        createDurationForm.reset();
                        if (createDurationSuccess) createDurationSuccess.style.display = 'none';
                    }, 1500);

                } else {
                    // Validation errors
                    if (data.errors) {
                        displayValidationErrors(data.errors);
                    } else {
                        if (createDurationErrors) {
                            createDurationErrors.textContent = data.message || 'An error occurred while creating the duration.';
                            createDurationErrors.style.display = 'block';
                        }
                    }
                }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (createDurationErrors) {
                        createDurationErrors.textContent = 'An unexpected error occurred: ' + error.message;
                        createDurationErrors.style.display = 'block';
                    }
                })
                .finally(() => {
                    // Reset loading state
                    const spinner = createDurationBtn.querySelector('.spinner-border');
                    const icon = createDurationBtn.querySelector('.fas');
                    const text = createDurationBtn.lastChild;
                    
                    if (spinner) spinner.style.display = 'none';
                    if (icon) icon.style.display = 'inline';
                    if (text) text.textContent = 'Create Duration';
                    createDurationBtn.disabled = false;
                });
            } catch (error) {
                console.error('JavaScript error in form submission:', error);
                if (createDurationErrors) {
                    createDurationErrors.textContent = 'JavaScript error: ' + error.message;
                    createDurationErrors.style.display = 'block';
                }
                
                // Reset loading state
                const spinner = createDurationBtn.querySelector('.spinner-border');
                const icon = createDurationBtn.querySelector('.fas');
                const text = createDurationBtn.lastChild;
                
                if (spinner) spinner.style.display = 'none';
                if (icon) icon.style.display = 'inline';
                if (text) text.textContent = 'Create Duration';
                createDurationBtn.disabled = false;
            }
        });
    } else {
        console.error('Create duration form not found');
    }

    function displayValidationErrors(errors) {
        Object.keys(errors).forEach(field => {
            const input = createDurationForm.querySelector(`[name="${field}"]`);
            if (input) {
                input.classList.add('is-invalid');
                const feedback = input.parentNode.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.textContent = errors[field][0];
                }
            }
        });
    }

    function addDurationToTable(duration) {
        const tbody = document.querySelector('#durations-table tbody');
        const emptyRow = tbody.querySelector('tr td[colspan]');

        // Create new row
        const newRow = document.createElement('tr');
        newRow.setAttribute('data-minutes', duration.minutes);
        newRow.setAttribute('data-duration-type', duration.duration_type);
        newRow.setAttribute('data-price', duration.price);
        newRow.setAttribute('data-is-active', duration.is_active ? '1' : '0');

        newRow.innerHTML = `
            <td>
                <strong>${duration.id}</strong>
            </td>
            <td>
                <div class="d-flex align-items-center">
                    <div class="avatar-circle me-3 bg-primary text-white d-flex align-items-center justify-content-center">
                        <i class="mdi mdi-timer"></i>
                    </div>
                    <div class="fw-bold">${duration.minutes} minutes</div>
                </div>
            </td>
            <td>
                <span class="badge bg-${duration.duration_type === 'general' ? 'info' : 'warning'} text-white">
                    ${duration.duration_type.charAt(0).toUpperCase() + duration.duration_type.slice(1)}
                </span>
            </td>
            <td>
                <div class="fw-bold text-success">UGX ${Number(duration.price).toLocaleString()}</div>
            </td>
            <td>
                ${duration.is_active ?
                    '<span class="badge bg-success text-white"><i class="fa fa-check-circle me-1"></i>Active</span>' :
                    '<span class="badge bg-secondary text-white"><i class="fa fa-pause-circle me-1"></i>Inactive</span>'
                }
            </td>
            <td>
                <small class="text-muted">${new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</small>
            </td>
            <td class="text-center">
                <div class="btn-group" role="group">
                    <a href="/admin/durations/${duration.id}/edit" class="btn btn-outline-secondary btn-sm" title="Edit">
                        <i class="mdi mdi-pencil"></i>
                    </a>
                    <button class="btn btn-outline-danger btn-sm" type="button"
                            data-toggle="modal"
                            data-target="#deleteDurationModal"
                            data-duration-id="${duration.id}"
                            data-duration-name="${duration.minutes} minutes (${duration.duration_type.charAt(0).toUpperCase() + duration.duration_type.slice(1)})"
                            title="Delete">
                        <i class="mdi mdi-delete"></i>
                    </button>
                </div>
            </td>
        `;

        // Remove empty state row if it exists
        if (emptyRow) {
            emptyRow.closest('tr').remove();
        }

        // Add new row to table
        tbody.appendChild(newRow);

        // Update global tableRows array for filtering
        tableRows.push(newRow);
    }
});
</script>
@endpush