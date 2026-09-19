<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unsubscribed</title>
    <style>
        body { margin:0; padding:0; background:#1a001a; color:#0b1220; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; }
        .wrap { padding:40px 16px; }
        .card { max-width:620px; margin:0 auto; background:#fff; border-radius:14px; overflow:hidden; box-shadow:0 10px 30px rgba(10,0,10,0.25); }
        .header { background:linear-gradient(135deg,#FF00F8,rgb(128,0,128)); padding:24px 28px; text-align:center; }
        .brand { color:#fff; font-weight:800; font-size:22px; letter-spacing:0.5px; }
        .content { padding:28px 32px; }
        h1 { margin:0 0 8px; font-size:22px; }
        p { margin:0 0 14px; color:#334155; }
        .muted { color:#64748b; font-size:13px; }
        .spacer { height:4px; background:linear-gradient(90deg,#FF00F8,rgb(128,0,128)); }
        a { color:#FF00F8; text-decoration:none; }
    </style>
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <meta property="og:title" content="You have been unsubscribed" />
    <meta property="og:description" content="Newsletter preferences updated." />
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="header"><div class="brand">Easemed</div></div>
            <div class="content">
                <h1>You're unsubscribed</h1>
                <p>
                    @if(!empty($email))
                        We've updated your newsletter preferences for <strong>{{ $email }}</strong>.
                    @else
                        We've updated your newsletter preferences.
                    @endif
                </p>
                <p class="muted">If this address wasn't subscribed, no action was taken.</p>
                <p class="muted">You can resubscribe any time on our website.</p>
            </div>
            <div class="spacer"></div>
            <div class="footer" style="padding:0 32px 24px; color:#94a3b8; font-size:12px;">Sent by {{ config('app.name') }} • {{ config('app.url') }}</div>
        </div>
    </div>
</body>
</html>
