<!-- resources/views/contact-us.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Contact Form Submissions</h1>
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Message</th>
                <th>Submitted At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($submissions as $submission)
            <tr>
                <td>{{ $submission->first_name }} {{ $submission->last_name }}</td>
                <td>{{ $submission->email }}</td>
                <td>{{ $submission->phone ?? 'N/A' }}</td>
                <td>{{ Str::limit($submission->message, 50) }}</td>
                <td>{{ $submission->created_at->format('M d, Y H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection