@extends('layouts.base')

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h3 class="m-0">Book Doctor</h3>
        <div class="d-flex gap-2">
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#bookDoctorModal">
                <i class="mdi mdi-calendar-plus me-2"></i> New Appointment
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-light text-dark">{{ session('success') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-3">Recent Appointments</h5>
            @if(isset($appointments) && $appointments->count())
            <div class="table-responsive">
                <table class="table table-striped text-dark">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Time</th>
                            <th>Duration</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($appointments as $appt)
                        <tr>
                            <td>{{ $appt->id }}</td>
                            <td>{{ optional($appt->patient)->name ?? '—' }}</td>
                            <td>{{ optional($appt->doctor)->name ? 'Dr. ' . $appt->doctor->name : '—' }}</td>
                            <td>{{ optional($appt->appointment_time)->format('D, M j, Y g:i A') }}</td>
                            <td>{{ $appt->duration ? $appt->duration->minutes . ' mins' : '—' }}</td>
                            <td>{{ $appt->duration ? number_format($appt->duration->getPriceForDoctor($appt->doctor), 0) . ' UGX' : '—' }}</td>
                            <td>
                                <span class="badge bg-{{ 
                                    $appt->status == 'confirmed' ? 'success' : 
                                    ($appt->status == 'awaiting_payment' ? 'warning' : 
                                    ($appt->status == 'awaiting_approval' ? 'info' : 
                                    ($appt->status == 'cancelled' ? 'danger' : 'secondary'))) 
                                }} text-white">
                                    {{ ucfirst(str_replace('_', ' ', $appt->status)) }}
                                </span>
                            </td>
                            <td>
                                @if($appt->status === 'awaiting_approval')
                                    <button type="button" class="btn btn-success btn-sm btn-approve" 
                                            data-appointment-id="{{ $appt->id }}"
                                            data-doctor-name="{{ optional($appt->doctor)->name ? 'Dr. ' . $appt->doctor->name : 'N/A' }}"
                                            data-toggle="modal" 
                                            data-target="#approveAppointmentModal">
                                        <i class="fas fa-check me-1"></i>Approve
                                    </button>
                                @elseif($appt->status === 'awaiting_payment')
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('payment.appointment.pay', $appt) }}" class="btn btn-sm btn-primary">
                                            <i class="mdi mdi-credit-card me-1"></i> Pay
                                        </a>
                                        <button class="btn btn-sm btn-warning cancel-appointment" 
                                                data-appointment-id="{{ $appt->id }}"
                                                data-toggle="modal" 
                                                data-target="#cancelAppointmentModal">
                                            <i class="mdi mdi-cancel me-1"></i> Cancel
                                        </button>
                                    </div>
                                @elseif($appt->status === 'cancelled')
                                    <button class="btn btn-sm btn-danger delete-appointment" 
                                            data-appointment-id="{{ $appt->id }}"
                                            data-toggle="modal" 
                                            data-target="#deleteAppointmentModal">
                                        <i class="mdi mdi-delete me-1"></i> Delete
                                    </button>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
                <div class="alert alert-light text-dark mb-0">No appointments yet.</div>
            @endif
        </div>
    </div>

    {{-- Book Doctor Modal --}}
    <div class="modal fade" id="bookDoctorModal" tabindex="-1" role="dialog" aria-labelledby="bookDoctorModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bookDoctorModalLabel">Book Appointment</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="appointment-form" action="{{ route('appointments.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="health_facility_id" value="{{ $healthFacility->id }}">
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Patient</label>
                                <select name="patient_id" class="form-select form-control" required>
                                    <option value="">Select Patient</option>
                                    @foreach($patients as $patient)
                                        <option value="{{ $patient->id }}">{{ $patient->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date & Time</label>
                                <input type="datetime-local" id="appointment_time" name="appointment_time" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Doctor</label>
                                <select id="doctor_id" name="doctor_id" class="form-select form-control" required>
                                    <option value="">Select Doctor</option>
                                    @foreach($doctors as $doc)
                                        <option value="{{ $doc->id }}" data-specialization="{{ $doc->specialization }}">Dr. {{ $doc->name }} ({{ $doc->specialization }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Duration</label>
                                <select name="duration_id" class="form-select form-control" required>
                                    <option value="">Select Duration</option>
                                    @foreach(\App\Models\Duration::active()->get() as $duration)
                                        <option value="{{ $duration->id }}" data-duration-id="{{ $duration->id }}" data-type="{{ $duration->duration_type }}" data-price="{{ $duration->price }}">
                                            {{ $duration->minutes }} minutes - {{ ucfirst($duration->duration_type) }}: UGX {{ number_format($duration->price, 0) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Reason</label>
                                <input type="text" name="reason" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm">Book</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Cancel Appointment Modal --}}
    <div class="modal fade" id="cancelAppointmentModal" tabindex="-1" role="dialog" aria-labelledby="cancelAppointmentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelAppointmentModalLabel">Cancel Appointment</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel this appointment? This action cannot be undone.</p>
                    <div class="appointment-details">
                        <strong>Patient:</strong> <span id="cancel-patient-name"></span><br>
                        <strong>Doctor:</strong> <span id="cancel-doctor-name"></span><br>
                        <strong>Time:</strong> <span id="cancel-appointment-time"></span><br>
                        <strong>Status:</strong> <span class="badge badge-warning">Awaiting Payment</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <form id="cancelForm" method="POST" style="display: inline;">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="appointment_id" id="cancelAppointmentId">
                        <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Keep Appointment</button>
                        <button type="submit" class="btn btn-warning btn-sm">Cancel Appointment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Appointment Modal --}}
    <div class="modal fade" id="deleteAppointmentModal" tabindex="-1" role="dialog" aria-labelledby="deleteAppointmentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteAppointmentModalLabel">Delete Appointment</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this cancelled appointment? This action cannot be undone.</p>
                    <div class="appointment-details">
                        <strong>Patient:</strong> <span id="delete-patient-name"></span><br>
                        <strong>Doctor:</strong> <span id="delete-doctor-name"></span><br>
                        <strong>Time:</strong> <span id="delete-appointment-time"></span><br>
                        <strong>Status:</strong> <span class="badge badge-danger">Cancelled</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <form id="deleteForm" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="appointment_id" id="deleteAppointmentId">
                        <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger btn-sm">Delete Appointment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Approve Appointment Modal --}}
    <div class="modal fade" id="approveAppointmentModal" tabindex="-1" role="dialog" aria-labelledby="approveAppointmentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="approveAppointmentModalLabel">Approve Appointment</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to approve this completed appointment? This will mark it as officially completed and notify the doctor.</p>
                    <div class="appointment-details">
                        <strong>Patient:</strong> <span id="approve-patient-name"></span><br>
                        <strong>Doctor:</strong> <span id="approve-doctor-name"></span><br>
                        <strong>Time:</strong> <span id="approve-appointment-time"></span><br>
                        <strong>Status:</strong> <span class="badge badge-info">Awaiting Approval</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success btn-sm" id="confirmApproveBtn">
                        <i class="fas fa-check me-1"></i>Approve Appointment
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const appointmentForm = document.getElementById('appointment-form');
    const modal = document.getElementById('bookDoctorModal');
    const modalBody = modal.querySelector('.modal-body');
    let appointmentToDelete = null;

    // Initialize Select2 when modal opens
    $('#bookDoctorModal').on('shown.bs.modal', function () {
        $("#doctor_id").select2({
            width: '100%',
            dropdownParent: $('#bookDoctorModal'),
            placeholder: 'Select a doctor',
            allowClear: true
        });
    });

    // Destroy Select2 when modal closes to prevent duplicates
    $('#bookDoctorModal').on('hidden.bs.modal', function () {
        $("#doctor_id").select2('destroy');
    });

    // Duration filtering code...

    // Handle form submission via AJAX
    appointmentForm.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission

        const formData = new FormData(appointmentForm);

        fetch(appointmentForm.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Success: close modal, show success message, refresh appointments table
                $(modal).modal('hide');
                showAlert('Appointment booked successfully!', 'success');
                refreshAppointmentsTable();
                appointmentForm.reset(); // Reset form
            } else {
                // Validation errors
                displayErrors(data.errors);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred while booking the appointment.', 'danger');
        });
    });

    // Handle cancel appointment button clicks
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('cancel-appointment') || e.target.closest('.cancel-appointment')) {
            e.preventDefault();
            const button = e.target.classList.contains('cancel-appointment') ? e.target : e.target.closest('.cancel-appointment');
            const appointmentId = button.getAttribute('data-appointment-id');
            
            // Find the appointment row to get details
            const row = button.closest('tr');
            const patientName = row.cells[1].textContent;
            const doctorName = row.cells[2].textContent;
            const appointmentTime = row.cells[3].textContent;
            
            // Populate cancel modal
            document.getElementById('cancel-patient-name').textContent = patientName;
            document.getElementById('cancel-doctor-name').textContent = doctorName;
            document.getElementById('cancel-appointment-time').textContent = appointmentTime;
            
            // Set the cancel URL and appointment ID
            const cancelUrl = '{{ url("/appointments") }}/' + appointmentId + '/cancel';
            document.getElementById('cancelForm').action = cancelUrl;
            document.getElementById('cancelAppointmentId').value = appointmentId;
        }
    });

    // Handle cancel form submission
    document.getElementById('cancelForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        const appointmentId = document.getElementById('cancelAppointmentId').value;

        // Submit the form
        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => {
            // Check if response is OK
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }

            // Try to parse as JSON, but handle non-JSON responses
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return response.json();
            } else {
                // If not JSON, treat as success (appointment was cancelled)
                return { success: true, message: 'Appointment cancelled successfully' };
            }
        })
        .then(data => {
            if (data.success) {
                // Force close modal first
                $('#cancelAppointmentModal').modal('hide');
                // Small delay to ensure modal is closed
                setTimeout(() => {
                    showAlert('Appointment cancelled successfully!', 'warning');
                    // Refresh the page to show updated data
                    location.reload();
                }, 300);
            } else {
                showAlert(data.message || 'Failed to cancel appointment.', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // If we get here, check if the appointment status was actually updated
            const row = document.querySelector(`button[data-appointment-id="${appointmentId}"]`);
            if (row && row.closest('tr').cells[4].textContent.includes('Cancelled')) {
                // Status was updated, cancellation worked
                $('#cancelAppointmentModal').modal('hide');
                setTimeout(() => {
                    showAlert('Appointment cancelled successfully!', 'warning');
                }, 300);
            } else {
                // There was a real error
                showAlert('An error occurred while cancelling the appointment.', 'danger');
            }
        });
    });

    // Handle delete appointment button clicks
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-appointment') || e.target.closest('.delete-appointment')) {
            e.preventDefault();
            const button = e.target.classList.contains('delete-appointment') ? e.target : e.target.closest('.delete-appointment');
            const appointmentId = button.getAttribute('data-appointment-id');
            
            // Find the appointment row to get details
            const row = button.closest('tr');
            const patientName = row.cells[1].textContent;
            const doctorName = row.cells[2].textContent;
            const appointmentTime = row.cells[3].textContent;
            
            // Populate delete modal
            document.getElementById('delete-patient-name').textContent = patientName;
            document.getElementById('delete-doctor-name').textContent = doctorName;
            document.getElementById('delete-appointment-time').textContent = appointmentTime;
            
            // Set the delete URL and appointment ID
            const deleteUrl = '{{ url("/appointments") }}/' + appointmentId;
            document.getElementById('deleteForm').action = deleteUrl;
            document.getElementById('deleteAppointmentId').value = appointmentId;
        }
    });

    // Handle approve appointment button clicks
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-approve') || e.target.closest('.btn-approve')) {
            e.preventDefault();
            const button = e.target.classList.contains('btn-approve') ? e.target : e.target.closest('.btn-approve');
            const appointmentId = button.getAttribute('data-appointment-id');
            const doctorName = button.getAttribute('data-doctor-name');
            
            // Find the appointment row to get details
            const row = button.closest('tr');
            const patientName = row.cells[1].textContent;
            const appointmentTime = row.cells[3].textContent;
            
            // Populate approve modal
            document.getElementById('approve-patient-name').textContent = patientName;
            document.getElementById('approve-doctor-name').textContent = doctorName;
            document.getElementById('approve-appointment-time').textContent = appointmentTime;
            
            // Store appointment ID for the confirm button
            document.getElementById('confirmApproveBtn').setAttribute('data-appointment-id', appointmentId);
        }
    });

    // Handle approve confirmation
    document.getElementById('confirmApproveBtn').addEventListener('click', function(e) {
        e.preventDefault();
        const appointmentId = this.getAttribute('data-appointment-id');
        const button = this;

        // Show loading state
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Approving...';

        // Make AJAX request to approve appointment
        fetch(`/appointments/${appointmentId}/approve`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                _method: 'PATCH'
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Hide modal
                $('#approveAppointmentModal').modal('hide');
                
                // Update the row status
                const row = document.querySelector(`tr:has(button[data-appointment-id="${appointmentId}"])`);
                if (row) {
                    const statusCell = row.cells[6]; // Status column
                    statusCell.innerHTML = '<span class="badge bg-success text-white">Completed</span>';
                    
                    // Remove the approve button
                    const actionsCell = row.cells[7]; // Actions column
                    actionsCell.innerHTML = '—';
                }
                
                // Show success message
                showAlert('Appointment approved successfully! Doctor has been notified.', 'success');
            } else {
                throw new Error(data.message || 'Failed to approve appointment');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred while approving the appointment.', 'danger');
        })
        .finally(() => {
            // Reset button state
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-check me-1"></i>Approve Appointment';
        });
    });

    // Handle delete form submission
    document.getElementById('deleteForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        const appointmentId = document.getElementById('deleteAppointmentId').value;

        // Submit the form
        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => {
            // Check if response is OK
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }

            // Try to parse as JSON, but handle non-JSON responses
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return response.json();
            } else {
                // If not JSON, treat as success (appointment was deleted)
                return { success: true, message: 'Appointment deleted successfully' };
            }
        })
        .then(data => {
            if (data.success) {
                // Force close modal first
                $('#deleteAppointmentModal').modal('hide');
                // Small delay to ensure modal is closed
                setTimeout(() => {
                    showAlert('Appointment deleted successfully!', 'success');
                    // Remove the deleted appointment row from the table
                    const row = document.querySelector(`button[data-appointment-id="${appointmentId}"]`).closest('tr');
                    if (row) {
                        row.remove();
                    }
                }, 300);
            } else {
                showAlert(data.message || 'Failed to delete appointment.', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // If we get here, check if the appointment was actually deleted
            // by trying to find it in the DOM
            const row = document.querySelector(`button[data-appointment-id="${appointmentId}"]`);
            if (!row) {
                // Row is gone, appointment was deleted
                $('#deleteAppointmentModal').modal('hide');
                setTimeout(() => {
                    showAlert('Appointment deleted successfully!', 'success');
                }, 300);
            } else {
                // Row still exists, there was a real error
                showAlert('An error occurred while deleting the appointment.', 'danger');
            }
        });
    });

    function showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        `;
        document.querySelector('.content-wrapper .row .col-12').prepend(alertDiv);
        setTimeout(() => alertDiv.remove(), 5000); // Auto remove after 5 seconds
    }

    function displayErrors(errors) {
        // Clear previous errors
        modalBody.querySelectorAll('.text-danger').forEach(el => el.remove());

        for (const field in errors) {
            const input = appointmentForm.querySelector(`[name="${field}"]`);
            if (input) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'text-danger';
                errorDiv.textContent = errors[field][0];
                input.parentNode.appendChild(errorDiv);
            }
        }
    }

    function refreshAppointmentsTable() {
        // Simple refresh by reloading the page or updating the table
        location.reload(); // For simplicity, reload the page to show new appointment
    }

    // Duration filtering code...
});
</script>
@endpush