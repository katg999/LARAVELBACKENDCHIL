@extends('visit.layout')
@section('title', 'Your video visit')
@section('content')
<div class="card">
    <h1>Your video visit</h1>
    <p class="muted">{{ $appointment->patient->name ?? 'Patient' }}</p>
    <div class="row"><strong>Doctor:</strong> Dr. {{ $appointment->doctor->name ?? '' }}</div>
    <div class="row"><strong>When:</strong> {{ $appointment->appointment_time->format('D j M Y, g:i A') }}</div>
    <div class="row"><strong>Length:</strong> {{ $appointment->duration->minutes ?? 30 }} minutes</div>

    @if($state === 'open')
        <span class="pill ok">Ready to join</span>
        <p class="muted">By joining you agree to a video consultation with your doctor. It is not recorded.</p>
        <form method="POST" action="{{ $joinUrl }}">
            @csrf
            <button class="btn" style="width:100%;border:0;cursor:pointer">Join video visit</button>
        </form>
        <form method="POST" action="{{ $joinUrl }}">
            @csrf
            <input type="hidden" name="audio_only" value="1">
            <button class="btn" style="width:100%;border:0;cursor:pointer;background:#5b5b70">Join with audio only (uses less data)</button>
        </form>
    @elseif($state === 'early')
        <span class="pill wait">Not open yet</span>
        <p class="muted">You can join from {{ $opensAt->format('g:i A') }}. Open this page again then.</p>
    @elseif($state === 'awaiting_verification')
        <span class="pill wait">Checking your insurance</span>
        <p class="muted">Your clinic is confirming your insurance cover. You will get a text when your visit is confirmed.</p>
    @elseif($state === 'awaiting_payment')
        <span class="pill wait">Waiting for payment</span>
        <p class="muted">Your visit is confirmed once payment is received. This page will show the join button after that.</p>
    @elseif($state === 'ended')
        <span class="pill no">Visit ended</span>
        <p class="muted">This visit is over. Contact the clinic if you need another appointment.</p>
    @else
        <span class="pill no">Cancelled</span>
        <p class="muted">This appointment was cancelled.</p>
    @endif
</div>
@endsection
