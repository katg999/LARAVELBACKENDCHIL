@extends('layouts.base')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Create New Patient</h1>

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('patients.general.store') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Patient Type Selection -->
                <div class="space-y-3">
                    <label class="block text-sm font-medium text-gray-700">Patient Type</label>
                    <div class="space-y-2">
                        <div class="flex items-center">
                            <input type="radio" id="new_patient" name="patient_type" value="new"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                   {{ old('patient_type', 'new') === 'new' ? 'checked' : '' }}>
                            <label for="new_patient" class="ml-2 block text-sm text-gray-900">
                                Create New Patient
                            </label>
                        </div>
                        <div class="flex items-center">
                            <input type="radio" id="existing_patient" name="patient_type" value="existing"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                   {{ old('patient_type') === 'existing' ? 'checked' : '' }}>
                            <label for="existing_patient" class="ml-2 block text-sm text-gray-900">
                                Associate Existing Patient by Patient ID
                            </label>
                        </div>
                    </div>
                </div>

                <!-- New Patient Fields -->
                <div id="new_patient_fields" class="space-y-4 {{ old('patient_type', 'new') === 'existing' ? 'hidden' : '' }}">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label for="gender" class="block text-sm font-medium text-gray-700">Gender</label>
                        <select name="gender" id="gender"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select Gender</option>
                            <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other" {{ old('gender') === 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>

                    <div>
                        <label for="birth_date" class="block text-sm font-medium text-gray-700">Date of Birth</label>
                        <input type="date" name="birth_date" id="birth_date" value="{{ old('birth_date') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label for="contact_number" class="block text-sm font-medium text-gray-700">Contact Number</label>
                        <input type="text" name="contact_number" id="contact_number" value="{{ old('contact_number') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                               placeholder="+256XXXXXXXXX">
                    </div>

                    <div>
                        <label for="medical_history" class="block text-sm font-medium text-gray-700">Medical History (Optional)</label>
                        <textarea name="medical_history" id="medical_history" rows="3"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Any relevant medical history...">{{ old('medical_history') }}</textarea>
                    </div>
                </div>

                <!-- Existing Patient Fields -->
                <div id="existing_patient_fields" class="space-y-4 {{ old('patient_type', 'new') === 'new' ? 'hidden' : '' }}">
                    <div>
                        <label for="patient_id" class="block text-sm font-medium text-gray-700">Patient ID</label>
                        <input type="text" name="patient_id" id="patient_id" value="{{ old('patient_id') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                               placeholder="P123456">
                        <p class="mt-1 text-sm text-gray-500">Enter the Patient ID to associate an existing patient record.</p>
                    </div>
                </div>

                <!-- Institution Association (Optional) -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Optional: Associate with Institution</h3>
                    <p class="text-sm text-gray-600 mb-4">You can associate this patient with a school or health facility now, or do it later.</p>

                    <div class="space-y-4">
                        <div>
                            <label for="school_id" class="block text-sm font-medium text-gray-700">School (Optional)</label>
                            <select name="school_id" id="school_id"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select School (Optional)</option>
                                @foreach($schools ?? [] as $school)
                                    <option value="{{ $school->id }}" {{ old('school_id') == $school->id ? 'selected' : '' }}>
                                        {{ $school->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="health_facility_id" class="block text-sm font-medium text-gray-700">Health Facility (Optional)</label>
                            <select name="health_facility_id" id="health_facility_id"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Health Facility (Optional)</option>
                                @foreach($healthFacilities ?? [] as $facility)
                                    <option value="{{ $facility->id }}" {{ old('health_facility_id') == $facility->id ? 'selected' : '' }}>
                                        {{ $facility->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Additional fields for school students -->
                        <div id="school_fields" class="space-y-4 {{ old('school_id') ? '' : 'hidden' }}">
                            <div>
                                <label for="grade" class="block text-sm font-medium text-gray-700">Grade (for School Students)</label>
                                <input type="text" name="grade" id="grade" value="{{ old('grade') }}"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Grade 5">
                            </div>

                            <div>
                                <label for="parent_contact" class="block text-sm font-medium text-gray-700">Parent Contact (for School Students)</label>
                                <input type="text" name="parent_contact" id="parent_contact" value="{{ old('parent_contact') }}"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="+256XXXXXXXXX">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-3">
                    <a href="{{ route('home') }}"
                       class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md text-sm font-medium">
                        Cancel
                    </a>
                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        Create Patient
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const patientTypeRadios = document.querySelectorAll('input[name="patient_type"]');
    const newPatientFields = document.getElementById('new_patient_fields');
    const existingPatientFields = document.getElementById('existing_patient_fields');
    const schoolSelect = document.getElementById('school_id');
    const schoolFields = document.getElementById('school_fields');

    function togglePatientFields() {
        const selectedType = document.querySelector('input[name="patient_type"]:checked').value;

        if (selectedType === 'new') {
            newPatientFields.classList.remove('hidden');
            existingPatientFields.classList.add('hidden');
        } else {
            newPatientFields.classList.add('hidden');
            existingPatientFields.classList.remove('hidden');
        }
    }

    function toggleSchoolFields() {
        if (schoolSelect.value) {
            schoolFields.classList.remove('hidden');
        } else {
            schoolFields.classList.add('hidden');
        }
    }

    patientTypeRadios.forEach(radio => {
        radio.addEventListener('change', togglePatientFields);
    });

    schoolSelect.addEventListener('change', toggleSchoolFields);

    // Initialize on page load
    togglePatientFields();
    toggleSchoolFields();
});
</script>
@endsection