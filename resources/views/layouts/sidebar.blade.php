@if(isset($school))
<ul class="nav">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('school.dashboard') ? 'active' : '' }}" href="{{ route('school.dashboard', ['school' => $school]) }}">
            <i class="typcn typcn-device-desktop menu-icon"></i>
            <span class="menu-title">Dashboard</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('students') ? 'active' : '' }}" href="{{ route('students', ['school' => $school]) }}">
            <i class="typcn typcn-user menu-icon"></i>
            <span class="menu-title">Students</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('book-doctor') ? 'active' : '' }}" href="{{ route('book-doctor', ['school' => $school])}}">
            <i class="mdi mdi-calendar-clock menu-icon"></i>
            <span class="menu-title">Appointments</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('book-doctor') ? 'active' : '' }}" href="{{ route('book-doctor', ['school' => $school])}}">
            <i class="mdi mdi-square-inc-cash menu-icon"></i>
            <span class="menu-title">Transactions</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('wallet.index') ? 'active' : '' }}" href="{{ route('wallet.index', ['type' => 'school', 'id' => $school->id]) }}">
            <i class="mdi mdi-wallet menu-icon"></i>
            <span class="menu-title">Wallet</span>
        </a>
    </li>
</ul>
@elseif(isset($healthFacility))
<ul class="nav">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('health-facility.dashboard') ? 'active' : '' }}" href="{{ route('health-facility.dashboard', ['id' => $healthFacility->id]) }}">
            <i class="mdi mdi-view-dashboard menu-icon"></i>
            <span class="menu-title">Dashboard</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('health-facility.patients') ? 'active' : '' }}" href="{{ route('health-facility.patients', ['id' => $healthFacility->id]) }}">
            <i class="mdi mdi-account-multiple menu-icon"></i>
            <span class="menu-title">Patients</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('health-facility.book-doctor') ? 'active' : '' }}" href="{{ route('health-facility.book-doctor', ['id' => $healthFacility->id]) }}">
            <i class="mdi mdi-calendar-plus menu-icon"></i>
            <span class="menu-title">Appointments</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('health-facility.staff') ? 'active' : '' }}" href="{{ route('health-facility.staff', ['id' => $healthFacility->id]) }}">
            <i class="mdi mdi-account-group menu-icon"></i>
            <span class="menu-title">Staff</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('health-facility.transactions') ? 'active' : '' }}" href="{{ route('health-facility.transactions', ['id' => $healthFacility->id]) }}">
            <i class="mdi mdi-square-inc-cash menu-icon"></i>
            <span class="menu-title">Transactions</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('wallet.index') ? 'active' : '' }}" href="{{ route('wallet.index', ['type' => 'health_facility', 'id' => $healthFacility->id]) }}">
            <i class="mdi mdi-wallet menu-icon"></i>
            <span class="menu-title">Wallet</span>
        </a>
    </li>
</ul>
@elseif(isset($doctor))
<ul class="nav">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('doctor.dashboard') ? 'active' : '' }}" href="{{ route('doctor.dashboard', ['doctorId' => $doctor->id]) }}">
            <i class="typcn typcn-device-desktop menu-icon"></i>
            <span class="menu-title">Dashboard</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('doctor.appointments') ? 'active' : '' }}" href="{{ route('doctor.appointments', ['doctorId' => $doctor->id]) }}">
            <i class="typcn typcn-calendar menu-icon"></i>
            <span class="menu-title">Appointments</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('doctor.meeting-link') ? 'active' : '' }}" href="{{ route('doctor.meeting-link', ['doctorId' => $doctor->id]) }}">
            <i class="typcn typcn-video menu-icon"></i>
            <span class="menu-title">Meeting link</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('doctor.availability') ? 'active' : '' }}" href="{{ route('doctor.availability', ['doctorId' => $doctor->id]) }}">
            <i class="mdi mdi-calendar-clock menu-icon"></i>
            <span class="menu-title">My Availability</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('doctor.availability') ? 'active' : '' }}" href="{{ route('doctor.availability', ['doctorId' => $doctor->id]) }}">
            <i class="mdi mdi-square-inc-cash menu-icon"></i>
            <span class="menu-title">Transactions</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('wallet.index') ? 'active' : '' }}" href="{{ route('wallet.index', ['type' => 'doctor', 'id' => $doctor->id]) }}">
            <i class="mdi mdi-wallet menu-icon"></i>
            <span class="menu-title">Wallet</span>
        </a>
    </li>
</ul>
@endif

@php $currentUser = $user ?? auth()->user(); @endphp
@if($currentUser && ($currentUser->is_admin ?? false))
<ul class="nav">
    <li class="nav-item">
        <a class="nav-link {{ request()->is('admin') ? 'active' : '' }}" href="{{ route('admin.index') }}">
            <i class="mdi mdi-view-dashboard menu-icon"></i>
            <span class="menu-title">Dashboard</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.doctors.index') ? 'active' : '' }}" href="{{ route('admin.doctors.index') }}">
            <i class="mdi mdi-stethoscope menu-icon"></i>
            <span class="menu-title">Doctors</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.appointments.index') ? 'active' : '' }}" href="{{ route('admin.appointments.index') }}">
            <i class="typcn typcn-calendar menu-icon"></i>
            <span class="menu-title">Appointments</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.payments.index') ? 'active' : '' }}" href="{{ route('admin.payments.index') }}">
            <i class="mdi mdi-credit-card menu-icon"></i>
            <span class="menu-title">Payments</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.payments.index') ? 'active' : '' }}" href="{{ route('admin.payments.index') }}">
            <i class="mdi mdi-contrast menu-icon"></i>
            <span class="menu-title">Transactions</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.patients.index') ? 'active' : '' }}" href="{{ route('admin.patients.index') }}">
            <i class="typcn typcn-user menu-icon"></i>
            <span class="menu-title">Patients</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.schools.index') ? 'active' : '' }}" href="{{ route('admin.schools.index') }}">
            <i class="mdi mdi-school menu-icon"></i>
            <span class="menu-title">Schools</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.health-facilities.index') ? 'active' : '' }}" href="{{ route('admin.health-facilities.index') }}">
            <i class="mdi mdi-hospital-building menu-icon"></i>
            <span class="menu-title">Health Facilities</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('doctor.all-availabilities') ? 'active' : '' }}" href="{{ route('doctor.all-availabilities') }}">
            <i class="mdi mdi-clock menu-icon"></i>
            <span class="menu-title">Doctor Availabilities</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.contact-submissions') ? 'active' : '' }}" href="{{ route('admin.contact-submissions') }}">
            <i class="typcn typcn-mail menu-icon"></i>
            <span class="menu-title">Contact Submissions</span>
        </a>
    </li>
</ul>
@elseif(isset($admin))
<ul class="nav">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('doctor.all-availabilities') ? 'active' : '' }}" href="{{ route('doctor.all-availabilities') }}">
            <i class="typcn typcn-time menu-icon"></i>
            <span class="menu-title">Doctor Availabilities</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.contact-submissions') ? 'active' : '' }}" href="{{ route('admin.contact-submissions') }}">
            <i class="typcn typcn-mail menu-icon"></i>
            <span class="menu-title">Contact Submissions</span>
        </a>
    </li>
</ul>
@endif