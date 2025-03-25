@component('mail::message')
# Verify Your Subscription

<img src="{{ asset('images/emoji-logo-white.svg') }}" alt="Keti AI" width="150">

Thank you for subscribing to our newsletter! To complete your subscription and start receiving updates,  
please verify your email address by clicking the button below.

@component('mail::button', ['url' => $verificationUrl, 'color' => 'success'])
Verify Email Address
@endcomponent

If you didn't request this subscription, you can safely ignore this email. No further action is required.

Thanks,  
**{{ config('app.name') }}**
@endcomponent
