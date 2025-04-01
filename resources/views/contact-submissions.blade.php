<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Form Submissions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Contact Form Submissions</h1>
        
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Message</th>
                    <th>Policy Accepted</th>
                    <th>Submitted At</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($submissions as $submission)
                <tr>
                    <td>{{ $submission->id }}</td>
                    <td>{{ $submission->first_name }}</td>
                    <td>{{ $submission->last_name }}</td>
                    <td>{{ $submission->email }}</td>
                    <td>{{ $submission->phone ?? 'N/A' }}</td>
                    <td>{{ Str::limit($submission->message, 50) }}</td>
                    <td>
                        @if($submission->accept_policy)
                            <span class="badge bg-success">Yes</span>
                        @else
                            <span class="badge bg-danger">No</span>
                        @endif
                    </td>
                    <td>{{ $submission->created_at->format('Y-m-d H:i:s') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center">No submissions found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>