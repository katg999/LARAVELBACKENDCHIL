@extends('layouts.base')

@section('content')
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <img src="{{ $doctor->file_url ?? asset('images/doctor.png') }}" alt="doctor" class="rounded-circle mb-3" style="width:120px;height:120px;object-fit:cover;">
                        <h4>{{ $doctor->name }}</h4>
                        <p class="text-muted">{{ $doctor->specialization ?? '' }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h5>Profile Details</h5>
                        <dl class="row">
                            <dt class="col-sm-3">Email</dt>
                            <dd class="col-sm-9">{{ $doctor->email }}</dd>

                            <dt class="col-sm-3">Contact</dt>
                            <dd class="col-sm-9">{{ $doctor->contact }}</dd>

                            <dt class="col-sm-3">School</dt>
                            <dd class="col-sm-9">{{ $doctor->school->name ?? '—' }}</dd>
                        </dl>
                        <a href="{{ route('doctor.meeting-link') }}" class="btn btn-sm btn-outline-secondary">Meeting Links</a>
                        <a href="{{ url('/doctor/'.$doctor->id.'/appointments') }}" class="btn btn-sm btn-outline-primary">Appointments</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
