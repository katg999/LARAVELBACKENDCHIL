<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Logged Out - Easemed</title>

    <!-- Pollix Template CSS -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/introjs.css') }}">
    <link rel="stylesheet" href="{{ asset('pollix/css/style.css') }}">

    <style>
        .logout-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.4) 0%, rgba(118, 75, 162, 0.4) 100%), url('{{ asset("images/doctor.jpg") }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .logout-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 3rem;
            text-align: center;
            max-width: 500px;
            width: 90%;
        }

        .logout-icon {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 1rem;
        }

        .logout-title {
            color: #333;
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .logout-message {
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
    <div class="logout-container">
        <div class="logout-card">
            <!-- Easemed Logo -->
            <img src="{{ asset('images/easemed-logo-dark.svg') }}" alt="Easemed" class="keti-logo" onerror="this.style.display='none'">

            <div class="logout-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>

            <h1 class="logout-title">You are currently logged out</h1>

            <p class="logout-message">
                You are currently logged out.<br>
                To access your dashboard again, please request a new one-time login link from your Easemed contact.
            </p>

            <div class="countdown" id="countdown">10</div>

            <p class="redirect-text">
                Taking you back to sign in in <span id="seconds">10</span> seconds...
            </p>
        </div>
    </div>

    <!-- Pollix Template JS -->
    <script src="{{ asset('js/app.js') }}"></script>
    <script src="{{ asset('js/intro.js') }}"></script>

    <script>
        let countdown = 10;
        const countdownElement = document.getElementById('countdown');
        const secondsElement = document.getElementById('seconds');

        const timer = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            secondsElement.textContent = countdown;

            if (countdown <= 0) {
                clearInterval(timer);
                window.location.href = '{{ url('/login') }}';
            }
        }, 1000);

        // Allow manual redirect by clicking anywhere
        document.addEventListener('click', () => {
            clearInterval(timer);
            window.location.href = '{{ url('/login') }}';
        });

        // Allow redirect with Enter or Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === 'Escape') {
                clearInterval(timer);
                window.location.href = '{{ url('/login') }}';
            }
        });
    </script>
</body>
</html>