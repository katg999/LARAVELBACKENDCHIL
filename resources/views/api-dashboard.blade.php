<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .action-buttons .btn {
            width: 100%;
        }
        .modal-message {
            min-height: 100px;
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
                                                    <th>Actions</th>
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
                                                        <div class="action-buttons">
                                                            <button class="btn btn-sm btn-success send-otp" 
                                                                    data-school-id="{{ $school['id'] }}"
                                                                    data-email="{{ $school['email'] }}">
                                                                <i class="fas fa-check-circle"></i> Accept & Send OTP
                                                            </button>
                                                            <button class="btn btn-sm btn-danger reject-school" 
                                                                    data-school-id="{{ $school['id'] }}"
                                                                    data-email="{{ $school['email'] }}">
                                                                <i class="fas fa-times-circle"></i> Reject
                                                            </button>
                                                            <button class="btn btn-sm btn-info query-school" 
                                                                    data-school-id="{{ $school['id'] }}"
                                                                    data-email="{{ $school['email'] }}">
                                                                <i class="fas fa-question-circle"></i> Query
                                                            </button>
                                                        </div>
                                                        <span class="action-status text-muted small d-block mt-1"></span>
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

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel">Reject School Registration</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="rejectReason">Reason for rejection:</label>
                        <textarea class="form-control modal-message" id="rejectReason" placeholder="Please specify the reason for rejection (e.g., document is invalid, incomplete information, etc.)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmReject">Confirm Reject</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Query Modal -->
    <div class="modal fade" id="queryModal" tabindex="-1" role="dialog" aria-labelledby="queryModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="queryModalLabel">Send Query to School</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="queryMessage">Query message:</label>
                        <textarea class="form-control modal-message" id="queryMessage" placeholder="Please specify your query (e.g., document is not clear, need more information, etc.)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmQuery">Send Query</button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE App -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

    <script>
    $(document).ready(function() {
        // Get CSRF token from meta tag
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        let currentSchoolId, currentEmail, currentAction;
        
        // Initialize modals
        const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
        const queryModal = new bootstrap.Modal(document.getElementById('queryModal'));
        
        // Handle reject button click
        $('.reject-school').click(function() {
            currentSchoolId = $(this).data('school-id');
            currentEmail = $(this).data('email');
            currentAction = 'reject';
            $('#rejectReason').val('');
            rejectModal.show();
        });
        
        // Handle query button click
        $('.query-school').click(function() {
            currentSchoolId = $(this).data('school-id');
            currentEmail = $(this).data('email');
            currentAction = 'query';
            $('#queryMessage').val('');
            queryModal.show();
        });
        
        // Handle confirm reject
        $('#confirmReject').click(function() {
            const message = $('#rejectReason').val().trim();
            if (!message) {
                alert('Please provide a reason for rejection');
                return;
            }
            
            sendActionRequest(currentSchoolId, currentEmail, 'reject', message);
            rejectModal.hide();
        });
        
        // Handle confirm query
        $('#confirmQuery').click(function() {
            const message = $('#queryMessage').val().trim();
            if (!message) {
                alert('Please provide a query message');
                return;
            }
            
            sendActionRequest(currentSchoolId, currentEmail, 'query', message);
            queryModal.hide();
        });
        
        // Handle send OTP/Accept button
        $('.send-otp').click(function() {
            const button = $(this);
            const schoolId = button.data('school-id');
            const email = button.data('email');
            
            sendActionRequest(schoolId, email, 'accept');
        });
        
        function sendActionRequest(schoolId, email, action, message = '') {
            const button = $(`button[data-school-id="${schoolId}"].${action === 'accept' ? 'send-otp' : action === 'reject' ? 'reject-school' : 'query-school'}`);
            const statusElement = button.closest('td').find('.action-status');
            
            button.prop('disabled', true);
            
            if (action === 'accept') {
                button.find('i').removeClass('fa-check-circle').addClass('fa-spinner fa-spin');
                statusElement.text('Sending OTP...').removeClass('text-muted text-success text-danger').addClass('text-info');
            } else if (action === 'reject') {
                statusElement.text('Processing rejection...').removeClass('text-muted text-success text-danger').addClass('text-info');
            } else {
                statusElement.text('Sending query...').removeClass('text-muted text-success text-danger').addClass('text-info');
            }
            
            const apiUrl = 'https://laravelbackendchil.onrender.com/api/voiceflow/school-action';
            const requestData = {
                school_id: schoolId,
                email: email,
                action: action
            };
            
            if (message) {
                requestData.message = message;
            }
            
            fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(requestData),
                credentials: 'include'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log("Success response:", data);
                if (data.success) {
                    if (action === 'accept') {
                        statusElement.text('OTP sent! Valid for 24 hours').addClass('text-success');
                    } else if (action === 'reject') {
                        statusElement.text('Rejection sent to school').addClass('text-success');
                    } else {
                        statusElement.text('Query sent to school').addClass('text-success');
                    }
                } else {
                    statusElement.text('Error: ' + (data.message || 'Action failed')).addClass('text-danger');
                }
            })
            .catch(error => {
                console.log("Error:", error);
                statusElement.text('Failed to process action').addClass('text-danger');
            })
            .finally(() => {
                button.prop('disabled', false);
                if (action === 'accept') {
                    button.find('i').removeClass('fa-spinner fa-spin').addClass('fa-check-circle');
                }
                
                setTimeout(() => {
                    statusElement.text('').removeClass('text-success text-danger text-info');
                }, 5000);
            });
        }
    });
    </script>
</body>
</html>