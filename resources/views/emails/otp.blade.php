<!DOCTYPE html>
<html>
<head>
    <title>Your OTP Code</title>
</head>
<body>
    <h2>Your School Login OTP</h2>
    <p>Here is your one-time password for school login:</p>
    
    <div style="font-size: 24px; font-weight: bold; margin: 20px 0;">
        {{ $otp }}
    </div>
    
    <p>This code is valid for 24 hours. Do not share it with anyone.</p>
    
    <p>If you didn't request this, please ignore this email.</p>
</body>
</html>