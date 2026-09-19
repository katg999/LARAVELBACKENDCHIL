<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Meeting Link</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; background:#f6f9fc; color:#333; margin:0; padding:20px; }
        .container { max-width:600px; margin:20px auto; background:white; border-radius:8px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.06); }
        .header { background:#1f2937; color:white; padding:18px 24px; }
        .content { padding:24px; }
        .btn { display:inline-block; padding:12px 18px; background:#2563eb; color:white; text-decoration:none; border-radius:6px; }
        .meta { color:#6b7280; font-size:13px; margin-bottom:12px; }
        .footer { background:#f3f4f6; padding:14px 20px; font-size:13px; color:#6b7280; }
        .card { border:1px solid #e5e7eb; padding:12px; border-radius:6px; background:#ffffff; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2 style="margin:0; font-size:18px;">Meeting link from Dr. {{ $doctor->name ?? 'Your Doctor' }}</h2>
    </div>

    <div class="content">
        <p class="meta">{{ $doctor->specialization ?? '' }}</p>

        <div class="card">
            <p style="margin-top:0;">Please click the button below to join the meeting.</p>
            <p style="margin:16px 0 0;">
                <a class="btn" href="{{ $link }}" target="_blank" rel="noopener" style="background:#2563eb;color:#ffffff;text-decoration:none;border-radius:6px;display:inline-block;padding:12px 18px;">Join Meeting</a>
            </p>
        </div>

        <p style="color:#6b7280; font-size:13px; margin-top:16px;">If the button doesn't work, you can copy and paste this link into your browser:</p>
        <p style="word-break:break-all; font-size:13px; color:#111827;"><a href="{{ $link }}">{{ $link }}</a></p>
    </div>

    <div class="footer">
        <div>Sent with care by the Easemed platform</div>
    </div>
</div>
</body>
</html>