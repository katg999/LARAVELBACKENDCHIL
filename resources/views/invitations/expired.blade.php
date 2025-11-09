@extends('layouts.base')

@section('content')
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">
                        <i class="mdi mdi-clock-alert me-2"></i>Invitation Expired
                    </h4>
                </div>
                <div class="card-body text-center py-5">
                    <i class="mdi mdi-calendar-remove text-danger" style="font-size: 72px;"></i>
                    <h3 class="mt-3">This invitation has expired</h3>
                    <p class="text-muted">
                        Please contact the administrator to request a new invitation link.
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
