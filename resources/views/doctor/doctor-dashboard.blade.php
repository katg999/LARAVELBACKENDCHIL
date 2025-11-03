@extends('layouts.base')

@section('content')

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Header: doctor info and prominent meeting card --}}
    @php
        $meetingSlug = $doctor->meeting_slug ?? null;
        $meetingLink = $meetingSlug ? ('https://meet.jit.si/' . $meetingSlug) : ('https://meet.jit.si/dr-' . strtolower(str_replace(' ', '-', $doctor->name)));
    @endphp

    {{-- compute small aggregates from appointments passed by controller --}}
    @php
        $appointments = $appointments ?? collect();
        $upcomingAppointments = $upcomingAppointments ?? collect();

        // unique patients count
        $patientsIds = $appointments->map(function($a){
            return data_get($a, 'patient.id') ?? null;
        })->filter()->unique();
        $patientsCount = $patientsIds->count();

        $pendingCount = $appointments->where('status', 'pending')->count();

        // Prepare monthly labels and data for current year
        $chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $appointmentsData = array_fill(0, 12, 0);
        $revenueData = array_fill(0, 12, 0);
        $currentYear = \Carbon\Carbon::now()->year;
        foreach ($appointments as $appt) {
            try {
                $date = \Carbon\Carbon::parse(data_get($appt, 'appointment_time'));
                if ($date->year == $currentYear) {
                    $month = $date->month - 1;
                    $appointmentsData[$month]++;
                    $revenue = $appt->duration ? $appt->duration->getPrice() : 0;
                    $revenueData[$month] += $revenue;
                }
            } catch (\Exception $e) {
                // Skip invalid dates
            }
        }

        $genderCounts = [];
        $uniquePatients = $appointments->pluck('patient')->filter()->unique('id');
        foreach ($uniquePatients as $patient) {
            if ($patient->gender) {
                $gender = $patient->gender;
                $genderCounts[$gender] = ($genderCounts[$gender] ?? 0) + 1;
            }
        }
    @endphp

    {{-- Summary cards --}}
    <div class="row mb-4">
        <div class="col-lg-3 col-sm-6 mb-2">
            <div class="card">
                <div class="card-body">
                  <div class="d-flex align-items-center justify-content-between justify-content-md-center justify-content-xl-between flex-wrap">
                    <div>
                      <div class="stat-label mb-2">Total Appointments</div>
                      <h5 class="mb-0">{{ $stats['total_appointments'] ?? 0 }}</h5>
                    </div>
                    <i class="mdi mdi-calendar-clock icon-xl text-primary"></i>
                  </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6 mb-2">
            <div class="card">
                <div class="card-body">
                  <div class="d-flex align-items-center justify-content-between justify-content-md-center justify-content-xl-between flex-wrap">
                    <div>
                      <div class="stat-label mb-2">Completed</div>
                      <h5 class="mb-0">{{ $stats['completed_appointments'] ?? 0 }}</h5>
                    </div>
                    <i class="mdi mdi-check-circle icon-xl text-success"></i>
                  </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6 mb-2">
            <div class="card">
                <div class="card-body">
                  <div class="d-flex align-items-center justify-content-between justify-content-md-center justify-content-xl-between flex-wrap">
                    <div>
                      <div class="stat-label mb-2">Patients</div>
                      <h5 class="mb-0">{{ $stats['patients'] ?? 0 }}</h5>
                    </div>
                    <i class="mdi mdi-account-group icon-xl text-info"></i>
                  </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6 mb-2">
            <div class="card">
                <div class="card-body">
                  <div class="d-flex align-items-center justify-content-between justify-content-md-center justify-content-xl-between flex-wrap">
                    <div>
                      <div class="stat-label mb-2">Revenue</div>
                      <h5 class="mb-0">UGX {{ number_format($stats['revenue'] ?? 0, 0) }}</h5>
                    </div>
                    <i class="mdi mdi-square-inc-cash icon-xl text-warning"></i>
                  </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Appointments and Revenue (Annual)</h5>
                    <div class="text-muted small">Year: {{ now()->year }}</div>
                </div>
                <div class="card-body" style="min-height:280px;">
                    <canvas id="appointmentsChart" height="200"></canvas>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Upcoming Appointments</h5>
                </div>
                <div class="card-body">
                    @if($upcomingAppointments->count())
                        <ul class="list-group">
                            @foreach($upcomingAppointments as $appt)
                                @php
                                    $studentName = data_get($appt, 'patient.name') ?? 'Unknown';
                                    $time = isset($appt->appointment_time) ? \Carbon\Carbon::parse($appt->appointment_time)->format('M d, Y h:i A') : 'N/A';
                                @endphp
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        @if($appt->patient)
                                            <a href="{{ route('patients.profile', ['patient' => $appt->patient->id]) }}" class="text-decoration-none" target="_blank">
                                                <strong>{{ $studentName }}</strong>
                                            </a>
                                        @else
                                            <strong>{{ $studentName }}</strong>
                                        @endif
                                        <br>
                                        <small class="text-muted">{{ $time }}</small>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="{{ $meetingLink }}" target="_blank" class="btn btn-sm btn-success">Start</a>
                                        <a href="{{ route('doctor.appointments') }}" class="btn btn-sm btn-outline-secondary">View</a>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted">No upcoming appointments.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <button class="btn btn-primary w-100 mb-2" onclick="copyMeetingLink()">Copy Meeting Link</button>
                    <a href="{{ $meetingLink }}" target="_blank" class="btn btn-success w-100 mb-2">Test Meeting Room</a>
                    <!-- Edit Meeting Link removed: permanent meeting link is displayed above -->
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Patient Distribution by Gender</h5>
                </div>
                <div class="card-body">
                    @if(count($genderCounts) > 0)
                        <canvas id="genderChart" height="200"></canvas>
                    @else
                        <p class="text-muted">No patient gender data available.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function copyMeetingLink() {
            const text = '{{ $meetingLink }}';
            navigator.clipboard?.writeText(text).then(function(){
                alert('Meeting link copied to clipboard');
            }).catch(function(){
                // fallback
                const el = document.createElement('textarea');
                el.value = text;
                document.body.appendChild(el);
                el.select();
                document.execCommand('copy');
                document.body.removeChild(el);
                alert('Meeting link copied to clipboard');
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            var ctx = document.getElementById('appointmentsChart');
            if (!ctx) return;

            var labels = {!! json_encode($chartLabels) !!};
            var appointmentsData = {!! json_encode($appointmentsData) !!};
            var revenueData = {!! json_encode($revenueData) !!};

            if (typeof Chart === 'undefined') {
                console.warn('Chart.js not loaded');
                return;
            }

            new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Appointments',
                        data: appointmentsData,
                        backgroundColor: 'rgba(255, 0, 248, 0.12)', // KETI pink fill
                        borderColor: '#000000', // black line
                        pointBackgroundColor: '#FF00F8', // pink points
                        pointBorderColor: '#000000', // black point border
                        pointHoverBackgroundColor: '#000000',
                        pointHoverBorderColor: '#FF00F8',
                        fill: true,
                        tension: 0.25,
                        yAxisID: 'y'
                    }, {
                        label: 'Revenue (UGX)',
                        data: revenueData,
                        backgroundColor: 'rgba(54, 162, 235, 0.12)', // blue fill
                        borderColor: '#36A2EB', // blue line
                        pointBackgroundColor: '#36A2EB', // blue points
                        pointBorderColor: '#000000', // black point border
                        pointHoverBackgroundColor: '#000000',
                        pointHoverBorderColor: '#36A2EB',
                        fill: true,
                        tension: 0.25,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: true } },
                    scales: {
                        yAxes: [{
                            id: 'y',
                            type: 'linear',
                            display: true,
                            position: 'left',
                            ticks: {
                                beginAtZero: true,
                                precision: 0
                            },
                            scaleLabel: {
                                display: true,
                                labelString: 'Appointments'
                            }
                        }, {
                            id: 'y1',
                            type: 'linear',
                            display: true,
                            position: 'right',
                            ticks: {
                                beginAtZero: true,
                                precision: 0
                            },
                            scaleLabel: {
                                display: true,
                                labelString: 'Revenue (UGX)'
                            },
                            gridLines: {
                                drawOnChartArea: false,
                            }
                        }]
                    }
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            var ctx = document.getElementById('genderChart');
            if (!ctx) return;

            var genderData = {!! json_encode($genderCounts) !!};
            var labels = Object.keys(genderData);
            var data = Object.values(genderData);

            if (typeof Chart === 'undefined') {
                console.warn('Chart.js not loaded');
                return;
            }

            new Chart(ctx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(255, 205, 86, 0.8)',
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(153, 102, 255, 0.8)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 205, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutoutPercentage: 50,
                    legend: {
                        position: 'bottom',
                    }
                }
            });
        });
    </script>
    @endpush

@endsection