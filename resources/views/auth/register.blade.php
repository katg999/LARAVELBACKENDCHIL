@extends('layouts.auth')

@section('title','Admin Registration')
@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7 col-xl-6">
                <div class="card shadow-lg border-0 rounded-3">
                    <!-- Header -->
                    <div class="card-header bg-magenta text-white text-center py-4">
                        <div class="mb-3">
                            <img src="{{ asset('images/emoji-logo-black.svg') }}" alt="KETI AI" class="img-fluid" style="height: 50px; width: auto;">
                        </div>
                        <h2 class="h4 mb-0 fw-bold">
                            @if(isset($invitation) && $invitation)
                                Accept Invitation
                            @elseif(isset($existingUser) && $existingUser)
                                Accept Admin Invitation
                            @else
                                Admin Registration
                            @endif
                        </h2>
                        <p class="mb-0 opacity-75">
                            @if(isset($invitation) && $invitation)
                                Create your account to join the team
                            @elseif(isset($existingUser) && $existingUser)
                                Login to accept your admin invitation
                            @else
                                Create your admin account
                            @endif
                        </p>
                    </div>

                    <!-- Body -->
                    <div class="card-body p-4 p-lg-5 py-5">
                        <!-- Invitation Info -->
                        @if(isset($invitation) && $invitation)
                            <div class="alert alert-info border-0 rounded-3 mb-4">
                                <div class="d-flex align-items-center">
                                    <i class="mdi mdi-email-open text-info me-3 fs-4"></i>
                                    <div>
                                        <h6 class="mb-1 fw-bold">Accepting Invitation</h6>
                                        <p class="mb-0 text-muted">
                                            You're creating an account to join
                                            <strong class="text-info">
                                                @if($invitationType === 'school')
                                                    {{ $invitation->school->name }}
                                                @else
                                                    {{ $invitation->healthFacility->name }}
                                                @endif
                                            </strong>
                                            as <strong>{{ ucwords(str_replace('-', ' ', str_replace($invitationType . '-', '', $invitation->role))) }}</strong>
                                        </p>
                                        <small class="text-muted">
                                            Invitation expires: {{ $invitation->expires_at->format('F d, Y') }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Error / Status container (used by server render and AJAX) -->
                        <div id="registerErrors">
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

                        <!-- Registration Form -->
                        <form id="registerForm" method="POST" action="{{ route('register') }}" novalidate>
                            @csrf

                            <!-- Hidden invitation fields -->
                            @if(isset($invitationToken) && $invitationToken)
                                <input type="hidden" name="invitation_token" value="{{ $invitationToken }}">
                                <input type="hidden" name="invitation_type" value="{{ $invitationType }}">
                            @endif

                            @if(isset($invite))
                                <input type="hidden" name="invite_token" value="{{ $invite->token }}">
                            @endif

                            <!-- Name Field -->
                            @if(!isset($existingUser) || !$existingUser)
                            <div class="mb-4">
                                <label for="name" class="form-label fw-bold text-muted mb-3">Full Name</label>
                                <div class="input-group mb-3">
                                    <span class="input-group-text bg-light">
                                        <i class="mdi mdi-account text-muted"></i>
                                    </span>
                                    <input type="text"
                                           id="name"
                                           name="name"
                                           class="form-control form-control-lg @error('name') is-invalid @enderror"
                                           value="{{ old('name') }}"
                                           placeholder="Enter your full name"
                                           required
                                           autocomplete="name">
                                </div>
                                @error('name')
                                    <div class="text-danger small mt-1">
                                        <i class="mdi mdi-alert-circle me-1"></i>{{ $message }}
                                    </div>
                                @enderror
                            </div>
                            @endif

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
                                           value="{{ old('email', $email ?? '') }}"
                                           placeholder="Enter your email address"
                                           required
                                           autocomplete="email"
                                           {{ (isset($email) && !empty($email)) ? 'readonly' : '' }}>
                                </div>
                                @error('email')
                                    <div class="text-danger small mt-1">
                                        <i class="mdi mdi-alert-circle me-1"></i>{{ $message }}
                                    </div>
                                @enderror
                                @if(isset($invitation) && $invitation)
                                    <small class="text-muted">
                                        <i class="mdi mdi-information me-1"></i>
                                        Email is pre-filled from your invitation
                                    </small>
                                @endif
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
                                           autocomplete="{{ (isset($existingUser) && $existingUser) ? 'current-password' : 'new-password' }}"
                                           {{ (!isset($existingUser) || !$existingUser) ? '' : 'autofocus' }}>
                                </div>
                                @error('password')
                                    <div class="text-danger small mt-1">
                                        <i class="mdi mdi-alert-circle me-1"></i>{{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <!-- Confirm Password Field -->
                            @if(!isset($existingUser) || !$existingUser)
                            <div class="mb-4">
                                <label for="password_confirmation" class="form-label fw-bold text-muted mb-3">Confirm Password</label>
                                <div class="input-group mb-3">
                                    <span class="input-group-text bg-light">
                                        <i class="mdi mdi-lock-check text-muted"></i>
                                    </span>
                                    <input type="password"
                                           id="password_confirmation"
                                           name="password_confirmation"
                                           class="form-control form-control-lg"
                                           placeholder="Confirm your password"
                                           autocomplete="new-password">
                                </div>
                            </div>
                            @endif

                            <!-- Submit Button -->
                            <div class="mt-4">
                                <button type="submit" id="registerBtn" class="btn btn-dark btn-lg w-100 fw-bold rounded-3 py-3">
                                    <span class="btn-text">
                                        <i class="mdi mdi-account-plus me-2"></i>
                                        @if(isset($invitation) && $invitation)
                                            Accept Invitation & Create Account
                                        @elseif(isset($existingUser) && $existingUser)
                                            Accept Admin Invitation
                                        @else
                                            Create Account
                                        @endif
                                    </span>
                                    <span class="btn-loading d-none">
                                        <i class="mdi mdi-loading mdi-spin me-2"></i>
                                        @if(isset($invitation) && $invitation)
                                            Accepting Invitation...
                                        @elseif(isset($existingUser) && $existingUser)
                                            Accepting Invitation...
                                        @else
                                            Creating Account...
                                        @endif
                                    </span>
                                </button>
                            </div>
                        </form>

                        <!-- Login Link -->
                        <div class="text-center mt-4">
                            <p class="text-muted mb-2">Already have an account?</p>
                            <a href="{{ route('login') }}" class="btn btn-outline-secondary rounded-3">
                                <i class="mdi mdi-login me-2"></i>Sign In
                            </a>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="card-footer bg-light text-center py-3">
                        <small class="text-muted">
                            <i class="mdi mdi-shield me-1"></i>
                            © {{ date('Y') }} KETI AI. Secure admin access only.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    const registerBtn = document.getElementById('registerBtn');
    const btnText = registerBtn.querySelector('.btn-text');
    const btnLoading = registerBtn.querySelector('.btn-loading');

    // Form validation
    const inputs = registerForm.querySelectorAll('input[required]');
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

        if (field.type === 'text' && field.name === 'name') {
            if (!value) {
                isValid = false;
                message = 'Name is required.';
            } else if (value.length < 2) {
                isValid = false;
                message = 'Name must be at least 2 characters long.';
            }
        } else if (field.type === 'email') {
            if (!value) {
                isValid = false;
                message = 'Email address is required.';
            } else {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                isValid = emailRegex.test(value);
                message = isValid ? '' : 'Please enter a valid email address.';
            }
        } else if (field.type === 'password') {
            if (field.name === 'password') {
                if (!value) {
                    isValid = false;
                    message = 'Password is required.';
                } else if (value.length < 8) {
                    isValid = false;
                    message = 'Password must be at least 8 characters long.';
                }
            } else if (field.name === 'password_confirmation') {
                const password = document.getElementById('password').value;
                if (!value) {
                    isValid = false;
                    message = 'Please confirm your password.';
                } else if (value !== password) {
                    isValid = false;
                    message = 'Passwords do not match.';
                }
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
    registerForm.addEventListener('submit', async function(e) {
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
        registerBtn.disabled = true;
        btnText.classList.add('d-none');
        btnLoading.classList.remove('d-none');

        // Clear previous errors inside registerErrors container
        const errorsContainer = document.getElementById('registerErrors');
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
                // Success - handle redirect based on invitation type
                @if(isset($invitationToken) && $invitationToken)
                    // For invitations, let the server redirect handle it
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        window.location.href = '/';
                    }
                @else
                    // For regular registration, redirect to login
                    window.location.href = '{{ route("login") }}';
                @endif
            } else {
                // Show errors
                showErrors(data.errors || data.message);
            }
        } catch (error) {
            console.error('Registration error:', error);
            showErrors('An unexpected error occurred. Please try again.');
        } finally {
            // Reset loading state
            registerBtn.disabled = false;
            btnText.classList.remove('d-none');
            btnLoading.classList.add('d-none');
        }
    });

    function showErrors(errors) {
        // Clear previous errors
        const errorsContainer = document.getElementById('registerErrors');
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

    // Auto-focus appropriate field
    @if(isset($existingUser) && $existingUser)
        document.getElementById('password').focus();
    @else
        const firstEmptyField = Array.from(inputs).find(input => !input.value);
        if (firstEmptyField) {
            firstEmptyField.focus();
        } else {
            document.getElementById('password').focus();
        }
    @endif
});
</script>
@endsection

<style>
/* Magenta background for registration header */
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
