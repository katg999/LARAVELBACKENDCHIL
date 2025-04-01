<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schools Dashboard (API)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Schools Dashboard (API)</h1>
        
        @if(isset($error))
            <div class="alert alert-danger">
                {{ $error }}
            </div>
        @endif

        @if(!empty($schools))
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>File URL</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($schools as $school)
                        <tr>
                            <td>{{ $school['id'] ?? 'N/A' }}</td>
                            <td>{{ $school['name'] ?? 'N/A' }}</td>
                            <td>{{ $school['email'] ?? 'N/A' }}</td>
                            <td>{{ $school['contact'] ?? 'N/A' }}</td>
                            <td>
                                @if(!empty($school['file_url']))
                                    <a href="{{ $school['file_url'] }}" target="_blank">View File</a>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>{{ $school['created_at'] ?? 'N/A' }}</td>
                            <td>
                                <a href="#" class="btn btn-sm btn-success action-btn" title="Approve">
                                    <i class="fas fa-check"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="alert alert-info">
                No school data available.
            </div>
        @endif
    </div>

    <script>
        // This will be enhanced with Filament and proper functionality later
        document.querySelectorAll('.action-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                alert('Action will be implemented with Filament integration');
            });
        });
    </script>
</body>
</html>