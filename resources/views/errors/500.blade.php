<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Server Error - {{ config('app.name', 'KETI AI') }}</title>
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/ketiai-logo.svg') }}">
    <link rel="stylesheet" href="{{ asset('pollix/vendors/typicons/typicons.css') }}">
    <link rel="stylesheet" href="{{ asset('pollix/vendors/css/vendor.bundle.base.css') }}">
    <link rel="stylesheet" href="{{ asset('pollix/css/vertical-layout-light/style.css') }}">
    <link rel="shortcut icon" href="{{ asset('images/ketiai-logo.svg') }}" />
</head>

<body>
  <div class="container-scroller">
    <div class="container-fluid page-body-wrapper full-page-wrapper">
      <div class="content-wrapper d-flex align-items-center text-center error-page bg-info">
        <div class="row flex-grow">
          <div class="col-lg-7 mx-auto text-white">
            <div class="row align-items-center d-flex flex-row">
              <div class="col-lg-6 text-lg-right pr-lg-4">
                <h1 class="display-1 mb-0">500</h1>
              </div>
              <div class="col-lg-6 error-page-divider text-lg-left pl-lg-4">
                <h2>SORRY!</h2>
                <h3 class="font-weight-light">Internal server error!</h3>
              </div>
            </div>
            <div class="row mt-5">
              <div class="col-12 text-center mt-xl-2">
                <a class="text-white font-weight-medium" href="{{ url('/') }}">Back to home</a>
              </div>
            </div>
            <div class="row mt-5">
              <div class="col-12 mt-xl-2">
                <p class="text-white font-weight-medium text-center">Copyright &copy; {{ date('Y') }} <a href="https://www.ketiai.com/" class="text-muted" target="_blank">KETI AI</a>. All rights reserved.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- content-wrapper ends -->
    </div>
    <!-- page-body-wrapper ends -->
  </div>
  <!-- container-scroller -->
  <!-- base:js -->
  <script src="{{ asset('pollix/vendors/js/vendor.bundle.base.js') }}"></script>
  <!-- endinject -->
  <!-- inject:js -->
  <script src="{{ asset('pollix/js/off-canvas.js') }}"></script>
  <script src="{{ asset('pollix/js/hoverable-collapse.js') }}"></script>
  <script src="{{ asset('pollix/js/template.js') }}"></script>
  <script src="{{ asset('pollix/js/settings.js') }}"></script>
  <script src="{{ asset('pollix/js/todolist.js') }}"></script>
  <!-- endinject -->
</body>

</html>