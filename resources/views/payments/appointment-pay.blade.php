@extends('layouts.base')

@section('content')
<style>
  .btn-brand{background-color:#FF00F8;border-color:#FF00F8;color:#fff}
  .btn-brand:hover{background-color:#d600d3;border-color:#d600d3;color:#fff}
  .amount-chip{display:inline-block;padding:6px 12px;border-radius:999px;background:linear-gradient(90deg,#FF00F8,rgb(128,0,128));color:#fff;font-weight:700;box-shadow:0 2px 8px rgba(0,0,0,.08)}
  .provider-panel{background:#f8f9fa;border-radius:8px;padding:8px}
  @media (prefers-color-scheme: dark){.provider-panel{background:#1f1f1f}}
</style>
<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header text-white" style="background-color: black;">
          <strong class="mb-3" style="font-size: 1.2rem;">Pay for Appointment #{{ $appointment->id }}</strong>
        </div>
        <div class="card-body">
          @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
          @endif
          @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
          @endif

          <div class="row mb-4 text-dark">
            <div class="col-12 col-md-7">
              <dl class="row small mb-0">
                <dt class="col-4">Patient</dt><dd class="col-8">{{ optional($appointment->patient)->name ?? '—' }}</dd>
                <dt class="col-4">Doctor</dt><dd class="col-8">{{ optional($appointment->doctor)->display_name ?? '—' }}</dd>
                <dt class="col-4">Time</dt><dd class="col-8">{{ optional($appointment->appointment_time)->format('D, M j, Y g:i A') }}</dd>
                <dt class="col-4">Amount</dt><dd class="col-8">{{ $appointment->duration ? number_format($appointment->duration->getPriceForDoctor($appointment->doctor), 0) . ' UGX' : '—' }}</dd>
                <dt class="col-4">Status</dt><dd class="col-8"><span class="badge bg-warning text-dark">{{ $appointment->status }}</span></dd>
              </dl>
              @if($appointment->duration)
              <div class="mt-2">
                <span class="amount-chip">UGX {{ number_format($appointment->duration->getPriceForDoctor($appointment->doctor), 0) }}</span>
              </div>
              @endif
            </div>
            <div class="col-12 col-md-5 d-flex align-items-start justify-content-md-end mt-3 mt-md-0">
              <div class="provider-panel w-100 d-flex justify-content-md-end justify-content-center" style="background-color: #fcca0a;">
                <img src="{{ asset('images/momo.png') }}" alt="MTN MoMo" class="img-fluid" style="max-width: 200px; height: auto;" loading="lazy">
              </div>
            </div>
          </div>

          <form class="text-dark" action="{{ route('payment.appointment.checkout') }}" method="POST" id="paymentForm">
            @csrf
            <input type="hidden" name="appointment_id" value="{{ $appointment->id }}">

            <div class="mb-3">
              <label class="form-label">Payer Phone Number</label>
              <input id="phone_number" type="text" name="phone_number" class="form-control" placeholder="2567XXXXXXXX"
                     inputmode="numeric" maxlength="12" pattern="^2567\d{8}$" aria-describedby="msisdnHelp"
                     value="{{ old('phone_number', $appointment->patient->contact_number ?? $appointment->healthFacility->contact ?? '') }}" required>
              <div id="msisdnHelp" class="form-text">Enter MSISDN in international format without + (e.g. 2567XXXXXXXX)</div>
              <div class="invalid-feedback">Enter a valid number like 2567XXXXXXXX</div>
            </div>

            <input type="hidden" name="amount" value="{{ old('amount', $appointment->duration ? $appointment->duration->getPriceForDoctor($appointment->doctor) : '') }}">

            
      <div class="d-flex justify-content-between mb-1">
                <a href="{{ url()->previous() }}" class="btn btn-light">Back</a>
        <div>
          <button type="button" class="btn btn-brand" id="requestPaymentBtn">Request Payment</button>
        </div>
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
          <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
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
          <p class="text-primary mt-3" id="countdownText">Redirecting in 10 seconds...</p>
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
    const form = document.getElementById('paymentForm');
    const btn = document.getElementById('requestPaymentBtn');
    const modal = new bootstrap.Modal(document.getElementById('paymentModal'), { backdrop: 'static', keyboard: false });
    const modalTitle = document.getElementById('paymentModalLabel');
    const modalBody = document.querySelector('#paymentModal .modal-body');
    
    if(!form || !btn) return;

    btn.addEventListener('click', function(e){
      e.preventDefault();

      // Basic validation
      const phoneInput = document.getElementById('phone_number');
      if(!phoneInput.checkValidity()){
        phoneInput.reportValidity();
        return;
      }
      
      // Update modal for payment request
      modalTitle.textContent = 'Processing Payment Request';
      modalBody.innerHTML = `
        <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
          <span class="visually-hidden"></span>
        </div>
        <h5>Processing Payment Request</h5>
        <p class="text-muted mt-2">Please wait while we initiate your payment...</p>
      `;
      
      // Show modal
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
          // Show success message with pending status
          modalTitle.textContent = 'Payment Request Sent!';
          modalBody.innerHTML = `
            <div class="text-success mb-3">
              <i class="mdi mdi-check-circle" style="font-size: 3rem;"></i>
            </div>
            <h5>Payment Request Sent!</h5>
            <p class="text-muted mt-2">${data.message || 'Please check your phone and approve the payment request.'}</p>
            <div class="alert alert-info mt-3">
              <i class="mdi mdi-clock-outline me-2"></i>
              <strong>Payment Status:</strong> Waiting for confirmation...<br>
              <small>Your appointment will be confirmed once payment is completed.</small>
            </div>
            <p class="text-primary mt-3" id="countdownText">Redirecting in 30 seconds...</p>
            <div class="mt-3 d-flex gap-2 justify-content-center">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
          `;
          modal.show();

          // Start countdown
          let countdown = 30;
          const countdownText = document.getElementById('countdownText');

          const countdownInterval = setInterval(() => {
            countdown--;
            countdownText.textContent = `Redirecting in ${countdown} seconds...`;

            if (countdown <= 0) {
              clearInterval(countdownInterval);
              // Redirect to appointments page
              @if($appointment->school_id)
                window.location.href = '{{ route("book-doctor", ["school" => $appointment->school_id]) }}';
              @elseif($appointment->healthFacility)
                window.location.href = '{{ route("health-facility.appointments", $appointment->healthFacility->id) }}';
              @else
                window.location.href = '/'; // fallback to home
              @endif
            }
          }, 1000);
        } else {
          // Show error
          modalTitle.textContent = 'Payment Request Failed';
          modalBody.innerHTML = `
            <div class="text-danger mb-3">
              <i class="mdi mdi-alert-circle" style="font-size: 3rem;"></i>
            </div>
            <h5>Error</h5>
            <p class="text-muted mt-2">${data.message || 'Payment request failed. Please try again.'}</p>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          `;
          modal.show();
        }
      })
      .catch(error => {
        modal.hide();
        console.error('Error:', error);
        // Show error modal
        modalTitle.textContent = 'Payment Request Failed';
        modalBody.innerHTML = `
          <div class="text-danger mb-3">
            <i class="mdi mdi-alert-circle" style="font-size: 3rem;"></i>
          </div>
          <h5>Error</h5>
          <p class="text-muted mt-2">An error occurred. Please try again.</p>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        `;
        modal.show();
      });
    });

    function showSuccessState() {
      // Hide processing state
      document.getElementById('processingState').style.display = 'none';
      // Show success state
      document.getElementById('successState').style.display = 'block';

      // Start countdown
      let countdown = 30;
      const countdownText = document.getElementById('countdownText');

      const countdownInterval = setInterval(() => {
        countdown--;
        countdownText.textContent = `Redirecting in ${countdown} seconds...`;

        if (countdown <= 0) {
          clearInterval(countdownInterval);
          // Redirect to appointments page
          @if($appointment->school_id)
            window.location.href = '{{ route("book-doctor", ["school" => $appointment->school_id]) }}';
          @elseif($appointment->healthFacility)
            window.location.href = '{{ route("health-facility.appointments", $appointment->healthFacility->id) }}';
          @else
            window.location.href = '/'; // fallback to home
          @endif
        }
      }, 1000);
    }
  })();
</script>
@endpush


