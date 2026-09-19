<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Appointment Confirmed</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #FF00F8, #000000); color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .appointment-details { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #FF00F8; }
        .meeting-link { background: #FF00F8; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>New Appointment Confirmed</h1>
            <p>You have a new confirmed appointment</p>
        </div>

        <div class="content">
            <p>Dear Dr. {{ $doctor->name }},</p>

            <p>A new appointment has been confirmed and is ready for you to conduct. Here are the details:</p>

            <div class="appointment-details">
                <h3>Appointment Details</h3>
                <p><strong>Patient:</strong> {{ $patient->name }}</p>
                <p><strong>Patient ID:</strong> {{ $patient->patient_id }}</p>
                <p><strong>Date & Time:</strong> {{ $appointment->appointment_time->format('l, F j, Y \a\t g:i A') }}</p>
                <p><strong>Duration:</strong> {{ $appointment->duration->minutes ?? 'N/A' }} minutes</p>
                <p><strong>Reason:</strong> {{ $appointment->reason }}</p>
                @if($institution)
                    <p><strong>Institution:</strong> {{ $institution->name }}</p>
                @endif
            </div>

            <p>You can start the meeting using the link below:</p>

            <a href="{{ $meetingLink }}" class="meeting-link" target="_blank">Join Meeting</a>

            <p>Please be available 5-10 minutes before the appointment time to prepare.</p>

            <p>If you have any questions or need to reschedule, please contact the institution directly.</p>

            <p>Best regards,<br>
            Easemed Health System</p>
        </div>

        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>&copy; 2025 Easemed. All rights reserved.</p>
        </div>
    </div>
</body>
</html>