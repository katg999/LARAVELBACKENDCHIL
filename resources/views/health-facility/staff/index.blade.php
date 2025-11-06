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
                            <h1 class="h3 mb-1 fw-bold">Staff Management</h1>
                            <p class="mb-0 opacity-85">Manage health facility staff and send invitations</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="mdi mdi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="mdi mdi-alert-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="stat-label mb-2">Total Staff</div>
                            <h5 class="mb-0">{{ $staffMembers->count() }}</h5>
                        </div>
                        <i class="mdi mdi-account-group icon-xl text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="stat-label mb-2">Admins</div>
                            <h5 class="mb-0">{{ $healthFacility->admins()->count() }}</h5>
                        </div>
                        <i class="mdi mdi-shield-account icon-xl text-success"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="stat-label mb-2">Medical Personnel</div>
                            <h5 class="mb-0">{{ $healthFacility->medicalPersonnel()->count() }}</h5>
                        </div>
                        <i class="mdi mdi-doctor icon-xl text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Send Invitation Card -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="mdi mdi-email-send me-2"></i>Send Invitation
            </h5>
        </div>
        <div class="card-body">
            <form action="{{ route('health-facility.staff.invite') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-5">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                               id="email" name="email" required value="{{ old('email') }}">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="health-facility-admin" {{ old('role') == 'health-facility-admin' ? 'selected' : '' }}>
                                Admin (Can manage staff)
                            </option>
                            <option value="health-facility-medical-personnel" {{ old('role') == 'health-facility-medical-personnel' ? 'selected' : '' }}>
                                Medical Personnel
                            </option>
                        </select>
                        @error('role')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="mdi mdi-send me-2"></i>Send Invitation
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Current Staff Members -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="mdi mdi-account-multiple me-2"></i>Current Staff Members
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($staffMembers as $staff)
                            <tr>
                                <td>{{ $staff->name }}</td>
                                <td>{{ $staff->email }}</td>
                                <td>
                                    @foreach($staff->roles as $role)
                                        @if(in_array($role->slug, ['health-facility-admin', 'health-facility-medical-personnel']))
                                            <span class="badge bg-{{ $role->slug == 'health-facility-admin' ? 'success' : 'info' }}">
                                                {{ $role->name }}
                                            </span>
                                        @endif
                                    @endforeach
                                </td>
                                <td>{{ $staff->created_at->format('M d, Y') }}</td>
                                <td>
                                    @if($staff->id != auth()->id())
                                        <form action="{{ route('health-facility.staff.remove', $staff->id) }}" method="POST" 
                                              onsubmit="return confirm('Are you sure you want to remove this staff member?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="mdi mdi-delete"></i> Remove
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted small">You</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    No staff members yet
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pending Invitations -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="mdi mdi-email-clock me-2"></i>Pending Invitations
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Invited By</th>
                            <th>Sent Date</th>
                            <th>Expires</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invitations as $invitation)
                            <tr>
                                <td>{{ $invitation->email }}</td>
                                <td>
                                    <span class="badge bg-{{ $invitation->role == 'health-facility-admin' ? 'success' : 'info' }}">
                                        {{ ucwords(str_replace('-', ' ', str_replace('health-facility-', '', $invitation->role))) }}
                                    </span>
                                </td>
                                <td>{{ $invitation->inviter->name }}</td>
                                <td>{{ $invitation->created_at->format('M d, Y') }}</td>
                                <td>{{ $invitation->expires_at->format('M d, Y') }}</td>
                                <td>
                                    @if($invitation->accepted)
                                        <span class="badge bg-success">Accepted</span>
                                    @elseif($invitation->isExpired())
                                        <span class="badge bg-danger">Expired</span>
                                    @else
                                        <span class="badge bg-warning">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!$invitation->accepted && !$invitation->isExpired())
                                        <form action="{{ route('health-facility.staff.invitation.revoke', $invitation->id) }}" method="POST"
                                              onsubmit="return confirm('Are you sure you want to revoke this invitation?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="mdi mdi-cancel"></i> Revoke
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No invitations sent yet
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($invitations->hasPages())
            <div class="card-footer">
                {{ $invitations->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
