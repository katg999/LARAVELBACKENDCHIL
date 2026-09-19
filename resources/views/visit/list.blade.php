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
        @php($label = ['open' => ['Ready to join','ok'], 'early' => ['Upcoming','wait'], 'awaiting_payment' => ['Waiting for payment','wait'], 'awaiting_verification' => ['Checking insurance','wait'], 'ended' => ['Ended','no'], 'cancelled' => ['Cancelled','no']][$item['state']])
        <span class="pill {{ $label[1] }}">{{ $label[0] }}</span>
        @if(in_array($item['state'], ['open','early','awaiting_payment','awaiting_verification']))
            <a class="btn" href="{{ $item['link'] }}">Open visit</a>
        @endif
    </div>
@empty
    <div class="card"><p class="muted">No visits yet.</p></div>
@endforelse

<div class="card">
    <h2>Medicine</h2>
    @if(session('status'))<p class="pill ok">{{ session('status') }}</p>@endif
    @foreach($prescriptions as $row)
        @php($rx = $row['prescription'])
        <div class="row" style="border-top:1px solid #eee;padding-top:10px">
            <strong>{{ $rx->source === 'uploaded' ? 'Uploaded prescription' : 'Prescription' }}</strong>
            <span class="pill {{ in_array($rx->status, ['issued','approved']) ? 'ok' : ($rx->status === 'rejected' ? 'no' : 'wait') }}">{{ ucfirst(str_replace('_', ' ', $rx->status)) }}</span>
            @foreach($rx->items as $item)
                <div class="muted">{{ $item->name }}@if($item->dosage), {{ $item->dosage }}@endif @if($item->quantity)&times; {{ $item->quantity }}@endif</div>
            @endforeach
            @if($rx->review_note)<div class="muted">Note: {{ $rx->review_note }}</div>@endif
            @if($rx->delivery_status)
                <div class="muted">Delivery: <strong>{{ ucfirst(str_replace('_', ' ', $rx->delivery_status)) }}</strong></div>
            @elseif($row['deliveryUrl'])
                <form method="POST" action="{{ $row['deliveryUrl'] }}">
                    @csrf
                    <input name="address" required maxlength="255" placeholder="Delivery address" style="width:100%;padding:10px;margin-top:8px;box-sizing:border-box">
                    <button class="btn" style="width:100%;border:0">Request delivery</button>
                </form>
            @endif
        </div>
    @endforeach
    <h2 style="margin-top:18px">Send a prescription from another doctor</h2>
    <form method="POST" action="{{ $uploadUrl }}" enctype="multipart/form-data">
        @csrf
        <input type="file" name="photo" accept="image/*,.pdf" required style="margin:8px 0">
        <button class="btn" style="width:100%;border:0">Send for review</button>
    </form>
    @if($errors->any())<p class="pill no">{{ $errors->first() }}</p>@endif
</div>
@endsection
