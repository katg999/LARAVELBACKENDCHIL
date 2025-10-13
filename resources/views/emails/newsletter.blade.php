<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 0 0 5px 5px;
        }
        .footer {
            margin-top: 20px;
            padding: 15px;
            background-color: #e9ecef;
            border-radius: 5px;
            font-size: 12px;
            color: #6c757d;
        }
        .unsubscribe {
            text-align: center;
            margin-top: 15px;
        }
        .unsubscribe a {
            color: #007bff;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>CHIL Newsletter</h1>
    </div>

    <div class="content">
        {!! $content !!}
    </div>

    <div class="footer">
        <p>You received this newsletter because you subscribed to updates from CHIL.</p>

        <div class="unsubscribe">
            <p><a href="{{ $unsubscribeUrl }}">Unsubscribe from this newsletter</a></p>
        </div>

        <p>&copy; {{ date('Y') }} CHIL. All rights reserved.</p>
    </div>
</body>
</html>