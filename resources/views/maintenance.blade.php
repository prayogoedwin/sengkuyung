<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            color: #212529;
        }

        .box {
            max-width: 460px;
            text-align: center;
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 28px 24px;
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.08);
        }

        h1 {
            margin-top: 0;
            font-size: 24px;
        }

        p {
            margin-bottom: 0;
            line-height: 1.6;
        }
    </style>
</head>

<body>
    <div class="box">
        <h1>Sistem Maintenance</h1>
        <p>{{ $message ?? 'Sistem sedang dalam maintenance. Silakan coba beberapa saat lagi.' }}</p>
    </div>
</body>

</html>
