@extends('layouts.base')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Doctor Availabilities Management</h4>
                <p class="card-text text-muted">Manage availability schedules for all doctors</p>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <form method="GET" class="mb-4">
                    <div class="row">
                        <div class="col-md-3">
                            <input type="text" name="name" class="form-control" placeholder="Search by doctor name" value="{{ $filters['name'] ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="specialization" class="form-control" placeholder="Search by specialization" value="{{ $filters['specialization'] ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <select name="day" class="form-control">
                                <option value="">All days</option>
                                <option value="monday" {{ ($filters['day'] ?? '') == 'monday' ? 'selected' : '' }}>Monday</option>
                                <option value="tuesday" {{ ($filters['day'] ?? '') == 'tuesday' ? 'selected' : '' }}>Tuesday</option>
                                <option value="wednesday" {{ ($filters['day'] ?? '') == 'wednesday' ? 'selected' : '' }}>Wednesday</option>
                                <option value="thursday" {{ ($filters['day'] ?? '') == 'thursday' ? 'selected' : '' }}>Thursday</option>
                                <option value="friday" {{ ($filters['day'] ?? '') == 'friday' ? 'selected' : '' }}>Friday</option>
                                <option value="saturday" {{ ($filters['day'] ?? '') == 'saturday' ? 'selected' : '' }}>Saturday</option>
                                <option value="sunday" {{ ($filters['day'] ?? '') == 'sunday' ? 'selected' : '' }}>Sunday</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary mr-2">
                                <i class="fa fa-search"></i> Filter
                            </button>
                            <a href="{{ route('doctor.all-availabilities') }}" class="btn btn-secondary">
                                <i class="fa fa-times"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>

                <!-- Doctors List -->
                @if($doctors->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Doctor</th>
                                    <th>Specialization</th>
                                    <th>Contact</th>
                                    <th>Monday</th>
                                    <th>Tuesday</th>
                                    <th>Wednesday</th>
                                    <th>Thursday</th>
                                    <th>Friday</th>
                                    <th>Saturday</th>
                                    <th>Sunday</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($doctors as $doctor)
                                <tr>
                                    <td>
                                        <strong>{{ $doctor->display_name }}</strong>
                                    </td>
                                    <td>{{ $doctor->specialization }}</td>
                                    <td>{{ $doctor->contact }}</td>
                                    @php
                                        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                                    @endphp
                                    @foreach($days as $day)
                                        @php
                                            $availability = $doctor->availabilities->where('day', $day)->first();
                                        @endphp
                                        <td>
                                            @if($availability && $availability->available)
                                                <span class="badge badge-success">
                                                    <i class="fa fa-check"></i> Yes
                                                </span>
                                                <br>
                                                <small class="text-muted">{{ $availability->max_appointments }} slots</small>
                                            @else
                                                <span class="badge badge-secondary">
                                                    <i class="fa fa-times"></i> No
                                                </span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td>
                                        <a href="{{ route('doctor.availability', $doctor->id) }}" class="btn btn-sm btn-primary">
                                            <i class="fa fa-edit"></i> Manage
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center">
                        {{ $doctors->appends(request()->query())->links() }}
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> No doctors found matching your criteria.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="row mt-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h5 class="card-title">{{ $doctors->total() }}</h5>
                <p class="card-text">Total Doctors</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h5 class="card-title">{{ $doctors->where('availabilities', '!=', collect())->count() }}</h5>
                <p class="card-text">Doctors with Availability Set</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h5 class="card-title">
                    @php
                        $availableToday = $doctors->filter(function($doctor) {
                            return $doctor->isAvailableOnDay(strtolower(now()->format('l')));
                        })->count();
                    @endphp
                    {{ $availableToday }}
                </h5>
                <p class="card-text">Available Today</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h5 class="card-title">
                    @php
                        $totalSlots = $doctors->sum(function($doctor) {
                            return $doctor->availabilities->where('available', true)->sum('max_appointments');
                        });
                    @endphp
                    {{ $totalSlots }}
                </h5>
                <p class="card-text">Total Available Slots</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Auto-submit form on filter change (optional)
    $('select[name="day"]').change(function() {
        $(this).closest('form').submit();
    });

    // Add loading state to filter button
    $('form button[type="submit"]').click(function() {
        $(this).html('<i class="fa fa-spinner fa-spin"></i> Filtering...').prop('disabled', true);
    });
});
</script>
@endpush