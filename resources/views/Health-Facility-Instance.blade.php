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
        .meeting-link {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 10px;
            font-family: monospace;
        }
        .unread-message {
            background-color: #e9f5ff;
        }
        .urgent-badge {
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { background-color: #dc3545; }
            50% { background-color: #ff6b7f; }
            100% { background-color: #dc3545; }
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
                    <p class="mb-1">{{ $healthFacility->type }}</p>
                    <p class="mb-1">{{ $healthFacility->location }}</p>
                    <hr class="border-light">
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link active" href="#dashboard" data-bs-toggle="tab">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a class="nav-link" href="#book-doctor" data-bs-toggle="tab">
                        <i class="fas fa-user-md me-2"></i> Book Doctor
                    </a>
                    <a class="nav-link" href="#available-doctors" data-bs-toggle="tab">
                        <i class="fas fa-list me-2"></i> Available Doctors
                    </a>
                    <a class="nav-link" href="#messages" data-bs-toggle="tab">
                        <i class="fas fa-inbox me-2"></i> Messages 
                        @if($unreadMessages > 0)
                        <span class="badge bg-danger rounded-pill ms-2">{{ $unreadMessages }}</span>
                        @endif
                    </a>
                    <a class="nav-link" href="#patients" data-bs-toggle="tab">
                        <i class="fas fa-procedures me-2"></i> Patients
                    </a>
                    <a class="nav-link" href="#emergency" data-bs-toggle="tab">
                        <i class="fas fa-ambulance me-2"></i> Emergency
                    </a>
                    <a class="nav-link" href="#profile" data-bs-toggle="tab">
                        <i class="fas fa-cog me-2"></i> Settings
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="tab-content">
                    <!-- Dashboard Tab -->
                    <div class="tab-pane fade show active" id="dashboard">
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h2>Facility Dashboard</h2>
                                <p class="text-muted">Welcome back, {{ $healthFacility->name }}. Here's your overview.</p>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card text-white bg-primary mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="card-title">Active Appointments</h5>
                                                {{-- <h2 class="mb-0">{{ $stats['active_appointments'] }}</h2> --}}

                                            </div>
                                            <i class="fas fa-calendar-check fa-3x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                           
                            <div class="col-md-4">
                                <div class="card text-white bg-success mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="card-title">Available Doctors</h5>
                                                {{-- <h2 class="mb-0">{{ $stats['available_doctors'] }}</h2> --}}
                                            </div>
                                            <i class="fas fa-user-md fa-3x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card text-white bg-info mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="card-title">New Messages</h5>
                                                <h2 class="mb-0">{{ $unreadMessages }}</h2>
                                            </div>
                                            <i class="fas fa-envelope fa-3x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
{{--
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header bg-primary text-white">
                                        <i class="fas fa-calendar me-2"></i> Upcoming Appointments
                                    </div>
                                    <div class="card-body">
                                        @if($upcomingAppointments->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Doctor</th>
                                                        <th>Patient</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($upcomingAppointments as $appointment)
                                                    <tr>
                                                        <td>{{ $appointment->appointment_time->format('M d, h:i A') }}</td>
                                                        <td>Dr. {{ $appointment->doctor->name }}</td>
                                                        <td>{{ $appointment->patient->name }}</td>
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
                                        <p class="text-muted">No upcoming appointments.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            --}}
                            {{--
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header bg-success text-white">
                                        <i class="fas fa-bell me-2"></i> Recent Notifications
                                    </div>
                                    <div class="card-body">
                                        @if($notifications->count() > 0)
                                        <div class="list-group">
                                            @foreach($notifications as $notification)
                                            <a href="#" class="list-group-item list-group-item-action">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1">{{ $notification->title }}</h6>
                                                    <small>{{ $notification->created_at->diffForHumans() }}</small>
                                                </div>
                                                <p class="mb-1">{{ Str::limit($notification->message, 100) }}</p>
                                            </a>
                                            @endforeach
                                        </div>
                                        @else
                                        <p class="text-muted">No recent notifications.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    --}}

                    <!-- Book Doctor Tab -->
                    <div class="tab-pane fade" id="book-doctor">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="d-flex justify-content-between mb-4">
                                    <h2>Book a Doctor</h2>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newAppointmentModal">
                                        <i class="fas fa-plus me-2"></i> New Booking
                                    </button>
                                </div>

                                @if($appointments->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Date</th>
                                                <th>Doctor</th>
                                                <th>Patient</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($appointments as $appointment)
                                            <tr>
                                                <td>{{ $appointment->appointment_time->format('M d, Y h:i A') }}</td>
                                                <td>Dr. {{ $appointment->doctor->name }}</td>
                                                <td>{{ $appointment->patient->name }}</td>
                                                <td>{{ $appointment->is_emergency ? 'Emergency' : 'Regular' }}</td>
                                                <td>
                                                    <span class="badge bg-{{ 
                                                        $appointment->status == 'confirmed' ? 'success' : 
                                                        ($appointment->status == 'pending' ? 'warning' : 'secondary') 
                                                    }}">
                                                        {{ ucfirst($appointment->status) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($appointment->status == 'confirmed' && $appointment->meeting_link)
                                                    <a href="{{ $appointment->meeting_link }}" 
                                                       target="_blank" 
                                                       class="btn btn-sm btn-success"
                                                       data-bs-toggle="tooltip" 
                                                       title="Join Meeting">
                                                        <i class="fas fa-video"></i>
                                                    </a>
                                                    @endif
                                                    <button class="btn btn-sm btn-info view-appointment" 
                                                            data-id="{{ $appointment->id }}">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger cancel-appointment" 
                                                            data-id="{{ $appointment->id }}"
                                                            {{ $appointment->status == 'cancelled' ? 'disabled' : '' }}>
                                                        <i class="fas fa-times"></i>
                                                    </button>
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
                            </div>
                        </div>
                    </div>

                    <!-- Available Doctors Tab -->
                    <div class="tab-pane fade" id="available-doctors">
                        <div class="row">
                            <div class="col-md-12">
                                <h2 class="mb-4">Available Doctors</h2>
                                
                                <div class="row">
                                    @foreach($availableDoctors as $doctor)
                                    <div class="col-md-4 mb-4">
                                        <div class="card h-100">
                                            <div class="card-body text-center">
                                                <img src="{{ $doctor->profile_image ? asset('storage/' . $doctor->profile_image) : asset('images/default-doctor.jpg') }}" 
                                                     class="rounded-circle mb-3" 
                                                     width="100" 
                                                     height="100" 
                                                     alt="Doctor Image">
                                                <h5 class="card-title">Dr. {{ $doctor->name }}</h5>
                                                <p class="text-muted">{{ $doctor->specialization }}</p>
                                                <p>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-circle me-1"></i> Available Now
                                                    </span>
                                                </p>
                                                <div class="d-flex justify-content-center">
                                                    <button class="btn btn-sm btn-primary me-2 book-doctor-btn" 
                                                            data-doctor-id="{{ $doctor->id }}">
                                                        <i class="fas fa-calendar-plus me-1"></i> Book Now
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-secondary view-profile-btn" 
                                                            data-doctor-id="{{ $doctor->id }}">
                                                        <i class="fas fa-user me-1"></i> Profile
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                
                                @if($availableDoctors->isEmpty())
                                <div class="alert alert-warning">
                                    No doctors are currently available. Try our emergency booking feature if you need immediate assistance.
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Messages Tab -->
                    <div class="tab-pane fade" id="messages">
                        <div class="row">
                            <div class="col-md-12">
                                <h2 class="mb-4">Messages</h2>
                                
                                <div class="card mb-4">
                                    <div class="card-header bg-primary text-white">
                                        <i class="fas fa-inbox me-2"></i> Inbox
                                    </div>
                                    <div class="card-body p-0">
                                        @if($messages->count() > 0)
                                        <div class="list-group list-group-flush">
                                            @foreach($messages as $message)
                                            <a href="#" class="list-group-item list-group-item-action message-item {{ $message->is_read ? '' : 'unread-message' }}" 
                                               data-message-id="{{ $message->id }}">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1">
                                                        @if(!$message->is_read)
                                                        <span class="badge bg-primary me-2">New</span>
                                                        @endif
                                                        {{ $message->subject }}
                                                    </h6>
                                                    <small>{{ $message->created_at->diffForHumans() }}</small>
                                                </div>
                                                <p class="mb-1">{{ Str::limit($message->message, 100) }}</p>
                                                <small>From: Dr. {{ $message->doctor->name }}</small>
                                            </a>
                                            @endforeach
                                        </div>
                                        @else
                                        <div class="p-4 text-center text-muted">
                                            No messages found.
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Patients Tab -->
                    <div class="tab-pane fade" id="patients">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="d-flex justify-content-between mb-4">
                                    <h2>Patient Management</h2>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPatientModal">
                                        <i class="fas fa-user-plus me-2"></i> Add Patient
                                    </button>
                                </div>

                                @if($patients->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Gender</th>
                                                <th>Age</th>
                                                <th>Last Visit</th>
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
                                                <td>{{ $patient->last_visit ? $patient->last_visit->format('M d, Y') : 'Never' }}</td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary edit-patient" 
                                                            data-patient-id="{{ $patient->id }}">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-info view-patient" 
                                                            data-patient-id="{{ $patient->id }}">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger delete-patient" 
                                                            data-patient-id="{{ $patient->id }}">
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
                                    No patients found. Add your first patient using the button above.
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Emergency Tab -->
                    <div class="tab-pane fade" id="emergency">
                        <div class="row">
                            <div class="col-md-8 offset-md-2">
                                <div class="card border-danger">
                                    <div class="card-header bg-danger text-white">
                                        <i class="fas fa-ambulance me-2"></i> Emergency Doctor Request
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-danger">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <strong>Use this only for urgent medical situations</strong> that require immediate doctor attention.
                                            Emergency requests will be charged at a higher rate.
                                        </div>
                                        
                                        <form id="emergency-form">
                                            @csrf
                                            <input type="hidden" name="is_emergency" value="1">
                                            
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
                                                <label class="form-label">Emergency Type</label>
                                                <select name="emergency_type" class="form-select" required>
                                                    <option value="">Select Type</option>
                                                    <option value="cardiac">Cardiac Emergency</option>
                                                    <option value="trauma">Trauma</option>
                                                    <option value="respiratory">Respiratory Distress</option>
                                                    <option value="neurological">Neurological Emergency</option>
                                                    <option value="other">Other</option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Symptoms/Description</label>
                                                <textarea name="symptoms" class="form-control" rows="4" required></textarea>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="confirmEmergency" required>
                                                    <label class="form-check-label" for="confirmEmergency">
                                                        I confirm this is a genuine emergency requiring immediate medical attention
                                                    </label>
                                                </div>
                                            </div>
                                            
                                            <div class="d-grid gap-2">
                                                <button type="submit" class="btn btn-danger btn-lg">
                                                    <i class="fas fa-bell me-2"></i> REQUEST EMERGENCY DOCTOR
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Profile/Settings Tab -->
                    <div class="tab-pane fade" id="profile">
                        <div class="row">
                            <div class="col-md-6">
                                <h2 class="mb-4">Facility Information</h2>
                                <form id="facility-form" method="POST" action="{{ route('health-facilities.update', $healthFacility->id) }}">
                                    @csrf
                                    @method('PUT')
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Facility Name</label>
                                        <input type="text" name="name" class="form-control" value="{{ $healthFacility->name }}" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Facility Type</label>
                                        <select name="type" class="form-select" required>
                                            <option value="clinic" {{ $healthFacility->type == 'clinic' ? 'selected' : '' }}>Clinic</option>
                                            <option value="hospital" {{ $healthFacility->type == 'hospital' ? 'selected' : '' }}>Hospital</option>
                                            <option value="health_center" {{ $healthFacility->type == 'health_center' ? 'selected' : '' }}>Health Center</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Location</label>
                                        <input type="text" name="location" class="form-control" value="{{ $healthFacility->location }}" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Contact Email</label>
                                        <input type="email" name="email" class="form-control" value="{{ $healthFacility->email }}" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Phone Number</label>
                                        <input type="tel" name="phone" class="form-control" value="{{ $healthFacility->phone }}" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Facility Description</label>
                                        <textarea name="description" class="form-control" rows="4">{{ $healthFacility->description }}</textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Update Information</button>
                                </form>
                            </div>
                            
                            <div class="col-md-6">
                                <h2 class="mb-4">Account Settings</h2>
                                
                                <div class="card mb-4">
                                    <div class="card-header bg-primary text-white">
                                        Change Password
                                    </div>
                                    <div class="card-body">
                                        <form id="password-form" method="POST" action="{{ route('health-facilities.change-password', $healthFacility->id) }}">
                                            @csrf
                                            <div class="mb-3">
                                                <label class="form-label">Current Password</label>
                                                <input type="password" name="current_password" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">New Password</label>
                                                <input type="password" name="new_password" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Confirm New Password</label>
                                                <input type="password" name="new_password_confirmation" class="form-control" required>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Change Password</button>
                                        </form>
                                    </div>
                                </div>
                                
                                <div class="card">
                                    <div class="card-header bg-warning text-white">
                                        Facility Logo
                                    </div>
                                    <div class="card-body">
                                        <form id="logo-form" method="POST" action="{{ route('health-facilities.upload-logo', $healthFacility->id) }}" enctype="multipart/form-data">
                                            @csrf
                                            <div class="mb-3">
                                                <label class="form-label">Upload New Logo</label>
                                                <input type="file" name="logo" class="form-control" accept="image/*">
                                            </div>
                                            <button type="submit" class="btn btn-primary">Upload Logo</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Appointment Modal -->
    <div class="modal fade" id="newAppointmentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Book New Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="appointment-form" method="POST" action="{{ route('appointments.store') }}">
                    @csrf
                    <input type="hidden" name="health_facility_id" value="{{ $healthFacility->id }}">
                    <input type="hidden" id="doctor-id" name="doctor_id">
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
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
                                    <select name="doctor_id" id="doctor-select" class="form-select" required>
                                        <option value="">Select Doctor</option>
                                        @foreach($allDoctors as $doctor)
                                        <option value="{{ $doctor->id }}" 
                                                data-specialization="{{ $doctor->specialization }}"
                                                data-available="{{ $doctor->is_available ? 'true' : 'false' }}">
                                            Dr. {{ $doctor->name }} ({{ $doctor->specialization }})
                                            @if($doctor->is_available)
                                            <span class="badge bg-success">Available</span>
                                            @endif
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Appointment Type</label>
                                    <select name="appointment_type" class="form-select" required>
                                        <option value="consultation">Consultation</option>
                                        <option value="follow_up">Follow-up</option>
                                        <option value="checkup">Checkup</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Date & Time</label>
                                    <input type="datetime-local" name="appointment_time" class="form-control" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Duration (minutes)</label>
                                    <select name="duration" class="form-select" required>
                                        <option value="15">15 minutes</option>
                                        <option value="30">30 minutes</option>
                                        <option value="45">45 minutes</option>
                                        <option value="60">60 minutes</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Reason</label>
                                    <textarea name="reason" class="form-control" rows="3" required></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Book Appointment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Patient Modal -->
    <div class="modal fade" id="addPatientModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Patient</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="patient-form" method="POST" action="{{ route('patients.store') }}">
                    @csrf
                    <input type="hidden" name="health_facility_id" value="{{ $healthFacility->id }}">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Gender</label>
                                <select name="gender" class="form-select" required>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Birth Date</label>
                                <input type="date" name="birth_date" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="tel" name="contact_number" class="form-control">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Medical History (Optional)</label>
                            <textarea name="medical_history" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Patient</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Message Modal -->
    <div class="modal fade" id="viewMessageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="message-modal-title">Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="message-modal-body">
                    <!-- Content will be loaded via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary reply-message-btn">Reply</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Doctor Profile Modal -->
    <div class="modal fade" id="doctorProfileModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Doctor Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="doctor-profile-content">
                    <!-- Content will be loaded via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary book-from-profile-btn">Book This Doctor</button>
                </div>
            </div>
        </div>
    </div>

   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Handle booking from available doctors list
    document.querySelectorAll('.book-doctor-btn').forEach(button => {
        button.addEventListener('click', function () {
            const doctorId = this.getAttribute('data-doctor-id');
            document.getElementById('doctor-id').value = doctorId;

            // Set the doctor in the select dropdown
            const doctorSelect = document.getElementById('doctor-select');
            doctorSelect.value = doctorId;

            // Open the modal
            const modal = new bootstrap.Modal(document.getElementById('newAppointmentModal'));
            modal.show();
        });
    });

    // Handle viewing doctor profile
    document.querySelectorAll('.view-profile-btn').forEach(button => {
        button.addEventListener('click', function () {
            const doctorId = this.getAttribute('data-doctor-id');

            axios.get(`/api/doctors/${doctorId}`)
                .then(response => {
                    document.getElementById('doctor-profile-content').innerHTML = response.data.html;
                    const modal = new bootstrap.Modal(document.getElementById('doctorProfileModal'));
                    modal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading doctor profile');
                });
        });
    });

    // Book from profile modal
    document.querySelector('.book-from-profile-btn')?.addEventListener('click', function () {
        const modal = bootstrap.Modal.getInstance(document.getElementById('doctorProfileModal'));
        modal.hide();

        const newAppointmentModal = new bootstrap.Modal(document.getElementById('newAppointmentModal'));
        newAppointmentModal.show();
    });

    // Handle viewing messages
    document.querySelectorAll('.message-item').forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            const messageId = this.getAttribute('data-message-id');

            axios.get(`/api/messages/${messageId}`)
                .then(response => {
                    document.getElementById('message-modal-title').textContent = response.data.subject;
                    document.getElementById('message-modal-body').innerHTML = `
                        <div class="mb-3">
                            <strong>From:</strong> Dr. ${response.data.doctor_name}
                        </div>
                        <div class="mb-3">
                            <strong>Date:</strong> ${response.data.created_at}
                        </div>
                        <div class="mb-3">
                            <strong>Message:</strong>
                            <p>${response.data.message}</p>
                        </div>
                        ${response.data.meeting_link ? `
                        <div class="alert alert-info">
                            <strong>Meeting Link:</strong>
                            <a href="${response.data.meeting_link}" target="_blank" rel="noopener noreferrer">
                                Join Meeting
                            </a>
                        </div>` : ''}
                    `;
                    const messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
                    messageModal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load message content.');
                });
        });
    });
</script>
</body>
</html>

    