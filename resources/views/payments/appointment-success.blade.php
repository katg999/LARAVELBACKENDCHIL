@extends('layouts.base')

@section('content')
<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header text-white" style="background-color: #28a745;">
          <strong class="mb-3" style="font-size: 1.2rem;">✅ Payment Successful - Appointment #{{ $appointment->id }}</strong>
        </div>
        <div class="card-body text-center">
          <div class="mb-4">
            <i class="mdi mdi-check-circle text-success" style="font-size: 4rem;"></i>
            <h5 class="text-success mt-3">Payment Confirmed!</h5>
            <p class="text-muted">Your appointment has been confirmed and is now scheduled.</p>
          </div>

          <div class="row mb-4 text-dark">
            <div class="col-12">
              <dl class="row small mb-0">
                <dt class="col-4">Patient</dt><dd class="col-8">{{ optional($appointment->patient)->name ?? '—' }}</dd>
                <dt class="col-4">Doctor</dt><dd class="col-8">{{ optional($appointment->doctor)->display_name ?? '—' }}</dd>
                <dt class="col-4">Time</dt><dd class="col-8">{{ optional($appointment->appointment_time)->format('D, M j, Y g:i A') }}</dd>
                <dt class="col-4">Amount</dt><dd class="col-8">{{ $appointment->duration ? number_format($appointment->duration->getPriceForDoctor($appointment->doctor), 0) . ' UGX' : '—' }}</dd>
                <dt class="col-4">Status</dt><dd class="col-8"><span class="badge bg-success">Confirmed</span></dd>
                <dt class="col-4">Payment</dt><dd class="col-8"><span class="badge bg-success">Completed</span></dd>
              </dl>
            </div>
          </div>

          <div class="d-flex justify-content-center">
            <a href="{{ $appointment->healthFacility ? route('health-facility.dashboard', ['id' => $appointment->healthFacility->id]) : route('school.dashboard') }}" class="btn btn-success btn-lg">
              <i class="mdi mdi-view-dashboard"></i> Go to Dashboard
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection