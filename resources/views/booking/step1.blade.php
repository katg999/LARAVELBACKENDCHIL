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
                                 alt="Dr. {{ $doctor->name }}"
                                 class="rounded-circle mb-2"
                                 style="width: 80px; height: 80px; object-fit: cover;">
                        @else
                            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-2"
                                 style="width: 80px; height: 80px;">
                                <i class="mdi mdi-account-circle text-muted" style="font-size: 40px;"></i>
                            </div>
                        @endif
                        <h5>Dr. {{ $doctor->name }}</h5>
                        <p class="text-muted">{{ $doctor->specialization ?? 'General Practitioner' }}</p>
                    </div>

                    <!-- Booking Form -->
                    <form method="POST" action="{{ isset($school) ? route('book.appointment.step1.process', $doctor) : route('health-facility.book.appointment.step1.process', $doctor) }}">
                        @csrf

                        <!-- Patient Selection -->
                        <div class="form-group mb-3">
                            <label for="patient_id" class="form-label">
                                <i class="mdi mdi-account"></i> Select Patient
                            </label>
                            <select id="patient_id" name="patient_id" class="form-control form-select" required>
                                <option value="">Choose a patient...</option>
                                @foreach($patients as $patient)
                                    <option value="{{ $patient->id }}">
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
                                    <option value="{{ $duration->id }}" data-price="{{ $duration->getPriceForDoctor($doctor) }}">
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
                            <a href="{{ isset($school) ? route('school.dashboard') : route('health-facility.dashboard') }}"
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
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const durationSelect = document.getElementById('duration_id');
    const priceDisplay = document.getElementById('price-display');
    const totalPrice = document.getElementById('total-price');
    const appointmentTimeInput = document.getElementById('appointment_time');

    // Set time constraints (8 AM to 6 PM)
    appointmentTimeInput.min = '08:00';
    appointmentTimeInput.max = '18:00';

    // Update price when duration is selected
    durationSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            const price = selectedOption.getAttribute('data-price');
            totalPrice.textContent = 'UGX ' + parseInt(price).toLocaleString();
            priceDisplay.style.display = 'block';
        } else {
            priceDisplay.style.display = 'none';
        }
    });

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
});
</script>
@endpush