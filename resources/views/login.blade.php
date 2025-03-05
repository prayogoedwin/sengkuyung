<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOGIN {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            /* background-color: #339BF1; */
            background-image: url('/bali2.jpg');
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

        .captcha-image {
            display: block;
            margin-bottom: 10px;
            width: 100%;
            height: 50px;
            background-color: #ddd;
            text-align: center;
            line-height: 50px;
            font-size: 24px;
            font-weight: bold;
            color: #555;
        }
    </style>
</head>

<body>
    <div class="container">

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        <h2 class="text-center mb-4">LOGIN {{ config('app.name') }}</h2>
        <form id="loginForm" action="{{ route('login.action') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
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
                    placeholder="Enter the text shown">
                @error('captcha')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            @error('login_error')
                <span class="text-danger">{{ $message }}</span>
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
            location.reload(); // Fungsi untuk menyegarkan halaman
        }
    </script>

    <script>
        $(document).ready(function() {
            $("#show-password").change(function() {
                $(this).prop("checked") ? $("#password").prop("type", "text") : $("#password").prop("type",
                    "password");
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Show password toggle
            document.getElementById('show-password').addEventListener('change', function() {
                const passwordField = document.getElementById('password');
                passwordField.type = this.checked ? 'text' : 'password';
            });

            // Refresh captcha image
            window.refreshCaptcha = function() {
                document.getElementById('captchaImage').src = '{{ captcha_src() }}' + '?' + Math.random();
            };

            // Form submit validation
            document.getElementById('submitButton').addEventListener('click', function(e) {
                const username = document.getElementById('username').value.trim();
                const password = document.getElementById('password').value.trim();
                const captcha = document.getElementById('captcha').value.trim();

                if (!username || !password || !captcha) {
                    Swal.fire({
                        title: 'Validation Error',
                        text: 'Pastikan semua kolom terisi!',
                        icon: 'info',
                    });
                    return;
                }

                // Show confirmation
                Swal.fire({
                    title: 'Konfirmasi',
                    text: 'Apakah Anda yakin ingin mengirim formulir?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Kirim',
                    cancelButtonText: 'Batal',
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Submit the form
                        document.getElementById('loginForm').submit();
                    }
                });
            });

            // Handle server-side errors
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
