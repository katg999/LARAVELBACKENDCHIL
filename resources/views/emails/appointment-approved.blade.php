<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Approved - {{ config('app.name') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            max-width: 150px;
            height: auto;
        }
        h1 {
            color: #28a745;
            margin: 20px 0;
            font-size: 24px;
        }
        .content {
            margin-bottom: 30px;
        }
        .appointment-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #28a745;
        }
        .detail-row {
            margin-bottom: 10px;
        }
        .detail-label {
            font-weight: bold;
            color: #495057;
        }
        .success-message {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Appointment Approved</h1>
        </div>

        <div class="content">
            <p>Hello Dr. {{ $doctor->name }},</p>

            <div class="success-message">
                <strong>Great news!</strong> Your completed appointment has been approved by the institution. The appointment is now officially completed and ready for your records.
            </div>

            <div class="appointment-details">
                <h3 style="margin-top: 0; color: #28a745;">Appointment Details</h3>

                <div class="detail-row">
                    <span class="detail-label">Patient:</span> {{ $patient->name }}
                </div>

                <div class="detail-row">
                    <span class="detail-label">Appointment Type:</span> {{ $doctor->specialization === 'General Practitioner' ? 'General Consultation' : 'Specialist Consultation' }}
                </div>

                <div class="detail-row">
                    <span class="detail-label">Duration:</span> {{ $appointment->duration->minutes }} minutes
                </div>

                <div class="detail-row">
                    <span class="detail-label">Date & Time:</span> {{ $appointment->appointment_time->format('l, F j, Y \a\t g:i A') }}
                </div>

                <div class="detail-row">
                    <span class="detail-label">Reason:</span> {{ $appointment->reason }}
                </div>

                @if($institution)
                <div class="detail-row">
                    <span class="detail-label">Institution:</span> {{ $institution->name }}
                </div>
                @endif

                <div class="detail-row">
                    <span class="detail-label">Appointment ID:</span> #{{ $appointment->id }}
                </div>

                <div class="detail-row">
                    <span class="detail-label">Status:</span> <span style="color: #28a745; font-weight: bold;">Completed</span>
                </div>
            </div>

            <p>This appointment has been successfully completed and approved. Thank you for your excellent service!</p>

            <p>You can view all your completed appointments in your doctor dashboard.</p>

            <p>Best regards,<br>The {{ config('app.name') }} Team</p>
        </div>

        <div class="footer">
            <p>This is an automated notification for appointment approval.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>