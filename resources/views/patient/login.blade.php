@extends('visit.layout')
@section('title', 'Sign in')
@section('content')
<div class="card">
    <h1>Your records</h1>
    <p class="muted">Sign in with the phone number you gave the clinic. We text you a 6-digit code.</p>
    @if(session('status'))<p class="pill ok">{{ session('status') }}</p>@endif
    @if($errors->any())<p class="pill no">{{ $errors->first() }}</p>@endif

    <form method="POST" action="{{ route('patient.login.code') }}">
        @csrf
        <input name="phone" value="{{ old('phone', session('phone')) }}" placeholder="Phone, e.g. 0772 123 456" required maxlength="20" inputmode="tel" style="width:100%;padding:12px;box-sizing:border-box;font-size:16px;margin-top:8px">
        <button class="btn" style="width:100%;border:0">Send code</button>
    </form>
</div>
@if(session('status') || $errors->any())
<div class="card">
    <h2>Enter your code</h2>
    <form method="POST" action="{{ route('patient.login.verify') }}">
        @csrf
        <input type="hidden" name="phone" value="{{ old('phone', session('phone')) }}">
        <input name="code" required maxlength="6" inputmode="numeric" autocomplete="one-time-code" placeholder="6-digit code" style="width:100%;padding:12px;box-sizing:border-box;font-size:20px;letter-spacing:4px;margin-top:8px">
        <button class="btn" style="width:100%;border:0">Sign in</button>
    </form>
</div>
@endif
@endsection
