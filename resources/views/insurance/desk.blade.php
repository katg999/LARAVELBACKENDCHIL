@extends('layouts.base')

@section('content')
<div class="container-fluid" id="desk">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h3 class="m-0">Insurance desk</h3>
            <small class="text-muted">Confirm cover, price medicine, send claims and track the insurer's replies. You record the insurer's answers. Easemed never approves or pays a claim.</small>
        </div>
    </div>
    <div id="desk-msg" class="alert d-none" role="status"></div>

    {{-- 0 --}}
    <div class="card mb-4"><div class="card-body" id="book-box">
        <h5 class="card-title">Book an insured visit</h5>
        <p class="text-muted mb-2">For patients whose insurance is confirmed. The booking then waits in step 2 for the insurer's answer.</p>
        @if($bookable->isEmpty())
            <p class="text-muted mb-0">No patients with confirmed insurance yet. Confirm what patients send in step 1.</p>
        @else
        <div class="form-row" data-row>
            <div class="col-md-4 mb-2"><select class="form-control form-control-sm" data-field="patient_id" id="book-patient">
                @foreach($bookable as $bp)@foreach($bp->policies as $pol)<option value="{{ $bp->id }}" data-policy="{{ $pol->id }}">{{ $bp->name }} · {{ $pol->insurer->name }}</option>@endforeach @endforeach
            </select></div>
            <div class="col-md-3 mb-2"><select class="form-control form-control-sm" data-field="doctor_id">@foreach($doctors as $d)<option value="{{ $d->id }}">Dr. {{ $d->name }}</option>@endforeach</select></div>
            <div class="col-md-2 mb-2"><select class="form-control form-control-sm" data-field="duration_id">@foreach($durations as $du)<option value="{{ $du->id }}">{{ $du->minutes }} min</option>@endforeach</select></div>
            <div class="col-md-3 mb-2"><input type="datetime-local" class="form-control form-control-sm" data-field="appointment_time" value="{{ now()->addDay()->setTime(10, 0)->format('Y-m-d\TH:i') }}"></div>
            <div class="col-md-3 mb-2"><input class="form-control form-control-sm" data-field="visit_code" placeholder="Insurer visit code"></div>
            <div class="col-md-6 mb-2"><input class="form-control form-control-sm" data-field="reason" placeholder="Reason for the visit" value="Consultation"></div>
            <div class="col-md-3 mb-2 text-md-right"><button class="btn btn-primary btn-sm" data-act="book">Book insured visit</button></div>
        </div>
        @endif
    </div></div>

    {{-- 1 --}}
    <div class="card mb-4"><div class="card-body">
        <h5 class="card-title">1. Insurance sent by patients <span class="badge badge-secondary">{{ $pendingPolicies->count() }}</span></h5>
        @forelse($pendingPolicies as $p)
            <div class="row align-items-center border-top py-2" data-row>
                <div class="col-md-4"><strong>{{ $p->patient->name }}</strong><br><small>{{ $p->insurer->name }} &middot; member {{ $p->member_number }}@if($p->scheme_name) &middot; {{ $p->scheme_name }}@endif</small>
                    @if($p->card_image_path)<br><a href="{{ route('insurance.policies.card', $p) }}" target="_blank" rel="noopener"><small>View card photo</small></a>@endif</div>
                <div class="col-md-4"><input class="form-control form-control-sm" data-field="note" placeholder="Reason if not confirmed"></div>
                <div class="col-md-4 text-md-right mt-2 mt-md-0">
                    <button class="btn btn-success btn-sm" data-act="{{ route('insurance.policies.review', $p) }}" data-body='{"result":"approved"}'>Confirm</button>
                    <button class="btn btn-outline-danger btn-sm" data-act="{{ route('insurance.policies.review', $p) }}" data-body='{"result":"rejected"}'>Not confirmed</button>
                </div>
            </div>
        @empty <p class="text-muted mb-0">Nothing waiting. Patients send their insurance from the link in their text message.</p> @endforelse
    </div></div>

    {{-- 2 --}}
    <div class="card mb-4"><div class="card-body">
        <h5 class="card-title">2. Insured visits to verify <span class="badge badge-secondary">{{ $toVerify->count() }}</span></h5>
        @forelse($toVerify as $a)
            <div class="row align-items-center border-top py-2" data-row>
                <div class="col-md-5"><strong>{{ $a->patient->name }}</strong> with Dr. {{ $a->doctor->name }}<br><small>{{ $a->appointment_time->format('D j M, g:i A') }} &middot; {{ $a->memberPolicy?->insurer?->name }} &middot; visit code {{ $a->visit_code ?: 'none' }}</small></div>
                <div class="col-md-3"><input class="form-control form-control-sm" data-field="note" placeholder="Note (needed if declined)"></div>
                <div class="col-md-4 text-md-right mt-2 mt-md-0">
                    <button class="btn btn-success btn-sm" data-act="{{ route('insurance.verify', $a) }}" data-body='{"result":"verified"}'>Cover confirmed</button>
                    <button class="btn btn-outline-danger btn-sm" data-act="{{ route('insurance.verify', $a) }}" data-body='{"result":"rejected"}'>Declined</button>
                </div>
            </div>
        @empty <p class="text-muted mb-0">No insured bookings waiting. A booking with a confirmed policy waits here for the insurer's answer.</p> @endforelse
    </div></div>

    {{-- 3 --}}
    <div class="card mb-4"><div class="card-body">
        <h5 class="card-title">3. Medicine: price it, then record the insurer's answer</h5>
        <h6 class="mt-3">To price <span class="badge badge-secondary">{{ $toPrice->count() }}</span></h6>
        @forelse($toPrice as $rx)
            <div class="border-top py-2" data-row data-price="{{ route('care.pharmacy.price', $rx) }}">
                <strong>{{ $rx->patient->name }}</strong> <small class="text-muted">prescription #{{ $rx->id }}</small>
                @foreach($rx->items as $item)
                    <div class="form-row align-items-center mt-1">
                        <div class="col-md-6">{{ $item->name }} {{ $item->dosage }} &times; {{ $item->quantity ?? 1 }}</div>
                        <div class="col-md-3"><input type="number" min="0" step="1" class="form-control form-control-sm" data-item="{{ $item->id }}" placeholder="Price each (UGX)"></div>
                    </div>
                @endforeach
                <div class="form-row align-items-center mt-2">
                    <div class="col-md-3">
                        <select class="form-control form-control-sm" data-field="coverage">
                            <option value="self_pay">Patient pays all</option>
                            @if($rx->patient->policies->isNotEmpty())<option value="insurance">Use insurance</option>@endif
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-control form-control-sm" data-field="member_policy_id">
                            @foreach($rx->patient->policies as $pol)<option value="{{ $pol->id }}">{{ $pol->insurer->name }} · {{ $pol->member_number }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><button class="btn btn-primary btn-sm" data-act="price">Price it</button></div>
                </div>
            </div>
        @empty <p class="text-muted">No prescriptions waiting for a price.</p> @endforelse

        <h6 class="mt-4">Waiting for the insurer's answer <span class="badge badge-secondary">{{ $awaitingInsurer->count() }}</span></h6>
        @forelse($awaitingInsurer as $rx)
            <div class="row align-items-center border-top py-2" data-row>
                <div class="col-md-4"><strong>{{ $rx->patient->name }}</strong> <small class="text-muted">#{{ $rx->id }}</small><br><small>{{ $rx->memberPolicy?->insurer?->name }} &middot; total UGX {{ number_format((float) $rx->total_amount) }}</small></div>
                <div class="col-md-2"><input type="number" min="0" class="form-control form-control-sm" data-field="insurer_amount" placeholder="Insurer pays"></div>
                <div class="col-md-2"><input class="form-control form-control-sm" data-field="reference" placeholder="Insurer ref"></div>
                <div class="col-md-4 text-md-right mt-2 mt-md-0">
                    <button class="btn btn-success btn-sm" data-act="{{ route('care.pharmacy.insurer', $rx) }}" data-body='{"result":"approved"}'>Insurer approved</button>
                    <button class="btn btn-outline-danger btn-sm" data-act="{{ route('care.pharmacy.insurer', $rx) }}" data-body='{"result":"declined"}'>Declined</button>
                </div>
            </div>
        @empty <p class="text-muted mb-0">No medicine is waiting for an insurer.</p> @endforelse
    </div></div>

    {{-- 4 --}}
    <div class="card mb-4"><div class="card-body">
        <h5 class="card-title">4. Claims ready to send</h5>
        <p class="text-muted mb-2">Only visits that were attended, and medicine whose patient share is paid.</p>
        <div class="mb-2">
            <a class="btn btn-outline-primary btn-sm" href="{{ route('insurance.export') }}">Download visit claims (CSV)</a>
            <a class="btn btn-outline-primary btn-sm" href="{{ route('care.pharmacy.claims') }}">Download medicine claims (CSV)</a>
        </div>
        @foreach($readyVisits as $a)
            <div class="row align-items-center border-top py-2"><div class="col-md-8">Visit &middot; <strong>{{ $a->patient->name }}</strong> &middot; {{ $a->memberPolicy?->insurer?->name }} &middot; {{ $a->appointment_time->format('j M') }}</div>
            <div class="col-md-4 text-md-right"><button class="btn btn-primary btn-sm" data-act="{{ route('insurance.submitted') }}" data-body='{"appointment_ids":[{{ $a->id }}]}'>Mark sent to insurer</button></div></div>
        @endforeach
        @foreach($readyMedicine as $rx)
            <div class="row align-items-center border-top py-2"><div class="col-md-8">Medicine &middot; <strong>{{ $rx->patient->name }}</strong> &middot; {{ $rx->memberPolicy?->insurer?->name }} &middot; insurer share UGX {{ number_format((float) $rx->insurer_amount) }}</div>
            <div class="col-md-4 text-md-right"><button class="btn btn-primary btn-sm" data-act="{{ route('care.pharmacy.submitted') }}" data-body='{"prescription_ids":[{{ $rx->id }}]}'>Mark sent to insurer</button></div></div>
        @endforeach
        @if($readyVisits->isEmpty() && $readyMedicine->isEmpty())<p class="text-muted mb-0">Nothing ready right now.</p>@endif
    </div></div>

    {{-- 5 --}}
    <div class="card mb-4"><div class="card-body">
        <h5 class="card-title">5. Claim tracker <span class="badge badge-secondary">{{ $tracked->count() }}</span></h5>
        <p class="text-muted">UGX {{ number_format($tracked->filter(fn ($r) => in_array($r['status'], $open, true))->sum('expected')) }} still waiting on insurers. Record what the insurer tells you.</p>
        @forelse($tracked as $r)
            <div class="border-top py-2" data-row>
                <div class="row align-items-center">
                    <div class="col-md-5"><strong>{{ ucfirst($r['type']) }}</strong> &middot; {{ $r['patient'] }} &middot; {{ $r['insurer'] }}<br>
                        <small class="text-muted">expected UGX {{ number_format($r['expected']) }}@if($r['paid'] > 0) &middot; paid UGX {{ number_format($r['paid']) }}@endif @if($r['ref']) &middot; ref {{ $r['ref'] }}@endif</small>
                        @if($r['note'])<br><small>Note: {{ $r['note'] }}</small>@endif</div>
                    <div class="col-md-2">
                        <span class="badge badge-{{ ['paid' => 'success', 'accepted' => 'info', 'submitted' => 'secondary', 'queried' => 'warning', 'rejected' => 'danger'][$r['status']] ?? 'secondary' }}">{{ ucfirst($r['status']) }}</span>
                        @if($r['days'] !== null)<br><small class="{{ $r['days'] >= 30 ? 'text-danger font-weight-bold' : 'text-muted' }}">{{ $r['days'] }} days waiting{{ $r['days'] >= 30 ? ' (overdue)' : '' }}</small>@endif
                    </div>
                    <div class="col-md-5">
                        @if($r['status'] !== 'paid')
                        <div class="form-row">
                            <div class="col-5"><select class="form-control form-control-sm" data-field="status">
                                @foreach(\App\Services\ClaimLifecycle::FLOW[$r['status']] as $to)<option value="{{ $to }}">{{ ucfirst($to) === 'Submitted' ? 'Corrected, sent again' : ucfirst($to) }}</option>@endforeach
                            </select></div>
                            <div class="col-4"><input class="form-control form-control-sm" data-field="note" placeholder="Reason / note"></div>
                            <div class="col-3"><input type="number" min="0" class="form-control form-control-sm" data-field="amount_paid" placeholder="Paid"></div>
                        </div>
                        <div class="form-row mt-1">
                            <div class="col-6"><input class="form-control form-control-sm" data-field="reference" placeholder="Insurer claim ref"></div>
                            <div class="col-6 text-right"><button class="btn btn-primary btn-sm" data-act="{{ $r['url'] }}" data-body='{}'>Save reply</button></div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty <p class="text-muted mb-0">No claims sent yet.</p> @endforelse
    </div></div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var token = document.querySelector('meta[name="csrf-token"]').content;
    var msg = document.getElementById('desk-msg');
    function show(text, ok) { msg.className = 'alert ' + (ok ? 'alert-success' : 'alert-danger'); msg.textContent = text; }
    function fields(row) {
        var o = {};
        row.querySelectorAll('[data-field]').forEach(function (el) { if (el.value !== '') o[el.dataset.field] = el.value; });
        return o;
    }
    document.getElementById('desk').addEventListener('click', function (e) {
        var btn = e.target.closest('[data-act]');
        if (!btn) return;
        var row = btn.closest('[data-row]') || document;
        var url = btn.dataset.act, body = JSON.parse(btn.dataset.body || '{}');
        Object.assign(body, fields(row));
        if (url === 'book') {
            url = '{{ route('appointments.store') }}';
            var opt = document.getElementById('book-patient').selectedOptions[0];
            body.member_policy_id = opt.dataset.policy;
            @if($facilityId) body.health_facility_id = {{ $facilityId }}; @endif
            body.appointment_time = String(body.appointment_time || '').replace('T', ' ');
        }
        if (url === 'price') {
            url = row.dataset.price;
            body.items = [];
            row.querySelectorAll('[data-item]').forEach(function (el) { body.items.push({ id: parseInt(el.dataset.item, 10), unit_price: el.value === '' ? null : el.value }); });
            if (body.coverage !== 'insurance') delete body.member_policy_id;
        }
        btn.disabled = true;
        fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify(body) })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
            .then(function (res) {
                if (res.ok && res.j.success !== false) { show('Saved.', true); setTimeout(function () { location.reload(); }, 700); }
                else { btn.disabled = false; show(res.j.message || (res.j.errors ? Object.values(res.j.errors)[0][0] : 'That did not work.'), false); }
            })
            .catch(function () { btn.disabled = false; show('Could not reach the server.', false); });
    });
})();
</script>
@endpush
