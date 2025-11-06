@extends('layouts.base')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="mdi mdi-stethoscope mr-2"></i>
                        Available Doctors
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Filter Section -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <form method="GET" class="d-flex">
                                <div class="form-group mr-3 flex-fill">
                                    <label for="date" class="form-label">Filter by Date</label>
                                    <input type="date"
                                           id="date"
                                           name="date"
                                           class="form-control"
                                           value="{{ $selectedDate ?? '' }}"
                                           min="{{ now()->format('Y-m-d') }}">
                                </div>
                                <div class="form-group d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary mr-2">
                                        <i class="mdi mdi-filter"></i> Filter
                                    </button>
                                    @if(request('date'))
                                        <a href="{{ request()->fullUrlWithQuery(['date' => null]) }}" class="btn btn-outline-secondary">
                                            <i class="mdi mdi-close"></i> Clear
                                        </a>
                                    @endif
                                </div>
                            </form>
                        </div>
                        <div class="col-md-6 d-flex align-items-center">
                            @if(!$selectedDate)
                                <div class="alert alert-info mb-0 w-100">
                                    <i class="mdi mdi-information-outline"></i>
                                    <strong>Select a date above</strong> to view doctor availability and enable booking.
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Doctors Grid -->
                    @if($doctors->count() > 0)
                        <div class="row">
                            @foreach($doctors as $doctor)
                                <div class="col-md-4 col-lg-3 mb-4">
                                    <div class="card h-100 doctor-card">
                                        <div class="card-body text-center">
                                            <!-- Doctor Image -->
                                            <div class="doctor-avatar mb-3">
                                                @if($doctor->file_url)
                                                    <img src="{{ $doctor->file_url }}"
                                                         alt="{{ $doctor->display_name }}"
                                                         class="rounded-circle img-fluid"
                                                         style="width: 100px; height: 100px; object-fit: cover;">
                                                @else
                                                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center"
                                                         style="width: 100px; height: 100px;">
                                                        <i class="mdi mdi-account-circle text-muted" style="font-size: 60px;"></i>
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- Doctor Info -->
                                            <h5 class="card-title mb-1">{{ $doctor->display_name }}</h5>
                                            <p class="text-muted mb-2">{{ $doctor->specialization ?? 'General Practitioner' }}</p>

                                            <!-- Availability Status -->
                                            @if($selectedDate)
                                                @php
                                                    $dayOfWeek = \Carbon\Carbon::parse($selectedDate)->format('l');
                                                    $isAvailable = $doctor->isAvailableOnDay($dayOfWeek);
                                                @endphp
                                                <div class="mb-3">
                                                    @if($isAvailable)
                                                        <span class="badge badge-success">
                                                            <i class="mdi mdi-check-circle"></i> Available on {{ $dayOfWeek }}
                                                        </span>
                                                    @else
                                                        <span class="badge badge-warning">
                                                            <i class="mdi mdi-clock-outline"></i> Not Available on {{ $dayOfWeek }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>

                                        <div class="card-footer bg-transparent">
                                            <!-- Book Button - Only show when date is selected -->
                                            @if($selectedDate)
                                                @if(isset($school))
                                                    <a href="{{ route('book.appointment.step1', [$doctor, 'date' => $selectedDate]) }}"
                                                       class="btn btn-primary btn-block">
                                                        <i class="mdi mdi-calendar-plus"></i> Book Appointment
                                                    </a>
                                                @elseif(isset($healthFacility))
                                                    <a href="{{ route('health-facility.book.appointment.step1', [$doctor, 'date' => $selectedDate]) }}"
                                                       class="btn btn-primary btn-block">
                                                        <i class="mdi mdi-calendar-plus"></i> Book Appointment
                                                    </a>
                                                @endif
                                            @else
                                                <button class="btn btn-outline-secondary btn-block" disabled>
                                                    <i class="mdi mdi-calendar-plus"></i> Select Date to Book
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $doctors->appends(request()->query())->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="mdi mdi-stethoscope text-muted" style="font-size: 64px;"></i>
                            <h4 class="text-muted mt-3">No Doctors Found</h4>
                            <p class="text-muted">
                                @if($selectedDate)
                                    No doctors are available on the selected date. Try a different date or clear the filter.
                                @else
                                    There are currently no doctors available.
                                @endif
                            </p>
                            @if($selectedDate)
                                <a href="{{ request()->fullUrlWithQuery(['date' => null]) }}" class="btn btn-outline-primary">
                                    <i class="mdi mdi-close"></i> Clear Filter
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.doctor-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    border: 1px solid #e9ecef;
}

.doctor-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.doctor-avatar {
    position: relative;
}

.doctor-avatar img {
    border: 3px solid #f8f9fa;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-block {
    width: 100%;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit form when date changes
    const dateInput = document.getElementById('date');
    if (dateInput) {
        dateInput.addEventListener('change', function() {
            this.closest('form').submit();
        });
    }
});
</script>
@endpush