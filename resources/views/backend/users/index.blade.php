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
                            <div class="col-xl-12">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row align-items-center m-l-0">
                                            <div class="col-sm-6">

                                            </div>
                                            <div class="col-sm-6 text-end">
                                                <button class="btn btn-success btn-sm btn-round has-ripple"
                                                    data-bs-toggle="modal" data-bs-target="#modal-report"><i
                                                        class="feather icon-plus"></i> Add
                                                    Data</button>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table id="simpletable" class="table table-bordered table-striped mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>No</th>
                                                        <th>Nama</th>
                                                        <th>Email</th>
                                                        <th>Whatsapp</th>
                                                        <th>Role</th>
                                                        <th>Options</th>
                                                    </tr>
                                                </thead>

                                            </table>
                                        </div>
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

    <div class="modal fade" id="modal-report" tabindex="-1" role="dialog" aria-labelledby="myExtraLargeModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="registerForm">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label class="floating-label" for="Name">Nama Lengkap</label>
                                        <input type="text" class="form-control" id="name" name="name"
                                            placeholder="">
                                    </div>
                                </div>

                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label class="floating-label" for="email">Email</label>
                                        <input type="text" class="form-control" id="email" name="email"
                                            placeholder="">
                                    </div>
                                </div>

                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label class="floating-label" for="whatsapp">Whatsapp</label>
                                        <input type="text" class="form-control" id="whatsapp" name="whatsapp"
                                            placeholder="">
                                    </div>
                                </div>

                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label for="userRole" class="form-label">Role</label>
                                        <select class="form-control" id="userRole" name="role_id" required>
                                            <option value="">Select Role</option>
                                            @foreach ($roles as $role)
                                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>



                                <div class="col-sm-12 mt-3">
                                    {{-- <div class="form-group">
                                        <label class="floating-label" for="Description">Description</label>
                                        <textarea class="form-control" id="Description" rows="3"></textarea>
                                    </div> --}}
                                    <button class="btn btn-primary">Submit</button>
                                    <button class="btn btn-danger">Clear</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modal-edit" tabindex="-1" role="dialog" aria-labelledby="modalEditLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEditLabel">Edit Admin</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editAdminForm">
                            <input type="hidden" id="editAdminId">
                            <div class="mb-3">
                                <label for="editName" class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" id="editName" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="editEmail" class="form-label">Email</label>
                                <input type="email" class="form-control" id="editEmail" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="editWhatsapp" class="form-label">Whatsapp</label>
                                <input type="text" class="form-control" id="editWhatsapp" name="whatsapp" required>
                            </div>
                            <div class="mb-3">
                                <label for="editRole" class="form-label">Role</label>
                                <select class="form-control" id="editRole" name="role_id" required>
                                    <option value="">Select Role</option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary" onclick="updateFaq()">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
@endsection


@push('js')
    <script>
        $(document).ready(function() {
            $('#simpletable').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('user.index') }}',
                autoWidth: false, // Menonaktifkan auto-width
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'user_name'
                    },
                    {
                        data: 'email'
                    },
                    {
                        data: 'whatsapp'
                    },
                    {
                        data: 'roles'
                    },
                    {
                        data: 'options',
                        orderable: false,
                        searchable: false
                    },
                ]
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            $('#registerForm').submit(function(e) {
                e.preventDefault(); // Prevent form from submitting normally

                // Clear previous error messages
                $('#errorMessages').html('').addClass('d-none');

                var formData = {
                    name: $('#name').val(),
                    email: $('#email').val(),
                    whatsapp: $('#whatsapp').val(),
                    role_id: $('#userRole').val(),
                    _token: '{{ csrf_token() }}' // Add CSRF token for security
                };

                $.ajax({
                    type: 'POST',
                    url: '{{ route('user.add') }}', // Ganti dengan rute yang sesuai
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            alert('User berhasil ditambahkan');
                            $('#modal-report').modal('hide');
                            location.reload(); // Refresh halaman
                        } else {
                            // If validation errors are found, display them in an alert
                            if (response.errors) {
                                let errorMessages = '';
                                $.each(response.errors, function(key, value) {
                                    $.each(value, function(index, errorMessage) {
                                        errorMessages += errorMessage +
                                            '\n'; // Gabungkan pesan error
                                    });
                                });
                                alert('Terjadi kesalahan:\n' + errorMessages);
                            } else {
                                alert('Gagal menambahkan user');
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Terjadi kesalahan: ' + error);
                    }
                });
            });
        });
    </script>

    <script>
        function showEditModal(adminId) {
            // alert('a');
            var detailUrl = "{{ route('user.detail', ':id') }}".replace(':id', adminId);
            $.ajax({
                url: detailUrl,
                type: 'GET',
                success: function(response) {
                    let user = response.data;

                    // Isi data modal dengan data yang diperoleh
                    $('#editAdminId').val(user.id);
                    $('#editName').val(user.name);
                    $('#editEmail').val(user.email);
                    $('#editWhatsapp').val(user.whatsapp);

                    //Pilih role di select box
                    if (user.roles.length > 0) {
                        // let roleId = user.roles.id; // Ambil ID peran pertama
                        let roleId = user.roles[0].id; // Ambil ID dari role pertama
                        $('#editRole').val(roleId).prop('selected', true);
                    } else {
                        $('#editRole').val(''); // Kosongkan jika tidak ada peran
                    }

                    // Tampilkan modal edit
                    $('#modal-edit').modal('show');
                },
                error: function(xhr) {
                    alert('Error: ' + xhr.responseText);
                }
            });
        }
    </script>

    <script>
        function updateFaq() {
            // Get data from the modal form
            var id = $('#editAdminId').val();
            var name = $('#editName').val();
            var email = $('#editEmail').val();
            var whatsapp = $('#editWhatsapp').val();
            var role_id = $('#editRole').val();




            // Send the data to the update route
            $.ajax({
                url: "{{ route('user.update', ':id') }}".replace(':id', id),
                type: 'PUT',
                data: {
                    _token: "{{ csrf_token() }}", // CSRF token for security
                    name: name,
                    email: email,
                    whatsapp: whatsapp,
                    role_id: role_id
                },
                success: function(response) {
                    if (response.success) {
                        // Display success message
                        alert(response.message);
                        // Close modal
                        $('#modal-edit').modal('hide');
                        // Optionally, reload the table or page to reflect the update
                        location.reload();
                    } else {
                        // Display error message
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr) {
                    alert('Error: ' + xhr.responseText);
                }
            });
        }
    </script>


    <script>
        function confirmDelete(adminId) {
            // Konfirmasi penghapusan
            var deleteUrl = "{{ route('user.softdelete', ':id') }}".replace(':id', adminId);
            if (confirm("Are you sure you want to delete this admin?")) {
                // Kirim request ke server untuk menghapus data
                $.ajax({
                    url: deleteUrl,
                    type: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'), // Menyertakan CSRF token
                    },
                    success: function(response) {
                        // Jika berhasil, reload DataTable
                        alert(response.message); // Menampilkan pesan
                        $('#simpletable').DataTable().ajax.reload(); // Reload data tabel
                    },
                    error: function(xhr, status, error) {
                        // Tampilkan error jika ada masalah
                        alert('Error: ' + xhr.responseText);
                    }
                });
            }
        }
    </script>
@endpush
