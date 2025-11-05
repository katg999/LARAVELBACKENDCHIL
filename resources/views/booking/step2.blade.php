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
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="mdi mdi-credit-card"></i>
                        Book Appointment - Step 2 of 2
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Progress Indicator -->
                    <div class="mb-4">
                        <div class="progress">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">Step 2 of 2</div>
                        </div>
                    </div>

                    <!-- Appointment Summary -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="mdi mdi-information-outline"></i>
                                Appointment Summary
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-6">
                                    <strong>Doctor:</strong><br>
                                    Dr. {{ $doctor->name }}<br>
                                    <small class="text-muted">{{ $doctor->specialization ?? 'General Practitioner' }}</small>
                                </div>
                                <div class="col-sm-6">
                                    <strong>Patient:</strong><br>
                                    {{ $patient->name }}<br>
                                    <small class="text-muted">{{ $patient->gender }}, Age: {{ $patient->birth_date ? \Carbon\Carbon::parse($patient->birth_date)->age : 'N/A' }}</small>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-sm-6">
                                    <strong>Date & Time:</strong><br>
                                    {{ \Carbon\Carbon::parse($appointment_time)->format('M d, Y \a\t h:i A') }}
                                </div>
                                <div class="col-sm-6">
                                    <strong>Duration:</strong><br>
                                    {{ $duration->minutes }} minutes ({{ ucfirst($duration->duration_type) }})
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-12 text-center">
                                    <h4 class="text-primary mb-0">
                                        <strong>Total Amount: UGX {{ number_format($price, 0) }}</strong>
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Form -->
                    <form method="POST" action="{{ route('appointments.store') }}">
                        @csrf
                        <input type="hidden" name="doctor_id" value="{{ $doctor->id }}">
                        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                        <input type="hidden" name="duration_id" value="{{ $duration->id }}">
                        <input type="hidden" name="appointment_time" value="{{ $appointment_time }}">
                        @if(isset($school))
                            <input type="hidden" name="school_id" value="{{ $school->id }}">
                        @else
                            <input type="hidden" name="health_facility_id" value="{{ $healthFacility->id }}">
                        @endif

                        <!-- Payment Method Selection -->
                        <div class="form-group mb-4">
                            <label class="form-label">
                                <i class="mdi mdi-cash-multiple"></i> Payment Method
                            </label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="marzpay" value="marzpay" checked>
                                        <label class="form-check-label" for="marzpay">
                                            <i class="mdi mdi-mobile-phone"></i> Mobile Money (MarzPay)
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="cash" value="cash">
                                        <label class="form-check-label" for="cash">
                                            <i class="mdi mdi-cash"></i> Pay at Facility
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Terms and Conditions -->
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" target="_blank">terms and conditions</a> and <a href="#" target="_blank">cancellation policy</a>
                            </label>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ isset($school) ? route('book.appointment.step1', $doctor) : route('health-facility.book.appointment.step1', $doctor) }}"
                               class="btn btn-outline-secondary">
                                <i class="mdi mdi-arrow-left"></i> Back to Step 1
                            </a>
                            <button type="submit" class="btn btn-success" id="book-btn">
                                <i class="mdi mdi-check-circle"></i> Book & Pay Now
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
    const termsCheckbox = document.getElementById('terms');
    const bookBtn = document.getElementById('book-btn');
    const paymentMethodRadios = document.querySelectorAll('input[name="payment_method"]');

    // Enable/disable book button based on terms acceptance
    termsCheckbox.addEventListener('change', function() {
        bookBtn.disabled = !this.checked;
    });

    // Initially disable book button
    bookBtn.disabled = !termsCheckbox.checked;

    // Handle payment method changes
    paymentMethodRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const method = this.value;
            if (method === 'cash') {
                bookBtn.innerHTML = '<i class="mdi mdi-check-circle"></i> Book Appointment';
            } else {
                bookBtn.innerHTML = '<i class="mdi mdi-check-circle"></i> Book & Pay Now';
            }
        });
    });
});
</script>
@endpush