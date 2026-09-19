@extends('visit.layout')
@section('title', 'Your visits')
@section('content')
<div class="card">
    <h1>Your visits</h1>
    <p class="muted">{{ $patient->name }}</p>
</div>
@forelse($items as $item)
    @php($a = $item['appointment'])
    <div class="card">
        <h2>Dr. {{ $a->doctor->name ?? '' }}</h2>
        <div class="row">{{ $a->appointment_time->format('D j M Y, g:i A') }} &middot; {{ $a->duration->minutes ?? 30 }} min</div>
        @php($label = ['open' => ['Ready to join','ok'], 'early' => ['Upcoming','wait'], 'awaiting_payment' => ['Waiting for payment','wait'], 'ended' => ['Ended','no'], 'cancelled' => ['Cancelled','no']][$item['state']])
        <span class="pill {{ $label[1] }}">{{ $label[0] }}</span>
        @if(in_array($item['state'], ['open','early','awaiting_payment']))
            <a class="btn" href="{{ $item['link'] }}">Open visit</a>
        @endif
    </div>
@empty
    <div class="card"><p class="muted">No visits yet.</p></div>
@endforelse
@endsection
