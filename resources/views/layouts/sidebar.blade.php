@php
    $currentUser = auth()->user();
    $isAdmin = $currentUser && $currentUser->is_admin;
    $currentRoute = Route::currentRouteName() ?: '';
    $currentPath = request()->path();
    $routeLength = strlen($currentRoute);
@endphp
<!-- Debug Info: 
Current Route: '{{ $currentRoute }}'
Current Path: '{{ $currentPath }}'
Route Length: {{ $routeLength }}
Route Dump: @json($currentRoute)
-->
<!-- Debug Info: 
Current Route: {{ $currentRoute }}
Current Path: {{ $currentPath }}
-->
@if($isAdmin)
<ul class="nav">
    <li class="nav-item">
        <a class="nav-link {{ Str::startsWith($currentRoute, 'admin.index') ? 'active' : '' }}" href="{{ route('admin.index') }}">
            <i class="mdi mdi-view-dashboard menu-icon"></i>
            <span class="menu-title">Dashboard</span>
            <!-- Debug: Route={{ $currentRoute }}, IsActive={{ Str::startsWith($currentRoute, 'admin.index') ? 'true' : 'false' }} -->
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ Str::startsWith($currentRoute, 'admin.doctors.') ? 'active' : '' }}" href="{{ route('admin.doctors.index') }}">
            <i class="mdi mdi-stethoscope menu-icon"></i>
            <span class="menu-title">Doctors</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ Str::startsWith($currentRoute, 'admin.appointments.') ? 'active' : '' }}" href="{{ route('admin.appointments.index') }}">
            <i class="typcn typcn-calendar menu-icon"></i>
            <span class="menu-title">Appointments</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ Str::startsWith($currentRoute, 'admin.payments.') ? 'active' : '' }}" href="{{ route('admin.payments.index') }}">
            <i class="mdi mdi-credit-card menu-icon"></i>
            <span class="menu-title">Payments</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ Str::startsWith($currentRoute, 'admin.transactions.') ? 'active' : '' }}" href="{{ route('admin.transactions.index') }}">
            <i class="mdi mdi-receipt menu-icon"></i>
            <span class="menu-title">Transactions</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ Str::startsWith($currentRoute, 'admin.patients.') ? 'active' : '' }}" href="{{ route('admin.patients.index') }}">
            <i class="typcn typcn-user menu-icon"></i>
            <span class="menu-title">Patients</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ Str::startsWith($currentRoute, 'admin.users.') ? 'active' : '' }}" href="{{ route('admin.model.index', 'users') }}">
            <i class="mdi mdi-account-group menu-icon"></i>
            <span class="menu-title">Company Admins</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ Str::startsWith($currentRoute, 'admin.schools.') ? 'active' : '' }}" href="{{ route('admin.schools.index') }}">
            <i class="mdi mdi-school menu-icon"></i>
            <span class="menu-title">Schools</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ Str::startsWith($currentRoute, 'admin.health-facilities.') ? 'active' : '' }}" href="{{ route('admin.health-facilities.index') }}">
            <i class="mdi mdi-hospital-building menu-icon"></i>
            <span class="menu-title">Health Facilities</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ Str::startsWith($currentRoute, 'admin.invitations.') ? 'active' : '' }}" href="{{ route('admin.invitations.index') }}">
            <i class="mdi mdi-account-multiple-plus menu-icon"></i>
            <span class="menu-title">Manage Invitations</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ Str::startsWith($currentRoute, 'admin.doctor-availabilities.') ? 'active' : '' }}" href="{{ route('admin.doctor-availabilities.index') }}">
            <i class="mdi mdi-clock menu-icon"></i>
            <span class="menu-title">Doctor Availabilities</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ Str::startsWith($currentRoute, 'admin.durations.') ? 'active' : '' }}" href="{{ route('admin.durations.index') }}">
            <i class="mdi mdi-timer menu-icon"></i>
            <span class="menu-title">Durations</span>
        </a>
    </li>
</ul>
@elseif(isset($school))
<ul class="nav">
    <li class="nav-item">
        <a class="nav-link {{ request()->is('school-dashboard') ? 'active' : '' }}" href="{{ route('school.dashboard', ['school' => $school->id]) }}">
            <i class="typcn typcn-device-desktop menu-icon"></i>
            <span class="menu-title">Dashboard</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->is('students*') ? 'active' : '' }}" href="{{ route('students', ['school' => $school->id]) }}">
            <i class="typcn typcn-user menu-icon"></i>
            <span class="menu-title">Students</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->is('book-doctor*') ? 'active' : '' }}" href="{{ route('book-doctor', ['school' => $school->id]) }}">
            <i class="mdi mdi-calendar-clock menu-icon"></i>
            <span class="menu-title">Appointments</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->is('lab-tests*') ? 'active' : '' }}" href="{{ route('lab-tests', ['school' => $school->id]) }}">
            <i class="mdi mdi-flask menu-icon"></i>
            <span class="menu-title">Lab Tests</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->is('transactions*') ? 'active' : '' }}" href="{{ route('school.transactions', ['school' => $school->id]) }}">
            <i class="mdi mdi-square-inc-cash menu-icon"></i>
            <span class="menu-title">Transactions</span>
        </a>
    </li>
</ul>
@elseif(isset($healthFacility))
<ul class="nav">
    <li class="nav-item">
        <a class="nav-link {{ request()->is('health-facility/dashboard*') ? 'active' : '' }}" href="{{ route('health-facility.dashboard') }}">
            <i class="mdi mdi-view-dashboard menu-icon"></i>
            <span class="menu-title">Dashboard</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->is('health-facility/patients*') ? 'active' : '' }}" href="{{ route('health-facility.patients') }}">
            <i class="mdi mdi-account-multiple menu-icon"></i>
            <span class="menu-title">Patients</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->is('health-facility/book-doctor*') ? 'active' : '' }}" href="{{ route('health-facility.book-doctor') }}">
            <i class="mdi mdi-calendar-plus menu-icon"></i>
            <span class="menu-title">Appointments</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->is('health-facility/staff*') ? 'active' : '' }}" href="{{ route('health-facility.staff') }}">
            <i class="mdi mdi-account-group menu-icon"></i>
            <span class="menu-title">Staff</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->is('health-facility/transactions*') ? 'active' : '' }}" href="{{ route('health-facility.transactions') }}">
            <i class="mdi mdi-square-inc-cash menu-icon"></i>
            <span class="menu-title">Transactions</span>
        </a>
    </li>
</ul>
@elseif(isset($doctor))
<ul class="nav">
    <li class="nav-item">
        <a class="nav-link {{ request()->is('doctor/dashboard*') ? 'active' : '' }}" href="{{ route('doctor.dashboard') }}">
            <i class="typcn typcn-device-desktop menu-icon"></i>
            <span class="menu-title">Dashboard</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->is('doctor/appointments*') ? 'active' : '' }}" href="{{ route('doctor.appointments') }}">
            <i class="typcn typcn-calendar menu-icon"></i>
            <span class="menu-title">Appointments</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->is('doctor/meeting-link*') ? 'active' : '' }}" href="{{ route('doctor.meeting-link') }}">
            <i class="typcn typcn-video menu-icon"></i>
            <span class="menu-title">Meeting link</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->is('doctor/availability*') ? 'active' : '' }}" href="{{ route('doctor.availability') }}">
            <i class="mdi mdi-calendar-clock menu-icon"></i>
            <span class="menu-title">My Availability</span>
        </a>
    </li>
</ul>
@elseif(isset($admin))
<ul class="nav">
    <li class="nav-item">
        <a class="nav-link {{ request()->is('admin/doctor-availabilities*') ? 'active' : '' }}" href="{{ route('admin.doctor-availabilities.index') }}">
            <i class="typcn typcn-time menu-icon"></i>
            <span class="menu-title">Doctor Availabilities</span>
        </a>
    </li>
</ul>
@endif