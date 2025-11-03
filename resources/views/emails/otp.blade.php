<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ isset($userType) ? ucfirst($userType) : 'School' }} Login OTP</title>
</head>
<body style="margin:0; padding:0; background:#f5f7fb; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; color:#0f172a;">

  @php
      // Ensure $userType is defined and sanitized
      $userTypeSafe = $userType ?? 'school';
      $entityLabel = match($userTypeSafe) {
          'health_facility' => 'Health Facility',
          'doctor' => 'Doctor',
          default => 'School'
      };
  @endphp

  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#f5f7fb; padding:24px 0;">
    <tr>
      <td align="center">

        <table role="presentation" cellpadding="0" cellspacing="0" width="480" style="max-width:480px; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.06);">
          
          <!-- Header -->
          <tr>
            <td style="padding:16px 24px; background:#FF00F8; color:#ffffff; text-align:center;">
              <div style="font-size:20px; font-weight:700; letter-spacing:0.3px;">KETI AI</div>
              <div style="opacity:0.9; font-size:13px; margin-top:4px;">Secure Sign-In</div>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:28px 24px 8px 24px;">
              <h1 style="margin:0 0 8px 0; font-size:22px; line-height:1.35;">Your {{ $entityLabel }} Login OTP</h1>
              <p style="margin:0; font-size:14px; color:#334155;">
                Use the one-time password below to complete your sign-in.
              </p>
            </td>
          </tr>

          <!-- OTP -->
          <tr>
            <td style="padding:12px 24px 4px 24px;" align="center">
              <div style="display:inline-block; font-size:32px; font-weight:700; letter-spacing:6px; padding:14px 20px; border-radius:10px; background:#f1f5f9; color:#0f172a; border:1px solid #e2e8f0;">
                {{ $otp ?? '------' }}
              </div>
            </td>
          </tr>

          <!-- Info -->
          <tr>
            <td style="padding:12px 24px 0 24px;">
              <p style="margin:0 0 10px 0; font-size:13px; color:#475569;">
                This code expires in <strong>{{ $expiryMinutes }} minutes.</strong> For your security, don’t share it with anyone.
              </p>
              <p style="margin:0 0 18px 0; font-size:13px; color:#475569;">
                If you didn’t request this, you can safely ignore this email.
              </p>
            </td>
          </tr>

          <!-- Button -->
          <tr>
            <td style="padding:0 24px 24px 24px;" align="center">
              <a href="https://keti.ai"
                 style="display:inline-block; background:#FF00F8; color:#ffffff; text-decoration:none; padding:10px 16px; border-radius:8px; font-size:14px;">
                Open Portal
              </a>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding:16px 24px; background:#f8fafc; color:#64748b; font-size:12px; text-align:center;">
              Sent by KETI AI • <a href="https://keti.ai" style="color:#64748b; text-decoration:none;">keti.ai</a>
            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>

</body>
</html>
