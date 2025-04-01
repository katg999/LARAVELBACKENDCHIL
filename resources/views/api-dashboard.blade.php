<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schools Dashboard</title>
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .file-link {
            transition: all 0.3s;
        }
        .file-link:hover {
            transform: translateY(-2px);
        }
        .card-header {
            border-bottom: 1px solid rgba(0,0,0,.125);
        }
        .otp-status {
            font-size: 0.8rem;
        }
        .send-otp:disabled {
            opacity: 0.7;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini">
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                        <i class="fas fa-bars"></i>
                    </a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="#" class="nav-link">Home</a>
                </li>
            </ul>
        </nav>

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
            <a href="#" class="brand-link">
                <span class="brand-text font-weight-light">Schools Admin</span>
            </a>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Sidebar Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                        <li class="nav-item">
                            <a href="#" class="nav-link active">
                                <i class="nav-icon fas fa-school"></i>
                                <p>Schools</p>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Schools List</h3>
                                    <div class="card-tools">
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control" placeholder="Search">
                                            <div class="input-group-append">
                                                <button class="btn btn-primary">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-body">
                                    @if(isset($error))
                                        <div class="alert alert-danger">
                                            {{ $error }}
                                        </div>
                                    @endif

                                    @if(!empty($schools))
                                        <table class="table table-bordered table-hover">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Name</th>
                                                    <th>Email</th>
                                                    <th>Contact</th>
                                                    <th>Document</th>
                                                    <th>Created At</th>
                                                    <th>OTP Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($schools as $school)
                                                <tr>
                                                    <td>{{ $school['id'] ?? 'N/A' }}</td>
                                                    <td>
                                                        <strong>{{ $school['name'] ?? 'N/A' }}</strong>
                                                    </td>
                                                    <td>{{ $school['email'] ?? 'N/A' }}</td>
                                                    <td>{{ $school['contact'] ?? 'N/A' }}</td>
                                                    <td>
                                                        @if(!empty($school['file_url']))
                                                            <a href="{{ $school['file_url'] }}" 
                                                               target="_blank" 
                                                               class="btn btn-sm btn-outline-primary file-link">
                                                                <i class="fas fa-file-download"></i> View File
                                                            </a>
                                                        @else
                                                            <span class="text-muted">No file</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $school['created_at'] ?? 'N/A' }}</td>
                                                    <td>
                                                        <button class="btn btn-sm btn-warning send-otp" 
                                                                data-school-id="{{ $school['id'] }}"
                                                                data-email="{{ $school['email'] }}">
                                                            <i class="fas fa-paper-plane"></i> Send OTP
                                                        </button>
                                                        <span class="otp-status text-muted small d-block mt-1"></span>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @else
                                        <div class="alert alert-info">
                                            No school data available.
                                        </div>
                                    @endif
                                </div>
                                <!-- /.card-body -->
                            </div>
                            <!-- /.card -->
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Footer -->
        <footer class="main-footer">
            <div class="float-right d-none d-sm-block">
                <b>Version</b> 1.0.0
            </div>
            <strong>Copyright &copy; 2023</strong> All rights reserved.
        </footer>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE App -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

    <script>
    $(document).ready(function() {
        $('.send-otp').click(function() {
            const button = $(this);
            const schoolId = button.data('school-id');
            const email = button.data('email');
            
            button.prop('disabled', true);
            button.find('i').removeClass('fa-paper-plane').addClass('fa-spinner fa-spin');
            button.siblings('.otp-status').text('Sending...').removeClass('text-muted text-success text-danger').addClass('text-info');

            $.post('/send-otp', {
                school_id: schoolId,
                email: email,
                _token: '{{ csrf_token() }}'
            })
            .done(function(response) {
                if(response.success) {
                    button.siblings('.otp-status').text('OTP sent! Valid for 24 hours').addClass('text-success');
                } else {
                    button.siblings('.otp-status').text('Error: ' + response.message).addClass('text-danger');
                }
            })
            .fail(function(xhr) {
                const error = xhr.responseJSON?.message || 'Failed to send OTP';
                button.siblings('.otp-status').text(error).addClass('text-danger');
            })
            .always(function() {
                button.prop('disabled', false);
                button.find('i').removeClass('fa-spinner fa-spin').addClass('fa-paper-plane');
                
                // Clear status after 5 seconds
                setTimeout(() => {
                    button.siblings('.otp-status').text('').removeClass('text-success text-danger text-info');
                }, 5000);
            });
        });
    });
    </script>
</body>
</html>