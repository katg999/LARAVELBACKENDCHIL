@extends('layouts.base')

@section('content')
<div class="container-fluid px-3 py-2">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm bg-info">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-white">
                            <h1 class="h3 mb-1 fw-bold">Staff Invitations</h1>
                            <p class="mb-0 opacity-85">Manage pending and sent staff invitations</p>
                        </div>
                        <a href="{{ route('school.staff.index') }}" class="btn btn-light">
                            <i class="mdi mdi-arrow-left me-2"></i>Back to Staff Management
                        </a>
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
                            <div class="stat-label mb-2">Total Invitations</div>
                            <h5 class="mb-0">{{ $invitations->total() }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="stat-label mb-2">Pending</div>
                            <h5 class="mb-0">{{ $invitations->where('accepted', false)->where('expires_at', '>', now())->count() }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="stat-label mb-2">Accepted</div>
                            <h5 class="mb-0">{{ $invitations->where('accepted', true)->count() }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Invitations Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="mdi mdi-email-clock me-2"></i>All Invitations
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
                                    <span class="badge bg-{{ $invitation->role == 'school-admin' ? 'success' : 'info' }} text-white">
                                        {{ ucwords(str_replace('-', ' ', str_replace('school-', '', $invitation->role))) }}
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
                                        <form action="{{ route('school.staff.invitation.revoke', $invitation->id) }}" method="POST"
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