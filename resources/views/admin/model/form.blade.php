@extends('layouts.base')

@section('content')
<div class="container py-4">
    <h2>{{ $item ? 'Edit' : 'Create' }} {{ ucfirst(str_replace('-', ' ', $modelKey)) }}</h2>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        // Map model keys to actual class names
        $modelMap = [
            'schools' => 'School',
            'doctors' => 'Doctor',
            'patients' => 'Patient',
            'appointments' => 'Appointment',
            'payments' => 'Payment',
            'health-facilities' => 'HealthFacility',
            'durations' => 'Duration',
            'users' => 'User',
        ];
        
        $className = $modelMap[$modelKey] ?? str_replace(' ', '', ucwords(str_replace('-', ' ', $modelKey)));
        $modelClass = '\\App\\Models\\' . $className;
        $fields = (new $modelClass)->getFillable() ?: array_keys((new $modelClass)->getAttributes());
    @endphp

    @php
        // Use specific routes for schools if they exist
        if ($modelKey === 'schools') {
            $storeRoute = 'admin.schools.store';
            $updateRoute = 'admin.schools.update';
            $indexRoute = 'admin.schools.index';
        } else {
            $storeRoute = 'admin.model.store';
            $updateRoute = 'admin.model.update';
            $indexRoute = 'admin.model.index';
        }
    @endphp

    <form method="POST" action="{{ $item ? route($updateRoute, $item->id) : route($storeRoute, $modelKey) }}" @if(in_array($modelKey, ['doctors', 'schools'])) enctype="multipart/form-data" @endif>
        @csrf
        @if($item)
            @method('PUT')
        @endif

        @if($modelKey === 'doctors')
            <div class="row g-2">
                <div class="col-md-6 mb-2">
                    <label class="form-label">Name</label>
                    <input name="name" class="form-control" required value="{{ old('name', $item->name ?? '') }}">
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">Email</label>
                    <input name="email" type="email" class="form-control" value="{{ old('email', $item->email ?? '') }}">
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">Specialization</label>
                    <input name="specialization" class="form-control" value="{{ old('specialization', $item->specialization ?? '') }}">
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">Contact</label>
                    <input name="contact" class="form-control" value="{{ old('contact', $item->contact ?? '') }}">
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">School</label>
                    @php $schools = \App\Models\School::pluck('name','id'); @endphp
                    <select name="school_id" class="form-select form-control">
                        <option value="">-- none --</option>
                        @foreach($schools as $id => $label)
                            <option value="{{ $id }}" @if(old('school_id', $item->school_id ?? '') == $id) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">Health Facility</label>
                    @php $hfs = \App\Models\HealthFacility::pluck('name','id'); @endphp
                    <select name="health_facility_id" class="form-select form-control">
                        <option value="">-- none --</option>
                        @foreach($hfs as $id => $label)
                            <option value="{{ $id }}" @if(old('health_facility_id', $item->health_facility_id ?? '') == $id) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 mb-2">
                    <label class="form-label">Profile Image</label>
                    @if(!empty($item->file_url))
                        <div class="mb-2">
                            <img src="{{ $item->file_url }}" alt="profile" style="max-height:80px;">
                        </div>
                    @endif
                    <input name="file_url" type="file" accept="image/*" class="form-control">
                </div>

                <div class="col-md-6 mb-2">
                    <label class="form-label">Meeting Slug</label>
                    <input name="meeting_slug" class="form-control" value="{{ old('meeting_slug', $item->meeting_slug ?? ($generatedSlug ?? '')) }}" readonly>

                    <label class="form-label small mt-2">Meeting URL</label>
                    <div class="input-group">
                        <input id="meeting-url" type="text" class="form-control" value="{{ 'https://meet.jit.si/' . (old('meeting_slug', $item->meeting_slug ?? ($generatedSlug ?? ''))) }}" readonly>
                        <button type="button" id="copy-meeting-url" class="btn btn-outline-secondary">Copy</button>
                    </div>
                    <div id="copy-feedback" class="small text-success mt-1" style="display:none">Copied to clipboard</div>
                </div>
            </div>
        @elseif($modelKey === 'schools')
            <div class="row g-2">
                <div class="col-md-6 mb-2">
                    <label class="form-label">Name</label>
                    <input name="name" class="form-control" required value="{{ old('name', $item->name ?? '') }}">
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">Email</label>
                    <input name="email" type="email" class="form-control" value="{{ old('email', $item->email ?? '') }}">
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">Contact</label>
                    <input name="contact" class="form-control" value="{{ old('contact', $item->contact ?? '') }}">
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">Address</label>
                    <input name="address" class="form-control" value="{{ old('address', $item->address ?? '') }}">
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">School Logo/Image</label>
                    @if(!empty($item->file_url))
                        <div class="mb-2">
                            <img src="{{ $item->file_url }}" alt="school logo" style="max-height:80px;">
                        </div>
                    @endif
                    <input name="file_url" type="file" accept="image/*" class="form-control">
                </div>
            </div>
        @else

            <div class="row g-2">
                @foreach($fields as $field)
                    <div class="col-md-6 mb-2">
                        <label class="form-label">{{ ucfirst(str_replace('_',' ', $field)) }}</label>
                        <input name="{{ $field }}" class="form-control" value="{{ old($field, $item->$field ?? '') }}">
                    </div>
                @endforeach
            </div>

        @endif

        <div class="mt-3">
            <button class="btn btn-primary">Save</button>
            <a href="{{ route($indexRoute, $modelKey) }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    const copyBtn = document.getElementById('copy-meeting-url');
    if (copyBtn) {
        copyBtn.addEventListener('click', function(){
            const target = document.getElementById('meeting-url');
            if (!target) return;
            target.select();
            target.setSelectionRange(0, 99999);
            try {
                document.execCommand('copy');
            } catch (e) {
                navigator.clipboard && navigator.clipboard.writeText && navigator.clipboard.writeText(target.value);
            }
            const fb = document.getElementById('copy-feedback');
            if (fb) {
                fb.style.display = 'block';
                setTimeout(() => fb.style.display = 'none', 2000);
            }
        });
    }
});
</script>
@endpush
