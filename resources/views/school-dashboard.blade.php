<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $school->name }} Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #343a40;
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.5);
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar p-0">
                <div class="p-3">
                    <h4>{{ $school->name }}</h4>
                    <hr class="border-light">
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link active" href="#students" data-bs-toggle="tab">
                        <i class="fas fa-users me-2"></i> Students
                    </a>
                    <a class="nav-link" href="#add-student" data-bs-toggle="tab">
                        <i class="fas fa-user-plus me-2"></i> Add Student
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="tab-content">
                    <!-- Students Tab -->
                    <div class="tab-pane fade show active" id="students">
                        <div class="d-flex justify-content-between mb-3">
                            <h2>Student Management</h2>
                            <div class="input-group" style="width: 300px;">
                                <input type="text" class="form-control" placeholder="Search students...">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        @if($students->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Grade</th>
                                        <th>Age</th>
                                        <th>Parent Contact</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($students as $student)
                                    <tr>
                                        <td>{{ $student->id }}</td>
                                        <td>{{ $student->name }}</td>
                                        <td>{{ $student->grade }}</td>
                                        <td>{{ \Carbon\Carbon::parse($student->birth_date)->age }}</td>
                                        <td>{{ $student->parent_contact ?? 'N/A' }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="alert alert-info">
                            No students found for this school.
                        </div>
                        @endif
                    </div>

                    <!-- Add Student Tab -->
                    <div class="tab-pane fade" id="add-student">
                        <h2 class="mb-4">Add New Student</h2>
                        <form id="student-form" method="POST" action="{{ url('/api/students') }}">
                            @csrf
                            <input type="hidden" name="school_id" value="{{ $school->id }}">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Grade/Class</label>
                                    <input type="text" name="grade" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Birth Date</label>
                                    <input type="date" name="birth_date" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Parent Contact</label>
                                    <input type="text" name="parent_contact" class="form-control">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> Save Student
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple form submission with fetch API
        document.getElementById('student-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = e.target;
            const formData = new FormData(form);
            
            fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert('Student added successfully!');
                    form.reset();
                    // You could refresh the student list here
                } else {
                    alert('Error: ' + (data.message || 'Failed to add student'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            });
        });
    </script>
</body>
</html>