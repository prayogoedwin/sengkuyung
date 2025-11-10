<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode OTP Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .email-container {
            max-width: 600px;
            margin: 30px auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .email-header {
            background-color: #005c99;
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }

        .email-header h1 {
            margin: 0;
            font-size: 24px;
        }

        .email-body {
            padding: 40px 30px;
        }

        .greeting {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
        }

        .message {
            font-size: 16px;
            color: #555;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .otp-box {
            background-color: #f8f9fa;
            border: 2px dashed #005c99;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 30px 0;
        }

        .otp-code {
            font-size: 36px;
            font-weight: bold;
            color: #005c99;
            letter-spacing: 8px;
            margin: 10px 0;
        }

        .otp-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }

        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            font-size: 14px;
            color: #856404;
        }

        .info {
            background-color: #d1ecf1;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            margin: 20px 0;
            font-size: 14px;
            color: #0c5460;
        }

        .email-footer {
            background-color: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
        }

        .email-footer a {
            color: #005c99;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <h1>🔐 Kode OTP Login</h1>
        </div>

        <!-- Body -->
        <div class="email-body">
            <p class="greeting">Halo <strong>{{ $nama }}</strong>,</p>

            <p class="message">
                Anda telah meminta kode OTP untuk login ke <strong>{{ config('app.name') }}</strong>.
                Gunakan kode berikut untuk melanjutkan proses login Anda:
            </p>

            <!-- OTP Box -->
            <div class="otp-box">
                <div class="otp-label">KODE VERIFIKASI ANDA</div>
                <div class="otp-code">{{ $otp }}</div>
            </div>

            <!-- Info -->
            <div class="info">
                <strong>⏱️ Perhatian:</strong><br>
                Kode OTP ini berlaku selama <strong>{{ $expired_minutes }} menit</strong>. 
                Setelah waktu tersebut, kode akan kadaluarsa dan Anda perlu meminta kode baru.
            </div>

            <!-- Warning -->
            <div class="warning">
                <strong>⚠️ Keamanan:</strong><br>
                Jangan bagikan kode ini kepada siapapun, termasuk kepada tim kami. 
                Kami tidak akan pernah meminta kode OTP Anda melalui telepon, email, atau pesan.
            </div>

            <p class="message">
                Jika Anda tidak melakukan permintaan login ini, abaikan email ini dan pastikan akun Anda aman.
            </p>

            <p class="message">
                Terima kasih,<br>
                <strong>Tim {{ config('app.name') }}</strong>
            </p>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p>
                Email ini dikirim secara otomatis. Mohon tidak membalas email ini.
            </p>
            <p>
                © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </p>
        </div>
    </div>
</body>

</html>