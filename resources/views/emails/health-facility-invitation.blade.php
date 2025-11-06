<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Facility Invitation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #c71585 0%, #800080 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px 20px;
        }
        .content p {
            margin: 15px 0;
        }
        .invitation-box {
            background: #f8f9fa;
            border-left: 4px solid #c71585;
            padding: 15px;
            margin: 20px 0;
        }
        .invitation-box strong {
            color: #c71585;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .accept-button {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #c71585 0%, #800080 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
        }
        .accept-button:hover {
            opacity: 0.9;
            color: #ffffff !important;
        }
        .info-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
        }
        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>🏥 Health Facility Staff Invitation</h1>
        </div>

        <!-- Content -->
        <div class="content">
            <p>Hello!</p>

            <p>You have been invited to join the <strong>{{ $healthFacility->name }}</strong> medical team on the KETI AI Healthcare Platform.</p>

            <div class="invitation-box">
                <p><strong>Health Facility:</strong> {{ $healthFacility->name }}</p>
                <p><strong>Your Role:</strong> {{ ucwords(str_replace('-', ' ', $invitation->role)) }}</p>
                <p><strong>Invited By:</strong> {{ $invitation->inviter->name ?? 'System Administrator' }}</p>
                <p><strong>Valid Until:</strong> {{ $invitation->expires_at->format('F d, Y') }}</p>
            </div>

            <p>As a <strong>{{ ucwords(str_replace('-', ' ', $invitation->role)) }}</strong>, you will have access to:</p>
            <ul>
                @if(str_contains($invitation->role, 'admin'))
                    <li>📊 Facility dashboard and analytics</li>
                    <li>👥 Staff management</li>
                    <li>🏥 Patient records and appointments</li>
                    <li>💊 Medical services management</li>
                    <li>📧 Send staff invitations</li>
                @else
                    <li>📊 Facility dashboard</li>
                    <li>🏥 Patient records and appointments</li>
                    <li>💊 Medical services</li>
                @endif
            </ul>

            <div class="info-box">
                <strong>⚠️ Important:</strong> This invitation will expire on <strong>{{ $invitation->expires_at->format('F d, Y') }}</strong>. Please accept it before then.
            </div>

            <div class="button-container">
                <a href="{{ $invitationUrl }}" class="accept-button" style="color: #ffffff !important;">Accept Invitation</a>
            </div>

            <p>Or copy and paste this link into your browser:</p>
            <p style="word-break: break-all; background: #f8f9fa; padding: 10px; border-radius: 5px; font-size: 12px;">
                {{ $invitationUrl }}
            </p>

            <p>If you did not expect this invitation, you can safely ignore this email.</p>

            <p>Best regards,<br><strong>KETI AI Team</strong></p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; {{ date('Y') }} KETI AI Healthcare Platform. All rights reserved.</p>
            <p>This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
