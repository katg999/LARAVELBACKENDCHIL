@extends('layouts.base')

@section('content')
<div class="container-fluid">
    <h3 class="mb-4">Add Patient</h3>
    
    @if(session('success'))
        <div class="alert alert-success mb-3" id="successAlert">
            {{ session('success') }}
        </div>
        <script>
            setTimeout(function() {
                var alert = document.getElementById('successAlert');
                if (alert) {
                    alert.style.display = 'none';
                }
            }, 3000);
        </script>
    @endif

    @if(session('error'))
        <div class="alert alert-danger mb-3" id="errorAlert">
            {{ session('error') }}
        </div>
        <script>
            setTimeout(function() {
                var alert = document.getElementById('errorAlert');
                if (alert) {
                    alert.style.display = 'none';
                }
            }, 3000);
        </script>
    @endif

    @if($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('patients.create') }}">
                @csrf
                
                <!-- Patient Type Selection -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Patient Type:</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="patient_type" id="new_patient" value="new" checked>
                        <label class="form-check-label" for="new_patient">
                            New Patient
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="patient_type" id="existing_patient" value="existing">
                        <label class="form-check-label" for="existing_patient">
                            Existing Patient (Enter Patient ID)
                        </label>
                    </div>
                </div>

                <!-- Existing Patient Section -->
                <div id="existingPatientSection" style="display: none;">
                    <div class="mb-3">
                        <label class="form-label">Patient ID:</label>
                        <input type="text" class="form-control" id="patient_id" name="patient_id" placeholder="e.g., P000001">
                        <small class="form-text text-muted">Enter the existing patient's ID to associate them with this health facility.</small>
                    </div>
                </div>

                <!-- New Patient Section -->
                <div id="newPatientSection">
                    <input type="hidden" name="health_facility_id" value="{{ $healthFacility->id }}">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select form-control">
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Birth Date</label>
                            <input type="date" name="birth_date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Medical History (Optional)</label>
                            <textarea name="medical_history" rows="3" class="form-control"></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <a href="{{ route('health-facility.patients') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Toggle patient type sections
    document.querySelectorAll('input[name="patient_type"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            var newPatientSection = document.getElementById('newPatientSection');
            var existingPatientSection = document.getElementById('existingPatientSection');
            var newPatientFields = newPatientSection.querySelectorAll('input:not([type="hidden"]), select');
            var existingPatientFields = existingPatientSection.querySelectorAll('input');

            if (this.value === 'existing') {
                newPatientSection.style.display = 'none';
                existingPatientSection.style.display = 'block';
                
                // Remove required attribute from new patient fields
                newPatientFields.forEach(function(field) {
                    field.removeAttribute('required');
                });
                
                // Add required to patient_id field
                document.getElementById('patient_id').setAttribute('required', 'required');
            } else {
                newPatientSection.style.display = 'block';
                existingPatientSection.style.display = 'none';
                
                // Add required attribute to new patient fields
                newPatientFields.forEach(function(field) {
                    if (field.name !== 'contact_number' && field.name !== 'medical_history') {
                        field.setAttribute('required', 'required');
                    }
                });
                
                // Remove required from patient_id field
                document.getElementById('patient_id').removeAttribute('required');
            }
        });
    });
</script>

@endsection