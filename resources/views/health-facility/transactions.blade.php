@extends('layouts.base')

@php
use Illuminate\Support\Str;
@endphp

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h3 class="m-0">Transactions</h3>
    </div>

    @if(session('success'))
        <div class="alert alert-light text-dark">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">Transactions</h5>
            @if(isset($appointments) && $appointments->count())
            <div class="table-responsive">
                <table class="table table-striped text-dark">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Appointment Date</th>
                            <th>Duration</th>
                            <th>Appointment Status</th>
                            <th>Payment Status</th>
                            <th>Amount</th>
                            <th>Payment Reference</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($appointments as $appointment)
                        <tr>
                            <td>{{ $appointment->id }}</td>
                            <td>{{ $appointment->patient->name ?? 'N/A' }}</td>
                            <td>{{ $appointment->doctor->name ?? 'N/A' }}</td>
                            <td>{{ $appointment->appointment_time ? $appointment->appointment_time->format('M d, Y H:i') : 'N/A' }}</td>
                            <td>{{ $appointment->duration ? $appointment->duration->minutes . ' mins ' . ucfirst($appointment->duration->duration_type) : 'N/A' }}</td>
                            <td>
                                <span class="badge bg-{{ $appointment->status === 'confirmed' ? 'success' : ($appointment->status === 'cancelled' ? 'danger' : 'warning') }}">
                                    {{ ucfirst(str_replace('_', ' ', $appointment->status ?? 'pending')) }}
                                </span>
                            </td>
                            <td>
                                @if($appointment->payment_status)
                                    <span class="badge bg-{{ $appointment->payment_status === 'completed' ? 'success' : ($appointment->payment_status === 'failed' ? 'danger' : 'info') }}">
                                        {{ ucfirst($appointment->payment_status) }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary">Not Started</span>
                                @endif
                            </td>
                            <td>UGX {{ number_format($appointment->duration ? $appointment->duration->getPrice() : 0, 0) }}</td>
                            <td>
                                @if($appointment->payment_reference)
                                    <small class="text-muted">{{ Str::limit($appointment->payment_reference, 20) }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($appointment->payment_status !== 'completed' && $appointment->status !== 'cancelled')
                                    <button class="btn btn-sm btn-danger btn-cancel-appointment" data-id="{{ $appointment->id }}" title="Cancel Appointment">
                                        <i class="fas fa-times mr-1"></i>Cancel
                                    </button>
                                @elseif($appointment->status === 'cancelled')
                                    <button class="btn btn-sm btn-outline-danger btn-delete-appointment" data-id="{{ $appointment->id }}" title="Delete Appointment">
                                        <i class="fas fa-trash mr-1"></i>Delete
                                    </button>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-3">
                {{ $appointments->links() }}
            </div>
            @else
                <div class="alert alert-info">No transactions found.</div>
            @endif
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Appointment cancellation functionality
    $(document).on('click', '.btn-cancel-appointment', function() {
        const appointmentId = $(this).data('id');
        const button = $(this);
        const originalHtml = button.html();

        if (!confirm('Are you sure you want to cancel this appointment?')) {
            return;
        }

        // Disable button and show loading
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Cancelling...');

        $.ajax({
            url: `/appointments/${appointmentId}/cancel`,
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    const successAlert = `
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check mr-1"></i>
                            <strong>Success!</strong> Appointment cancelled successfully.
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    `;
                    $('.container-fluid').prepend(successAlert);

                    // Update the appointment row status
                    const row = button.closest('tr');
                    row.find('td:nth-child(6) .badge').removeClass('bg-warning bg-success').addClass('bg-danger').text('Cancelled');

                    // Remove the button
                    button.closest('td').html('<span class="text-muted">-</span>');

                    // Auto-hide alert after 3 seconds
                    setTimeout(function() {
                        $('.alert-success').fadeOut();
                    }, 3000);
                } else {
                    alert('Failed to cancel appointment: ' + (response.message || 'Unknown error'));
                    button.prop('disabled', false).html(originalHtml);
                }
            },
            error: function(xhr) {
                let errorMessage = 'Failed to cancel appointment.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                alert(errorMessage);
                button.prop('disabled', false).html(originalHtml);
            }
        });
    });

        // Handle delete appointment
    $(document).on('click', '.btn-delete-appointment', function() {
        const button = $(this);
        const appointmentId = button.data('id');

        if (!confirm('Are you sure you want to permanently delete this cancelled appointment? This action cannot be undone.')) {
            return;
        }

        const originalHtml = button.html();
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Deleting...');

        $.ajax({
            url: '/appointments/' + appointmentId,
            type: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    // Remove the row from the table
                    button.closest('tr').fadeOut(300, function() {
                        $(this).remove();
                    });
                    alert('Appointment deleted successfully.');
                } else {
                    alert('Failed to delete appointment: ' + (response.message || 'Unknown error'));
                    button.prop('disabled', false).html(originalHtml);
                }
            },
            error: function(xhr) {
                let errorMessage = 'Failed to delete appointment.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                alert(errorMessage);
                button.prop('disabled', false).html(originalHtml);
            }
        });
    });
});
</script>
});
</script>
@endsection