<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ config('app.name') }}</title>
</head>
<body style="font-family: sans-serif; background-color: #f5f5f5; padding: 20px;">
<div style="max-width: 600px; margin: auto; background: #fff; padding: 30px; border-radius: 8px;">
    <h2 style="color: #2c3e50;">{{ config('app.name') }}</h2>
    <p style="font-size: 16px; color: #555;">
        {!! $messageContent !!}
    </p>
</div>
</body>
</html>