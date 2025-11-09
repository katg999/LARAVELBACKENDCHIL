@extends('layouts.auth')

@section('title', 'Setup Health Facility Admin Account')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Setup Admin Account - {{ $healthFacility->name }}</h4>
                </div>
                <div class="card-body p-5">
                    <div class="alert alert-info">
                        <i class="mdi mdi-information"></i>
                        <strong>Important:</strong> You've verified ownership of <strong>{{ $healthFacility->email }}</strong>. 
                        Now create your personal admin account using your own email address (not the facility email).
                    </div>

                    <form method="POST" action="{{ route('initial-admin-setup.health-facility.store', $healthFacility->id) }}">
                        @csrf

                        <div class="form-group mb-3">
                            <label for="name">Your Full Name</label>
                            <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" 
                                   name="name" value="{{ old('name') }}" required autofocus>
                            @error('name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="email">Your Personal Email</label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" 
                                   name="email" value="{{ old('email') }}" required>
                            <small class="form-text text-muted">
                                Use your personal email address, not {{ $healthFacility->email }}
                            </small>
                            @error('email')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="password">Password</label>
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" 
                                   name="password" required>
                            @error('password')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group mb-4">
                            <label for="password_confirmation">Confirm Password</label>
                            <input id="password_confirmation" type="password" class="form-control" 
                                   name="password_confirmation" required>
                        </div>

                        <button type="submit" class="btn btn-success btn-block">
                            Create Admin Account
                        </button>
                    </form>

                    <div class="mt-4 text-center">
                        <p class="text-muted">
                            After creating your account, you'll be able to invite other staff members 
                            with specific roles (admin or medical personnel).
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
