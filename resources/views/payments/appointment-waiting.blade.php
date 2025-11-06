@extends('layouts.base')

@section('content')
<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header text-white" style="background-color: black;">
          <strong class="mb-3" style="font-size: 1.2rem;">Payment Requested - Appointment #{{ $appointment->id }}</strong>
        </div>
        <div class="card-body text-center">
          @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
          @endif
          @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
          @endif

          <div class="mb-4">
            <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
              <span class="visually-hidden">Loading...</span>
            </div>
            <h5 class="text-primary">Waiting for Payment Approval</h5>
            <p class="text-muted">Please check your phone and approve the payment request from MTN MoMo.</p>
          </div>

          <div class="row mb-4 text-dark">
            <div class="col-12">
              <dl class="row small mb-0">
                <dt class="col-4">Patient</dt><dd class="col-8">{{ optional($appointment->patient)->name ?? '—' }}</dd>
                <dt class="col-4">Doctor</dt><dd class="col-8">{{ optional($appointment->doctor)->display_name ?? '—' }}</dd>
                <dt class="col-4">Time</dt><dd class="col-8">{{ optional($appointment->appointment_time)->format('D, M j, Y g:i A') }}</dd>
                <dt class="col-4">Amount</dt><dd class="col-8">{{ $appointment->duration ? number_format($appointment->duration->getPriceForDoctor($appointment->doctor), 0) . ' UGX' : '—' }}</dd>
                <dt class="col-4">Status</dt><dd class="col-8"><span class="badge bg-warning text-dark">{{ $appointment->status }}</span></dd>
                <dt class="col-4">Payment</dt><dd class="col-8"><span class="badge bg-info">{{ $appointment->payment_status ?? 'pending' }}</span></dd>
              </dl>
            </div>
          </div>

          <div class="d-flex justify-content-between">
            <a href="{{ $appointment->healthFacility ? route('health-facility.dashboard', ['id' => $appointment->healthFacility->id]) : route('school.dashboard') }}" class="btn btn-light">Back to Dashboard</a>
            <button type="button" class="btn btn-primary" onclick="checkPaymentStatus()">Check Status</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
function checkPaymentStatus() {
  // Simple page refresh to check status
  window.location.reload();
}

// Auto-refresh every 10 seconds to check payment status
setInterval(function() {
  fetch(window.location.href, {
    headers: {
      'X-Requested-With': 'XMLHttpRequest'
    }
  })
  .then(response => response.text())
  .then(html => {
    // If the page content changed (payment completed), reload
    if (!html.includes('Waiting for Payment Approval')) {
      window.location.reload();
    }
  })
  .catch(err => console.log('Status check failed'));
}, 10000);
</script>
@endpush