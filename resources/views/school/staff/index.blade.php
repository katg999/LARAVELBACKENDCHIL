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
                            <h1 class="h3 mb-1 fw-bold">Staff Management</h1>
                            <p class="mb-0 opacity-85">Manage school staff and send invitations</p>
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

    <!-- Action Buttons -->
    <div class="row mb-4">
        <div class="col-12">
            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#sendInvitationModal">
                <i class="mdi mdi-email-send me-2"></i>Send Invitation
            </button>
            <a href="{{ route('school.staff.invitations') }}" class="btn btn-info ms-2">
                <i class="mdi mdi-email-clock me-2"></i>View Invitations
            </a>
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
                                        @if(in_array($role->slug, ['school-admin', 'school-staff']))
                                            <span class="badge bg-{{ $role->slug == 'school-admin' ? 'success' : 'info' }}">
                                                {{ $role->name }}
                                            </span>
                                        @endif
                                    @endforeach
                                </td>
                                <td>{{ $staff->created_at->format('M d, Y') }}</td>
                                <td>
                                    @if($staff->id != auth()->id())
                                        <form action="{{ route('school.staff.remove', $staff->id) }}" method="POST" 
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
</div>

<!-- Send Invitation Modal -->
<div class="modal fade" id="sendInvitationModal" tabindex="-1" role="dialog" aria-labelledby="sendInvitationModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sendInvitationModalLabel">
                    <i class="mdi mdi-email-send me-2"></i>Send Staff Invitation
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('school.staff.invite') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="modalEmail" class="form-label">Email Address</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                               id="modalEmail" name="email" required value="{{ old('email') }}">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Enter the email address of the person you want to invite</div>
                    </div>
                    <div class="mb-3">
                        <label for="modalRole" class="form-label">Role</label>
                        <select class="form-select @error('role') is-invalid @enderror" id="modalRole" name="role" required>
                            <option value="">Select Role</option>
                            <option value="school-admin" {{ old('role') == 'school-admin' ? 'selected' : '' }}>
                                Admin (Can manage staff)
                            </option>
                            <option value="school-staff" {{ old('role') == 'school-staff' ? 'selected' : '' }}>
                                Staff Member
                            </option>
                        </select>
                        @error('role')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Choose the role for the new staff member</div>
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

@endsection
