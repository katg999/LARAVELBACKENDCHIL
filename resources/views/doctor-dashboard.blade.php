<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dr. {{ $doctor->name }} Dashboard</title>
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
        .online-status {
            width: 15px;
            height: 15px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .online {
            background-color: #28a745;
        }
        .offline {
            background-color: #dc3545;
        }
        .meeting-link {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 10px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar p-0">
                <div class="text-center p-3">
                    <!-- Profile Image with Upload Option -->
                    <div class="position-relative mb-3">
                        <img src="{{ $doctor->profile_image ? asset('storage/' . $doctor->profile_image) : asset('images/default-doctor.jpg') }}" 
                             class="profile-img" 
                             id="profile-image"
                             alt="Doctor Profile">
                        <button class="btn btn-sm btn-primary position-absolute bottom-0 end-0 rounded-circle" 
                                data-bs-toggle="modal" 
                                data-bs-target="#uploadImageModal">
                            <i class="fas fa-camera"></i>
                        </button>
                    </div>
                    
                    <h4>Dr. {{ $doctor->name }}</h4>
                    <p class="mb-1">{{ $doctor->specialization }}</p>
                    
                    <!-- Online Status Toggle -->
                    <div class="form-check form-switch d-inline-block">
                        <input class="form-check-input" type="checkbox" id="onlineStatusToggle" 
                               {{ $doctor->is_online ? 'checked' : '' }}>
                        <label class="form-check-label" for="onlineStatusToggle">
                            <span class="online-status {{ $doctor->is_online ? 'online' : 'offline' }}"></span>
                            {{ $doctor->is_online ? 'Online' : 'Offline' }}
                        </label>
                    </div>
                    <hr class="border-light">
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link active" href="#appointments" data-bs-toggle="tab">
                        <i class="fas fa-calendar-check me-2"></i> Appointments
                    </a>
                    <a class="nav-link" href="#meeting-link" data-bs-toggle="tab">
                        <i class="fas fa-video me-2"></i> Meeting Link
                    </a>
                    <a class="nav-link" href="#availability" data-bs-toggle="tab">
                        <i class="fas fa-clock me-2"></i> Availability
                    </a>
                    <a class="nav-link" href="#profile" data-bs-toggle="tab">
                        <i class="fas fa-user me-2"></i> Profile
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="tab-content">
                    <!-- Appointments Tab -->
                    <div class="tab-pane fade show active" id="appointments">
                        <div class="d-flex justify-content-between mb-3">
                            <h2>Appointments</h2>
                            <div class="input-group" style="width: 300px;">
                                <input type="text" class="form-control" placeholder="Search appointments...">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        @if($appointments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Patient</th>
                                        <th>School</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($appointments as $appointment)
                                    <tr>
                                        <td>{{ $appointment->appointment_time->format('M d, Y h:i A') }}</td>
                                        <td>{{ $appointment->student->name }}</td>
                                        <td>{{ $appointment->school->name }}</td>
                                        <td>{{ $appointment->duration }} mins</td>
                                        <td>
                                            <span class="badge bg-{{ 
                                                $appointment->status == 'confirmed' ? 'success' : 
                                                ($appointment->status == 'pending' ? 'warning' : 'secondary') 
                                            }}">
                                                {{ ucfirst($appointment->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($appointment->status == 'confirmed')
                                            <a href="{{ $doctor->meeting_link }}" 
                                               target="_blank" 
                                               class="btn btn-sm btn-success"
                                               data-bs-toggle="tooltip" 
                                               title="Start Meeting">
                                                <i class="fas fa-video"></i>
                                            </a>
                                            @endif
                                            <button class="btn btn-sm btn-primary"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#appointmentDetailsModal"
                                                    data-appointment-id="{{ $appointment->id }}">
                                                <i class="fas fa-eye"></i>
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

                    <!-- Meeting Link Tab -->
                    <div class="tab-pane fade" id="meeting-link">
                        <div class="row">
                            <div class="col-md-8">
                                <h2 class="mb-4">Meeting Link</h2>
                                
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    Remember to record your meetings and keep track of time.
                                </div>
                                
                                <div class="card mb-4">
                                    <div class="card-header bg-primary text-white">
                                        <i class="fas fa-video me-2"></i> Your Personal Meeting Room
                                    </div>
                                    <div class="card-body">
                                        <p>Your permanent meeting link:</p>
                                        <div class="meeting-link mb-3">
                                            meet.ketiai.com/{{ $doctor->meeting_slug ?? 'dr-' . strtolower(str_replace(' ', '-', $doctor->name)) }}
                                        </div>
                                        <p class="text-muted">This link will be shared with patients when they book appointments with you.</p>
                                        
                                        <form id="meeting-link-form" method="POST" action="{{ route('api.doctors.update-meeting-link', $doctor->id) }}">
                                            @csrf
                                            <div class="input-group mb-3">
                                                <span class="input-group-text">meet.ketiai.com/</span>
                                                <input type="text" 
                                                       class="form-control" 
                                                       name="meeting_slug" 
                                                       value="{{ $doctor->meeting_slug ?? 'dr-' . strtolower(str_replace(' ', '-', $doctor->name)) }}"
                                                       placeholder="custom-link">
                                                <button class="btn btn-primary" type="submit">Update</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                
                                <div class="card">
                                    <div class="card-header bg-info text-white">
                                        <i class="fas fa-calendar-check me-2"></i> Upcoming Appointments
                                    </div>
                                    <div class="card-body">
                                        @if($upcomingAppointments->count() > 0)
                                        <ul class="list-group">
                                            @foreach($upcomingAppointments as $appointment)
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong>{{ $appointment->student->name }}</strong> from {{ $appointment->school->name }}<br>
                                                    <small>{{ $appointment->appointment_time->format('M d, Y h:i A') }}</small>
                                                </div>
                                                <a href="{{ $doctor->meeting_link }}" 
                                                   target="_blank" 
                                                   class="btn btn-sm btn-success">
                                                    Start Meeting
                                                </a>
                                            </li>
                                            @endforeach
                                        </ul>
                                        @else
                                        <p class="text-muted">No upcoming appointments.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <div class="card-header bg-success text-white">
                                        <i class="fas fa-bullhorn me-2"></i> Quick Actions
                                    </div>
                                    <div class="card-body">
                                        <button class="btn btn-primary w-100 mb-2" onclick="copyMeetingLink()">
                                            <i class="fas fa-copy me-2"></i> Copy Meeting Link
                                        </button>
                                        <a href="{{ $doctor->meeting_link }}" 
                                           target="_blank" 
                                           class="btn btn-success w-100 mb-2">
                                            <i class="fas fa-video me-2"></i> Test Meeting Room
                                        </a>
                                        <button class="btn btn-info w-100" data-bs-toggle="modal" data-bs-target="#sendLinkModal">
                                            <i class="fas fa-envelope me-2"></i> Send Link to School
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="card">
                                    <div class="card-header bg-secondary text-white">
                                        <i class="fas fa-chart-bar me-2"></i> Statistics
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <h6>Total Appointments</h6>
                                            <div class="progress">
                                                <div class="progress-bar bg-primary" 
                                                     style="width: {{ min(100, $stats['total_appointments'] / 50 * 100) }}%">
                                                    {{ $stats['total_appointments'] ?? 0 }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <h6>Completed Meetings</h6>
                                            <div class="progress">
                                                <div class="progress-bar bg-success" 
                                                     style="width: {{ min(100, $stats['completed_appointments'] / max(1, $stats['total_appointments']) * 100) }}%">
                                                    {{ $stats['completed_appointments']  ?? 0 }}
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <h6>Upcoming</h6>
                                            <div class="progress">
                                                <div class="progress-bar bg-warning" 
                                                     style="width: {{ min(100, $stats['upcoming_appointments'] / 10 * 100) }}%">
                                                    {{ $stats['upcoming_appointments']   ?? 0 }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Availability Tab -->
                    <div class="tab-pane fade" id="availability">
                        <div class="row">
                            <div class="col-md-8">
                                <h2 class="mb-4">Set Your Availability</h2>
                                
                                <div class="card mb-4">
                                    <div class="card-header bg-primary text-white">
                                        <i class="fas fa-calendar-alt me-2"></i> Weekly Schedule
                                    </div>
                                    <div class="card-body">
                                        <form id="availability-form" method="POST" action="{{ route('api.doctors.update-availability', $doctor->id) }}">
                                            @csrf
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Day</th>
                                                        <th>Available</th>
                                                        <th>Start Time</th>
                                                        <th>End Time</th>
                                                        <th>Max Appointments</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                                                    @php
                                                        $availability = $doctor->availabilities->where('day', strtolower($day))->first();
                                                    @endphp
                                                    <tr>
                                                        <td>{{ $day }}</td>
                                                        <td>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input availability-toggle" 
                                                                       type="checkbox" 
                                                                       name="days[{{ strtolower($day) }}][available]"
                                                                       {{ $availability && $availability->available ? 'checked' : '' }}>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <select class="form-control time-select" 
                                                                    name="days[{{ strtolower($day) }}][start_time]"
                                                                    {{ $availability && $availability->available ? '' : 'disabled' }}>
                                                                @for($hour = 8; $hour <= 20; $hour++)
                                                                <option value="{{ sprintf('%02d:00', $hour) }}"
                                                                        {{ $availability && $availability->start_time == sprintf('%02d:00:00', $hour) ? 'selected' : '' }}>
                                                                    {{ sprintf('%02d:00', $hour) }}
                                                                </option>
                                                                @endfor
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <select class="form-control time-select" 
                                                                    name="days[{{ strtolower($day) }}][end_time]"
                                                                    {{ $availability && $availability->available ? '' : 'disabled' }}>
                                                                @for($hour = 9; $hour <= 21; $hour++)
                                                                <option value="{{ sprintf('%02d:00', $hour) }}"
                                                                        {{ $availability && $availability->end_time == sprintf('%02d:00:00', $hour) ? 'selected' : '' }}>
                                                                    {{ sprintf('%02d:00', $hour) }}
                                                                </option>
                                                                @endfor
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="number" 
                                                                   class="form-control" 
                                                                   name="days[{{ strtolower($day) }}][max_appointments]"
                                                                   value="{{ $availability ? $availability->max_appointments : 5 }}"
                                                                   min="1" max="20"
                                                                   {{ $availability && $availability->available ? '' : 'disabled' }}>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                            <button type="submit" class="btn btn-primary">Save Availability</button>
                                        </form>
                                    </div>
                                </div>
                             
                               
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <div class="card-header bg-success text-white">
                                        <i class="fas fa-info-circle me-2"></i> Availability Summary
                                    </div>
                                    <div class="card-body">
                                        <h5>Current Availability</h5>
                                        <ul class="list-unstyled">
                                            @foreach($doctor->availabilities->where('available', true)->sortBy('day') as $avail)
                                            <li>
                                                <strong>{{ ucfirst($avail->day) }}:</strong> 
                                                {{ substr($avail->start_time, 0, 5) }} - {{ substr($avail->end_time, 0, 5) }}
                                                (Max: {{ $avail->max_appointments }})
                                            </li>
                                            @endforeach
                                        </ul>
                                        <hr>
                                        <h5>Next 7 Days</h5>
                                        <div id="availability-calendar">
                                            <!-- Simple calendar view showing availability -->
                                            @foreach($nextSevenDays as $day)
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>{{ $day['date']->format('D, M j') }}</span>
                                                @if($day['available'])
                                                <span class="badge bg-success">Available</span>
                                                @else
                                                <span class="badge bg-secondary">Not Available</span>
                                                @endif
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card">
                                    <div class="card-header bg-warning text-white">
                                        <i class="fas fa-exclamation-triangle me-2"></i> Important Notes
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled">
                                            <li class="mb-2">
                                                <i class="fas fa-info-circle text-primary me-2"></i>
                                                Your availability affects when schools can book appointments.
                                            </li>
                                            <li class="mb-2">
                                                <i class="fas fa-clock text-primary me-2"></i>
                                                Ensure your meeting times don't overlap.
                                            </li>
                                            <li>
                                                <i class="fas fa-calendar-times text-primary me-2"></i>
                                                Add special days off for holidays or personal time.
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Tab -->
                    <div class="tab-pane fade" id="profile">
                        <div class="row">
                            <div class="col-md-6">
                                <h2 class="mb-4">Profile Information</h2>
                                <form method="POST" action="{{ route('api.doctors.update', $doctor->id) }}">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" name="name" class="form-control" value="{{ $doctor->name }}" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" value="{{ $doctor->email }}" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Specialization</label>
                                        <input type="text" name="specialization" class="form-control" value="{{ $doctor->specialization }}" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">License Number</label>
                                        <input type="text" name="license_number" class="form-control" value="{{ $doctor->license_number }}" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Phone Number</label>
                                        <input type="tel" name="phone" class="form-control" value="{{ $doctor->phone }}">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Bio</label>
                                        <textarea name="bio" class="form-control" rows="4">{{ $doctor->bio }}</textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Update Profile</button>
                                </form>
                            </div>
                            
                            <div class="col-md-6">
                                <h2 class="mb-4">Account Settings</h2>
                                
                                <div class="card mb-4">
                                    <div class="card-header bg-primary text-white">
                                        Change Password
                                    </div>
                                    <div class="card-body">
                                        <form id="password-form" method="POST" action="{{ route('api.doctors.change-password', $doctor->id) }}">
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
                                
                                <div class="card mb-4">
                                    <div class="card-header bg-info text-white">
                                        Notification Preferences
                                    </div>
                                    <div class="card-body">
                                        <form id="notifications-form" method="POST" action="{{ route('api.doctors.update-notifications', $doctor->id) }}">
                                            @csrf
                                            <div class="mb-3 form-check">
                                                <input type="checkbox" class="form-check-input" name="email_notifications" 
                                                       id="email-notifications" {{ $doctor->email_notifications ? 'checked' : '' }}>
                                                <label class="form-check-label" for="email-notifications">Email Notifications</label>
                                            </div>
                                            <div class="mb-3 form-check">
                                                <input type="checkbox" class="form-check-input" name="sms_notifications" 
                                                       id="sms-notifications" {{ $doctor->sms_notifications ? 'checked' : '' }}>
                                                <label class="form-check-label" for="sms-notifications">SMS Notifications</label>
                                            </div>
                                            <div class="mb-3 form-check">
                                                <input type="checkbox" class="form-check-input" name="appointment_reminders" 
                                                       id="appointment-reminders" {{ $doctor->appointment_reminders ? 'checked' : '' }}>
                                                <label class="form-check-label" for="appointment-reminders">Appointment Reminders</label>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Save Preferences</button>
                                        </form>
                                    </div>
                                </div>
                                
                                <div class="card">
                                    <div class="card-header bg-warning text-white">
                                        Payment Information
                                    </div>
                                    <div class="card-body">
                                        <form id="payment-form" method="POST" action="{{ route('api.doctors.update-payment', $doctor->id) }}">
                                            @csrf
                                            <div class="mb-3">
                                                <label class="form-label">Mobile Money Number</label>
                                                <input type="tel" name="momo_number" class="form-control" value="{{ $doctor->momo_number }}">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Bank Name (Optional)</label>
                                                <input type="text" name="bank_name" class="form-control" value="{{ $doctor->bank_name }}">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Account Number (Optional)</label>
                                                <input type="text" name="account_number" class="form-control" value="{{ $doctor->account_number }}">
                                            </div>
                                            <button type="submit" class="btn btn-primary">Update Payment Info</button>
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

    <!-- Upload Image Modal -->
    <div class="modal fade" id="uploadImageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Profile Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="image-upload-form" method="POST" action="{{ route('api.doctors.upload-image', $doctor->id) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="profile_image" class="form-label">Choose Image</label>
                            <input class="form-control" type="file" id="profile_image" name="profile_image" accept="image/*" required>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Image should be square and at least 300x300 pixels for best results.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Upload Image</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Send Link Modal -->
    <div class="modal fade" id="sendLinkModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Send Meeting Link</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="send-link-form" method="POST" action="{{ route('api.doctors.send-link', $doctor->id) }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Recipient Email</label>
                            <input type="email" name="recipient_email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message (Optional)</label>
                            <textarea name="message" class="form-control" rows="3">Here is my meeting link for our appointments: meet.ketiai.com/{{ $doctor->meeting_slug ?? 'dr-' . strtolower(str_replace(' ', '-', $doctor->name)) }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Send Link</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Appointment Details Modal -->
    <div class="modal fade" id="appointmentDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Appointment Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="appointment-details-content">
                    <!-- Content will be loaded via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        // Online status toggle
        document.getElementById('onlineStatusToggle').addEventListener('change', function() {
            const isOnline = this.checked;
            const statusElement = document.querySelector('.online-status');
            
            axios.post('{{ route("api.doctors.update-online-status", $doctor->id) }}', {
                is_online: isOnline
            })
            .then(response => {
                statusElement.className = 'online-status ' + (isOnline ? 'online' : 'offline');
                document.querySelector('.form-check-label').textContent = isOnline ? 'Online' : 'Offline';
            })
            .catch(error => {
                console.error('Error:', error);
                this.checked = !isOnline; // Revert if error
            });
        });

        // Enable/disable time selects based on availability toggle
        document.querySelectorAll('.availability-toggle').forEach(toggle => {
            toggle.addEventListener('change', function() {
                const row = this.closest('tr');
                const timeSelects = row.querySelectorAll('.time-select');
                const maxAppointments = row.querySelector('input[type="number"]');
                
                timeSelects.forEach(select => {
                    select.disabled = !this.checked;
                });
                maxAppointments.disabled = !this.checked;
            });
        });

        // Copy meeting link to clipboard
        function copyMeetingLink() {
            const link = 'meet.ketiai.com/{{ $doctor->meeting_slug ?? "dr-" . strtolower(str_replace(" ", "-", $doctor->name)) }}';
            navigator.clipboard.writeText(link).then(() => {
                alert('Meeting link copied to clipboard!');
            });
        }

        // Handle image upload
        document.getElementById('image-upload-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            axios.post(this.action, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
            .then(response => {
                if (response.data.success) {
                    document.getElementById('profile-image').src = response.data.image_url;
                    $('#uploadImageModal').modal('hide');
                    alert('Profile image updated successfully!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error uploading image. Please try again.');
            });
        });

        // Load appointment details
        document.getElementById('appointmentDetailsModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const appointmentId = button.getAttribute('data-appointment-id');
            
            axios.get(`/api/appointments/${appointmentId}`)
                .then(response => {
                    document.getElementById('appointment-details-content').innerHTML = response.data.html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('appointment-details-content').innerHTML = 
                        '<div class="alert alert-danger">Error loading appointment details.</div>';
                });
        });

        // Delete day off
        document.querySelectorAll('.delete-dayoff').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('Are you sure you want to remove this day off?')) {
                    const dayOffId = this.getAttribute('data-id');
                    
                    axios.delete(`/api/doctors/dayoff/${dayOffId}`)
                        .then(response => {
                            if (response.data.success) {
                                this.closest('li').remove();
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error removing day off. Please try again.');
                        });
                }
            });
        });

        // Form submission handlers
        document.getElementById('profile-form').addEventListener('submit', handleFormSubmit);
        document.getElementById('password-form').addEventListener('submit', handleFormSubmit);
        document.getElementById('notifications-form').addEventListener('submit', handleFormSubmit);
        document.getElementById('payment-form').addEventListener('submit', handleFormSubmit);
        document.getElementById('availability-form').addEventListener('submit', handleFormSubmit);
        document.getElementById('dayoff-form').addEventListener('submit', handleFormSubmit);
        document.getElementById('meeting-link-form').addEventListener('submit', handleFormSubmit);
        document.getElementById('send-link-form').addEventListener('submit', handleFormSubmit);

        function handleFormSubmit(e) {
            e.preventDefault();
            const form = e.target;
            const submitButton = form.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
            
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
                if (data.success) {
                    alert(data.message || 'Operation successful!');
                    if (form.id === 'meeting-link-form') {
                        // Update the displayed meeting link
                        const meetingLink = document.querySelector('.meeting-link');
                        meetingLink.textContent = 'meet.ketiai.com/' + formData.get('meeting_slug');
                    }
                    if (form.id === 'dayoff-form') {
                        // Reload to show new day off
                        window.location.reload();
                    }
                } else {
                    alert(data.message || 'Operation failed');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            });
        }

        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map