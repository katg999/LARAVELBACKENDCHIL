<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Newsletter Verification</title>
  <style>
    body { margin:0; padding:0; background:#1a001a; color:#0b1220; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; }
    .wrap { padding:40px 16px; }
    .card { max-width:620px; margin:0 auto; background:#fff; border-radius:14px; overflow:hidden; box-shadow:0 10px 30px rgba(10,0,10,0.25); }
    .header { background:linear-gradient(135deg,#FF00F8,rgb(128,0,128)); padding:24px 28px; text-align:center; }
    .brand { color:#fff; font-weight:800; font-size:22px; letter-spacing:0.5px; }
    .content { padding:28px 32px; }
    h1 { margin:0 0 8px; font-size:22px; }
    p { margin:0 0 14px; color:#334155; }
    .status-success { color:#15803d; font-weight:700; }
    .status-already { color:#6b7280; font-weight:700; }
    .status-invalid { color:#b91c1c; font-weight:700; }
    .footer { padding:0 32px 24px; color:#94a3b8; font-size:12px; }
    .spacer { height:4px; background:linear-gradient(90deg,#FF00F8,rgb(128,0,128)); }
  </style>
  <meta name="robots" content="noindex, nofollow">
</head>
<body>
  <div class="wrap">
    <div class="card">
      <div class="header"><div class="brand">Easemed</div></div>
      <div class="content">
        @if($status === 'success')
          <h1>You're verified</h1>
          <p class="status-success">Thanks! We've confirmed <strong>{{ $email }}</strong>.</p>
          <p>You'll start receiving our newsletter at this address.</p>
        @elseif($status === 'already')
          <h1>Already verified</h1>
          <p class="status-already">This email <strong>{{ $email }}</strong> has already been verified.</p>
        @else
          <h1>Invalid or expired link</h1>
          <p class="status-invalid">That verification link is invalid or has expired.</p>
        @endif
      </div>
      <div class="spacer"></div>
      <div class="footer">Sent by {{ config('app.name') }} • {{ config('app.url') }}</div>
    </div>
  </div>
</body>
</html>
