<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Finance Loan Submissions</h1>

        <!-- Display error message if API call fails -->
        @if (isset($error))
            <div class="alert alert-danger">
                {{ $error }}
            </div>
        @endif

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Facility Name</th>
                    <th>Facility Report</th>
                    <th>License</th>
                    <th>Submitted At</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($loans as $loan)
                <tr>
                    <td>{{ $loan['id'] }}</td>
                    <td>{{ $loan['first_name'] }}</td>
                    <td>{{ $loan['last_name'] }}</td>
                    <td>{{ $loan['phone'] }}</td>
                    <td>{{ $loan['email'] }}</td>
                    <td>{{ $loan['facility_name'] }}</td>
                    <td>
                        <a href="/storage/{{ $loan['facility_report_path'] }}" target="_blank">
                            View Facility Report
                        </a>
                    </td>
                    <td>
                        <a href="/storage/{{ $loan['license_path'] }}" target="_blank">
                            View License
                        </a>
                    </td>
                    <td>{{ \Carbon\Carbon::parse($loan['created_at'])->format('Y-m-d H:i:s') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Pagination -->
        @if (isset($pagination))
        <div class="d-flex justify-content-center">
            <nav aria-label="Page navigation">
                <ul class="pagination">
                    @foreach ($pagination['links'] as $link)
                    <li class="page-item {{ $link['active'] ? 'active' : '' }} {{ $link['url'] ? '' : 'disabled' }}">
                        <a class="page-link" href="{{ $link['url'] ? $link['url'] : '#' }}">
                            {!! $link['label'] !!}
                        </a>
                    </li>
                    @endforeach
                </ul>
            </nav>
        </div>
        @endif
    </div>
</body>
</html>