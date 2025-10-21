@extends('layouts.base')

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h3 class="m-0">Wallet</h3>
    </div>

    @if(session('success'))
        <div class="alert alert-light text-dark">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
        <!-- Wallet Balance Card -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Current Balance</h5>
                    <h2 class="text-primary" id="currentBalance">
                        UGX {{ number_format($wallet->wallet_balance, 2) }}
                    </h2>
                    <p class="text-muted">{{ ucfirst(str_replace('_', ' ', $userType)) }}</p>
                </div>
            </div>
        </div>

        <!-- Deposit Card -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Deposit Money</h5>
                    <form id="depositForm">
                        @csrf
                        <div class="mb-3">
                            <label for="depositAmount" class="form-label">Amount (UGX)</label>
                            <input type="number" class="form-control" id="depositAmount" name="amount" min="0.01" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="depositDescription" class="form-label">Description (Optional)</label>
                            <input type="text" class="form-control" id="depositDescription" name="description" placeholder="e.g., Payment received">
                        </div>
                        <button type="submit" class="btn btn-success w-100" id="depositBtn">
                            <i class="mdi mdi-plus-circle me-2"></i>Deposit
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Withdraw Card -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Withdraw Money</h5>
                    <form id="withdrawForm">
                        @csrf
                        <div class="mb-3">
                            <label for="withdrawAmount" class="form-label">Amount (UGX)</label>
                            <input type="number" class="form-control" id="withdrawAmount" name="amount" min="0.01" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="withdrawDescription" class="form-label">Description (Optional)</label>
                            <input type="text" class="form-control" id="withdrawDescription" name="description" placeholder="e.g., Cash withdrawal">
                        </div>
                        <button type="submit" class="btn btn-warning w-100" id="withdrawBtn">
                            <i class="mdi mdi-minus-circle me-2"></i>Withdraw
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction History -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Transaction History</h5>
                    <div class="table-responsive">
                        <table class="table table-striped text-dark">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Description</th>
                                    <th>Balance After</th>
                                </tr>
                            </thead>
                            <tbody id="transactionTable">
                                @forelse($transactions as $transaction)
                                <tr>
                                    <td>{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $transaction->type === 'deposit' ? 'success' : 'warning' }}">
                                            {{ ucfirst($transaction->type) }}
                                        </span>
                                    </td>
                                    <td>UGX {{ number_format($transaction->amount, 2) }}</td>
                                    <td>{{ $transaction->description ?? 'N/A' }}</td>
                                    <td>UGX {{ number_format($transaction->balance_after, 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No transactions yet</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Deposit form submission
    document.getElementById('depositForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const submitBtn = document.getElementById('depositBtn');
        const originalText = submitBtn.innerHTML;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="mdi mdi-loading mdi-spin me-2"></i>Processing...';

        fetch('{{ route("wallet.deposit") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('currentBalance').textContent = 'UGX ' + parseFloat(data.new_balance).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                this.reset();
                showAlert('Deposit successful!', 'success');
            } else {
                showAlert(data.error || 'Deposit failed', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred', 'danger');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });

    // Withdraw form submission
    document.getElementById('withdrawForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const submitBtn = document.getElementById('withdrawBtn');
        const originalText = submitBtn.innerHTML;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="mdi mdi-loading mdi-spin me-2"></i>Processing...';

        fetch('{{ route("wallet.withdraw") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('currentBalance').textContent = 'UGX ' + parseFloat(data.new_balance).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                this.reset();
                showAlert('Withdrawal successful!', 'success');
            } else {
                showAlert(data.error || 'Withdrawal failed', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred', 'danger');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });

    function showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        const container = document.querySelector('.container-fluid');
        container.insertBefore(alertDiv, container.firstChild);

        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
});
</script>
@endpush
@endsection