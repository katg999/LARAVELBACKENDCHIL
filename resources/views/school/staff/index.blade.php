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
                            <p class="mb-0 opacity-85">Manage school staff and send invitations</p>
                        </div>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-light btn-sm" data-toggle="modal" data-target="#sendInvitationModal">
                                <i class="mdi mdi-email-send me-2"></i>Send Invitation
                            </button>
                            <a href="{{ route('school.staff.invitations') }}" class="btn btn-outline-light btn-sm">
                                <i class="mdi mdi-email-clock me-2"></i>View Invitations
                            </a>
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
                                            <span class="badge bg-{{ $role->slug == 'school-admin' ? 'success' : 'info' }} text-white">
                                                {{ $role->name }}
                                            </span>
                                        @endif
                                    @endforeach
                                </td>
                                <td>{{ $staff->created_at->format('M d, Y') }}</td>
                                <td>
                                    @if($staff->id != auth()->id())
                                        <a href="javascript:void(0)" class="btn btn-sm btn-danger" 
                                           data-toggle="modal" 
                                           data-target="#removeStaffModal"
                                           data-staff-id="{{ $staff->id }}"
                                           data-staff-name="{{ $staff->name }}">
                                            <i class="mdi mdi-delete"></i> Remove
                                        </a>
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
            <form id="sendInvitationForm" action="{{ route('school.staff.invite') }}" method="POST">
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
                        <select class="form-control form-select @error('role') is-invalid @enderror" id="modalRole" name="role" required>
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
                    <button type="submit" class="btn btn-success" id="sendInvitationBtn">
                        <i class="mdi mdi-send me-2"></i>Send Invitation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Remove Staff Confirmation Modal -->
<div class="modal fade" id="removeStaffModal" tabindex="-1" role="dialog" aria-labelledby="removeStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="removeStaffModalLabel">
                    <i class="mdi mdi-alert-circle text-danger me-2"></i>Remove Staff Member
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to remove <strong id="staffName"></strong> from the staff list?</p>
                <p class="text-muted small">This action cannot be undone. The staff member will lose access to the school dashboard.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form id="removeStaffForm" method="GET" action="" style="display: inline;">
                    <button type="submit" class="btn btn-danger">
                        <i class="mdi mdi-delete me-2"></i>Remove Staff Member
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    console.log('Document ready - staff management JS loaded');
    
    // Handle remove staff modal
    $('#removeStaffModal').on('show.bs.modal', function (event) {
        console.log('=== MODAL SHOW EVENT TRIGGERED ===');
        const button = $(event.relatedTarget);
        const staffId = button.data('staff-id');
        const staffName = button.data('staff-name');
        
        console.log('Staff ID:', staffId, 'Staff Name:', staffName);
        
        const modal = $(this);
        modal.find('#staffName').text(staffName);
        
        // Set the form action and staff ID
        const removeUrl = '/staff/remove/' + staffId;
        console.log('Setting form action to:', removeUrl);
        modal.find('#removeStaffForm').attr('action', removeUrl);
        
        console.log('Form action set successfully');
    });

    // Debug form submission
    $('#removeStaffForm').on('submit', function(e) {
        console.log('=== FORM SUBMIT EVENT ===');
        console.log('Form action:', $(this).attr('action'));
        console.log('Form method:', $(this).attr('method'));
        // Don't prevent default - let it submit normally
    });

    $('#sendInvitationForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = $('#sendInvitationBtn');
        const originalText = submitBtn.html();
        
        // Disable button and show loading state
        submitBtn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin me-2"></i>Sending...');
        
        // Clear any previous errors
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.invalid-feedback').remove();
        
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || $('[name="_token"]').val()
            },
            success: function(response) {
                // Close modal
                $('#sendInvitationModal').modal('hide');
                
                // Reset form
                form[0].reset();
                
                // Show success message
                const successAlert = `
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="mdi mdi-check-circle me-2"></i>Invitation sent successfully to mukisaelijah293@gmail.com! They will receive an email with instructions to accept the invitation.
                        <button type="button" class="btn btn-sm btn-outline-success ms-2" data-dismiss="alert" style="border: none; background: transparent;">
                            <i class="mdi mdi-close"></i>
                        </button>
                    </div>
                `;
                $('.container-fluid').prepend(successAlert);
                
                // Refresh the page after a short delay to show updated staff list
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors || {};
                
                // Show field-specific errors
                Object.keys(errors).forEach(function(field) {
                    const input = $(`[name="${field}"]`);
                    const errorDiv = $(`<div class="invalid-feedback">${errors[field][0]}</div>`);
                    
                    input.addClass('is-invalid');
                    input.after(errorDiv);
                });
                
                // Show general error message
                if (!Object.keys(errors).length && xhr.responseJSON?.error) {
                    const errorAlert = `
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="mdi mdi-alert-circle me-2"></i>${xhr.responseJSON.error}
                            <button type="button" class="btn-close" data-dismiss="alert"></button>
                        </div>
                    `;
                    $('.container-fluid').prepend(errorAlert);
                }
            },
            complete: function() {
                // Re-enable button and restore original text
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>

@endsection
