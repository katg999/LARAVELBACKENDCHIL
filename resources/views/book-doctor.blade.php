@extends('layouts.base')


@section('content')
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="mb-0">Doctor Appointments</h2>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#newAppointmentModal"><i class="mdi mdi-stethoscope"></i> Book Doctor</button>

                    </div>
                </div>
                <!-- Modal and form remain unchanged -->
                <div>
                    @if($appointments->count() > 0)
                        <div class="table-responsive mt-3">
                            <table class="table table-bordered table-hover align-middle text-dark">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Date</th>
                                        <th>Student</th>
                                        <th>Doctor</th>
                                        <th>Duration</th>
                                        <th>Amount</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($appointments as $appointment)
                                    <tr>
                                        <td>{{ $appointment->appointment_time->format('M d, Y h:i A') }}</td>
                                        <td>{{ $appointment->patient->name }}</td>
                                        <td>Dr. {{ $appointment->doctor->name }}</td>
                                        <td>{{ $appointment->duration ? $appointment->duration->minutes . ' mins' : '—' }}</td>
                                        <td>{{ $appointment->duration ? number_format($appointment->duration->getPriceForDoctor($appointment->doctor)) . ' UGX' : '—' }}</td>
                                        <td>{{ $appointment->reason }}</td>
                                        <td>
                                            <span class="badge bg-{{ 
                                                $appointment->status == 'confirmed' ? 'success' : 
                                                ($appointment->status == 'pending_payment' ? 'warning' : 'danger') 
                                            }} text-white">
                                                {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            @if($appointment->status === 'awaiting_payment')
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('payment.appointment.pay', $appointment) }}" class="btn btn-sm btn-primary">
                                                        <i class="fa fa-credit-card me-1"></i> Pay
                                                    </a>
                                                    <button class="btn btn-sm btn-warning cancel-appointment" 
                                                            data-appointment-id="{{ $appointment->id }}"
                                                            data-toggle="modal" 
                                                            data-target="#cancelAppointmentModal">
                                                        <i class="fa fa-times me-1"></i> Cancel
                                                    </button>
                                                </div>
                                            @elseif($appointment->status === 'confirmed')
                                                <div class="btn-group" role="group">
                                                    @if($appointment->conference)
                                                        <a href="{{ route('conferences.join', $appointment->conference) }}" class="btn btn-sm btn-success">
                                                            <i class="fas fa-video me-1"></i> Join Conference
                                                        </a>
                                                    @else
                                                        <button class="btn btn-sm btn-info create-conference" 
                                                                data-appointment-id="{{ $appointment->id }}">
                                                            <i class="fas fa-plus me-1"></i> Start Conference
                                                        </button>
                                                    @endif
                                                </div>
                                            @elseif($appointment->status === 'cancelled')
                                                <button class="btn btn-sm btn-danger delete-appointment" 
                                                        data-appointment-id="{{ $appointment->id }}"
                                                        data-toggle="modal" 
                                                        data-target="#deleteAppointmentModal">
                                                    <i class="fa fa-trash me-1"></i> Delete
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
                        <div class="alert alert-info text-dark">
                            No appointments found.
                        </div>
                    @endif
                </div>
            </div>
        </div>
            <div class="modal fade" id="newAppointmentModal" tabindex="-1" aria-labelledby="newAppointmentModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="newAppointmentModalLabel">New Doctor Appointment</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <!-- Error messages container -->
                            <div id="appointment-errors" class="alert alert-danger" style="display: none;"></div>
                            <div id="appointment-success" class="alert alert-success" style="display: none;"></div>
                            
                            <form id="appointment-form" action="{{ route('appointments.store') }}" method="POST">
    @csrf
    <input type="hidden" name="school_id" value="{{ $school->id }}">

    <div class="mb-3">
        <label for="patient_id" class="form-label">Student</label>
        <select id="patient_id" class="form-control form-select" name="patient_id" required>
            <option value="">Select student</option>
            @foreach($patients as $patient)
            <option value="{{ $patient->id }}">{{ $patient->name }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback" id="patient_id-error"></div>
    </div>

    <div class="mb-3">
        <label for="appointment_time" class="form-label">Appointment Time</label>
        <input id="appointment_time" type="datetime-local" class="form-control" name="appointment_time" required>
        <div class="invalid-feedback" id="appointment_time-error"></div>
    </div>

    <div class="mb-3">
        <label for="duration_id" class="form-label">Duration</label>
        <select id="duration_id" class="form-control form-select" name="duration_id" required>
            <option value="">Select Duration</option>
            @foreach(\App\Models\Duration::active()->get() as $duration)
                <option value="{{ $duration->id }}-general" data-duration-id="{{ $duration->id }}" data-type="general" data-price="{{ $duration->general_price }}">
                    {{ $duration->minutes }} minutes - General: UGX {{ number_format($duration->general_price, 0) }}
                </option>
                <option value="{{ $duration->id }}-specialist" data-duration-id="{{ $duration->id }}" data-type="specialist" data-price="{{ $duration->specialist_price }}">
                    {{ $duration->minutes }} minutes - Specialist: UGX {{ number_format($duration->specialist_price, 0) }}
                </option>
            @endforeach
        </select>
        <div class="invalid-feedback" id="duration_id-error"></div>
    </div>

    <div class="mb-3">
        <label for="doctor_id" class="form-label">Doctor</label>
        <select id="doctor_id" class="form-control form-select" name="doctor_id" required>
            <option value="">Select Doctor</option>
            @foreach($doctors ?? [] as $doc)
                <option value="{{ $doc->id }}">Dr. {{ $doc->name }} ({{ $doc->specialization }})</option>
            @endforeach
        </select>
        <div class="invalid-feedback" id="doctor_id-error"></div>
    </div>

    <div class="mb-3">
        <label for="reason" class="form-label">Reason</label>
        <textarea id="reason" class="form-control" name="reason" required></textarea>
        <div class="invalid-feedback" id="reason-error"></div>
    </div>

    <button type="submit" class="btn btn-primary" id="submit-btn">
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display: none;"></span>
        Book Appointment
    </button>
                        </form>

                        </div>
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
                            <strong>Student:</strong> <span id="cancel-patient-name"></span><br>
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
                            <strong>Student:</strong> <span id="delete-patient-name"></span><br>
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
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const appointmentTimeInput = document.getElementById('appointment_time');
    const doctorSelect = document.getElementById('doctor_id');
    const durationSelect = document.getElementById('duration_id');
    const appointmentForm = document.getElementById('appointment-form');
    const submitBtn = document.getElementById('submit-btn');
    const submitBtnText = submitBtn.querySelector('span:last-child');
    const spinner = submitBtn.querySelector('.spinner-border');

    // Get duration options for filtering
    const durationOptions = durationSelect ? durationSelect.querySelectorAll('option') : [];

    // Function to filter duration options based on selected doctor
    function filterDurationOptions() {
        const selectedDoctorOption = doctorSelect.options[doctorSelect.selectedIndex];
        const doctorSpecialization = selectedDoctorOption ? selectedDoctorOption.getAttribute('data-specialization') : null;

        // Show/hide duration options based on doctor type
        durationOptions.forEach(option => {
            const optionType = option.getAttribute('data-type');
            const isGeneralDoctor = doctorSpecialization && doctorSpecialization.toLowerCase().includes('general practitioner');

            if (optionType === 'general' && isGeneralDoctor) {
                option.style.display = 'block';
            } else if (optionType === 'specialist' && !isGeneralDoctor && doctorSpecialization) {
                option.style.display = 'block';
            } else {
                option.style.display = 'none';
            }
        });

        // Reset duration selection if current selection is not available
        if (durationSelect.value) {
            const selectedOption = durationSelect.querySelector(`option[value="${durationSelect.value}"]`);
            if (selectedOption && selectedOption.style.display === 'none') {
                durationSelect.value = '';
            }
        }
    }

    // Function to show errors
    function showErrors(errors) {
        // Clear previous errors
        document.querySelectorAll('.invalid-feedback').forEach(el => {
            el.textContent = '';
            el.style.display = 'none';
        });
        document.querySelectorAll('.form-control').forEach(el => {
            el.classList.remove('is-invalid');
        });

        // Hide success message
        document.getElementById('appointment-success').style.display = 'none';

        // Show error message
        const errorContainer = document.getElementById('appointment-errors');
        if (typeof errors === 'string') {
            errorContainer.textContent = errors;
            errorContainer.style.display = 'block';
        } else if (typeof errors === 'object') {
            let errorMessages = [];
            for (const [field, messages] of Object.entries(errors)) {
                if (Array.isArray(messages)) {
                    messages.forEach(message => errorMessages.push(message));
                } else {
                    errorMessages.push(messages);
                }

                // Show field-specific errors
                const errorElement = document.getElementById(field + '-error');
                if (errorElement) {
                    errorElement.textContent = Array.isArray(messages) ? messages[0] : messages;
                    errorElement.style.display = 'block';
                    const inputElement = document.getElementById(field);
                    if (inputElement) {
                        inputElement.classList.add('is-invalid');
                    }
                }
            }

            if (errorMessages.length > 0) {
                errorContainer.innerHTML = errorMessages.join('<br>');
                errorContainer.style.display = 'block';
            }
        }
    }

    // Function to show success
    function showSuccess(message) {
        // Clear errors
        document.querySelectorAll('.invalid-feedback').forEach(el => {
            el.textContent = '';
            el.style.display = 'none';
        });
        document.querySelectorAll('.form-control').forEach(el => {
            el.classList.remove('is-invalid');
        });
        document.getElementById('appointment-errors').style.display = 'none';

        // Show success message
        const successContainer = document.getElementById('appointment-success');
        successContainer.textContent = message;
        successContainer.style.display = 'block';

        // Reset form after 2 seconds and close modal
        setTimeout(() => {
            appointmentForm.reset();
            $('#newAppointmentModal').modal('hide');
            successContainer.style.display = 'none';
            // Reload page to show new appointment
            location.reload();
        }, 2000);
    }

    // Function to set loading state
    function setLoading(loading) {
        submitBtn.disabled = loading;
        spinner.style.display = loading ? 'inline-block' : 'none';
        submitBtnText.textContent = loading ? 'Booking...' : 'Book Appointment';
    }

    // Handle form submission via AJAX
    appointmentForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Clear previous messages
        document.getElementById('appointment-errors').style.display = 'none';
        document.getElementById('appointment-success').style.display = 'none';

        // Prepare form data
        const formData = new FormData(appointmentForm);

        // Handle duration selection
        const durationValue = durationSelect.value;
        if (durationValue) {
            const selectedOption = durationSelect.querySelector(`option[value="${durationValue}"]`);
            if (selectedOption) {
                const durationId = selectedOption.getAttribute('data-duration-id');
                const type = selectedOption.getAttribute('data-type');

                // Update form data with correct values
                formData.set('duration_id', durationId);
                formData.append('consultation_type', type);
            }
        }

        setLoading(true);

        // Submit via AJAX
        fetch(appointmentForm.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            setLoading(false);

            if (data.success) {
                showSuccess(data.message || 'Appointment booked successfully!');
            } else {
                showErrors(data.errors || data.message || 'An error occurred');
            }
        })
        .catch(error => {
            setLoading(false);
            console.error('AJAX Error:', error);
            showErrors('Network error occurred. Please try again.');
        });
    });

    // Filter duration options when doctor is selected
    doctorSelect.addEventListener('change', filterDurationOptions);

    // Initial filter of duration options
    filterDurationOptions();

    // Handle create conference button clicks
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('create-conference') || e.target.closest('.create-conference')) {
            e.preventDefault();
            const button = e.target.classList.contains('create-conference') ? e.target : e.target.closest('.create-conference');
            const appointmentId = button.getAttribute('data-appointment-id');

            // Disable button and show loading
            button.disabled = true;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Creating...';

            // Create conference
            fetch(`/conferences/appointment/${appointmentId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Conference created successfully!', 'success');
                    // Reload page to show join button
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert(data.message || 'Failed to create conference', 'danger');
                    // Re-enable button
                    button.disabled = false;
                    button.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error creating conference:', error);
                showAlert('Failed to create conference', 'danger');
                // Re-enable button
                button.disabled = false;
                button.innerHTML = originalText;
            });
        }
    });
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('cancel-appointment') || e.target.closest('.cancel-appointment')) {
            e.preventDefault();
            const button = e.target.classList.contains('cancel-appointment') ? e.target : e.target.closest('.cancel-appointment');
            const appointmentId = button.getAttribute('data-appointment-id');
            
            // Find the appointment row to get details
            const row = button.closest('tr');
            const patientName = row.cells[1].textContent;
            const doctorName = row.cells[2].textContent;
            const appointmentTime = row.cells[0].textContent;
            
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
            if (row && row.closest('tr').cells[6].textContent.includes('Cancelled')) {
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
            const appointmentTime = row.cells[0].textContent;
            
            // Populate delete modal
            document.getElementById('delete-patient-name').textContent = patientName;
            document.getElementById('delete-doctor-name').textContent = doctorName;
            document.getElementById('delete-appointment-time').textContent = appointmentTime;
            
            // Set the delete URL and appointment ID
            const deleteUrl = '{{ url("/appointments") }}/' + appointmentId;
            document.getElementById('deleteForm').action = deleteUrl;
            document.getElementById('deleteAppointmentId').value = appointmentId;
            
            console.log('Delete button clicked for appointment:', appointmentId);
            console.log('Delete URL set to:', deleteUrl);
        }
    });

    // Handle delete form submission
    document.getElementById('deleteForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        const appointmentId = document.getElementById('deleteAppointmentId').value;

        console.log('Delete form submitted for appointment:', appointmentId);
        console.log('Form action:', form.action);

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
});
</script>
@endpush