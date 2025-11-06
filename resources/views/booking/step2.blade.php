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
                                    {{ $doctor->display_name }}<br>
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
                    <form method="POST" action="{{ route('appointments.store') }}" id="bookingForm">
                        @csrf
                        <input type="hidden" name="doctor_id" value="{{ $doctor->id }}">
                        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                        <input type="hidden" name="duration_id" value="{{ $duration->id }}">
                        <input type="hidden" name="appointment_time" value="{{ $appointment_time }}">
                        <input type="hidden" name="reason" value="Doctor Consultation KETIAI">
                        @if(isset($school))
                            <input type="hidden" name="school_id" value="{{ $school->id }}">
                        @else
                            <input type="hidden" name="health_facility_id" value="{{ $healthFacility->id }}">
                        @endif

                        <!-- Phone Number for Mobile Money Payment -->
                        <input type="hidden" name="payment_method" value="marzpay">
                        <div class="form-group mb-4">
                            <label class="form-label">
                                <i class="mdi mdi-phone"></i> Payer Phone Number
                            </label>
                            <input id="phone_number" type="text" name="phone_number" class="form-control" 
                                   placeholder="2567XXXXXXXX"
                                   inputmode="numeric" maxlength="12" pattern="^2567\d{8}$" 
                                   aria-describedby="msisdnHelp"
                                   value="{{ old('phone_number', '') }}" required>
                            <div id="msisdnHelp" class="form-text">Enter phone number in international format without + (e.g. 2567XXXXXXXX)</div>
                            <div class="invalid-feedback">Enter a valid number like 2567XXXXXXXX</div>
                        </div>

                        <!-- Terms and Conditions -->
                        <div class="mb-4">
                            <div class="d-flex align-items-start" style="gap: 8px;">
                                <input type="checkbox" id="terms" required style="width: 18px; height: 18px; margin-top: 2px; flex-shrink: 0;">
                                <label for="terms" style="cursor: pointer;">
                                    I agree to the <a href="#" target="_blank">terms and conditions</a> and <a href="#" target="_blank">cancellation policy</a>
                                </label>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ isset($school) ? route('book.appointment.step1', ['doctor' => $doctor, 'date' => \Carbon\Carbon::parse($appointment_time)->format('Y-m-d')]) : route('health-facility.book.appointment.step1', ['doctor' => $doctor, 'date' => \Carbon\Carbon::parse($appointment_time)->format('Y-m-d')]) }}"
                               class="btn btn-outline-secondary">
                                <i class="mdi mdi-arrow-left"></i> Back to Step 1
                            </a>
                            <button type="submit" class="btn btn-success" id="book-btn" disabled>
                                <i class="mdi mdi-check-circle"></i> Book & Pay Now
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Processing Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-5" id="modalContent">
                <!-- Processing State -->
                <div id="processingState">
                    <div class="spinner-border text-success mb-3" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden"></span>
                    </div>
                    <h5 class="modal-title" id="paymentModalLabel">Processing Payment Request</h5>
                    <p class="text-muted mt-2">Please wait while we initiate your payment...</p>
                </div>

                <!-- Success State -->
                <div id="successState" style="display: none;">
                    <div class="text-success mb-3">
                        <i class="mdi mdi-check-circle" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="modal-title text-success">Payment Request Sent!</h5>
                    <p class="text-muted mt-2">Please check your phone and approve the payment request.</p>
                    <div class="alert alert-info mt-3">
                        <i class="mdi mdi-clock-outline me-2"></i>
                        <strong>Payment Status:</strong> Waiting for confirmation...<br>
                        <small>Your appointment will be confirmed once payment is completed.</small>
                    </div>
                    <p class="text-primary mt-3" id="countdownText">Redirecting in 30 seconds...</p>
                </div>

                <!-- Error State -->
                <div id="errorState" style="display: none;">
                    <div class="text-danger mb-3">
                        <i class="mdi mdi-alert-circle" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="modal-title text-danger">Payment Request Failed</h5>
                    <p class="text-muted mt-2" id="errorMessage">An error occurred. Please try again.</p>
                    <button type="button" class="btn btn-secondary mt-3" id="closeErrorBtn">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
  (function(){
    const input = document.getElementById('phone_number');
    if(!input) return;
    function normalize(val){
      // remove non-digits
      let digits = (val || '').replace(/\D+/g, '');
      if(digits.startsWith('07')){
        // 07XXXXXXXX -> 2567XXXXXXXX
        digits = '256' + digits.substring(1);
      } else if(digits.startsWith('2560')){
        // 2560XXXXXXXX -> 2567XXXXXXXX
        digits = '256' + digits.substring(3);
      } else if(digits.startsWith('0') && digits.length >= 9){
        // 0XXXXXXXXX -> 256XXXXXXXXX
        digits = '256' + digits.substring(1);
      } else if(digits.startsWith('256+')){
        digits = '256' + digits.substring(4);
      }
      return digits;
    }
    input.addEventListener('blur', function(){
      input.value = normalize(input.value);
      // basic validity check against pattern
      try{
        const re = new RegExp('^2567\\\d{8}$');
        input.classList.toggle('is-invalid', !re.test(input.value));
      }catch(e){}
    });
    input.addEventListener('input', function(){
      // do not aggressively mutate while typing; only strip leading +
      if(input.value.startsWith('+256')){
        input.value = input.value.replace(/^\+/, '');
      }
      // clear invalid as user types
      if(input.classList.contains('is-invalid')){
        input.classList.remove('is-invalid');
      }
    });
  })();
</script>
@endpush

@push('scripts')
<script>
  (function(){
    const termsCheckbox = document.getElementById('terms');
    const bookBtn = document.getElementById('book-btn');
    const form = document.getElementById('bookingForm');
    const modal = new bootstrap.Modal(document.getElementById('paymentModal'), { backdrop: 'static', keyboard: false });
    
    if(!form || !bookBtn) return;

    // Enable/disable book button based on terms acceptance
    if(termsCheckbox) {
      termsCheckbox.addEventListener('change', function() {
          bookBtn.disabled = !this.checked;
          if (this.checked) {
              bookBtn.classList.remove('disabled');
          } else {
              bookBtn.classList.add('disabled');
          }
      });
    }

    bookBtn.addEventListener('click', function(e){
      e.preventDefault();

      // Basic validation
      const phoneInput = document.getElementById('phone_number');
      if(!phoneInput.checkValidity()){
        phoneInput.reportValidity();
        return;
      }

      if(!form.checkValidity()){
        form.reportValidity();
        return;
      }
      
      // Show modal
      document.getElementById('processingState').style.display = 'block';
      document.getElementById('successState').style.display = 'none';
      document.getElementById('errorState').style.display = 'none';
      modal.show();

      // Prepare form data
      const formData = new FormData(form);

      // Send AJAX request
      fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      })
      .then(response => response.json())
      .then(data => {
        if(data.success){
          // Show success message
          document.getElementById('processingState').style.display = 'none';
          document.getElementById('successState').style.display = 'block';
          document.getElementById('errorState').style.display = 'none';

          // Start countdown
          let countdown = 30;
          const countdownText = document.getElementById('countdownText');

          const countdownInterval = setInterval(() => {
            countdown--;
            countdownText.textContent = `Redirecting in ${countdown} seconds...`;

            if (countdown <= 0) {
              clearInterval(countdownInterval);
              // Redirect to appointments page
              @if(isset($school))
                window.location.href = '{{ route("available-doctors") }}';
              @elseif(isset($healthFacility))
                window.location.href = '{{ route("health-facility.appointments") }}';
              @else
                window.location.href = '/';
              @endif
            }
          }, 1000);
        } else {
          // Show error
          document.getElementById('processingState').style.display = 'none';
          document.getElementById('successState').style.display = 'none';
          document.getElementById('errorState').style.display = 'block';
          document.getElementById('errorMessage').textContent = data.message || 'Payment request failed. Please try again.';
        }
      })
      .catch(error => {
        console.error('Error:', error);
        // Show error
        document.getElementById('processingState').style.display = 'none';
        document.getElementById('successState').style.display = 'none';
        document.getElementById('errorState').style.display = 'block';
        document.getElementById('errorMessage').textContent = 'An error occurred. Please try again.';
      });
    });

    // Close modal button handler
    const closeErrorBtn = document.getElementById('closeErrorBtn');
    if (closeErrorBtn) {
      closeErrorBtn.addEventListener('click', function() {
        modal.hide();
      });
    }
  })();
</script>
@endpush