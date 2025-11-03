@extends('layouts.base')

@section('title', $item ? 'Edit Duration' : 'Create Duration')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">{{ $item ? 'Edit Duration' : 'Create Duration' }}</h3>
                    <a href="{{ route('admin.durations.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>

                <form id="duration-form" method="POST" action="{{ $item ? route('admin.durations.update', $item->id) : route('admin.durations.store') }}">
                    @csrf

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="minutes">Minutes <span class="text-danger">*</span></label>
                                    <input type="number" name="minutes" id="minutes" class="form-control @error('minutes') is-invalid @enderror"
                                           value="{{ old('minutes', $item->minutes ?? '') }}" min="1" required>
                                    @error('minutes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="duration_type">Duration Type <span class="text-danger">*</span></label>
                                    <select name="duration_type" id="duration_type" class="form-control @error('duration_type') is-invalid @enderror" required>
                                        <option value="general" {{ old('duration_type', $item->duration_type ?? 'general') === 'general' ? 'selected' : '' }}>General</option>
                                        <option value="specialist" {{ old('duration_type', $item->duration_type ?? '') === 'specialist' ? 'selected' : '' }}>Specialist</option>
                                    </select>
                                    @error('duration_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="price">Price (UGX) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">UGX</span>
                                        </div>
                                        <input type="number" name="price" id="price" class="form-control @error('price') is-invalid @enderror"
                                               value="{{ old('price', $item->price ?? '') }}" step="0.01" min="0" required>
                                    </div>
                                    @error('price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" class="custom-control-input" id="is_active"
                                       {{ old('is_active', $item->is_active ?? true) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_active">Active</label>
                            </div>
                            <small class="form-text text-muted">Inactive durations won't be available for new appointments</small>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="btn-group" role="group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> {{ $item ? 'Update' : 'Create' }} Duration
                            </button>
                            <a href="{{ route('admin.durations.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#duration-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = new FormData(this);
        var url = form.attr('action');
        var method = '{{ $item ? "PUT" : "POST" }}';
        
        console.log('AJAX Request:', {
            url: url,
            method: method,
            csrfToken: $('meta[name="csrf-token"]').attr('content'),
            formData: Object.fromEntries(formData.entries())
        });
        
        $.ajax({
            url: url,
            method: method,
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            beforeSend: function() {
                form.find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
            },
            success: function(response) {
                window.location.href = '{{ route("admin.durations.index") }}';
            },
            error: function(xhr) {
                console.log('AJAX Error:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    responseJSON: xhr.responseJSON
                });
                form.find('button[type="submit"]').prop('disabled', false).html('<i class="fas fa-save"></i> {{ $item ? "Update" : "Create" }} Duration');
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    form.find('.invalid-feedback').remove();
                    form.find('.is-invalid').removeClass('is-invalid');
                    $.each(errors, function(field, messages) {
                        var input = form.find('[name="' + field + '"]');
                        input.addClass('is-invalid');
                        input.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                    });
                } else if (xhr.status === 401) {
                    alert('Session expired. Please log in again.');
                    window.location.href = '{{ route("login") }}';
                } else if (xhr.status === 403) {
                    alert('Access denied. Admin privileges required.');
                } else {
                    alert('An error occurred. Status: ' + xhr.status + '. Please try again.');
                }
            }
        });
    });
});
</script>
@endpush