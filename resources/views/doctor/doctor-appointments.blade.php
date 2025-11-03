@extends('layouts.base')

@section('content')
<div class="container-fluid p-4">
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="mb-0">Appointments</h2>
            </div>

            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Search</label>
                    <input id="search" class="form-control form-control-sm" placeholder="Search by patient or school">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Status</label>
                    <select id="filter-status" class="form-control form-control-sm">
                        <option value="">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Date</label>
                    <input id="filter-date" type="date" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <button id="clear-filters" class="btn btn-sm btn-outline-secondary w-100">Clear Filters</button>
                </div>
            </div>
        </div>

        <div class="card-body">

    <div id="appointments-container">
    @if($appointments->count())
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle text-dark" id="appointments-table">
                <thead class="table-dark">
                    <tr>
                        <th>Date & Time</th>
                        <th>Patient</th>
                        <th>School</th>
                        <th>Health Facility</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($appointments as $appointment)
                    <tr data-id="{{ $appointment->id }}">
                        <td class="appt-time">{{ $appointment->appointment_time->format('M d, Y h:i A') }}</td>
                        <td class="appt-patient">
                            @if($appointment->patient)
                                <a href="{{ route('patients.profile', ['patient' => $appointment->patient->id]) }}" class="text-decoration-none" target="_blank">
                                    {{ $appointment->patient->name }}
                                </a>
                            @else
                                -
                            @endif
                        </td>
                        <td class="appt-school">
                            @if($appointment->school)
                                {{ $appointment->school->name }}<br>
                                <small class="text-muted">{{ $appointment->school->email ?? '-' }}</small>
                            @else
                                -
                            @endif
                        </td>
                        <td class="appt-facility">
                            @if($appointment->healthFacility)
                                {{ $appointment->healthFacility->name }}<br>
                                <small class="text-muted">{{ $appointment->healthFacility->email ?? '-' }}</small>
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{ $appointment->duration ? $appointment->duration->minutes . ' mins' : 'N/A' }}</td>
                        <td class="appt-status">
                            <span class="badge bg-{{ $appointment->status == 'confirmed' ? 'success' : ($appointment->status == 'cancelled' ? 'danger' : ($appointment->status=='completed' ? 'secondary' : 'warning')) }}">{{ ucfirst($appointment->status) }}</span>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="{{ route('doctor.meeting-link', ['id' => $doctor->id]) }}" class="btn btn-sm btn-info" title="Start Meeting" target="_blank">
                                    <i class="mdi mdi-video"></i>
                                </a>
                                @if($appointment->status !== 'completed')
                                    <button class="btn btn-sm btn-danger btn-cancel" data-id="{{ $appointment->id }}" title="Cancel">
                                        <i class="mdi mdi-close"></i>
                                    </button>
                                @endif
                                @if($appointment->status === 'pending')
                                    <button class="btn btn-sm btn-success btn-complete" data-id="{{ $appointment->id }}" title="Mark Complete">
                                        <i class="mdi mdi-check"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $appointments->links() }}
        </div>
    @else
        <div class="alert alert-info mt-4">No appointments found.</div>
    @endif
        </div>
    </div>
</div>
</div>

@push('scripts')
<script>
    (function(){
        const table = document.getElementById('appointments-table');
        const search = document.getElementById('search');
    const filterStatus = document.getElementById('filter-status');
    const filterDate = document.getElementById('filter-date');
        const clearBtn = document.getElementById('clear-filters');

        function matchesFilters(row){
            const status = row.querySelector('.appt-status').innerText.trim().toLowerCase();
            const patient = row.querySelector('.appt-patient').innerText.toLowerCase();
            const school = row.querySelector('.appt-school').innerText.toLowerCase();
            const timeText = row.querySelector('.appt-time').innerText;
            const time = new Date(timeText);

            if (filterStatus.value && status !== filterStatus.value) return false;
            if (search.value){
                const q = search.value.toLowerCase();
                if (!patient.includes(q) && !school.includes(q)) return false;
            }
            if (filterDate && filterDate.value){
                const d = new Date(filterDate.value);
                // Compare only the date portion
                if (!(time.getFullYear() === d.getFullYear() && time.getMonth() === d.getMonth() && time.getDate() === d.getDate())) return false;
            }
            return true;
        }

        function applyFilters(){
            const rows = table ? table.querySelectorAll('tbody tr') : [];
            rows.forEach(r => {
                if (matchesFilters(r)) r.style.display = '';
                else r.style.display = 'none';
            });
        }

    [search, filterStatus, filterDate].forEach(el => el && el.addEventListener('input', applyFilters));
    if (clearBtn) clearBtn.addEventListener('click', function(){ search.value=''; filterStatus.value=''; if(filterDate) filterDate.value=''; applyFilters(); });

        // AJAX appointment actions
        async function sendAction(url, method='PATCH'){
            try{
                const res = await fetch(url, { method, headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept':'application/json' } });
                if (!res.ok) throw new Error('Request failed');
                return await res.json().catch(()=>({ success:true }));
            } catch(e){
                alert('Action failed: '+e.message);
                return null;
            }
        }

        document.querySelectorAll('.btn-cancel').forEach(btn => {
            btn.addEventListener('click', async function(){
                if (!confirm('Cancel this appointment?')) return;
                const id = btn.getAttribute('data-id');
                const url = `/appointments/${id}/cancel`;
                const data = await sendAction(url,'PATCH');
                if (data && data.success){
                    const row = document.querySelector(`tr[data-id='${id}']`);
                    if (row){
                        row.querySelector('.appt-status').innerHTML = '<span class="badge bg-danger">Cancelled</span>';
                        btn.remove();
                    }
                }
            });
        });

        document.querySelectorAll('.btn-complete').forEach(btn => {
            btn.addEventListener('click', async function(){
                if (!confirm('Mark as completed?')) return;
                const id = btn.getAttribute('data-id');
                const url = `/appointments/${id}/complete`;
                const data = await sendAction(url,'PATCH');
                if (data && data.success){
                    const row = document.querySelector(`tr[data-id='${id}']`);
                    if (row){
                        row.querySelector('.appt-status').innerHTML = '<span class="badge bg-secondary">Completed</span>';
                        btn.remove();
                    }
                }
            });
        });

    })();
</script>
@endpush

@endsection
