@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Main Content -->
        <div class="col-md-12 px-md-4 py-4">
            <div class="d-flex justify-content-between mb-4">
                <h2>
                    <i class="fas fa-baby"></i> Maternal Documents for {{ $patient->name }}
                </h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
                    <i class="fas fa-upload me-2"></i> Upload Document
                </button>
            </div>

            <!-- Document Categories -->
            <div class="row">
                @foreach(['ultrasound report', 'blood test results', 'urine analysis', 'prenatal screening', 'unclassified document'] as $type)
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                {{ ucfirst($type) }} 
                                <span class="badge bg-light text-dark float-end">
                                    {{ $groupedDocuments->has($type) ? count($groupedDocuments[$type]) : 0 }}
                                </span>
                            </h5>
                        </div>
                        <div class="card-body">
                            @if($groupedDocuments->has($type))
                                <div class="list-group">
                                    @foreach($groupedDocuments[$type] as $document)
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between">
                                            <a href="{{ $document->s3_path }}" target="_blank" class="text-decoration-none">
                                                <i class="fas fa-file-pdf me-2"></i> {{ $document->original_filename }}
                                            </a>
                                            <small class="text-muted">{{ $document->created_at->format('M d, Y') }}</small>
                                        </div>
                                        @if($type != 'unclassified document')
                                        <div class="confidence-badge mt-1">
                                            <span class="badge bg-{{ $document->confidence > 0.7 ? 'success' : ($document->confidence > 0.4 ? 'warning' : 'danger') }}">
                                                Confidence: {{ number_format($document->confidence * 100, 1) }}%
                                            </span>
                                        </div>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted">No documents in this category</p>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Upload Document Modal -->
<div class="modal fade" id="uploadDocumentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Maternal Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="document-upload-form" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Document File</label>
                        <input type="file" name="document" id="document-file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                        <small class="text-muted">Supported formats: PDF, JPG, PNG (Max 10MB)</small>
                        <div class="invalid-feedback" id="file-error"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Document Type (Optional)</label>
                        <select name="document_type" class="form-select">
                            <option value="">Let AI Classify</option>
                            <option value="ultrasound report">Ultrasound Report</option>
                            <option value="blood test results">Blood Test Results</option>
                            <option value="urine analysis">Urine Analysis</option>
                            <option value="prenatal screening">Prenatal Screening</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="upload-button">
                        <span id="upload-text"><i class="fas fa-upload me-2"></i> Upload & Classify</span>
                        <span id="upload-spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle document upload and classification
    document.getElementById('document-upload-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const modal = bootstrap.Modal.getInstance(document.getElementById('uploadDocumentModal'));
        
        const uploadButton = document.getElementById('upload-button');
        const uploadText = document.getElementById('upload-text');
        const uploadSpinner = document.getElementById('upload-spinner');
        
        // Show loading state
        uploadText.innerHTML = 'Processing...';
        uploadSpinner.classList.remove('d-none');
        uploadButton.disabled = true;
        
        try {
            const response = await fetch('{{ route("api.maternal-documents.store") }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            });
            
            const data = await response.json();
            
            if(data.success) {
                // Close modal and refresh page
                modal.hide();
                window.location.reload();
            } else {
                throw new Error(data.message || 'Upload failed');
            }
        } catch (error) {
            console.error('Upload error:', error);
            alert('Error: ' + error.message);
        } finally {
            // Reset button state
            uploadText.innerHTML = '<i class="fas fa-upload me-2"></i> Upload & Classify';
            uploadSpinner.classList.add('d-none');
            uploadButton.disabled = false;
        }
    });

    // File validation
    document.getElementById('document-file').addEventListener('change', function() {
        const file = this.files[0];
        const maxSize = 10 * 1024 * 1024; // 10MB
        const validTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        
        if (file.size > maxSize) {
            this.classList.add('is-invalid');
            document.getElementById('file-error').textContent = 'File too large (max 10MB)';
            this.value = '';
        } else if (!validTypes.includes(file.type)) {
            this.classList.add('is-invalid');
            document.getElementById('file-error').textContent = 'Invalid file type (PDF, JPG, PNG only)';
            this.value = '';
        } else {
            this.classList.remove('is-invalid');
            document.getElementById('file-error').textContent = '';
        }
    });
    
    // Reset form when modal is closed
    document.getElementById('uploadDocumentModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('document-upload-form').reset();
        document.getElementById('document-file').classList.remove('is-invalid');
        document.getElementById('file-error').textContent = '';
    });
});
</script>
@endsection