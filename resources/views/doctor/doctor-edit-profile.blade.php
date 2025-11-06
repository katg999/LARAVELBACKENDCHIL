@extends('layouts.base')

@section('content')
<div class="container-fluid px-3 py-2">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h2 mb-1">Edit Profile</h1>
            <p class="text-muted mb-3">Update your profile information</p>
        </div>
    </div>

    <!-- Edit Profile Form -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-account-edit me-2"></i>Profile Information
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('doctor.update-profile') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="{{ $doctor->name }}" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="{{ $doctor->email }}" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="contact" class="form-label">Contact Number</label>
                                <input type="text" class="form-control" id="contact" name="contact" value="{{ $doctor->contact }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="specialization" class="form-label">Specialization</label>
                                <input type="text" class="form-control" id="specialization" name="specialization" value="{{ $doctor->specialization }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="meeting_slug" class="form-label">Meeting Room Slug</label>
                            <input type="text" class="form-control" id="meeting_slug" name="meeting_slug" value="{{ $doctor->meeting_slug }}" placeholder="e.g., dr-john-doe">
                            <div class="form-text">This will be used for your Jitsi Meet room: https://meet.jit.si/{slug}</div>
                        </div>

                        <div class="mb-3">
                            <label for="profile_image" class="form-label">Profile Image</label>
                            <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                            <div class="form-text">Upload a new profile image (JPEG, PNG, JPG, GIF, SVG - Max 2MB)</div>
                            @if($doctor->file_url)
                                <div class="mt-2">
                                    <img src="{{ $doctor->file_url }}" alt="Current profile image" class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover;">
                                    <small class="text-muted ms-2">Current image</small>
                                </div>
                            @endif
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-content-save me-2"></i>Save Changes
                            </button>
                            <a href="{{ route('doctor.profile') }}" class="btn btn-outline-secondary">
                                <i class="mdi mdi-arrow-left me-2"></i>Back to Profile
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Profile Preview -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-eye me-2"></i>Profile Preview
                    </h5>
                </div>
                <div class="card-body text-center">
                    <img src="{{ $doctor->file_url ?? asset('images/doctor.png') }}"
                         alt="Profile preview"
                         class="rounded-circle mb-3 border border-white border-3"
                         style="width: 100px; height: 100px; object-fit: cover;">
                    <h5>{{ $doctor->display_name }}</h5>
                    <p class="text-muted mb-1">
                        <i class="mdi mdi-doctor me-1"></i>{{ $doctor->specialization ?? 'General Practitioner' }}
                    </p>
                    <small class="text-muted">{{ $doctor->email }}</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('profile_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/svg+xml'];
        if (!allowedTypes.includes(file.type)) {
            alert('Please select a valid image file (JPEG, PNG, JPG, GIF, SVG)');
            e.target.value = '';
            return;
        }

        // Validate file size (2MB max)
        const maxSize = 2 * 1024 * 1024; // 2MB in bytes
        if (file.size > maxSize) {
            alert('File size must be less than 2MB');
            e.target.value = '';
            return;
        }

        // Preview the image
        const reader = new FileReader();
        reader.onload = function(e) {
            // Update the preview image
            const previewImg = document.querySelector('.card-body.text-center img');
            if (previewImg) {
                previewImg.src = e.target.result;
            }
        };
        reader.readAsDataURL(file);
    }
});
</script>
@endsection