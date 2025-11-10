<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>LOGIN {{ config('app.name') }}</title>
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
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
            max-width: 600px;
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

        .otp-method-card {
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 10px;
        }

        .otp-method-card:hover {
            border-color: #005c99;
            background-color: #f0f8ff;
        }

        .otp-method-card.selected {
            border-color: #005c99;
            background-color: #e6f2ff;
        }

        .otp-method-card input[type="radio"] {
            transform: scale(1.2);
        }

        .otp-icon {
            font-size: 24px;
            margin-right: 10px;
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

        <h2 class="text-center mb-4">LOGIN</h2>
        <img src="{{ asset('LOGO_SENGKUYUNG/LOGO_SENGKUYUNG.png') }}" alt="Logo {{ config('app.name') }}"
            class="img-fluid mx-auto d-block">
        <br />
        <br />
        <form id="loginForm" action="{{ route('login.action') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="username" class="form-label">Username / Email</label>
                <input type="text" class="form-control" id="username" name="username" required
                    value="{{ old('username') }}">
                @error('username')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <input type="checkbox" id="show-password"><small>Lihat Kata Sandi</small>
                @error('password')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="mb-3">
                <label for="captcha" class="form-label">Captcha</label>
                <div>
                    <img src="{{ captcha_src() }}" id="captchaImage" alt="captcha" class="mb-2">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="refreshCaptcha()">Refresh</button>
                </div>
                <input type="text" name="captcha" class="form-control" id="captcha" required
                    placeholder="Masukkan teks captcha">
                @error('captcha')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>

            <!-- Pilihan Metode OTP -->
            <div class="mb-3">
                <label class="form-label">Metode Pengiriman Kode OTP <span class="text-danger">*</span></label>

                <div class="otp-method-card" data-method="email">
                    <div class="d-flex align-items-center">
                        <input type="radio" name="otp_method" id="otp_email" value="email" checked
                            class="form-check-input me-3">
                        <label for="otp_email" class="form-check-label w-100 cursor-pointer">
                            <span class="otp-icon">📧</span>
                            <strong>Email</strong>
                            <p class="mb-0 text-muted small">Kode OTP akan dikirim ke email Anda</p>
                        </label>
                    </div>
                </div>

                <div class="otp-method-card" data-method="wa">
                    <div class="d-flex align-items-center">
                        <input type="radio" name="otp_method" id="otp_wa" value="wa"
                            class="form-check-input me-3">
                        <label for="otp_wa" class="form-check-label w-100 cursor-pointer">
                            <span class="otp-icon">💬</span>
                            <strong>WhatsApp</strong>
                            <p class="mb-0 text-muted small">Kode OTP akan dikirim ke nomor WhatsApp Anda</p>
                        </label>
                    </div>
                </div>

                @error('otp_method')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>

            @error('login_error')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror

            <button id="submitButton" type="button" class="btn btn-primary w-100">Submit</button>
        </form>

    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function refreshCaptcha() {
            document.getElementById('captchaImage').src = '{{ captcha_src() }}' + '?' + Math.random();
        }
    </script>

    <script>
        $(document).ready(function() {
            // Show password toggle
            $("#show-password").change(function() {
                $(this).prop("checked") ? $("#password").prop("type", "text") : $("#password").prop("type",
                    "password");
            });

            // OTP Method selection styling
            $('.otp-method-card').click(function() {
                $('.otp-method-card').removeClass('selected');
                $(this).addClass('selected');
                $(this).find('input[type="radio"]').prop('checked', true);
            });

            // Set initial selected card
            $('input[name="otp_method"]:checked').closest('.otp-method-card').addClass('selected');

            // Form submit validation
            $('#submitButton').click(function(e) {
                const username = $('#username').val().trim();
                const password = $('#password').val().trim();
                const captcha = $('#captcha').val().trim();
                const otpMethod = $('input[name="otp_method"]:checked').val();

                if (!username || !password || !captcha) {
                    Swal.fire({
                        title: 'Validasi Error',
                        text: 'Pastikan semua kolom terisi!',
                        icon: 'info',
                    });
                    return;
                }

                if (!otpMethod) {
                    Swal.fire({
                        title: 'Validasi Error',
                        text: 'Pilih metode pengiriman OTP!',
                        icon: 'info',
                    });
                    return;
                }

                const methodText = otpMethod === 'email' ? 'Email' : 'WhatsApp';

                // Show confirmation
                Swal.fire({
                    title: 'Konfirmasi',
                    html: `Kode OTP akan dikirim melalui <strong>${methodText}</strong>.<br/>Apakah Anda yakin ingin melanjutkan?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Kirim OTP',
                    cancelButtonText: 'Batal',
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading
                        Swal.fire({
                            title: 'Mengirim OTP...',
                            text: 'Mohon tunggu sebentar',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        // Submit the form
                        $('#loginForm').submit();
                    }
                });
            });

            // Handle server-side errors with SweetAlert
            @if ($errors->has('login_error'))
                Swal.fire({
                    title: 'Login Error',
                    text: '{{ $errors->first('login_error') }}',
                    icon: 'error',
                });
            @endif

            @if ($errors->has('captcha'))
                Swal.fire({
                    title: 'Captcha Error',
                    text: '{{ $errors->first('captcha') }}',
                    icon: 'error',
                });
            @endif
        });
    </script>

</body>

</html>