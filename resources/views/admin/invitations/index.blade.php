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
                            <h1 class="h3 mb-1 fw-bold">Invitation Management</h1>
                            <p class="mb-0 opacity-85">View and manage all staff invitations for schools and health facilities</p>
                        </div>
                        <div class="btn-group">
                            <a href="{{ route('admin.schools.index') }}" class="btn btn-light btn-sm">
                                <i class="mdi mdi-school me-2"></i>Schools
                            </a>
                            <a href="{{ route('admin.health-facilities.index') }}" class="btn btn-light btn-sm">
                                <i class="mdi mdi-hospital-building me-2"></i>Health Facilities
                            </a>
                        </div>
                    </div>
                </div>
            </div>
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

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.invitations.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control" onchange="this.form.submit()">
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Invitations</option>
                        <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="accepted" {{ $status === 'accepted' ? 'selected' : '' }}>Accepted</option>
                        <option value="expired" {{ $status === 'expired' ? 'selected' : '' }}>Expired</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-control" onchange="this.form.submit()">
                        <option value="all" {{ $type === 'all' ? 'selected' : '' }}>All Types</option>
                        <option value="school" {{ $type === 'school' ? 'selected' : '' }}>Schools Only</option>
                        <option value="health-facility" {{ $type === 'health-facility' ? 'selected' : '' }}>Health Facilities Only</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="mdi mdi-filter me-2"></i>Apply Filters
                    </button>
                    <a href="{{ route('admin.invitations.index') }}" class="btn btn-secondary">
                        <i class="mdi mdi-refresh me-2"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Invitations Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="mdi mdi-email-multiple me-2"></i>All Invitations ({{ $invitations->count() }})
            </h5>
        </div>
        <div class="card-body p-0">
            @if($invitations->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Type</th>
                                <th>Entity</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Invited By</th>
                                <th>Invited At</th>
                                <th>Expires At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invitations as $invitation)
                                <tr>
                                    <td>
                                        @if($invitation['type'] === 'school')
                                            <span class="badge bg-info">
                                                <i class="mdi mdi-school me-1"></i>School
                                            </span>
                                        @else
                                            <span class="badge bg-success">
                                                <i class="mdi mdi-hospital-building me-1"></i>Health Facility
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $invitation['entity_name'] }}</strong>
                                    </td>
                                    <td>
                                        <i class="mdi mdi-email me-1"></i>{{ $invitation['email'] }}
                                    </td>
                                    <td>
                                        <span class="badge bg-success">{{ $invitation['role_label'] }}</span>
                                    </td>
                                    <td>
                                        @if($invitation['accepted'])
                                            <span class="badge bg-success">
                                                <i class="mdi mdi-check-circle me-1"></i>Accepted
                                            </span>
                                            <br><small class="text-muted">{{ $invitation['accepted_at']->format('M d, Y') }}</small>
                                        @elseif($invitation['is_expired'])
                                            <span class="badge bg-danger">
                                                <i class="mdi mdi-clock-alert me-1"></i>Expired
                                            </span>
                                        @else
                                            <span class="badge bg-warning text-dark">
                                                <i class="mdi mdi-clock-outline me-1"></i>Pending
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ $invitation['invited_by'] }}</td>
                                    <td>{{ $invitation['invited_at']->format('M d, Y H:i') }}</td>
                                    <td>
                                        {{ $invitation['expires_at']->format('M d, Y') }}
                                        @if(!$invitation['accepted'] && !$invitation['is_expired'])
                                            <br><small class="text-muted">({{ $invitation['expires_at']->diffForHumans() }})</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <!-- Copy Link Button -->
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="copyToClipboard('{{ $invitation['url'] }}')" 
                                                    title="Copy Invitation Link">
                                                <i class="mdi mdi-content-copy"></i>
                                            </button>

                                            @if(!$invitation['accepted'])
                                                <!-- Resend Button -->
                                                <form action="{{ route('admin.invitations.resend', [$invitation['type'], $invitation['id']]) }}" 
                                                      method="POST" 
                                                      class="d-inline">
                                                    @csrf
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-outline-success" 
                                                            title="Resend Invitation">
                                                        <i class="mdi mdi-send"></i>
                                                    </button>
                                                </form>

                                                <!-- Revoke Button -->
                                                <form action="{{ route('admin.invitations.revoke', [$invitation['type'], $invitation['id']]) }}" 
                                                      method="POST" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to revoke this invitation?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-outline-danger" 
                                                            title="Revoke Invitation">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="mdi mdi-email-off" style="font-size: 48px; color: #ccc;"></i>
                    <p class="text-muted mt-3">No invitations found matching your filters.</p>
                    <a href="{{ route('admin.invitations.index') }}" class="btn btn-primary">
                        <i class="mdi mdi-refresh me-2"></i>View All Invitations
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() {
            // Show success feedback
            const originalText = event.target.closest('button').innerHTML;
            event.target.closest('button').innerHTML = '<i class="mdi mdi-check"></i>';
            event.target.closest('button').classList.remove('btn-outline-primary');
            event.target.closest('button').classList.add('btn-success');
            
            setTimeout(function() {
                event.target.closest('button').innerHTML = originalText;
                event.target.closest('button').classList.remove('btn-success');
                event.target.closest('button').classList.add('btn-outline-primary');
            }, 2000);
        }).catch(function(err) {
            alert('Failed to copy: ' + err);
        });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            alert('Invitation link copied to clipboard!');
        } catch (err) {
            alert('Failed to copy link');
        }
        document.body.removeChild(textArea);
    }
}
</script>
@endpush
