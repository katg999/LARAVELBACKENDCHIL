<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $healthFacility->name }} Dashboard</title>
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
        .profile-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar p-0">
                <div class="text-center p-3">
                    <div class="position-relative mb-3">
                        <img src="{{ $healthFacility->logo ? asset('storage/' . $healthFacility->logo) : asset('images/default-facility.png') }}" 
                             class="profile-img" 
                             id="facility-image"
                             alt="Facility Logo">
                    </div>
                    <h4>{{ $healthFacility->name }}</h4>
                    <hr class="border-light">
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link active" href="#patients" data-bs-toggle="tab">
                        <i class="fas fa-users me-2"></i> Patients
                    </a>
                    <a class="nav-link" href="#add-patient" data-bs-toggle="tab">
                        <i class="fas fa-user-plus me-2"></i> Add Patient
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
                    <!-- Patients Tab -->
                    <div class="tab-pane fade show active" id="patients">
                        <div class="d-flex justify-content-between mb-3">
                            <h2>Patient Management</h2>
                            <div class="input-group" style="width: 300px;">
                                <input type="text" class="form-control" placeholder="Search patients...">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        @if($patients->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Gender</th>
                                        <th>Age</th>
                                        <th>Contact</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($patients as $patient)
                                    <tr>
                                        <td>{{ $patient->id }}</td>
                                        <td>{{ $patient->name }}</td>
                                        <td>{{ ucfirst($patient->gender) }}</td>
                                        <td>{{ \Carbon\Carbon::parse($patient->birth_date)->age }}</td>
                                        <td>{{ $patient->contact_number ?? 'N/A' }}</td>
                                        <td>
                                        <button class="btn btn-sm btn-primary">
        <i class="fas fa-edit"></i>
    </button>
    <button class="btn btn-sm btn-danger">
        <i class="fas fa-trash"></i>
    </button>
    <a href="{{ route('patient.maternal', $patient->id) }}" class="btn btn-sm btn-info" title="Maternal Documents">
        <i class="fas fa-baby"></i>
    </a>
                                        </td>
                                    </tr>

                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="alert alert-info">
                            No patients found for this facility.
                        </div>
                        @endif
                    </div>

                    <!-- Add Patient Tab -->
                    <div class="tab-pane fade" id="add-patient">
                        <h2 class="mb-4">Add New Patient</h2>
                        <form id="patient-form" method="POST" action="{{ url('/api/patients') }}">
                            @csrf
                            <input type="hidden" name="health_facility_id" value="{{ $healthFacility->id }}">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Gender</label>
                                    <select name="gender" class="form-control" required>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Birth Date</label>
                                    <input type="date" name="birth_date" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Contact Number</label>
                                    <input type="text" name="contact_number" class="form-control">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Medical History (Optional)</label>
                                <textarea name="medical_history" class="form-control" rows="3"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> Save Patient
                            </button>
                        </form>
                    </div>

                    <!-- Book Doctor Tab -->
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
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Duration</th>
                    <th>Reason</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($appointments as $appointment)
                <tr>
                    <td>{{ $appointment->appointment_time->format('M d, Y h:i A') }}</td>
                    <td>{{ $appointment->patient->name }}</td>
                    <td>Dr. {{ $appointment->doctor->name }}</td>
                    <td>{{ $appointment->duration }} mins</td>
                    <td>{{ $appointment->reason }}</td>
                    <td>
                        <span class="badge bg-{{ 
                            $appointment->status == 'confirmed' ? 'success' : 
                            ($appointment->status == 'pending' ? 'warning' : 'secondary') 
                        }}">
                            {{ ucfirst($appointment->status) }}
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

    <!-- Appointment Modal -->
    <div class="modal fade" id="newAppointmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Book Doctor Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="appointment-form" action="{{ route('api.appointments.store') }}" method="POST">

                    @csrf
                    <input type="hidden" name="health_facility_id" value="{{ $healthFacility->id }}">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Patient</label>
                            <select name="patient_id" class="form-select" required>
                                <option value="">Select Patient</option>
                                @foreach($patients as $patient)
                                <option value="{{ $patient->id }}">{{ $patient->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Doctor</label>
                            <select name="doctor_id" class="form-select" required>
                                <option value="">Select Doctor</option>
                                @foreach($allDoctors as $doctor)
                                <option value="{{ $doctor->id }}">
                                    Dr. {{ $doctor->name }} ({{ $doctor->specialization }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Duration (minutes)</label>
                            <select name="duration" class="form-select" required>
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
</div>

                   
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
                                        <input type="hidden" name="health_facility_id" value="{{ $healthFacility->id }}">
                                        
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Patient</label>
                                                <select name="patient_id" class="form-select" required>
                                                    <option value="">Select Patient</option>
                                                    @foreach($patients as $patient)
                                                    <option value="{{ $patient->id }}">{{ $patient->name }}</option>
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



    // Handle form submission for booking
// Fixed JavaScript for handling appointment form submission
document.getElementById('appointment-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = e.target;
    
    // Get all form data
    const formData = new FormData(form);
    
    // Log form data for debugging
    console.log('Form data being sent:');
    for (let [key, value] of formData.entries()) {
        console.log(key, value);
    }
    
    // Disable button during processing
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Booking...';
    submitButton.disabled = true;
    
    // Make sure we have the required fields
    if (!formData.get('patient_id')) {
        alert('Please select a patient');
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
        return;
    }
    
    if (!formData.get('doctor_id')) {
        alert('Please select a doctor');
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
        return;
    }
    
    // Send the request
    fetch(form.action, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            health_facility_id: formData.get('health_facility_id'),
            patient_id: formData.get('patient_id'),
            doctor_id: formData.get('doctor_id'),
            duration: formData.get('duration'),
            appointment_time: formData.get('appointment_time'),
            reason: formData.get('reason')
        })
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        
        if(data.success) {
            alert('Appointment booked successfully!');
            form.reset();
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('newAppointmentModal'));
            if (modal) {
                modal.hide();
            }
            
            // Reload page to see new appointment
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            // Handle validation errors
            if (data.errors) {
                let errorMessage = 'Validation errors:\n';
                Object.keys(data.errors).forEach(key => {
                    errorMessage += `- ${data.errors[key].join(', ')}\n`;
                });
                alert(errorMessage);
            } else {
                alert('Error: ' + (data.message || 'Booking failed'));
            }
            
            submitButton.innerHTML = originalText;
            submitButton.disabled = false;
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('An error occurred while booking. Please check the console for details.');
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    });
});

// Also add some validation to the form fields
document.addEventListener('DOMContentLoaded', function() {
    // Set minimum datetime to 1 hour from now
    const datetimeInput = document.querySelector('input[name="appointment_time"]');
    if (datetimeInput) {
        const now = new Date();
        now.setHours(now.getHours() + 1); // Add 1 hour
        const minDateTime = now.toISOString().slice(0, 16); // Format for datetime-local
        datetimeInput.min = minDateTime;
    }
    
    // Validate form before submission
    const form = document.getElementById('appointment-form');
    const patientSelect = form.querySelector('select[name="patient_id"]');
    const doctorSelect = form.querySelector('select[name="doctor_id"]');
    const durationSelect = form.querySelector('select[name="duration"]');
    const reasonTextarea = form.querySelector('textarea[name="reason"]');
    
    // Add change listeners for real-time validation
    [patientSelect, doctorSelect, durationSelect].forEach(select => {
        if (select) {
            select.addEventListener('change', function() {
                validateForm();
            });
        }
    });
    
    if (reasonTextarea) {
        reasonTextarea.addEventListener('input', function() {
            validateForm();
        });
    }
    
    function validateForm() {
        const submitBtn = form.querySelector('button[type="submit"]');
        const isValid = patientSelect.value && 
                       doctorSelect.value && 
                       durationSelect.value && 
                       datetimeInput.value && 
                       reasonTextarea.value.trim().length > 0;
        
        if (submitBtn) {
            submitBtn.disabled = !isValid;
        }
    }
    
    // Initial validation
    validateForm();
});
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