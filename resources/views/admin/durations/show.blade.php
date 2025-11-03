@extends('layouts.base')

@push('styles')
<link href="{{ asset('css/admin-dashboard.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container-fluid px-3 py-2">
    <!-- Header Section with Gradient Background -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm bg-primary">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-white">
                            <h1 class="h3 mb-1 fw-bold text-white">Duration Details</h1>
                            <p class="mb-0 opacity-85">{{ $item->minutes }} minutes ({{ ucfirst($item->duration_type) }})</p>
                        </div>
                        <div class="d-flex gap-3">
                            <a href="{{ route('admin.durations.edit', $item->id) }}" class="btn btn-light btn-sm">
                                <i class="mdi mdi-pencil me-2"></i> Edit Duration
                            </a>
                            <a href="{{ route('admin.durations.index') }}" class="btn btn-outline-light btn-sm">
                                <i class="mdi mdi-arrow-left me-2"></i> Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Duration Details Card -->
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-timer me-2"></i>Duration Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-muted">ID</label>
                                <p class="h5 mb-0">{{ $item->id }}</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-muted">Duration</label>
                                <p class="h5 mb-0">
                                    <span class="badge bg-primary text-white">{{ $item->minutes }} minutes</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-muted">Duration Type</label>
                                <p class="h5 mb-0">
                                    <span class="badge bg-{{ $item->duration_type === 'general' ? 'info' : 'warning' }} text-white">
                                        {{ ucfirst($item->duration_type) }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-muted">Price</label>
                                <p class="h5 mb-0 text-success">UGX {{ number_format($item->price, 0) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-muted">Status</label>
                                <p class="mb-0">
                                    @if($item->is_active)
                                        <span class="badge bg-success text-white">
                                            <i class="fa fa-check-circle me-1"></i>Active
                                        </span>
                                    @else
                                        <span class="badge bg-secondary text-white">
                                            <i class="fa fa-pause-circle me-1"></i>Inactive
                                        </span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-muted">Created At</label>
                                <p class="mb-0">{{ optional($item->created_at)->format('M j, Y \a\t g:i A') ?? '-' }}</p>
                            </div>
                        </div>
                    </div>

                    @if($item->updated_at && $item->updated_at != $item->created_at)
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-muted">Last Updated</label>
                                <p class="mb-0">{{ optional($item->updated_at)->format('M j, Y \a\t g:i A') ?? '-' }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <div class="card-footer">
                    <div class="btn-group" role="group">
                        <a href="{{ route('admin.durations.edit', $item->id) }}" class="btn btn-primary">
                            <i class="mdi mdi-pencil"></i> Edit Duration
                        </a>
                        <a href="{{ route('admin.durations.index') }}" class="btn btn-secondary">
                            <i class="mdi mdi-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection