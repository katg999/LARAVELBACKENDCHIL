@extends('layouts.auth')

@section('title','Reset Password')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <div class="card shadow-lg border-0 rounded-lg" style="background-color: transparent;">
                <div class="card-header text-white py-4 d-flex flex-column align-items-center" style="background-color: #CC00C6;">
                    <img src="{{ asset('images/easemed-logo-white.svg') }}" alt="Easemed" style="height: 40px; width: auto;">
                    <h1 class="mt-2 fw-bold display-7">Reset Password</h1>
                </div>

                <div class="card-body p-5" style="background-color: rgba(255, 255, 255, 0.75);">
                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form id="reset-form" method="POST" action="{{ route('password.email') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold" style="color: #CC00C6;">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text" style="color: #CC00C6;"><i class="fas fa-envelope"></i></span>
                                <input id="email" type="email" class="form-control form-control-lg @error('email') is-invalid @enderror"
                                       name="email" value="{{ old('email') }}" required autocomplete="email" autofocus
                                       placeholder="Enter your email">
                            </div>
                            @error('email')
                                <div class="invalid-feedback d-block">
                                    <strong>{{ $message }}</strong>
                                </div>
                            @enderror
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-lg" style="background-color: #CC00C6; border-color: #CC00C6; color: white;">
                                <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                            </button>
                        </div>

                        <div class="text-center mt-3">
                            <a class="text-decoration-none" href="{{ route('login') }}" style="color: #CC00C6;">
                                Back to Login
                            </a>
                        </div>
                    </form>
                </div>

                <div class="card-footer text-center py-3 bg-light">
                    <small class="text-muted">© {{ date('Y') }} Easemed. All rights reserved.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('reset-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const submitButton = this.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';
    submitButton.disabled = true;

    fetch(this.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success !== false) {
            // Success
            let successHtml = '<div class="alert alert-success">Password reset link sent! Check your email.</div>';
            const existingAlert = document.querySelector('.alert');
            if (existingAlert) existingAlert.remove();
            const form = document.getElementById('reset-form');
            form.insertAdjacentHTML('afterbegin', successHtml);
        } else {
            // Error
            let errorHtml = '';
            if (data.errors) {
                for (let field in data.errors) {
                    let errorMsg = Array.isArray(data.errors[field]) ? data.errors[field][0] : data.errors[field];
                    errorHtml += '<div class="alert alert-danger">' + errorMsg + '</div>';
                }
            } else if (data.message) {
                errorHtml = '<div class="alert alert-danger">' + data.message + '</div>';
            }
            const existingAlert = document.querySelector('.alert');
            if (existingAlert) existingAlert.remove();
            const form = document.getElementById('reset-form');
            form.insertAdjacentHTML('afterbegin', errorHtml);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const errorHtml = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
        const existingAlert = document.querySelector('.alert');
        if (existingAlert) existingAlert.remove();
        const form = document.getElementById('reset-form');
        form.insertAdjacentHTML('afterbegin', errorHtml);
    })
    .finally(() => {
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    });
});
</script>
@endsection
