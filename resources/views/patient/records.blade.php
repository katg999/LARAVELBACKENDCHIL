@extends('visit.layout')
@section('title', 'Your records')
@section('content')
<div class="card">
    <h1>Your records</h1>
    <form method="POST" action="{{ route('patient.logout') }}">@csrf<button style="border:0;background:none;color:#c800c4;font-size:15px;padding:0;cursor:pointer">Sign out</button></form>
</div>
@foreach($patients as $p)
    <div class="card">
        <h2>{{ $p->name }}</h2>
        <p class="muted">Patient ID {{ $p->patient_id }}</p>
    </div>

    <div class="card">
        <h2>Visit notes</h2>
        @forelse($p->medicalHistories as $note)
            <div class="row" style="border-top:1px solid #eee;padding-top:8px">
                <div class="muted">{{ optional($note->recorded_date)->format('j M Y') }}@if($note->doctor) &middot; Dr. {{ $note->doctor->name }}@endif</div>
                <div>{{ $note->content }}</div>
            </div>
        @empty
            <p class="muted">No notes yet.</p>
        @endforelse
    </div>

    <div class="card">
        <h2>Prescriptions</h2>
        @forelse($p->prescriptions as $rx)
            <div class="row" style="border-top:1px solid #eee;padding-top:8px">
                <span class="pill {{ in_array($rx->status, ['issued','approved']) ? 'ok' : ($rx->status === 'rejected' ? 'no' : 'wait') }}">{{ ucfirst(str_replace('_', ' ', $rx->status)) }}</span>
                @foreach($rx->items as $item)
                    <div>{{ $item->name }}@if($item->dosage), {{ $item->dosage }}@endif @if($item->quantity)&times; {{ $item->quantity }}@endif
                        @if($item->instructions)<div class="muted">{{ $item->instructions }}</div>@endif</div>
                @endforeach
                @if($rx->delivery_status)<div class="muted">Delivery: {{ ucfirst(str_replace('_', ' ', $rx->delivery_status)) }}</div>@endif
            </div>
        @empty
            <p class="muted">No prescriptions yet.</p>
        @endforelse
    </div>

    <div class="card">
        <h2>Lab tests</h2>
        @forelse($p->labTests as $lab)
            <div class="row" style="border-top:1px solid #eee;padding-top:8px">
                <strong>{{ $lab->test_type }}</strong> <span class="pill wait">{{ ucfirst($lab->status ?? 'pending') }}</span>
                @if($lab->results)<div>{{ is_string($lab->results) ? $lab->results : json_encode($lab->results) }}</div>@endif
            </div>
        @empty
            <p class="muted">No lab tests yet.</p>
        @endforelse
    </div>

    <div class="card">
        <h2>Appointments</h2>
        @forelse($p->appointments as $a)
            <div class="row" style="border-top:1px solid #eee;padding-top:8px">
                {{ $a->appointment_time->format('D j M Y, g:i A') }} &middot; Dr. {{ $a->doctor->name ?? '' }}
                <span class="pill wait">{{ ucfirst(str_replace('_', ' ', $a->status)) }}</span>
            </div>
        @empty
            <p class="muted">No appointments yet.</p>
        @endforelse
    </div>
@endforeach
@endsection
