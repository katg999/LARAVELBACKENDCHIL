<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Subscription</title>
</head>
@component('mail::layout')
    @slot('header')
        @component('mail::header', ['url' => config('app.url')])
            <img src="{{ asset('images/emoji-logo-black.svg') }}" alt="{{ config('app.name') }}" style="height: 50px;">
        @endcomponent
    @endslot

    <style>
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            font-family: 'Geist', 'Helvetica Neue', Arial, sans-serif;
            color: #333333;
        }
        .email-header {
            color: #890085;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .email-content {
            font-size: 16px;
            line-height: 1.5;
            margin-bottom: 24px;
        }
        .verification-button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #890085;
            color: white !important;
            text-decoration: none;
            border-radius: 32px;
            font-weight: 500;
            margin: 20px 0;
        }
        .email-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eeeeee;
            font-size: 14px;
            color: #666666;
        }
    </style>

    <div class="email-container">
        <h1 class="email-header">Verify Your Subscription</h1>

        <p class="email-content">
            Thank you for subscribing to our newsletter! To complete your subscription and start receiving updates, 
            please verify your email address by clicking the button below.
        </p>

        <div style="text-align: center;">
            <a href="{{ $verificationUrl }}" class="verification-button">
                Verify Email Address
            </a>
        </div>

        <p class="email-content">
            If you didn't request this subscription, you can safely ignore this email. No further action is required.
        </p>

        <div class="email-footer">
            Thanks,<br>
            <strong>{{ config('app.name') }}</strong>
        </div>
    </div>

    @slot('footer')
        @component('mail::footer')
            © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        @endcomponent
    @endslot
@endcomponent
</html>