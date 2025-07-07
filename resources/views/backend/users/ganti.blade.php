@extends('backend.template.backend')

@section('content')
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Layout container -->
            <div class="layout-page">
                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <!-- Content -->
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <div class="row">
                            <div class="col-xl-6">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row align-items-center m-l-0">
                                            <div class="col-sm-6">

                                            </div>
                                            
                                        </div>

                                        
                                        <form action="{{ route('user.ganti_password', ['id' => $user->id]) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="row mb-3">
                                            
                                            @if(session('success'))
                                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                                {{ session('success') }}
                                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                            </div>
                                            @endif

                                            @if(session('error'))
                                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                                {{ session('error') }}
                                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                            </div>
                                            @endif

                                           
                                            <div class="col-md-6">
                                                <label for="tanggal_start">Password</label>
                                                <input type="password" id="tanggal_start" name="password" class="form-control" placeholder="*****">
                                                <button type="button" class="btn toggle-password btn-xs" data-target="#tanggal_start">
                                                   <i class="bx bx-show"></i>
                                                </button>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="tanggal_end">Re-Password</label>
                                                <input type="password" id="tanggal_end" name="re-password" class="form-control"  placeholder="*****">
                                                <button type="button" class="btn toggle-password btn-xs" data-target="#tanggal_end">
                                                    <i class="bx bx-show"></i>
                                                </button>
                                            </div>
                                           
                                            <div class="col-md-12 mt-2">
                                                <button type="submit" class="btn btn-primary mt-2" id="submitFilter">Ganti</button>
                                            </div>
                                        </div>
                                        </form>


                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- / Content -->

                    <div class="content-backdrop fade"></div>
                </div>
                <!-- Content wrapper -->
            </div>
            <!-- / Layout page -->
        </div>
    </div>

@endsection


@push('js')

<script>

document.addEventListener('DOMContentLoaded', function() {

    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(function(button) {
        button.addEventListener('click', function() {
            const target = document.querySelector(this.getAttribute('data-target'));
            const icon = this.querySelector('i');
            
            if (target.type === 'password') {
                target.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                target.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    });

    const passwordInput = document.getElementById('tanggal_start');
    const rePasswordInput = document.getElementById('tanggal_end');
    const submitButton = document.getElementById('submitFilter');
    
    // Create error message elements
    const passwordError = document.createElement('div');
    passwordError.style.color = 'red';
    passwordError.style.fontSize = '0.8rem';
    passwordError.style.marginTop = '0.25rem';
    passwordInput.parentNode.appendChild(passwordError);
    
    const rePasswordError = document.createElement('div');
    rePasswordError.style.color = 'red';
    rePasswordError.style.fontSize = '0.8rem';
    rePasswordError.style.marginTop = '0.25rem';
    rePasswordInput.parentNode.appendChild(rePasswordError);
    
    // Validation functions
    function validatePassword(password) {
        // At least 8 characters, contains letter, number, and special character
        const regex = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/;
        return regex.test(password);
    }
    
    function validatePasswordMatch(password, rePassword) {
        return password === rePassword;
    }
    
    // Real-time validation
    passwordInput.addEventListener('input', function() {
        if (!validatePassword(passwordInput.value)) {
            passwordError.textContent = 'Password must be at least 8 characters and contain letters, numbers, and special characters';
            submitButton.disabled = true;
        } else {
            passwordError.textContent = '';
            if (validatePasswordMatch(passwordInput.value, rePasswordInput.value)) {
                submitButton.disabled = false;
                rePasswordError.textContent = '';
            }
        }
    });
    
    rePasswordInput.addEventListener('input', function() {
        if (!validatePasswordMatch(passwordInput.value, rePasswordInput.value)) {
            rePasswordError.textContent = 'Passwords do not match';
            submitButton.disabled = true;
        } else {
            rePasswordError.textContent = '';
            if (validatePassword(passwordInput.value)) {
                submitButton.disabled = false;
            }
        }
    });
    
    // Form submission validation
    submitButton.addEventListener('click', function(e) {
        if (!validatePassword(passwordInput.value)) {
            e.preventDefault();
            passwordError.textContent = 'Password must be at least 8 characters and contain letters, numbers, and special characters';
            return false;
        }
        
        if (!validatePasswordMatch(passwordInput.value, rePasswordInput.value)) {
            e.preventDefault();
            rePasswordError.textContent = 'Passwords do not match';
            return false;
        }
        
        // If validation passes, the form will submit
        return true;
    });
});

</script>



 
@endpush
