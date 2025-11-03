@extends('layouts.base')

@push('styles')
<link href="{{ asset('css/admin-dashboard.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container-fluid px-3 py-2">
    <!-- Header Section with Gradient Background -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm doctors-header-gradient">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-white">
                            <h1 class="h3 mb-1 fw-bold text-white">Payments Management</h1>
                            <p class="mb-0 opacity-85">Manage payment transactions and their details</p>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#createPaymentModal">
                                <i class="mdi mdi-plus me-3"></i> <span>Record</span>
                            </button>
                            <button type="button" class="btn btn-secondary outline btn-sm" data-bs-toggle="modal" data-bs-target="#createPaymentModal">
                                <i class="mdi mdi-export me-3"></i> <span>Export</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-12 mb-2">
            <input type="text" id="admin-search" class="form-control" placeholder="Search by reference, phone, or amount...">
        </div>
        <div class="col-md-2 col-sm-12 mb-2">
            <select id="status-filter" class="form-select form-control">
                <option value="">All Statuses</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 col-sm-12 mb-2">
            <select id="appointment-filter" class="form-select form-control">
                <option value="">All Appointments</option>
                @foreach($appointments as $id => $name)
                    <option value="{{ $id }}">Appointment #{{ $id }} - {{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 col-sm-12 mb-2">
            <input type="date" id="date-from" class="form-control" placeholder="From Date">
        </div>
        <div class="col-md-2 col-sm-12 mb-2">
            <input type="date" id="date-to" class="form-control" placeholder="To Date">
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="mdi mdi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Payments Table -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark">All Payments</h6>
            </div>
        </div>

        <div class="card-body p-0">
            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0" id="payments-table">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0 fw-semibold px-3 py-3">ID</th>
                            <th class="border-0 fw-semibold px-3 py-3">Amount</th>
                            <th class="border-0 fw-semibold px-3 py-3">Status</th>
                            <th class="border-0 fw-semibold px-3 py-3">Reference</th>
                            <th class="border-0 fw-semibold px-3 py-3">Phone</th>
                            <th class="border-0 fw-semibold px-3 py-3">Appointment</th>
                            <th class="border-0 fw-semibold px-3 py-3">Created</th>
                            <th class="border-0 fw-semibold px-3 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $payment)
                        <tr>
                            <td class="px-3 py-3 fw-medium">#{{ $payment->id }}</td>
                            <td class="px-3 py-3">
                                <span class="fw-bold text-success">UGX {{ number_format($payment->amount, 0) }}</span>
                            </td>
                            <td class="px-3 py-3">
                                @php
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'completed' => 'success',
                                        'failed' => 'danger',
                                        'cancelled' => 'secondary'
                                    ];
                                    $statusColor = $statusColors[$payment->status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $statusColor }} px-2 py-1">{{ ucfirst($payment->status) }}</span>
                            </td>
                            <td class="px-3 py-3">
                                <code class="small">{{ $payment->reference_id ?? '-' }}</code>
                            </td>
                            <td class="px-3 py-3">
                                <span class="text-muted">{{ $payment->phone_number ?? '-' }}</span>
                            </td>
                            <td class="px-3 py-3">
                                @if($payment->appointment)
                                    <div class="small">
                                        <div>Apt #{{ $payment->appointment->id }}</div>
                                        @if($payment->appointment->patient)
                                            <div class="text-muted">{{ $payment->appointment->patient->name }}</div>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <div class="small text-muted">
                                    {{ optional($payment->created_at)->format('M j, Y') }}
                                </div>
                                <div class="small text-muted">
                                    {{ optional($payment->created_at)->format('g:i A') }}
                                </div>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <button class="btn btn-outline-info btn-sm" title="View Details" data-toggle="modal" data-target="#paymentDetailsModal" onclick="showPaymentDetails({{ $payment->id }}, '{{ $payment->reference_id ?? '' }}', '{{ $payment->amount }}', '{{ $payment->status }}', '{{ $payment->phone_number ?? '' }}', '{{ $payment->appointment ? $payment->appointment->id : '' }}', '{{ $payment->appointment && $payment->appointment->patient ? $payment->appointment->patient->name : '' }}', '{{ optional($payment->created_at)->format('M j, Y g:i A') }}')">
                                    <i class="mdi mdi-eye"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="mdi mdi-credit-card mdi-36px text-muted mb-3"></i>
                                <h6 class="text-muted">No payments found</h6>
                                <p class="text-muted small">Try adjusting your filters or record a new payment.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-4">{{ $items->appends(request()->query())->links() }}</div>
</div>

<!-- Create Payment Modal (Placeholder) -->
<div class="modal fade doctors-modal" id="createPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record New Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Payment creation form would go here. For now, use the generic form.</p>
                <a href="{{ route('admin.payments.create') }}" class="btn btn-primary btn-sm">Go to Create Form</a>
            </div>
        </div>
    </div>
</div>

<!-- Payment Details Modal -->
<div class="modal fade doctors-modal" id="paymentDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="mdi mdi-credit-card me-2"></i>Payment Details
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted">Payment ID</label>
                            <p class="mb-0" id="payment-id">-</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted">Amount</label>
                            <p class="mb-0 fs-5 text-success fw-bold" id="payment-amount">-</p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted">Status</label>
                            <p class="mb-0">
                                <span class="badge" id="payment-status">-</span>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted">Reference</label>
                            <p class="mb-0">
                                <code id="payment-reference">-</code>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted">Phone Number</label>
                            <p class="mb-0" id="payment-phone">-</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted">Created At</label>
                            <p class="mb-0" id="payment-created">-</p>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-muted">Appointment Details</label>
                    <div class="border rounded p-3 bg-light">
                        <div id="appointment-details">
                            <p class="mb-0 text-muted">No appointment associated</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    // Filter functionality
    const searchInput = document.getElementById('admin-search');
    const statusFilter = document.getElementById('status-filter');
    const appointmentFilter = document.getElementById('appointment-filter');
    const dateFromInput = document.getElementById('date-from');
    const dateToInput = document.getElementById('date-to');

    function filterPayments() {
        const q = (searchInput?.value || '').trim().toLowerCase();
        const selectedStatus = (statusFilter?.value || '').toLowerCase();
        const selectedAppointment = appointmentFilter?.value || '';
        const dateFrom = dateFromInput?.value || '';
        const dateTo = dateToInput?.value || '';

        // Get all table rows (excluding header and empty state)
        const rows = Array.from(document.querySelectorAll('#payments-table tbody tr')).filter(row =>
            !row.querySelector('.mdi-credit-card')
        );

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length < 8) return; // Skip if not enough cells

            const reference = cells[3]?.textContent?.toLowerCase() || '';
            const phone = cells[4]?.textContent?.toLowerCase() || '';
            const amount = cells[1]?.textContent?.toLowerCase() || '';
            const status = cells[2]?.textContent?.toLowerCase() || '';
            const appointment = cells[5]?.textContent?.toLowerCase() || '';

            const matchesSearch = !q || reference.includes(q) || phone.includes(q) || amount.includes(q);
            const matchesStatus = !selectedStatus || status.includes(selectedStatus);
            const matchesAppointment = !selectedAppointment || appointment.includes(selectedAppointment);

            row.style.display = (matchesSearch && matchesStatus && matchesAppointment) ? '' : 'none';
        });
    }

    // Filter event listeners
    if (searchInput) searchInput.addEventListener('input', filterPayments);
    if (statusFilter) statusFilter.addEventListener('change', filterPayments);
    if (appointmentFilter) appointmentFilter.addEventListener('change', filterPayments);
    if (dateFromInput) dateFromInput.addEventListener('change', filterPayments);
    if (dateToInput) dateToInput.addEventListener('change', filterPayments);
});

// Function to show payment details in modal
function showPaymentDetails(id, reference, amount, status, phone, appointmentId, patientName, createdAt) {
    // Set payment ID
    document.getElementById('payment-id').textContent = '#' + id;

    // Set amount
    document.getElementById('payment-amount').textContent = 'UGX ' + parseInt(amount).toLocaleString();

    // Set status with appropriate badge
    const statusColors = {
        'pending': 'warning',
        'completed': 'success',
        'failed': 'danger',
        'cancelled': 'secondary'
    };
    const statusColor = statusColors[status] || 'secondary';
    document.getElementById('payment-status').className = 'badge bg-' + statusColor;
    document.getElementById('payment-status').textContent = status.charAt(0).toUpperCase() + status.slice(1);

    // Set reference
    document.getElementById('payment-reference').textContent = reference || 'N/A';

    // Set phone
    document.getElementById('payment-phone').textContent = phone || 'N/A';

    // Set created date
    document.getElementById('payment-created').textContent = createdAt;

    // Set appointment details
    const appointmentDetails = document.getElementById('appointment-details');
    if (appointmentId && patientName) {
        appointmentDetails.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>Appointment #${appointmentId}</strong><br>
                    <small class="text-muted">Patient: ${patientName}</small>
                </div>
                <i class="mdi mdi-calendar-check text-primary"></i>
            </div>
        `;
    } else {
        appointmentDetails.innerHTML = '<p class="mb-0 text-muted">No appointment associated</p>';
    }

    // Show modal using jQuery
    $('#paymentDetailsModal').modal('show');
}
</script>
@endpush