@extends('layouts.base')

@section('content')
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="mdi mdi-check-circle me-2"></i>Already Accepted
                    </h4>
                </div>
                <div class="card-body text-center py-5">
                    <i class="mdi mdi-account-check text-success" style="font-size: 72px;"></i>
                    <h3 class="mt-3">This invitation has already been accepted</h3>
                    <p class="text-muted">
                        If you're having trouble logging in, please contact the administrator.
                    </p>
                    <a href="{{ route('login') }}" class="btn btn-primary mt-3">
                        <i class="mdi mdi-login me-2"></i>Go to Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
