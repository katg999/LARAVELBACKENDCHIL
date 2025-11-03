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
                            <h1 class="h3 mb-1 fw-bold text-white">Transactions Management</h1>
                            <p class="mb-0 opacity-85">Manage payment transactions and their details</p>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createTransactionModal">
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
            <input type="text" id="admin-search" class="form-control" placeholder="Search by reference, transaction ID...">
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
            <select id="payment-filter" class="form-select form-control">
                <option value="">All Payments</option>
                @foreach($payments as $id => $name)
                    <option value="{{ $id }}">Payment #{{ $id }} - {{ $name }}</option>
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
            <i class="fa fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Transactions Table -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark">All Transactions</h6>
            </div>
        </div>

        <div class="card-body p-0">
            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0" id="transactions-table">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0 fw-semibold px-3 py-3">ID</th>
                            <th class="border-0 fw-semibold px-3 py-3">Amount</th>
                            <th class="border-0 fw-semibold px-3 py-3">Status</th>
                            <th class="border-0 fw-semibold px-3 py-3">Transaction ID</th>
                            <th class="border-0 fw-semibold px-3 py-3">Reference</th>
                            <th class="border-0 fw-semibold px-3 py-3">Provider</th>
                            <th class="border-0 fw-semibold px-3 py-3">Payment</th>
                            <th class="border-0 fw-semibold px-3 py-3">Created</th>
                            <th class="border-0 fw-semibold px-3 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $transaction)
                        <tr>
                            <td class="px-3 py-3 fw-medium">#{{ $transaction->id }}</td>
                            <td class="px-3 py-3">
                                <span class="fw-bold text-success">UGX {{ number_format($transaction->amount, 0) }}</span>
                            </td>
                            <td class="px-3 py-3">
                                @php
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'completed' => 'text-success bg-white border border-success',
                                        'successful' => 'text-success bg-white border border-success',
                                        'failed' => 'danger',
                                        'cancelled' => 'secondary'
                                    ];
                                    $statusColor = $statusColors[$transaction->status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $statusColor }} px-2 py-1">{{ ucfirst($transaction->status) }}</span>
                            </td>
                            <td class="px-3 py-3">
                                <code class="small">{{ $transaction->transaction_id ?? '-' }}</code>
                            </td>
                            <td class="px-3 py-3">
                                <code class="small">{{ $transaction->reference_id ?? '-' }}</code>
                            </td>
                            <td class="px-3 py-3">
                                <span class="text-muted">{{ ucfirst($transaction->provider ?? 'Unknown') }}</span>
                            </td>
                            <td class="px-3 py-3">
                                @if($transaction->payment && $transaction->payment->appointment)
                                    <div class="small">
                                        <div>Payment #{{ $transaction->payment->id }}</div>
                                        <div class="text-muted">Apt #{{ $transaction->payment->appointment->id }}</div>
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <div class="small text-muted">
                                    {{ optional($transaction->created_at)->format('M j, Y') }}
                                </div>
                                <div class="small text-muted">
                                    {{ optional($transaction->created_at)->format('g:i A') }}
                                </div>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <button class="btn btn-outline-info btn-sm me-1" title="View Details"
                                        data-toggle="modal" data-target="#transactionDetailsModal"
                                        data-transaction-id="{{ $transaction->id }}"
                                        data-amount="{{ $transaction->amount }}"
                                        data-status="{{ $transaction->status }}"
                                        data-transaction-id-val="{{ $transaction->transaction_id }}"
                                        data-reference="{{ $transaction->reference_id }}"
                                        data-provider="{{ $transaction->provider }}"
                                        data-webhook-event="{{ $transaction->webhook_event_type }}"
                                        data-payment-id="{{ $transaction->payment_id }}"
                                        data-created="{{ $transaction->created_at->format('M j, Y g:i A') }}"
                                        data-processed="{{ $transaction->processed_at ? $transaction->processed_at->format('M j, Y g:i A') : 'Not processed' }}">
                                    <i class="mdi mdi-eye"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <i class="fa fa-exchange-alt fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">No transactions found</h6>
                                <p class="text-muted small">Try adjusting your filters or add a new transaction.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-3">{{ $items->appends(request()->query())->links() }}</div>
</div>

<!-- Transaction Details Modal -->
<div class="modal fade" id="transactionDetailsModal" tabindex="-1" aria-labelledby="transactionDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="transactionDetailsModalLabel">
                    <i class="fa fa-exchange-alt me-2"></i>Transaction Details
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <!-- Transaction ID -->
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="text-muted mb-2" style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">
                                <i class="fa fa-hashtag me-2"></i>Transaction ID
                            </h6>
                            <h5 class="mb-0 fw-bold" id="modal-transaction-id">TXN-001</h5>
                        </div>
                    </div>

                    <!-- Amount -->
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="text-muted mb-2" style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">
                                <i class="fa fa-money-bill-wave me-2"></i>Amount
                            </h6>
                            <h5 class="mb-0 fw-bold text-success" id="modal-amount">UGX 100,000</h5>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="text-muted mb-2" style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">
                                <i class="fa fa-info-circle me-2"></i>Status
                            </h6>
                            <span class="badge fs-6 px-3 py-2" id="modal-status">Pending</span>
                        </div>
                    </div>

                    <!-- Provider -->
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="text-muted mb-2" style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">
                                <i class="fa fa-credit-card me-2"></i>Provider
                            </h6>
                            <h6 class="mb-0 fw-bold" id="modal-provider">MarzPay</h6>
                        </div>
                    </div>

                    <!-- Reference ID -->
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="text-muted mb-2" style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">
                                <i class="fa fa-link me-2"></i>Reference ID
                            </h6>
                            <code class="fs-6" id="modal-reference">REF-123456</code>
                        </div>
                    </div>

                    <!-- Payment ID -->
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="text-muted mb-2" style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">
                                <i class="fa fa-receipt me-2"></i>Payment ID
                            </h6>
                            <span class="fw-bold" id="modal-payment-id">PAY-001</span>
                        </div>
                    </div>

                    <!-- Webhook Event -->
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="text-muted mb-2" style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">
                                <i class="fa fa-bolt me-2"></i>Webhook Event
                            </h6>
                            <span class="badge bg-secondary text-white" id="modal-webhook-event">checkout.session.completed</span>
                        </div>
                    </div>

                    <!-- Created Date -->
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="text-muted mb-2" style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">
                                <i class="fa fa-calendar-plus me-2"></i>Created
                            </h6>
                            <div class="small text-muted" id="modal-created">Oct 23, 2025 2:30 PM</div>
                        </div>
                    </div>

                    <!-- Processed Date (Full Width) -->
                    <div class="col-12">
                        <div class="border rounded p-3">
                            <h6 class="text-muted mb-2" style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">
                                <i class="fa fa-clock me-2"></i>Processed At
                            </h6>
                            <div class="small text-muted" id="modal-processed">Not processed yet</div>
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
    const paymentFilter = document.getElementById('payment-filter');
    const dateFromInput = document.getElementById('date-from');
    const dateToInput = document.getElementById('date-to');

    function filterTransactions() {
        const q = (searchInput?.value || '').trim().toLowerCase();
        const selectedStatus = (statusFilter?.value || '').toLowerCase();
        const selectedPayment = paymentFilter?.value || '';
        const dateFrom = dateFromInput?.value || '';
        const dateTo = dateToInput?.value || '';

        // Get all table rows (excluding header and empty state)
        const rows = Array.from(document.querySelectorAll('#transactions-table tbody tr')).filter(row =>
            !row.querySelector('.fa-exchange-alt')
        );

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length < 9) return; // Skip if not enough cells

            const reference = cells[4]?.textContent?.toLowerCase() || '';
            const transactionId = cells[3]?.textContent?.toLowerCase() || '';
            const amount = cells[1]?.textContent?.toLowerCase() || '';
            const status = cells[2]?.textContent?.toLowerCase() || '';
            const payment = cells[6]?.textContent?.toLowerCase() || '';

            const matchesSearch = !q || reference.includes(q) || transactionId.includes(q) || amount.includes(q);
            const matchesStatus = !selectedStatus || status.includes(selectedStatus);
            const matchesPayment = !selectedPayment || payment.includes(selectedPayment);

            row.style.display = (matchesSearch && matchesStatus && matchesPayment) ? '' : 'none';
        });
    }

    // Filter event listeners
    if (searchInput) searchInput.addEventListener('input', filterTransactions);
    if (statusFilter) statusFilter.addEventListener('change', filterTransactions);
    if (paymentFilter) paymentFilter.addEventListener('change', filterTransactions);
    if (dateFromInput) dateFromInput.addEventListener('change', filterTransactions);
    if (dateToInput) dateToInput.addEventListener('change', filterTransactions);

    // Transaction Details Modal - Direct click handler
    document.addEventListener('click', function(e) {
        const eyeButton = e.target.closest('[data-target="#transactionDetailsModal"]');
        if (eyeButton) {
            e.preventDefault();
            e.stopPropagation();

            const transactionId = eyeButton.getAttribute('data-transaction-id');
            const amount = eyeButton.getAttribute('data-amount');
            const status = eyeButton.getAttribute('data-status');
            const transactionIdVal = eyeButton.getAttribute('data-transaction-id-val');
            const reference = eyeButton.getAttribute('data-reference');
            const provider = eyeButton.getAttribute('data-provider');
            const webhookEvent = eyeButton.getAttribute('data-webhook-event');
            const paymentId = eyeButton.getAttribute('data-payment-id');
            const created = eyeButton.getAttribute('data-created');
            const processed = eyeButton.getAttribute('data-processed');

            // Update modal title
            const modalTitle = document.querySelector('#transactionDetailsModal .modal-title');
            modalTitle.innerHTML = `<i class="fa fa-exchange-alt me-2"></i>Transaction #${transactionId} Details`;

            // Update modal content
            document.getElementById('modal-transaction-id').textContent = transactionIdVal || 'N/A';
            document.getElementById('modal-amount').textContent = `UGX ${parseInt(amount).toLocaleString()}`;
            document.getElementById('modal-status').textContent = status.charAt(0).toUpperCase() + status.slice(1);
            document.getElementById('modal-provider').textContent = provider || 'Unknown';
            document.getElementById('modal-reference').textContent = reference || 'N/A';
            document.getElementById('modal-payment-id').textContent = paymentId || 'N/A';
            document.getElementById('modal-webhook-event').textContent = webhookEvent || 'N/A';
            document.getElementById('modal-created').textContent = created;
            document.getElementById('modal-processed').textContent = processed;

            // Update status badge color
            const statusBadge = document.getElementById('modal-status');
            statusBadge.className = 'badge fs-6 px-3 py-2';

            const statusColors = {
                'pending': 'bg-warning text-dark',
                'completed': 'text-success bg-white border border-success',
                'successful': 'text-success bg-white border border-success',
                'failed': 'bg-danger',
                'cancelled': 'bg-secondary'
            };

            statusBadge.classList.add(statusColors[status] || 'bg-secondary');

            // Show modal
            $('#transactionDetailsModal').modal('show');
        }
    });
});
</script>
@endpush