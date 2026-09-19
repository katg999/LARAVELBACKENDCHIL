<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login link</title>
    <style>
      body { font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; background:#f8f9fa; color:#222; }
      .card { max-width:700px;margin:80px auto;padding:28px;background:#fff;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,0.06); }
      .cta { display:inline-block;padding:10px 14px;border-radius:6px;background:#0d6efd;color:#fff;text-decoration:none }
      .muted { color:#666;font-size:14px }
    </style>
  </head>
  <body>
    <div class="card">
      <h2>Login link not valid</h2>
      <p>{{ $message }}</p>

      <p class="muted">You'll be redirected to the home page in <strong id="count">{{ $seconds }}</strong> seconds.</p>

      <p>If you need a new login link, please ask your Easemed contact to send you a new one.</p>

      <p style="margin-top:18px">
        <a href="/" class="cta">Go now</a>
      </p>
    </div>

    <script>
      (function(){
        var seconds = {{ $seconds }};
        var el = document.getElementById('count');
        var id = setInterval(function(){
          seconds -= 1;
          if (seconds <= 0) {
            clearInterval(id);
            window.location = '/';
            return;
          }
          el.textContent = seconds;
        }, 1000);
      })();
    </script>
  </body>
  </html>
