@extends('layouts.auth')

@section('title','Admin Login')
@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7 col-xl-6">
                <div class="card shadow-lg border-0 rounded-3">
                    <!-- Header -->
                    <div class="card-header bg-magenta text-white text-center py-4">
                        <div class="mb-3">
                            <img src="{{ asset('images/easemed-logo-dark.svg') }}" alt="Easemed" class="img-fluid" style="height: 50px; width: auto;">
                        </div>
                        <h2 class="h4 mb-0 fw-bold">Admin Portal</h2>
                        <p class="mb-0 opacity-75">Sign in to access your dashboard</p>
                    </div>

                    <!-- Body -->
                    <div class="card-body p-4 p-lg-5 py-5">
                        <!-- Error / Status container (used by server render and AJAX) -->
                        <div id="loginErrors">
                            @if($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if(session('status'))
                                <div class="alert alert-success">
                                    {{ session('status') }}
                                </div>
                            @endif
                        </div>

                        <!-- Login Form -->
                        <form id="loginForm" method="POST" action="{{ route('login.post') }}" novalidate>
                            @csrf

                            <!-- Email Field -->
                            <div class="mb-4">
                                <label for="email" class="form-label fw-bold text-muted mb-3">Email Address</label>
                                <div class="input-group mb-3">
                                    <span class="input-group-text bg-light">
                                        <i class="mdi mdi-email text-muted"></i>
                                    </span>
                                    <input type="email"
                                           id="email"
                                           name="email"
                                           class="form-control form-control-lg @error('email') is-invalid @enderror"
                                           value="{{ old('email') }}"
                                           placeholder="Enter your email address"
                                           required
                                           autocomplete="email"
                                           autofocus>
                                </div>
                                @error('email')
                                    <div class="text-danger small mt-1">
                                        <i class="mdi mdi-alert-circle me-1"></i>{{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <!-- Password Field -->
                            <div class="mb-4">
                                <label for="password" class="form-label fw-bold text-muted mb-3">Password</label>
                                <div class="input-group mb-3">
                                    <span class="input-group-text bg-light">
                                        <i class="mdi mdi-lock text-muted"></i>
                                    </span>
                                    <input type="password"
                                           id="password"
                                           name="password"
                                           class="form-control form-control-lg @error('password') is-invalid @enderror"
                                           placeholder="Enter your password"
                                           required
                                           autocomplete="current-password">
                                </div>
                                @error('password')
                                    <div class="text-danger small mt-1">
                                        <i class="mdi mdi-alert-circle me-1"></i>{{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <!-- Remember Me & Forgot Password -->
                            <div class="my-2 d-flex justify-content-between align-items-center">
                                <div class="form-check">
                                    <label class="form-check-label text-muted">
                                        <input type="checkbox" class="form-check-input" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                        Keep me signed in
                                    </label>
                                </div>
                                <a href="{{ route('password.request') }}" class="auth-link text-black">Forgot password?</a>
                            </div>

                            <!-- Submit Button -->
                            <div class="mt-4">
                                <button type="submit" id="loginBtn" style="background-color: #CC00C6; border-color: #CC00C6; color: white;" class="btn btn-lg w-100 fw-bold rounded-3 py-3">
                                    <span class="btn-text">
                                        <i class="mdi mdi-login me-2"></i>Sign In
                                    </span>
                                    <span class="btn-loading d-none">
                                        <i class="mdi mdi-loading mdi-spin me-2"></i>Signing In...
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Footer -->
                    <div class="card-footer bg-light text-center py-3">
                        <small class="text-muted">
                            <i class="mdi mdi-shield me-1"></i>
                            © {{ date('Y') }} Easemed. Secure admin access only.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    const btnText = loginBtn.querySelector('.btn-text');
    const btnLoading = loginBtn.querySelector('.btn-loading');

    // Form validation
    const inputs = loginForm.querySelectorAll('input[required]');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });

        input.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                validateField(this);
            }
        });
    });

    function validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let message = '';

        if (field.type === 'email') {
            if (!value) {
                isValid = false;
                message = 'Email address is required.';
            } else {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                isValid = emailRegex.test(value);
                message = isValid ? '' : 'Please enter a valid email address.';
            }
        } else if (field.type === 'password') {
            if (!value) {
                isValid = false;
                message = 'Password is required.';
            } else if (value.length < 6) {
                isValid = false;
                message = 'Password must be at least 6 characters long.';
            }
        }

        field.classList.toggle('is-invalid', !isValid);
        field.classList.toggle('is-valid', isValid && value.length > 0);

        // Update or create feedback element
        let feedback = field.parentElement.parentElement.querySelector('.text-danger');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'text-danger small mt-1';
            field.parentElement.parentElement.appendChild(feedback);
        }

        if (message) {
            feedback.innerHTML = `<i class="mdi mdi-alert-circle me-1"></i>${message}`;
            feedback.style.display = 'block';
        } else {
            feedback.style.display = 'none';
        }

        return isValid;
    }

    // Form submission
    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Validate all fields
        let isFormValid = true;
        inputs.forEach(input => {
            if (!validateField(input)) {
                isFormValid = false;
            }
        });

        if (!isFormValid) {
            return;
        }

        // Show loading state
        loginBtn.disabled = true;
        btnText.classList.add('d-none');
        btnLoading.classList.remove('d-none');

        // Clear previous errors inside loginErrors container
        const errorsContainer = document.getElementById('loginErrors');
        if (errorsContainer) {
            errorsContainer.innerHTML = '';
        }

        try {
            const formData = new FormData(this);
            const response = await fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                // Success - redirect
                window.location.href = data.redirect || '/admin';
            } else {
                // Show errors
                showErrors(data.errors || data.message);
            }
        } catch (error) {
            console.error('Login error:', error);
            showErrors('An unexpected error occurred. Please try again.');
        } finally {
            // Reset loading state
            loginBtn.disabled = false;
            btnText.classList.remove('d-none');
            btnLoading.classList.add('d-none');
        }
    });

    function showErrors(errors) {
        // Clear previous errors
        const errorsContainer = document.getElementById('loginErrors');
        if (errorsContainer) {
            errorsContainer.innerHTML = '';
        }

        let errorHtml = '';

        if (typeof errors === 'string') {
            errorHtml = `<div class="alert alert-danger">${errors}</div>`;
        } else if (typeof errors === 'object' && errors !== null) {
            const errorMessages = [];
            for (const field in errors) {
                const messages = Array.isArray(errors[field]) ? errors[field] : [errors[field]];
                errorMessages.push(...messages);
            }
            
            if (errorMessages.length > 0) {
                errorHtml = `<div class="alert alert-danger">
                    <ul class="mb-0">
                        ${errorMessages.map(msg => `<li>${msg}</li>`).join('')}
                    </ul>
                </div>`;
            }
        }

        if (errorHtml && errorsContainer) {
            errorsContainer.innerHTML = errorHtml;
        }
    }

    // Auto-focus first empty field
    const firstEmptyField = Array.from(inputs).find(input => !input.value);
    if (firstEmptyField) {
        firstEmptyField.focus();
    }
});
</script>
@endsection

<style>
/* Magenta background for login header */
.bg-magenta {
    background-color: #FF00FF !important; /* Magenta color */
}

/* Auth link styling */
.auth-link {
    color: #6c757d !important;
    text-decoration: none;
}

.auth-link:hover {
    color: #495057 !important;
    text-decoration: underline;
}

/* Form validation styling */
.was-validated .form-control:invalid,
.form-control.is-invalid {
    border-color: #dc3545;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23dc3545' viewBox='0 0 12 12'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 4.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.was-validated .form-control:valid,
.form-control.is-valid {
    border-color: #28a745;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8' viewBox='0 0 8 8'%3e%3cpath fill='%2328a745' d='m2.3 6.73L.82 4.78l1.11-1.05L2.3 4.6l3.4-3.55L6.82 2.5z'/%3e%3c/svg%3e");
}

/* Button loading state */
.btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

/* Focus states */
.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.form-control.is-invalid:focus {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}
</style>
