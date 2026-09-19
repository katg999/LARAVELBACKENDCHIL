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
    <h2>Your insurance</h2>
    @if(session('insurance_status'))<p class="pill ok">{{ session('insurance_status') }}</p>@endif
    @foreach($policies as $pol)
        <div class="row" style="border-top:1px solid #eee;padding-top:8px">
            <strong>{{ $pol->insurer->name ?? 'Insurance' }}</strong> &middot; {{ $pol->member_number }}
            <span class="pill {{ $pol->status === 'active' ? 'ok' : ($pol->status === 'rejected' ? 'no' : 'wait') }}">{{ $pol->status === 'active' ? 'Confirmed' : ($pol->status === 'rejected' ? 'Not confirmed' : 'Being checked') }}</span>
            @if($pol->status === 'rejected' && $pol->review_note)<div class="muted">{{ $pol->review_note }}</div>@endif
        </div>
    @endforeach
    <h2 style="margin-top:14px">Add your insurance</h2>
    <p class="muted">Send it once and your clinic can use it every time you visit or collect medicine.</p>
    <form method="POST" action="{{ $policyUrl }}" enctype="multipart/form-data">
        @csrf
        <select name="insurer_id" required style="width:100%;padding:12px;box-sizing:border-box;font-size:16px;margin-top:8px">
            <option value="">Choose your insurer</option>
            @foreach($insurers as $ins)<option value="{{ $ins->id }}">{{ $ins->name }}</option>@endforeach
        </select>
        <input name="member_number" required maxlength="64" placeholder="Member number on your card" style="width:100%;padding:12px;box-sizing:border-box;font-size:16px;margin-top:8px">
        <input name="scheme_name" maxlength="120" placeholder="Scheme or employer (optional)" style="width:100%;padding:12px;box-sizing:border-box;font-size:16px;margin-top:8px">
        <label class="muted" style="display:block;margin-top:8px">Photo of your card (optional)</label>
        <input type="file" name="card" accept="image/*,.pdf">
        <button class="btn" style="width:100%;border:0;cursor:pointer">Send my insurance details</button>
    </form>
</div>

<div class="card">
    <h2>Medicine</h2>
    @if(session('status'))<p class="pill ok">{{ session('status') }}</p>@endif
    @foreach($prescriptions as $row)
        @php($rx = $row['prescription'])
        <div class="row" style="border-top:1px solid #eee;padding-top:10px">
            <strong>{{ $rx->source === 'uploaded' ? 'Uploaded prescription' : 'Prescription' }}</strong>
            <span class="pill {{ in_array($rx->status, ['issued','approved']) ? 'ok' : ($rx->status === 'rejected' ? 'no' : 'wait') }}">{{ ucfirst(str_replace('_', ' ', $rx->status)) }}</span>
            @foreach($rx->items as $item)
                <div class="muted">{{ $item->name }}@if($item->dosage), {{ $item->dosage }}@endif @if($item->quantity)&times; {{ $item->quantity }}@endif @if($item->unit_price !== null)&middot; UGX {{ number_format((float) $item->unit_price) }} each @endif</div>
            @endforeach
            @if($rx->review_note)<div class="muted">Note: {{ $rx->review_note }}</div>@endif

            @if(in_array($rx->status, ['issued','approved']))
                @if($rx->total_amount === null)
                    <div class="muted">The pharmacy is pricing this.</div>
                @else
                    <div class="row">Total <strong>UGX {{ number_format((float) $rx->total_amount) }}</strong>
                        @if((float) $rx->insurer_amount > 0) &middot; Insurer pays UGX {{ number_format((float) $rx->insurer_amount) }} @endif
                        @if($rx->patient_amount !== null) &middot; You pay <strong>UGX {{ number_format((float) $rx->patient_amount) }}</strong> @endif
                    </div>
                    @if($rx->insurance_status === 'pending')
                        <div class="muted">Waiting for your insurer to confirm cover. You will get a text.</div>
                    @elseif($rx->insurance_status === 'declined')
                        <div class="muted">Your insurer did not cover this{{ $rx->insurance_note ? ': ' . $rx->insurance_note : '' }}. You pay the full price.</div>
                    @endif
                    @if($row['momoUrl'])
                        <form method="POST" action="{{ $row['momoUrl'] }}">
                            @csrf
                            <input name="phone" required maxlength="20" inputmode="tel" placeholder="Mobile money number" style="width:100%;padding:10px;margin-top:8px;box-sizing:border-box">
                            <button class="btn" style="width:100%;border:0;cursor:pointer">Pay UGX {{ number_format($row['due']) }} with mobile money</button>
                        </form>
                    @endif
                    @if($row['walletUrl'])
                        <form method="POST" action="{{ $row['walletUrl'] }}">@csrf<button class="btn" style="width:100%;border:0;cursor:pointer;background:#5b5b70">Pay from wallet (UGX {{ number_format($walletBalance) }})</button></form>
                    @endif
                    @if($rx->payment_status === 'pending')<div class="muted">Payment requested. Approve it on your phone.</div>@endif
                    @if($rx->payment_status === 'paid' && !$rx->delivery_status && !$row['deliveryUrl'])<div class="muted">Paid.</div>@endif
                @endif
            @endif

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
