@extends('visit.layout')
@section('title', $title)
@section('content')
@php($pct = fn ($v) => $v === null ? 'no data yet' : round($v * 100) . '%')
<div class="card">
    <h1>{{ $title }}</h1>
    <p class="muted">{{ $m['period']['from'] }} to {{ $m['period']['to'] }} &middot; {{ $m['bookings'] }} bookings</p>
</div>
<div class="card"><h2>Booked and paid</h2><div class="row"><strong>{{ $pct($m['booked_and_paid']['share']) }}</strong> of bookings are paid ({{ $m['booked_and_paid']['count'] }})</div>
    @foreach($m['payment_methods'] as $method => $n)<div class="muted">{{ str_replace('_', ' ', $method) }}: {{ $n }}</div>@endforeach
    <div class="muted">Insured: {{ $pct($m['insured_share']) }}</div></div>
<div class="card"><h2>First visits</h2><div class="row"><strong>{{ $pct($m['first_visit_completion']['share']) }}</strong> of {{ $m['new_patients'] }} new patients completed a first visit</div></div>
<div class="card"><h2>Booking to consultation</h2><div class="row"><strong>{{ $m['minutes_booking_to_consult']['median'] === null ? 'no data yet' : round($m['minutes_booking_to_consult']['median'] / 60, 1) . ' hours (median)' }}</strong></div>
    <div class="muted">Based on {{ $m['minutes_booking_to_consult']['sample'] }} completed visits</div></div>
<div class="card"><h2>Coming back</h2>
    <div class="row">Within 30 days: <strong>{{ $pct($m['repeat_30_days']['share']) }}</strong> ({{ $m['repeat_30_days']['returned'] }} of {{ $m['repeat_30_days']['eligible'] }})</div>
    <div class="row">Within 90 days: <strong>{{ $pct($m['repeat_90_days']['share']) }}</strong> ({{ $m['repeat_90_days']['returned'] }} of {{ $m['repeat_90_days']['eligible'] }})</div></div>
<div class="card"><p class="muted">Call-centre load is not tracked in this system, so it is not shown.</p></div>
@endsection
