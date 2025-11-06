@extends('layouts.base')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="mdi mdi-calendar-plus"></i>
                        Book Appointment - Step 1 of 2
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Progress Indicator -->
                    <div class="mb-4">
                        <div class="progress">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: 50%" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100">Step 1 of 2</div>
                        </div>
                    </div>

                    <!-- Doctor Info -->
                    <div class="text-center mb-4">
                        @if($doctor->file_url)
                            <img src="{{ $doctor->file_url }}"
                                 alt="{{ $doctor->display_name }}"
                                 class="rounded-circle mb-2"
                                 style="width: 80px; height: 80px; object-fit: cover;">
                        @else
                            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-2"
                                 style="width: 80px; height: 80px;">
                                <i class="mdi mdi-account-circle text-muted" style="font-size: 40px;"></i>
                            </div>
                        @endif
                        <h5>{{ $doctor->display_name }}</h5>
                        <p class="text-muted">{{ $doctor->specialization ?? 'General Practitioner' }}</p>
                    </div>

                    <!-- Booking Form -->
                    <form method="POST" action="{{ isset($school) ? route('book.appointment.step1.process', $doctor) : route('health-facility.book.appointment.step1.process', $doctor) }}" id="bookingStep1Form">
                        @csrf

                        <!-- Patient Selection -->
                        <div class="form-group mb-3">
                            <label for="patient_id" class="form-label">
                                <i class="mdi mdi-account"></i> Select Patient
                            </label>
                            <select id="patient_id" name="patient_id" class="form-control form-select" required>
                                <option value="">Choose a patient...</option>
                                @foreach($patients as $patient)
                                    <option value="{{ $patient->id }}" {{ old('patient_id', session('booking.patient_id')) == $patient->id ? 'selected' : '' }}>
                                        {{ $patient->name }} ({{ $patient->gender }}, Age: {{ $patient->birth_date ? \Carbon\Carbon::parse($patient->birth_date)->age : 'N/A' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('patient_id')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Duration Selection -->
                        <div class="form-group mb-3">
                            <label for="duration_id" class="form-label">
                                <i class="mdi mdi-clock-outline"></i> Select Duration
                            </label>
                            <select id="duration_id" name="duration_id" class="form-control form-select" required>
                                <option value="">Choose duration...</option>
                                @foreach($durations as $duration)
                                    <option value="{{ $duration->id }}" data-price="{{ $duration->getPriceForDoctor($doctor) }}" {{ old('duration_id', session('booking.duration_id')) == $duration->id ? 'selected' : '' }}>
                                        {{ $duration->minutes }} minutes - {{ ucfirst($duration->duration_type) }}: UGX {{ number_format($duration->getPriceForDoctor($doctor), 0) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('duration_id')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Appointment Time - Only time selection since date is pre-selected -->
                        <div class="form-group mb-4">
                            <label for="appointment_time" class="form-label">
                                <i class="mdi mdi-clock-outline"></i> Appointment Time for {{ \Carbon\Carbon::parse($selectedDate)->format('M d, Y') }}
                            </label>
                            <input type="time"
                                   id="appointment_time"
                                   name="appointment_time"
                                   class="form-control"
                                   value="{{ old('appointment_time', session('booking.appointment_time') ? \Carbon\Carbon::parse(session('booking.appointment_time'))->format('H:i') : '') }}"
                                   required>
                            <input type="hidden" name="appointment_date" value="{{ $selectedDate }}">
                            <small class="form-text text-muted">Please select a time between 8:00 AM and 6:00 PM</small>
                            @error('appointment_time')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Price Display -->
                        <div id="price-display" class="alert alert-info" style="display: none;">
                            <strong>Total Cost: <span id="total-price">UGX 0</span></strong>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ isset($school) ? route('available-doctors') : route('health-facility.available-doctors') }}"
                               class="btn btn-outline-secondary">
                                <i class="mdi mdi-arrow-left"></i> Back
                            </a>
                            <button type="submit" class="btn btn-primary">
                                Continue to Payment <i class="mdi mdi-arrow-right"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Validation Modal -->
<div class="modal fade" id="validationModal" tabindex="-1" aria-labelledby="validationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-5">
                <!-- Checking State -->
                <div id="checkingState">
                    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden"></span>
                    </div>
                    <h5 class="modal-title">Checking Availability</h5>
                    <p class="text-muted mt-2">Please wait while we verify the selected time slot...</p>
                </div>

                <!-- Success State -->
                <div id="availableState" style="display: none;">
                    <div class="text-success mb-3">
                        <i class="mdi mdi-check-circle" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="modal-title text-success">Time Slot Available!</h5>
                    <p class="text-muted mt-2">Proceeding to payment...</p>
                </div>

                <!-- Conflict State -->
                <div id="conflictState" style="display: none;">
                    <div class="text-danger mb-3">
                        <i class="mdi mdi-alert-circle" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="modal-title text-danger">Time Slot Unavailable</h5>
                    <p class="text-muted mt-2" id="conflictMessage">This time slot conflicts with an existing appointment.</p>
                    <button type="button" class="btn btn-secondary mt-3" id="closeConflictBtn">Choose Another Time</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const durationSelect = document.getElementById('duration_id');
    const priceDisplay = document.getElementById('price-display');
    const totalPrice = document.getElementById('total-price');
    const appointmentTimeInput = document.getElementById('appointment_time');
    const bookingForm = document.getElementById('bookingStep1Form');
    const modal = new bootstrap.Modal(document.getElementById('validationModal'), { backdrop: 'static', keyboard: false });

    // Set time constraints (8 AM to 6 PM)
    appointmentTimeInput.min = '08:00';
    appointmentTimeInput.max = '18:00';

    // Function to update price display
    function updatePriceDisplay() {
        const selectedOption = durationSelect.options[durationSelect.selectedIndex];
        if (selectedOption.value) {
            const price = selectedOption.getAttribute('data-price');
            totalPrice.textContent = 'UGX ' + parseInt(price).toLocaleString();
            priceDisplay.style.display = 'block';
        } else {
            priceDisplay.style.display = 'none';
        }
    }

    // Update price when duration is selected
    durationSelect.addEventListener('change', updatePriceDisplay);

    // Trigger price display on page load if duration is pre-selected
    if (durationSelect.value) {
        updatePriceDisplay();
    }

    // Validate appointment time
    appointmentTimeInput.addEventListener('change', function() {
        const selectedTime = this.value;
        const minTime = '08:00';
        const maxTime = '18:00';

        if (selectedTime < minTime || selectedTime > maxTime) {
            this.setCustomValidity('Please select a time between 8:00 AM and 6:00 PM');
        } else {
            this.setCustomValidity('');
        }
    });

    // Form submission with conflict check
    bookingForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Validate form first
        if (!bookingForm.checkValidity()) {
            bookingForm.reportValidity();
            return;
        }

        // Show checking state
        showCheckingState();
        modal.show();

        // Get form data
        const appointmentDate = document.querySelector('input[name="appointment_date"]').value;
        const appointmentTime = appointmentTimeInput.value;
        const durationId = durationSelect.value;
        const doctorId = '{{ $doctor->id }}';

        // Combine date and time
        const appointmentDateTime = appointmentDate + 'T' + appointmentTime;

        // Add 4-second intentional delay
        setTimeout(function() {
            // Check for conflicts via AJAX
            fetch('{{ route("appointments.validate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    doctor_id: doctorId,
                    appointment_time: appointmentDateTime,
                    duration_id: durationId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.available) {
                    // Show success state
                    showAvailableState();
                    
                    // Wait 1 second then submit the form
                    setTimeout(function() {
                        modal.hide();
                        bookingForm.submit();
                    }, 1000);
                } else {
                    // Show conflict state
                    showConflictState(data.message || 'This time slot is already booked.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showConflictState('An error occurred while checking availability. Please try again.');
            });
        }, 4000); // 4-second delay
    });

    function showCheckingState() {
        document.getElementById('checkingState').style.display = 'block';
        document.getElementById('availableState').style.display = 'none';
        document.getElementById('conflictState').style.display = 'none';
    }

    function showAvailableState() {
        document.getElementById('checkingState').style.display = 'none';
        document.getElementById('availableState').style.display = 'block';
        document.getElementById('conflictState').style.display = 'none';
    }

    function showConflictState(message) {
        document.getElementById('checkingState').style.display = 'none';
        document.getElementById('availableState').style.display = 'none';
        document.getElementById('conflictState').style.display = 'block';
        document.getElementById('conflictMessage').textContent = message;
    }

    // Close modal button handler
    const closeConflictBtn = document.getElementById('closeConflictBtn');
    if (closeConflictBtn) {
        closeConflictBtn.addEventListener('click', function() {
            modal.hide();
        });
    }
});
</script>
@endpush