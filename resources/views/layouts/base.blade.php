<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>
        @if(isset($school))
            {{ $school->name }} - Dashboard
        @elseif(isset($doctor))
            Dr. {{ $doctor->name }} - Dashboard
        @elseif(isset($healthFacility))
            {{ $healthFacility->name }} - Dashboard
        @else
            Dashboard
        @endif
    </title>
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/easemed-mark.svg') }}">
    <link rel="stylesheet" href="{{ asset('pollix/vendors/typicons/typicons.css') }}">
    <link rel="stylesheet" href="{{ asset('pollix/vendors/css/vendor.bundle.base.css') }}">
    <link rel="stylesheet" href="{{ asset('pollix/vendors/mdi/css/materialdesignicons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('pollix/vendors/select2/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('pollix/css/vertical-layout-light/style.css') }}">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <link rel="shortcut icon" href="{{ asset('images/easemed-mark.svg') }}" />

    {{-- Allow pages to push additional styles (icons, page-level CSS) --}}
    @stack('styles')



</head>

<body>
  <div class="container-scroller">
    <nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
      <div class="navbar-brand-wrapper d-flex justify-content-center">
        <div class="navbar-brand-inner-wrapper d-flex justify-content-between align-items-center w-100">
          <a class="navbar-brand brand-logo" href="{{ url('/') }}"><img src="{{ asset('images/easemed-logo-white.svg') }}" alt="Easemed" style="height:40px; width:auto;"></a>
          <a class="navbar-brand brand-logo-mini" href="{{ url('/') }}"><img src="{{ asset('images/easemed-mark.svg') }}" alt="Easemed" style="height:30px; width:auto;"></a>
          <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
            <span class="typcn typcn-th-menu"></span>
          </button>
        </div>
      </div>
      <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
        <ul class="navbar-nav mr-lg-2">
          <li class="nav-item nav-profile dropdown">
            <a class="nav-link" href="#" data-toggle="dropdown" id="profileDropdown">
              <img src="{{ asset('images/profile.png') }}" alt="profile"/>
              <span class="nav-profile-name">
                @if(auth()->user() && auth()->user()->is_admin)
                  Admin - {{ auth()->user()->name }}
                @elseif(isset($doctor))
                  Dr. {{ $doctor->name }}
                @else
                  {{ auth()->user()->name ?? session('authenticated_user.name', 'User') }}
                @endif
              </span>
            </a>
            <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="profileDropdown">
              <a class="dropdown-item" href="{{ isset($doctor) ? route('doctor.profile') : route('user.profile') }}">
                <i class="typcn typcn-user-outline"></i>
                Profile
              </a>
              <div class="dropdown-divider"></div>
              <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit" class="dropdown-item">
                  <i class="typcn typcn-power-outline"></i>
                  Logout
                </button>
              </form>
            </div>
          </li>
          <li class="nav-item nav-user-status dropdown">
              <p class="mb-0">Last login: 
                @if(isset($doctor))
                  {{ $doctor->last_login_at ? $doctor->last_login_at->diffForHumans() : 'N/A' }}
                @else
                  {{ auth()->user() && auth()->user()->last_login_at ? auth()->user()->last_login_at->diffForHumans() : 'N/A' }}
                @endif
              </p>
          </li>
        </ul>
        <ul class="navbar-nav navbar-nav-right">
          <li class="nav-item nav-date dropdown">
            <a class="nav-link d-flex justify-content-center align-items-center" href="javascript:;">
              <h6 class="date mb-0">{{ isset($walletBalance) ? number_format($walletBalance) : '0' }} UGX</h6>
              <i class="mdi mdi-wallet"></i>
            </a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link count-indicator dropdown-toggle d-flex justify-content-center align-items-center" id="messageDropdown" href="#" data-toggle="dropdown">
              <i class="typcn typcn-mail mx-0"></i>
              <span class="count"></span>
            </a>
            <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list" aria-labelledby="messageDropdown">
              <p class="mb-0 font-weight-normal float-left dropdown-header">Messages</p>
              <a class="dropdown-item preview-item">
                <div class="preview-thumbnail">
                    <img src="{{ asset('pollix/images/faces/face4.jpg') }}" alt="image" class="profile-pic">
                </div>
                <div class="preview-item-content flex-grow">
                  <h6 class="preview-subject ellipsis font-weight-normal">No new messages</h6>
                  <p class="font-weight-light small-text text-muted mb-0">
                    Check back later
                  </p>
                </div>
              </a>
            </div>
          </li>
          <li class="nav-item dropdown mr-0">
            <a class="nav-link count-indicator dropdown-toggle d-flex align-items-center justify-content-center" id="notificationDropdown" href="#" data-toggle="dropdown">
              <i class="typcn typcn-bell mx-0"></i>
              <span class="count"></span>
            </a>
            <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list" aria-labelledby="notificationDropdown">
              <p class="mb-0 font-weight-normal float-left dropdown-header">Notifications</p>
              <a class="dropdown-item preview-item">
                <div class="preview-thumbnail">
                  <div class="preview-icon bg-success">
                    <i class="typcn typcn-info mx-0"></i>
                  </div>
                </div>
                <div class="preview-item-content">
                  <h6 class="preview-subject font-weight-normal">Welcome to Dashboard</h6>
                  <p class="font-weight-light small-text mb-0 text-muted">
                    Just now
                  </p>
                </div>
              </a>
            </div>
          </li>
        </ul>
        <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
          <span class="typcn typcn-th-menu"></span>
        </button>
      </div>
    </nav>
    <nav class="navbar-breadcrumb col-xl-12 col-12 d-flex flex-row p-0">
      <div class="navbar-links-wrapper d-flex align-items-stretch" style="background-color: magenta;">
        <div class="nav-link">
          <a href="javascript:;"><i class="typcn typcn-calendar-outline"></i></a>
        </div>
        <div class="nav-link">
          <a href="javascript:;"><i class="typcn typcn-mail"></i></a>
        </div>
        <div class="nav-link">
          <a href="javascript:;"><i class="typcn typcn-folder"></i></a>
        </div>
        <div class="nav-link">
          <a href="javascript:;"><i class="typcn typcn-document-text"></i></a>
        </div>
      </div>
      <div class="navbar-menu-wrapper d-flex align-items-center justify-content-start" style="background-color: magenta;">
        <ul class="navbar-nav mr-lg-2">
          <li class="nav-item ml-0">
            <h4 class="mb-0">{{ isset($pageTitle) ? $pageTitle : 'Dashboard' }}</h4>
          </li>
        </ul>
        
      </div>
    </nav>
    <div class="container-fluid page-body-wrapper">
      <nav class="sidebar sidebar-offcanvas" id="sidebar">
        @include('layouts.sidebar')
      </nav>
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">
            <div class="col-12">
              @yield('content')
            </div>
          </div>
        </div>
        <footer class="footer">
          <div class="card">
            <div class="card-body">
              <div class="d-sm-flex justify-content-center justify-content-sm-between">
                <span class="text-muted text-center text-sm-left d-block d-sm-inline-block">Copyright © {{ date('Y') }} <a href="{{ url('/') }}" class="text-muted">Easemed</a>. All rights reserved.</span>
              </div>
            </div>
          </div>
        </footer>
      </div>
    </div>
  </div>

  <script src="{{ asset('pollix/vendors/js/vendor.bundle.base.js') }}"></script>
  <script src="{{ asset('pollix/vendors/chart.js/Chart.min.js') }}"></script>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="{{ asset('pollix/vendors/select2/select2.min.js') }}"></script>
  <script src="{{ asset('pollix/js/off-canvas.js') }}"></script>
  <script src="{{ asset('pollix/js/hoverable-collapse.js') }}"></script>
  <script src="{{ asset('pollix/js/template.js') }}"></script>
  <script src="{{ asset('pollix/js/settings.js') }}"></script>
  <script src="{{ asset('pollix/js/todolist.js') }}"></script>
  <script src="{{ asset('pollix/js/select2.js') }}"></script>
  <script src="{{ asset('pollix/js/dashboard.js') }}"></script>

  <script>
    $(document).ready(function() {
      $('#minimizeSidebar').on('click', function() {
        $('body').toggleClass('sidebar-mini');
        $('.sidebar').toggleClass('sidebar-mini');
      });
    });
  </script>

  {{-- Allow pages to push additional scripts (charts, inline JS) --}}
  @stack('scripts')

</body>

</html>