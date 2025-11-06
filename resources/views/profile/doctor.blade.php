@extends('layouts.base')

@section('content')
<div class="container-fluid px-3 py-2">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body bg-primary p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <img src="{{ $doctor->file_url ?? asset('images/doctor.png') }}"
                                 alt="doctor"
                                 class="rounded-circle me-5 border border-white border-3"
                                 style="width: 80px; height: 80px; object-fit: cover;">
                            <div>
                                <h1 class="h3 mb-1 fw-bold">{{ $doctor->display_name }}</h1>
                                <p class="mb-1 opacity-85">
                                    <i class="mdi mdi-doctor me-2"></i>{{ $doctor->specialization ?? 'General Practitioner' }}
                                </p>
                                <div class="d-flex gap-3 text-white-50">
                                    @if($doctor->school)
                                        <small><i class="mdi mdi-school me-1"></i>{{ $doctor->school->name }}</small>
                                    @endif
                                    @if($doctor->healthFacility)
                                        <small><i class="mdi mdi-hospital-building me-1"></i>{{ $doctor->healthFacility->name }}</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="btn-group">
                            @if($doctor->meeting_slug)
                                <a href="https://meet.jit.si/{{ $doctor->meeting_slug }}"
                                   target="_blank"
                                   class="btn btn-light btn-sm">
                                    <i class="mdi mdi-video me-2"></i>Start Meeting
                                </a>
                            @endif
                            <a href="{{ route('doctor.edit-profile') }}" class="btn btn-outline-light btn-sm">
                                <i class="mdi mdi-account-edit me-2"></i>Edit Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-sm-6 mb-2">
            <div class="card">
                <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
                    <div>
                        <div class="stat-label mb-2">Total Appointments</div>
                        <h5 class="mb-0">{{ $totalAppointments ?? 0 }}</h5>
                    </div>
                    <i class="mdi mdi-calendar-check icon-xl text-primary"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6 mb-2">
            <div class="card">
                <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
                    <div>
                        <div class="stat-label mb-2">Completed</div>
                        <h5 class="mb-0">{{ $completedAppointments ?? 0 }}</h5>
                    </div>
                    <i class="mdi mdi-check-circle icon-xl text-success"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6 mb-2">
            <div class="card">
                <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
                    <div>
                        <div class="stat-label mb-2">Upcoming</div>
                        <h5 class="mb-0">{{ $upcomingAppointments ?? 0 }}</h5>
                    </div>
                    <i class="mdi mdi-clock-outline icon-xl text-warning"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6 mb-2">
            <div class="card">
                <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
                    <div>
                        <div class="stat-label mb-2">Cancelled</div>
                        <h5 class="mb-0">{{ $cancelledAppointments ?? 0 }}</h5>
                    </div>
                    <i class="mdi mdi-close-circle icon-xl text-danger"></i>
                </div>
            </div>
        </div>
    </div>    <div class="row">
        <!-- Profile Information -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-account me-2"></i>Profile Information
                    </h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted fw-semibold">Name</dt>
                        <dd class="col-sm-8">{{ $doctor->display_name }}</dd>

                        <dt class="col-sm-4 text-muted fw-semibold">Specialization</dt>
                        <dd class="col-sm-8">{{ $doctor->specialization ?? 'Not specified' }}</dd>

                        @if($doctor->email)
                        <dt class="col-sm-4 text-muted fw-semibold">Email</dt>
                        <dd class="col-sm-8">
                            <a href="mailto:{{ $doctor->email }}" class="text-decoration-none">
                                {{ $doctor->email }}
                            </a>
                        </dd>
                        @endif

                        @if($doctor->contact)
                        <dt class="col-sm-4 text-muted fw-semibold">Contact</dt>
                        <dd class="col-sm-8">
                            <a href="tel:{{ $doctor->contact }}" class="text-decoration-none">
                                {{ $doctor->contact }}
                            </a>
                        </dd>
                        @endif

                        @if($doctor->school)
                        <dt class="col-sm-4 text-muted fw-semibold">School</dt>
                        <dd class="col-sm-8">{{ $doctor->school->name }}</dd>
                        @endif

                        @if($doctor->healthFacility)
                        <dt class="col-sm-4 text-muted fw-semibold">Health Facility</dt>
                        <dd class="col-sm-8">{{ $doctor->healthFacility->name }}</dd>
                        @endif

                        @if($doctor->meeting_slug)
                        <dt class="col-sm-4 text-muted fw-semibold">Meeting Room</dt>
                        <dd class="col-sm-8">
                            <code class="small">{{ $doctor->meeting_slug }}</code>
                        </dd>
                        @endif

                        <dt class="col-sm-4 text-muted fw-semibold">Joined</dt>
                        <dd class="col-sm-8">{{ $doctor->created_at->format('M j, Y') }}</dd>
                    </dl>
                </div>
            </div>

            <!-- Availability Summary -->
            @if(isset($availabilitySummary))
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-calendar-week me-2"></i>Weekly Availability
                    </h5>
                </div>
                <div class="card-body">
                    @php
                        $daysMap = [
                            'monday' => 'Monday',
                            'tuesday' => 'Tuesday',
                            'wednesday' => 'Wednesday',
                            'thursday' => 'Thursday',
                            'friday' => 'Friday',
                            'saturday' => 'Saturday',
                            'sunday' => 'Sunday'
                        ];
                    @endphp
                    @foreach($daysMap as $key => $day)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">{{ $day }}</span>
                            <span class="badge {{ ($availabilitySummary[$key] ?? false) ? 'bg-success' : 'bg-secondary' }}">
                                {{ ($availabilitySummary[$key] ?? false) ? 'Available' : 'Unavailable' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Recent Appointments -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-calendar me-2"></i>Recent Appointments
                    </h5>
                    <a href="{{ route('doctor.appointments') }}" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
                <div class="card-body p-0">
                    @if(isset($recentAppointments) && $recentAppointments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0 fw-semibold">Patient</th>
                                        <th class="border-0 fw-semibold">Date & Time</th>
                                        <th class="border-0 fw-semibold">Status</th>
                                        <th class="border-0 fw-semibold">Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentAppointments as $appointment)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2 fw-bold"
                                                     style="width: 40px; height: 40px; font-size: 1rem;">
                                                    {{ strtoupper(substr($appointment->patient->name ?? 'P', 0, 1)) }}
                                                </div>
                                                <div>
                                                    <div class="fw-bold">{{ $appointment->patient->name ?? 'Unknown' }}</div>
                                                    @if($appointment->patient->contact)
                                                        <small class="text-muted">{{ $appointment->patient->contact }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-bold">{{ \Carbon\Carbon::parse($appointment->appointment_time)->format('M j, Y') }}</div>
                                            <small class="text-muted">{{ \Carbon\Carbon::parse($appointment->appointment_time)->format('g:i A') }}</small>
                                        </td>
                                        <td>
                                            @php
                                                $statusClasses = [
                                                    'pending' => 'bg-warning',
                                                    'scheduled' => 'bg-info',
                                                    'completed' => 'bg-success',
                                                    'cancelled' => 'bg-danger'
                                                ];
                                                $statusClass = $statusClasses[$appointment->status] ?? 'bg-secondary';
                                            @endphp
                                            <span class="badge {{ $statusClass }}">
                                                {{ ucfirst($appointment->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-truncate d-inline-block" style="max-width: 150px;" title="{{ $appointment->reason }}">
                                                {{ $appointment->reason }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="mdi mdi-calendar-blank display-1 text-muted mb-3"></i>
                            <h5 class="text-muted">No appointments yet</h5>
                            <p class="text-muted">Appointments will appear here once scheduled.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
