<!DOCTYPE html>
<html>
<head>
    <title>Your OTP Code</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .otp-code {
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 2px;
            margin: 20px 0;
            padding: 10px;
            background: #f4f4f4;
            display: inline-block;
        }
    </style>
</head>
<body>
    <h2>Your School Login OTP</h2>
    <p>Here is your one-time password:</p>
    
    <div class="otp-code">{{ $otp }}</div>
    
    <p>This code is valid for 24 hours. Do not share it with anyone.</p>
    
    <p>If you didn't request this, please ignore this email.</p>
</body>
</html>