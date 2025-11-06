@extends('layouts.base')

@section('content')
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="mdi mdi-email-open me-2"></i>Accept Invitation
                    </h4>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h5>You've been invited to join</h5>
                        <h3 class="text-success">{{ $invitation->school->name }}</h3>
                        <p class="text-muted">
                            As: <strong>{{ ucwords(str_replace('-', ' ', str_replace('school-', '', $invitation->role))) }}</strong>
                        </p>
                    </div>

                    <form action="{{ route('school.invitation.accept.post', $invitation->token) }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" required value="{{ old('name') }}">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" 
                                   value="{{ $invitation->email }}" disabled>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                   id="password" name="password" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Must be at least 8 characters</small>
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="password_confirmation" 
                                   name="password_confirmation" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="mdi mdi-check-circle me-2"></i>Accept Invitation
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center text-muted">
                    <small>
                        This invitation expires on {{ $invitation->expires_at->format('F d, Y') }}
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
