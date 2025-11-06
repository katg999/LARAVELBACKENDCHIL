<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Appointment Completed - Awaiting Your Approval</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #FF00F8, #800080); color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .appointment-details { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #FF00F8; }
        .approve-button { background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Appointment Completed - Awaiting Your Approval</h1>
            <p>An appointment has been completed and requires your approval</p>
        </div>

        <div class="content">
            <p>Dear {{ $institution->name }},</p>

            <p>Dr. {{ $doctor->name }} has marked an appointment as completed. Please review and approve the appointment in your dashboard.</p>

            <div class="appointment-details">
                <h3>Appointment Details</h3>
                <p><strong>Patient:</strong> {{ $patient->name }}</p>
                <p><strong>Patient ID:</strong> {{ $patient->patient_id }}</p>
                <p><strong>Doctor:</strong> Dr. {{ $doctor->name }}</p>
                <p><strong>Date & Time:</strong> {{ $appointment->appointment_time->format('l, F j, Y \a\t g:i A') }}</p>
                <p><strong>Duration:</strong> {{ $appointment->duration->minutes ?? 'N/A' }} minutes</p>
                <p><strong>Reason:</strong> {{ $appointment->reason }}</p>
            </div>

            <p>Please log in to your dashboard to review and approve this appointment.</p>

            <p>If you have any questions about this appointment, please contact Dr. {{ $doctor->name }} directly.</p>

            <p>Best regards,<br>
            KETI AI Health System</p>
        </div>

        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>&copy; 2025 KETI AI. All rights reserved.</p>
        </div>
    </div>
</body>
</html>