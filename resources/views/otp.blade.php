<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Verifikasi OTP - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('LOGO_SENGKUYUNG/ICON_LOGO_SENGKUYUNG.png') }}">
    <style>
        body {
            background-image: url('/perambanan.avif');
            background-size: cover;
            background-position: center;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .container {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
            max-width: 500px;
            width: 100%;
        }

        .btn-primary {
            background-color: #005c99;
            border-color: #005c99;
        }

        .btn-primary:hover {
            background-color: #00487a;
            border-color: #00487a;
        }

        .otp-input {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            margin: 0 5px;
            border: 2px solid #ddd;
            border-radius: 8px;
        }

        .otp-input:focus {
            border-color: #005c99;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 92, 153, 0.3);
        }

        .otp-container {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }

        .timer {
            font-size: 18px;
            font-weight: bold;
            color: #005c99;
            text-align: center;
            margin: 15px 0;
        }

        .timer.expired {
            color: #dc3545;
        }

        .resend-section {
            text-align: center;
            margin-top: 20px;
            display: none; /* 🔹 Disembunyikan dulu */
        }

        .method-badge {
            display: inline-block;
            padding: 8px 16px;
            background-color: #e6f2ff;
            border-radius: 20px;
            font-weight: 500;
            color: #005c99;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <div class="container">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <h3 class="text-center mb-3">Verifikasi OTP</h3>

        <div class="text-center">
            <span class="method-badge">
                @if ($otp_method === 'email')
                    📧 Email
                @else
                    💬 WhatsApp
                @endif
            </span>
        </div>

        <p class="text-center text-muted">
            Masukkan 6 digit kode OTP yang telah dikirim ke
            <strong>{{ $otp_method === 'email' ? 'email' : 'WhatsApp' }}</strong> Anda
        </p>

        <form id="otpForm" action="{{ route('login.otp.verify') }}" method="POST">
            @csrf

            <div class="otp-container">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" id="otp1" autofocus>
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" id="otp2">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" id="otp3">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" id="otp4">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" id="otp5">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" id="otp6">
            </div>

            <input type="hidden" name="otp" id="otpValue">

            @error('otp_error')
                <div class="alert alert-danger text-center">{{ $message }}</div>
            @enderror

            <div class="timer" id="timer">
                Kode akan kadaluarsa dalam: <span id="countdown"></span>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-2" id="verifyButton">
                Verifikasi OTP
            </button>

            <button type="button" class="btn btn-outline-secondary w-100" onclick="window.location.href='{{ route('login.form') }}'">
                Kembali ke Login
            </button>
        </form>

        <!-- 🔹 Resend OTP Section -->
        <div class="resend-section" id="resendSection">
            <p class="text-muted mb-2">Tidak menerima kode?</p>
            <form action="{{ route('login.otp.resend') }}" method="POST" id="resendForm">
                @csrf
                <button type="button" class="btn btn-link" id="resendButton" onclick="resendOTP()">
                    Kirim Ulang OTP
                </button>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            const inputs = $('.otp-input');

            inputs.on('input', function() {
                const value = $(this).val();
                if (value.length === 1) {
                    const next = $(this).next('.otp-input');
                    if (next.length) {
                        next.focus();
                    } else {
                        submitOTP();
                    }
                }
            });

            inputs.on('keydown', function(e) {
                if (e.key === 'Backspace' && $(this).val() === '') {
                    const prev = $(this).prev('.otp-input');
                    if (prev.length) {
                        prev.focus();
                    }
                }
            });

            inputs.on('keypress', function(e) {
                if (e.which < 48 || e.which > 57) e.preventDefault();
            });

            inputs.first().on('paste', function(e) {
                e.preventDefault();
                const pasteData = e.originalEvent.clipboardData.getData('text');
                const digits = pasteData.replace(/\D/g, '').split('');
                inputs.each(function(index) {
                    if (digits[index]) $(this).val(digits[index]);
                });
                if (digits.length === 6) submitOTP();
            });

            $('#otpForm').submit(function(e) {
                e.preventDefault();
                submitOTP();
            });
        });

        function submitOTP() {
            let otp = '';
            $('.otp-input').each(function() { otp += $(this).val(); });

            if (otp.length !== 6) {
                Swal.fire({ title: 'Validasi Error', text: 'Masukkan 6 digit kode OTP!', icon: 'warning' });
                return;
            }

            $('#otpValue').val(otp);
            Swal.fire({
                title: 'Memverifikasi OTP...',
                text: 'Mohon tunggu sebentar',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            $('#otpForm').off('submit').submit();
        }

        function resendOTP() {
            Swal.fire({
                title: 'Kirim Ulang OTP?',
                text: 'Kode OTP baru akan dikirim ke Anda',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Kirim',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Mengirim OTP...',
                        text: 'Mohon tunggu sebentar',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading(),
                    });
                    $('#resendForm').submit();
                }
            });
        }

        let otpExpiredShown = false;
        function updateCountdown() {
            const now = new Date().getTime();
            const distance = expiredAt - now;

            if (distance < 0 && !otpExpiredShown) {
                otpExpiredShown = true;
                clearInterval(countdownInterval);

                $('#countdown').text('00:00');
                $('#timer').addClass('expired').text('Kode OTP telah kadaluarsa!');
                $('#verifyButton').prop('disabled', true);
                $('#resendSection').fadeIn();

                Swal.fire({
                    title: 'OTP Kadaluarsa',
                    text: 'Kode OTP telah kadaluarsa. Silakan kirim ulang OTP atau login kembali.',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.href = '{{ route('login.form') }}';
                });
            }

            if (distance >= 0) {
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                $('#countdown').text(
                    String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0')
                );
            }
        }

        @if ($errors->has('otp_error'))
            Swal.fire({
                title: 'OTP Error',
                text: '{{ $errors->first('otp_error') }}',
                icon: 'error',
            }).then(() => {
                $('.otp-input').val('');
                $('#otp1').focus();
            });
        @endif
    </script>
</body>
</html>
