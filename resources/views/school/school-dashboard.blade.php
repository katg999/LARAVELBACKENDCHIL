@extends('layouts.base')

@section('content')
    <!-- Sta    <div class="row mt-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-primary text-dark">Weekly Activity</div>
                <div class="card-body">
                    <canvas id="weeklyActivityChart" height="120"></canvas>
                </div>
            </div>

            <!-- Appointments Awaiting Approval -->
            @php
                $awaitingApprovalAppointments = $appointments->where('status', 'awaiting_approval')->take(5);
            @endphp
            @if($awaitingApprovalAppointments->count() > 0)
            <div class="card mt-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Appointments Awaiting Approval</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Date & Time</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($awaitingApprovalAppointments as $appointment)
                                <tr>
                                    <td>{{ $appointment->patient->name }}</td>
                                    <td>Dr. {{ $appointment->doctor->name }}</td>
                                    <td>{{ $appointment->appointment_time->format('M j, Y g:i A') }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('appointments.approve', $appointment) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($appointments->where('status', 'awaiting_approval')->count() > 5)
                    <div class="text-center mt-3">
                        <a href="#" class="btn btn-outline-primary">View All Awaiting Approval</a>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>ards -->
    <div class="row mb-4">
        <!-- Students -->
        <div class="col-lg-3 col-sm-6 mb-2">
            <div class="card">
                <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
                    <div>
                        <div class="stat-label mb-2">Students</div>
                        <h5 class="mb-0">{{ $studentsCount }}</h5>
                    </div>
                    <i class="mdi mdi-account-group icon-xl text-primary"></i>
                </div>
            </div>
        </div>

        <!-- Appointments -->
        <div class="col-lg-3 col-sm-6 mb-2">
            <div class="card">
                <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
                    <div>
                        <div class="stat-label mb-2">Appointments</div>
                        <h5 class="mb-0">{{ $appointmentsCount }}</h5>
                    </div>
                    <i class="mdi mdi-calendar-clock icon-xl text-success"></i>
                </div>
            </div>
        </div>

        <!-- Lab Tests -->
        <div class="col-lg-3 col-sm-6 mb-2">
            <div class="card">
                <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
                    <div>
                        <div class="stat-label mb-2">Lab Tests</div>
                        <h5 class="mb-0">{{ $labTestsCount }}</h5>
                    </div>
                    <i class="mdi mdi-flask icon-xl text-warning"></i>
                </div>
            </div>
        </div>

        <!-- Doctors -->
        <div class="col-lg-3 col-sm-6 mb-2">
            <div class="card">
                <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
                    <div>
                        <div class="stat-label mb-2">Doctors</div>
                        <h5 class="mb-0">{{ $doctorsCount }}</h5>
                    </div>
                    <i class="mdi mdi-doctor icon-xl text-info"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-primary text-dark">Weekly Activity</div>
                <div class="card-body">
                    <canvas id="weeklyActivityChart" height="120"></canvas>
                </div>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var ctx = document.getElementById('weeklyActivityChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                            datasets: [
                                {
                                    label: 'Appointments',
                                    backgroundColor: 'rgba(0, 0, 0, 0.85)',
                                    borderColor: '#000000',
                                    borderWidth: 2,
                                    hoverBackgroundColor: '#000000',
                                    hoverBorderColor: '#593bdb',
                                    data: @json($weeklyAppointments)
                                },
                                {
                                    label: 'Lab Tests',
                                    backgroundColor: '#593bdb',
                                    borderColor: '#593bdb',
                                    borderWidth: 2,
                                    hoverBackgroundColor: 'rgba(89, 59, 219, 0.85)',
                                    hoverBorderColor: '#000000',
                                    data: @json($weeklyLabTests)
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: { position: 'top' },
                                title: { display: false }
                            },
                            scales: {
                                x: { stacked: true },
                                y: { stacked: true, beginAtZero: true }
                            }
                        }
                    });
                });
            </script>
        </div>
        <div class="col-lg-4">
            @php
                $pendingLabTests = isset($labTests) ? $labTests->where('status', 'pending')->count() : 0;
                $completedLabTests = isset($labTests) ? $labTests->where('status', 'completed')->count() : 0;
            @endphp
            <div class="card stat-card stat-labtests">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="stat-icon"><i class="fa fa-flask" aria-hidden="true"></i></div>
                        <div class="stat-sep" aria-hidden="true"></div>
                        <div>
                            <div class="h5 mb-0">Lab Test Summary</div>
                            <small class="text-muted">Pending and completed</small>
                        </div>
                    </div>
                    <div class="row g-2 simple-metrics">
                        <div class="col-6">
                            <div class="metric-badge completed">
                                <div class="left d-flex align-items-center gap-2">
                                    <div class="metric-icon"><i class="fa fa-check" aria-hidden="true"></i></div>
                                    <div class="label">Completed</div>
                                </div>
                                <div class="value">{{ $completedLabTests }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="metric-badge pending">
                                <div class="left d-flex align-items-center gap-2">
                                    <div class="metric-icon"><i class="fa fa-hourglass-half" aria-hidden="true"></i></div>
                                    <div class="label">Pending</div>
                                </div>
                                <div class="value">{{ $pendingLabTests }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- ======================= STYLES ======================= --}}
<style>
.card {
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    border: none;
}
.card-header {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    border-bottom: none;
    border-radius: 15px 15px 0 0 !important;
}
</style>