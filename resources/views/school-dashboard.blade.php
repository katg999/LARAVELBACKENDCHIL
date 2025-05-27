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
            background: rgba(88, 14, 14, 0.1);
        }
        .tab-content {
            padding: 20px;
            background: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
                    <a class="nav-link" href="#book-doctor" data-bs-toggle="tab">
                        <i class="fas fa-user-md me-2"></i> Book Doctor
                    </a>
                    <a class="nav-link" href="#lab-tests" data-bs-toggle="tab">
                        <i class="fas fa-flask me-2"></i> Lab Tests
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

                    <!-- Book Doctor Tab -->
                    <div class="tab-pane fade" id="book-doctor">
                        <div class="d-flex justify-content-between mb-4">
                            <h2>Doctor Appointments</h2>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newAppointmentModal">
                                <i class="fas fa-plus me-2"></i> New Appointment
                            </button>
                        </div>

                        @if($appointments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
    <thead class="table-dark">
        <tr>
            <th>Date</th>
            <th>Student</th>
            <th>Doctor</th>
            <th>Duration</th>
            <th>Amount</th>
            <th>Reason</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($appointments as $appointment)
        <tr>
            <td>{{ $appointment->appointment_time->format('M d, Y h:i A') }}</td>
            <td>{{ $appointment->student->name }}</td>
            <td>Dr. {{ $appointment->doctor->name }}</td>
            <td>{{ $appointment->duration }} mins</td>
            <td>{{ number_format($appointment->amount) }} UGX</td>
            <td>{{ $appointment->reason }}</td>
            <td>
                <span class="badge bg-{{ 
                    $appointment->status == 'confirmed' ? 'success' : 
                    ($appointment->status == 'pending_payment' ? 'warning' : 'danger') 
                }}">
                    {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
                </span>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
                        </div>
                        @else
                        <div class="alert alert-info">
                            No appointments found.
                        </div>
                        @endif

     <!-- School Appointment Modal -->
<div class="modal fade" id="newAppointmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Book Doctor Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="appointment-form" action="{{ route('api.appointments.store') }}" method="POST">
                @csrf
                <input type="hidden" name="school_id" value="{{ $school->id }}">
                <input type="hidden" name="amount" id="appointment-amount">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Student</label>
                        <select name="student_id" class="form-select" required>
                            <option value="">Select Student</option>
                            @foreach($students as $student)
                            <option value="{{ $student->id }}">{{ $student->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Doctor</label>
                        <select name="doctor_id" id="doctor-select" class="form-select" required>
                            <option value="">Select Doctor</option>
                            @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}" data-specialization="{{ $doctor->specialization }}">
                                Dr. {{ $doctor->name }} ({{ $doctor->specialization }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Duration (minutes)</label>
                        <select name="duration" id="duration-select" class="form-select" required>
                            <option value="">Select Duration</option>
                            <option value="15">15 minutes</option>
                            <option value="20">20 minutes</option>
                            <option value="30">30 minutes</option>
                            <option value="45">45 minutes</option>
                            <option value="60">60 minutes</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Date & Time</label>
                        <input type="datetime-local" name="appointment_time" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <textarea name="reason" class="form-control" rows="3" required></textarea>
                    </div>

                    <!-- Display payment summary -->
                    <div class="alert alert-info d-none" id="payment-info">
                        <h5>Payment Details</h5>
                        <p>Doctor Type: <span id="doctor-type-display"></span></p>
                        <p>Duration: <span id="duration-display"></span> minutes</p>
                        <p>Amount to Pay: <strong><span id="amount-display"></span> UGX</strong></p>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-calendar-check me-2"></i> Book Appointment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

                    <!-- Lab Tests Tab -->
                    <div class="tab-pane fade" id="lab-tests">
                        <div class="d-flex justify-content-between mb-4">
                            <h2>Lab Test Requests</h2>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newLabTestModal">
                                <i class="fas fa-plus me-2"></i> New Request
                            </button>
                        </div>

                        @if($labTests->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Request Date</th>
                                        <th>Student</th>
                                        <th>Test Type</th>
                                        <th>Status</th>
                                        <th>Results</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($labTests as $labTest)
                                    <tr>
                                        <td>{{ $labTest->created_at->format('M d, Y') }}</td>
                                        <td>{{ $labTest->student->name }}</td>
                                        <td>{{ $labTest->test_type }}</td>
                                        <td>
                                            <span class="badge bg-{{ 
                                                $labTest->status == 'completed' ? 'success' : 
                                                ($labTest->status == 'processing' ? 'warning' : 'secondary') 
                                            }}">
                                                {{ ucfirst($labTest->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($labTest->results)
                                            <a href="#" class="btn btn-sm btn-info">View</a>
                                            @else
                                            <span class="text-muted">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="alert alert-info">
                            No lab tests found.
                        </div>
                        @endif

                        <!-- Lab Test Modal -->
                        <div class="modal fade" id="newLabTestModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Request Lab Test</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form id="labtest-form" action="{{ url('/api/lab-tests') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="school_id" value="{{ $school->id }}">
                                        
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Student</label>
                                                <select name="student_id" class="form-select" required>
                                                    <option value="">Select Student</option>
                                                    @foreach($students as $student)
                                                    <option value="{{ $student->id }}">{{ $student->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Test Type</label>
                                                <select name="test_type" class="form-select" required>
                                                    <option value="">Select Test</option>
                                                    <option value="Blood Test">Blood Test</option>
                                                    <option value="Urine Test">Urine Test</option>
                                                    <option value="X-Ray">X-Ray</option>
                                                    <option value="Allergy Test">Allergy Test</option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Notes</label>
                                                <textarea name="notes" class="form-control" rows="3"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Submit Request</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Calculate appointment cost based on doctor type and duration
    function calculateAppointmentCost() {
        const doctorSelect = document.getElementById('doctor-select');
        const durationSelect = document.getElementById('duration-select');
        const paymentInfo = document.getElementById('payment-info');
        const payButton = document.getElementById('initiate-payment-btn');
        const amountInput = document.getElementById('appointment-amount');
        
        if (!doctorSelect.value || !durationSelect.value) {
            paymentInfo.style.display = 'none';
            payButton.disabled = true;
            return;
        }
        
        const selectedOption = doctorSelect.options[doctorSelect.selectedIndex];
        const isSpecialist = selectedOption.dataset.specialization !== 'General Practitioner';
        const duration = parseInt(durationSelect.value);
        
        // Pricing structure
        const amount = isSpecialist 
            ? (duration === 15 ? 100000 : 150000)
            : (duration === 15 ? 30000 : 45000);
        
        // Update display
        document.getElementById('doctor-type-display').textContent = 
            isSpecialist ? 'Specialist' : 'General Doctor';
        document.getElementById('duration-display').textContent = duration;
        document.getElementById('amount-display').textContent = amount.toLocaleString();
        amountInput.value = amount;
        
        paymentInfo.style.display = 'block';
        payButton.disabled = false;
    }

    // Add event listeners for dynamic pricing
    document.getElementById('doctor-select').addEventListener('change', calculateAppointmentCost);
    document.getElementById('duration-select').addEventListener('change', calculateAppointmentCost);

    // Handle payment initiation
    document.getElementById('initiate-payment-btn').addEventListener('click', function() {
        const form = document.getElementById('appointment-form');
        const formData = new FormData(form);
        
        // Disable button during processing
        const payButton = this;
        const originalText = payButton.innerHTML;
        payButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
        payButton.disabled = true;
        
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
            if (data.success) {
                if (data.payment_reference) {
                    // Start checking payment status
                    checkPaymentStatus(data.payment_reference);
                } else {
                    alert('Appointment booked successfully!');
                    window.location.reload();
                }
            } else {
                alert('Error: ' + (data.message || 'Operation failed'));
                payButton.innerHTML = originalText;
                payButton.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
            payButton.innerHTML = originalText;
            payButton.disabled = false;
        });
    });

    // Check payment status periodically
    function checkPaymentStatus(referenceId) {
        const statusInterval = setInterval(() => {
            fetch(`/api/momo/payment-status/${referenceId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'SUCCESSFUL' || data.status === 'successful') {
                    clearInterval(statusInterval);
                    alert('Payment successful! Appointment confirmed.');
                    window.location.reload();
                } else if (data.status === 'FAILED' || data.status === 'failed') {
                    clearInterval(statusInterval);
                    alert('Payment failed. Please try again.');
                }
                // else continue checking
            });
        }, 3000); // Check every 3 seconds
    }

    // General form submission handler
    function handleFormSubmit(e) {
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
                alert('Operation successful!');
                form.reset();
                window.location.reload();
            } else {
                alert('Error: ' + (data.message || 'Operation failed'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    }
</script>
</body>
</html>