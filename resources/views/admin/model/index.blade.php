@extends('layouts.base')

@section('content')
<div class="container-fluid px-3 py-2">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">{{ ucfirst(str_replace('-', ' ', $modelKey)) }}</h2>
        <div>
            @if($modelKey === 'appointments')
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAppointmentModal">Create</button>
            @elseif($modelKey === 'doctors')
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createDoctorModal">Create Doctor</button>
            @else
                <a href="{{ route('admin.model.create', $modelKey) }}" class="btn btn-primary">Create</a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-responsive">
        @if($modelKey === 'appointments')
            <!-- Appointments Section -->
            <div class="mb-3 d-flex gap-2 align-items-center">
                @php $statuses = \App\Models\Appointment::select('status')->distinct()->pluck('status')->filter()->values(); @endphp
                <form method="GET" class="d-flex gap-2 align-items-center">
                    <input name="q" value="{{ request('q') }}" placeholder="Search reason or id" class="form-control form-control-sm">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All statuses</option>
                        @foreach($statuses as $st)
                            <option value="{{ $st }}" @if(request('status')== $st) selected @endif>{{ ucfirst($st) }}</option>
                        @endforeach
                    </select>
                    @php $doctors = \App\Models\Doctor::pluck('name','id'); @endphp
                    <select name="doctor_id" class="form-select form-select-sm">
                        <option value="">All doctors</option>
                        @foreach($doctors as $id => $label)
                            <option value="{{ $id }}" @if(request('doctor_id') == $id) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
                        <select name="sort" class="form-select form-select-sm">
                        <option value="appointment_time_desc" @if(request('sort')=='appointment_time_desc') selected @endif>Appointment (new → old)</option>
                        <option value="appointment_time_asc" @if(request('sort')=='appointment_time_asc') selected @endif>Appointment (old → new)</option>
                        <option value="created_at_desc" @if(request('sort')=='created_at_desc') selected @endif>Created (new → old)</option>
                        <option value="created_at_asc" @if(request('sort')=='created_at_asc') selected @endif>Created (old → new)</option>
                    </select>
                        <button class="btn btn-sm btn-primary">Filter</button>
                        </form>
                        <form method="GET" action="{{ route('admin.appointments.export') }}" class="d-flex align-items-center ms-2">
                            <input type="hidden" name="q" value="{{ request('q') }}">
                            <input type="hidden" name="status" value="{{ request('status') }}">
                            <input type="hidden" name="doctor_id" value="{{ request('doctor_id') }}">
                            <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                            <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                            <input type="hidden" name="sort" value="{{ request('sort') }}">
                            <button class="btn btn-sm btn-outline-secondary">Export CSV</button>
                        </form>
                </form>
                    <div class="ms-2">
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createAppointmentModal">Create Appointment</button>
                    </div>
            </div>

            <table class="table table-striped table-sm text-dark">
                <form id="bulk-form" method="POST" action="{{ route('admin.appointments.bulk') }}">
                    @csrf
                    <input type="hidden" name="action" id="bulk-action-input" value="">
                    <table class="table table-striped table-sm text-dark">
                <thead>
                    <tr>
                            <th><input type="checkbox" id="select-all"></th>
                        <th>ID</th>
                        <th>When</th>
                        <th>Doctor</th>
                        <th>Institution</th>
                        <th>Patient</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                            <tr>
                                <td><input type="checkbox" name="ids[]" value="{{ $item->id }}" class="row-checkbox"></td>
                                <td>{{ $item->id }}</td>
                            <td>{{ optional($item->appointment_time)->format('Y-m-d H:i') ?? '-' }}</td>
                            <td>{{ $item->doctor->name ?? '-' }}</td>
                            <td>{{ $item->school->name ?? $item->healthFacility->name ?? '-' }}</td>
                            <td>{{ $item->student->name ?? $item->patient->name ?? '-' }}</td>
                            <td>{{ ucfirst($item->status ?? 'unknown') }}</td>
                            <td class="text-nowrap">
                                <a href="{{ route('admin.appointments.edit', $item->id) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                @if($item->status !== 'completed')
                                        <button type="button" class="btn btn-sm btn-success ajax-complete" data-id="{{ $item->id }}">Complete</button>
                                @endif
                                @if($item->status !== 'cancelled')
                                        <button type="button" class="btn btn-sm btn-warning text-dark ajax-cancel" data-id="{{ $item->id }}">Cancel</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

                    <div class="d-flex gap-2 mt-2">
                        <button type="button" class="btn btn-sm btn-success" id="bulk-complete">Mark selected as complete</button>
                        <button type="button" class="btn btn-sm btn-warning" id="bulk-cancel">Cancel selected</button>
                    </div>
                </form>

        @elseif($modelKey === 'doctors')
            <!-- Doctors Section -->
            <div class="mb-3 d-flex justify-content-between flex-wrap">
                <div class="mb-2">
                    <input id="admin-search" class="form-control form-control-sm" placeholder="Search doctors by name, email or specialization" style="width:320px;">
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <form method="GET" action="{{ route('admin.model.index', 'doctors') }}" class="d-flex align-items-center">
                        <input type="hidden" name="export" value="csv">
                        <button class="btn btn-sm btn-outline-secondary">Export CSV</button>
                    </form>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createDoctorModal">Create Doctor</button>
                </div>
            </div>

            <!-- Filters for Doctors -->
            <div class="mb-3">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-auto">
                        <label class="form-label small fw-semibold">Specialization</label>
                        @php $specializations = \App\Models\Doctor::select('specialization')->distinct()->pluck('specialization')->filter()->values(); @endphp
                        <select name="specialization" class="form-select form-select-sm">
                            <option value="">All specializations</option>
                            @foreach($specializations as $spec)
                                <option value="{{ $spec }}" @if(request('specialization') == $spec) selected @endif>{{ $spec }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="form-label small fw-semibold">School</label>
                        @php $schools = \App\Models\School::pluck('name','id'); @endphp
                        <select name="school_id" class="form-select form-select-sm">
                            <option value="">All schools</option>
                            @foreach($schools as $id => $name)
                                <option value="{{ $id }}" @if(request('school_id') == $id) selected @endif>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="form-label small fw-semibold">Health Facility</label>
                        @php $hfs = \App\Models\HealthFacility::pluck('name','id'); @endphp
                        <select name="health_facility_id" class="form-select form-select-sm">
                            <option value="">All facilities</option>
                            @foreach($hfs as $id => $name)
                                <option value="{{ $id }}" @if(request('health_facility_id') == $id) selected @endif>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="form-label small fw-semibold">Sort By</label>
                        <select name="sort" class="form-select form-select-sm">
                            <option value="created_at_desc" @if(request('sort')=='created_at_desc') selected @endif>Created (new → old)</option>
                            <option value="created_at_asc" @if(request('sort')=='created_at_asc') selected @endif>Created (old → new)</option>
                            <option value="name_asc" @if(request('sort')=='name_asc') selected @endif>Name (A → Z)</option>
                            <option value="name_desc" @if(request('sort')=='name_desc') selected @endif>Name (Z → A)</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-sm btn-primary">Filter</button>
                        <a href="{{ route('admin.model.index', 'doctors') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                    </div>
                </form>
            </div>

            <!-- Doctors Cards Grid -->
            <div class="row g-4" id="admin-doctors-cards">
                @forelse($items as $index => $doctor)
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 doctor-card"
                     data-name="{{ strtolower($doctor->name ?? '') }}"
                     data-email="{{ strtolower($doctor->email ?? '') }}"
                     data-specialization="{{ strtolower($doctor->specialization ?? '') }}">
                    <div class="card h-100 shadow-sm border-0" style="background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%); border-radius: 15px; overflow: hidden; transition: all 0.3s ease;">
                        <!-- Card Header with Enhanced Avatar -->
                        <div class="card-header bg-gradient-primary text-white position-relative" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 1.5rem 1rem;">
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle me-4 bg-white text-primary d-flex align-items-center justify-content-center border border-3 border-white" style="width: 60px; height: 60px; border-radius: 50%; font-size: 20px; font-weight: bold; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                                    {{ strtoupper(substr($doctor->name ?? 'D', 0, 1)) }}
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-bold text-white" style="font-size: 1.1rem;">{{ $doctor->display_name ?? '-' }}</h6>
                                    <small class="opacity-85">#{{ $doctor->id }}</small>
                                    @if($doctor->specialization)
                                        <div class="mt-1">
                                            <span class="badge bg-light text-primary small fw-semibold">{{ $doctor->specialization }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <!-- Status Indicator -->
                            <div class="position-absolute top-0 end-0 mt-2 me-2">
                                <span class="badge bg-success rounded-pill px-2 py-1" style="font-size: 0.7rem;">
                                    <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>Active
                                </span>
                            </div>
                        </div>

                        <div class="card-body p-3">
                            <!-- Contact Information Section -->
                            <div class="mb-3">
                                <h6 class="text-muted mb-2" style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <i class="fas fa-address-card me-1"></i>Contact Information
                                </h6>
                                <div class="row g-2">
                                    @if($doctor->email)
                                    <div class="col-12">
                                        <div class="d-flex align-items-center p-2 rounded" style="background: rgba(102, 126, 234, 0.05); border-left: 3px solid #667eea;">
                                            <i class="fas fa-envelope text-primary me-2" style="width: 16px;"></i>
                                            <a href="mailto:{{ $doctor->email }}" class="text-decoration-none small text-dark fw-medium">{{ $doctor->email }}</a>
                                        </div>
                                    </div>
                                    @endif

                                    @if($doctor->contact)
                                    <div class="col-12">
                                        <div class="d-flex align-items-center p-2 rounded" style="background: rgba(40, 167, 69, 0.05); border-left: 3px solid #28a745;">
                                            <i class="fas fa-phone text-success me-2" style="width: 16px;"></i>
                                            <a href="tel:{{ $doctor->contact }}" class="text-decoration-none small text-dark fw-medium">{{ $doctor->contact }}</a>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Affiliation Section -->
                            <div class="mb-3">
                                <h6 class="text-muted mb-2" style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <i class="fas fa-building me-1"></i>Affiliations
                                </h6>
                                <div class="row g-2">
                                    @if($doctor->school)
                                    <div class="col-12">
                                        <div class="d-flex align-items-center p-2 rounded" style="background: rgba(23, 162, 184, 0.05); border-left: 3px solid #17a2b8;">
                                            <i class="fas fa-school text-info me-2" style="width: 16px;"></i>
                                            <span class="badge bg-info small text-white fw-medium">{{ Str::limit($doctor->school->name, 25) }}</span>
                                        </div>
                                    </div>
                                    @endif

                                    @if($doctor->healthFacility)
                                    <div class="col-12">
                                        <div class="d-flex align-items-center p-2 rounded" style="background: rgba(255, 193, 7, 0.05); border-left: 3px solid #ffc107;">
                                            <i class="fas fa-hospital text-warning me-2" style="width: 16px;"></i>
                                            <span class="badge bg-warning text-dark small fw-medium">{{ Str::limit($doctor->healthFacility->name, 25) }}</span>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Meeting Information -->
                            @if($doctor->meeting_slug)
                            <div class="mb-3">
                                <h6 class="text-muted mb-2" style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <i class="fas fa-video me-1"></i>Meeting Room
                                </h6>
                                <div class="p-2 rounded bg-light border">
                                    <code class="small text-primary fw-medium">{{ $doctor->meeting_slug }}</code>
                                    <button class="btn btn-sm btn-outline-primary ms-2" onclick="navigator.clipboard.writeText('https://meet.jit.si/{{ $doctor->meeting_slug }}')" style="font-size: 0.7rem; padding: 0.2rem 0.5rem;">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            @endif

                            <!-- Metadata -->
                            <div class="pt-2 border-top border-light">
                                <small class="text-muted d-block">
                                    <i class="fas fa-calendar-plus me-1"></i>
                                    Created {{ optional($doctor->created_at)->format('M j, Y') ?? '-' }}
                                </small>
                                @if($doctor->updated_at && $doctor->updated_at != $doctor->created_at)
                                <small class="text-muted d-block">
                                    <i class="fas fa-edit me-1"></i>
                                    Updated {{ optional($doctor->updated_at)->format('M j, Y') ?? '-' }}
                                </small>
                                @endif
                            </div>
                        </div>

                        <!-- Enhanced Card Footer -->
                        <div class="card-footer bg-white border-0 p-3">
                            <div class="row g-2">
                                <div class="col-auto">
                                    <a href="{{ route('profile.show', $doctor->id) }}" class="btn btn-outline-info btn-sm fw-semibold px-3" title="View Profile" style="border-radius: 8px;">
                                        <i class="fas fa-eye me-1"></i>View
                                    </a>
                                </div>
                                <div class="col-auto">
                                    <a href="{{ route('admin.doctors.edit', $doctor->id) }}" class="btn btn-outline-secondary btn-sm fw-semibold px-3" title="Edit" style="border-radius: 8px;">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </a>
                                </div>
                                <div class="col-auto">
                                    <div class="dropdown">
                                        <button class="btn btn-outline-primary btn-sm dropdown-toggle fw-semibold px-3" type="button" data-bs-toggle="dropdown" style="border-radius: 8px;">
                                            <i class="fas fa-ellipsis-h"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow">
                                            <li>
                                                <form action="{{ route('admin.doctors.send-login', $doctor->id) }}" method="POST" style="display:inline-block">
                                                    @csrf
                                                    <button class="dropdown-item" type="submit" onclick="return confirm('Send login OTP to this doctor?')" style="padding: 0.5rem 1rem;">
                                                        <i class="fas fa-envelope me-2 text-primary"></i>Send Login Link
                                                    </button>
                                                </form>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('admin.doctors.destroy', $doctor->id) }}" method="POST" style="display:inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="dropdown-item text-danger" type="submit" onclick="return confirm('Delete this doctor?')" style="padding: 0.5rem 1rem;">
                                                        <i class="fas fa-trash me-2"></i>Delete
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="card border-0">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-user-md fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">No doctors found</h5>
                            <p class="text-muted">Try adjusting your filters or create a new doctor.</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createDoctorModal">
                                <i class="fas fa-plus me-2"></i>Create Doctor
                            </button>
                        </div>
                    </div>
                </div>
                @endforelse
            </div>

        @else
            <!-- Generic Models Section -->
            <table class="table table-striped text-dark">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Preview</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td style="max-width:420px;word-break:break-word;">{{ json_encode($item->toArray()) }}</td>
                        <td>{{ $item->created_at ?? '-' }}</td>
                        <td>
                            <a href="{{ route('admin.model.edit', [$modelKey, $item->id]) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form action="{{ route('admin.model.destroy', [$modelKey, $item->id]) }}" method="POST" style="display:inline-block" onsubmit="return confirm('Delete this item?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="mt-3">{{ $items->links() }}</div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    // select all checkbox
    const selectAll = document.getElementById('select-all');
    if (selectAll) {
        selectAll.addEventListener('change', function(){
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = selectAll.checked);
        });
    }

    // AJAX actions
    function ajaxPatch(url, cb) {
        fetch(url, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        }).then(r => r.json()).then(cb).catch(err => console.error(err));
    }

    document.querySelectorAll('.ajax-complete').forEach(btn => {
        btn.addEventListener('click', function(){
            if (!confirm('Mark appointment as completed?')) return;
            const id = this.dataset.id;
            ajaxPatch('/appointments/' + id + '/complete', function(res){ location.reload(); });
        });
    });

    document.querySelectorAll('.ajax-cancel').forEach(btn => {
        btn.addEventListener('click', function(){
            if (!confirm('Cancel this appointment?')) return;
            const id = this.dataset.id;
            ajaxPatch('/appointments/' + id + '/cancel', function(res){ location.reload(); });
        });
    });

    // bulk actions
    document.getElementById('bulk-complete')?.addEventListener('click', function(){
        if (!confirm('Mark selected appointments as completed?')) return;
        document.getElementById('bulk-action-input').value = 'complete';
        document.getElementById('bulk-form').submit();
    });
    document.getElementById('bulk-cancel')?.addEventListener('click', function(){
        if (!confirm('Cancel selected appointments?')) return;
        document.getElementById('bulk-action-input').value = 'cancel';
        document.getElementById('bulk-form').submit();
    });
});
</script>
<!-- Create Doctor Modal -->
@if($modelKey === 'doctors')
<div class="modal fade" id="createDoctorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Doctor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.model.store', 'doctors') }}" enctype="multipart/form-data">
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

                    <div class="row g-2">
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Name</label>
                            <input name="name" class="form-control" required value="{{ old('name') }}">
                            @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Email</label>
                            <input name="email" type="email" class="form-control" value="{{ old('email') }}">
                            @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Specialization</label>
                            <input name="specialization" class="form-control" value="{{ old('specialization') }}">
                            @error('specialization')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Contact</label>
                            <input name="contact" class="form-control" value="{{ old('contact') }}">
                            @error('contact')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label">School</label>
                            @php $schools = \App\Models\School::pluck('name','id'); @endphp
                            <select name="school_id" class="form-select form-control">
                                <option value="">-- none --</option>
                                @foreach($schools as $id => $label)
                                    <option value="{{ $id }}" @if(old('school_id') == $id) selected @endif>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Health Facility</label>
                            @php $hfs = \App\Models\HealthFacility::pluck('name','id'); @endphp
                            <select name="health_facility_id" class="form-select form-control">
                                <option value="">-- none --</option>
                                @foreach($hfs as $id => $label)
                                    <option value="{{ $id }}" @if(old('health_facility_id') == $id) selected @endif>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-2">
                            <label class="form-label">Profile Image</label>
                            <input name="file_url" type="file" accept="image/*" class="form-control">
                            @error('file_url')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 mb-2">
                            <label class="form-label">Meeting Slug</label>
                            <input name="meeting_slug" class="form-control" value="{{ old('meeting_slug', $generatedSlug ?? '') }}" readonly>

                            <label class="form-label small mt-2">Meeting URL</label>
                            <div class="input-group">
                                <input id="modal-meeting-url" type="text" class="form-control" value="{{ 'https://meet.jit.si/' . (old('meeting_slug', $generatedSlug ?? '')) }}" readonly>
                                <button type="button" id="copy-modal-meeting-url" class="btn btn-outline-secondary">Copy</button>
                            </div>
                            <div id="modal-copy-feedback" class="small text-success mt-1" style="display:none">Copied to clipboard</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
<div class="modal fade" id="createAppointmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="create-appointment-errors" class="alert alert-danger d-none"></div>
                <form id="create-appointment-form">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6 mb-2">
                            <label class="form-label fw-semibold">Doctor <sup class="text-danger">*</sup></label>
                            <select name="doctor_id" class="form-select form-select-sm text-dark" required aria-label="Select doctor">
                                <option value="" disabled selected>-- select doctor --</option>
                                @foreach(\App\Models\Doctor::pluck('name','id') as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-2">
                            <label class="form-label fw-semibold">Duration (mins) <sup class="text-danger">*</sup></label>
                            <select name="duration" class="form-select form-select-sm text-dark" required aria-label="Duration in minutes">
                                <option value="" disabled selected>-- choose duration --</option>
                                <option value="15">15</option>
                                <option value="20">20</option>
                                <option value="30">30</option>
                                <option value="45">45</option>
                                <option value="60">60</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-2">
                            <label class="form-label fw-semibold">Appointment Time <span class="text-danger">*</span></label>
                            <input name="appointment_time" type="datetime-local" class="form-control form-control-sm text-dark" required>
                            <div class="form-text">Times are in your local timezone.</div>
                        </div>

                        <div class="col-md-6 mb-2">
                            <label class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                            <input name="reason" class="form-control form-control-sm text-dark" placeholder="Brief reason for visit" required>
                        </div>

                        <div class="col-md-6 mb-2">
                            <label class="form-label fw-semibold">School (optional)</label>
                            <select name="school_id" class="form-select form-select-sm text-dark">
                                <option value="" selected>-- none --</option>
                                @foreach(\App\Models\School::pluck('name','id') as $id => $label)
                                    <option value="{{ $id }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-2">
                            <label class="form-label fw-semibold">Student (optional)</label>
                            <select name="student_id" class="form-select form-select-sm text-dark">
                                <option value="" selected>-- none --</option>
                                @foreach(\App\Models\Student::pluck('name','id') as $id => $label)
                                    <option value="{{ $id }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-2">
                            <label class="form-label fw-semibold">Health Facility (optional)</label>
                            <select name="health_facility_id" class="form-select form-select-sm text-dark">
                                <option value="" selected>-- none --</option>
                                @foreach(\App\Models\HealthFacility::pluck('name','id') as $id => $label)
                                    <option value="{{ $id }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-2">
                            <label class="form-label fw-semibold">Patient (optional)</label>
                            <select name="patient_id" class="form-select form-select-sm text-dark">
                                <option value="" selected>-- none --</option>
                                @foreach(\App\Models\Patient::pluck('name','id') as $id => $label)
                                    <option value="{{ $id }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" id="create-appointment-submit" class="btn btn-primary">Create</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const submit = document.getElementById('create-appointment-submit');
    submit?.addEventListener('click', function(){
        const form = document.getElementById('create-appointment-form');
        const data = new FormData(form);
        fetch('/appointments', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
            body: data
        }).then(async res => {
            if (res.status === 422) {
                const json = await res.json();
                const errors = json.errors || {};
                const el = document.getElementById('create-appointment-errors');
                el.style.display = 'block';
                el.innerHTML = Object.values(errors).map(v => '<div>'+v[0]+'</div>').join('');
                return;
            }
            if (!res.ok) {
                const txt = await res.text();
                alert('Failed: ' + txt);
                return;
            }

            // success — close modal and reload
            var myModalEl = document.getElementById('createAppointmentModal');
            var modal = bootstrap.Modal.getInstance(myModalEl);
            modal.hide();
            location.reload();
        }).catch(err => { alert('Error: ' + err); });
    });
});
</script>
@endpush

@push('scripts')
@if($modelKey === 'doctors')
<script>
document.addEventListener('DOMContentLoaded', function(){
    const search = document.getElementById('admin-search');
    if (!search) return;
    const cards = Array.from(document.querySelectorAll('#admin-doctors-cards .doctor-card'));
    search.addEventListener('input', function(){
        const q = search.value.trim().toLowerCase();
        cards.forEach(card => {
            const name = card.getAttribute('data-name') || '';
            const email = card.getAttribute('data-email') || '';
            const specialization = card.getAttribute('data-specialization') || '';
            if (!q || name.includes(q) || email.includes(q) || specialization.includes(q)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    // If there were validation errors or old input for creating a doctor, open the modal
    @if($errors->any() && old())
        var myModal = new bootstrap.Modal(document.getElementById('createDoctorModal'));
        myModal.show();
    @endif
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const modalCopy = document.getElementById('copy-modal-meeting-url');
    if (modalCopy) {
        modalCopy.addEventListener('click', function(){
            const target = document.getElementById('modal-meeting-url');
            if (!target) return;
            target.select();
            target.setSelectionRange(0, 99999);
            try { document.execCommand('copy'); } catch(e) { navigator.clipboard && navigator.clipboard.writeText && navigator.clipboard.writeText(target.value); }
            const fb = document.getElementById('modal-copy-feedback');
            if (fb) { fb.style.display = 'block'; setTimeout(() => fb.style.display = 'none', 2000); }
        });
    }
});
</script>
@endif
@endpush

@if($modelKey === 'health-facilities')
@endif
@endif
