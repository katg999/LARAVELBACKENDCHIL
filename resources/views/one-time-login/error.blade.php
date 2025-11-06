<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Invalid Login Link - KETI AI</title>

    <!-- Pollix Template CSS -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/introjs.css') }}">
    <link rel="stylesheet" href="{{ asset('pollix/css/style.css') }}">

    <style>
        .error-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.4) 0%, rgba(118, 75, 162, 0.4) 100%), url('{{ asset("images/doctor.jpg") }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .error-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 3rem;
            text-align: center;
            max-width: 500px;
            width: 90%;
        }

        .error-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 1rem;
        }

        .error-title {
            color: #333;
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .error-message {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .countdown {
            font-size: 3rem;
            font-weight: bold;
            color: #667eea;
            margin: 2rem 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .redirect-text {
            color: #888;
            font-size: 0.9rem;
            margin-top: 1rem;
        }

        .keti-logo {
            max-width: 150px;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-card">
            <!-- KETI AI Logo -->
            <img src="{{ asset('images/logo.png') }}" alt="KETI AI" class="keti-logo" onerror="this.style.display='none'">

            <div class="error-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>

            <h1 class="error-title">Login link not valid</h1>

            <p class="error-message">
                {{ $message }}<br><br>
                If you need a new login link, please ask the KETI AI Bot to request one for you.
            </p>

            <div class="countdown" id="countdown">{{ $seconds }}</div>

            <p class="redirect-text">
                Redirecting to KETI AI website in <span id="seconds">{{ $seconds }}</span> seconds...
            </p>
        </div>
    </div>

    <!-- Pollix Template JS -->
    <script src="{{ asset('js/app.js') }}"></script>
    <script src="{{ asset('js/intro.js') }}"></script>

    <script>
        let countdown = {{ $seconds }};
        const countdownElement = document.getElementById('countdown');
        const secondsElement = document.getElementById('seconds');

        const timer = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            secondsElement.textContent = countdown;

            if (countdown <= 0) {
                clearInterval(timer);
                window.location.href = 'https://ketiai.com';
            }
        }, 1000);

        // Allow manual redirect by clicking anywhere
        document.addEventListener('click', () => {
            clearInterval(timer);
            window.location.href = 'https://ketiai.com';
        });

        // Allow redirect with Enter or Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === 'Escape') {
                clearInterval(timer);
                window.location.href = 'https://ketiai.com';
            }
        });
    </script>
</body>
</html>
