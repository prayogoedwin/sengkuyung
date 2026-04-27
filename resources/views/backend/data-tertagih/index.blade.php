@extends('backend.template.backend')

@section('content')
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <div class="layout-page">
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Import CSV Data Tertagih</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('data-tertagih.import') }}"
                                    enctype="multipart/form-data" id="importForm">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label class="form-label">Tahun</label>
                                            <input type="number" class="form-control" name="year"
                                                value="{{ $defaultYear }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">File CSV</label>
                                            <input type="file" class="form-control" name="csv_file" accept=".csv"
                                                required>
                                        </div>
                                        <div class="col-md-3 mt-4">
                                            <button type="submit" class="btn btn-primary btn-sm">Import CSV</button>
                                        </div>
                                    </div>
                                </form>
                                <hr>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ route('data-tertagih.template', ['format' => 'xlsx', 'type' => 'format']) }}"
                                        class="btn btn-outline-success btn-sm">
                                        Download Format Excel (Tanpa Isi)
                                    </a>
                                    <a href="{{ route('data-tertagih.template', ['format' => 'csv', 'type' => 'format']) }}"
                                        class="btn btn-outline-secondary btn-sm">
                                        Download Format CSV (Tanpa Isi)
                                    </a>
                                    <a href="{{ route('data-tertagih.template', ['format' => 'xlsx', 'type' => 'contoh']) }}"
                                        class="btn btn-success btn-sm">
                                        Download Contoh Excel (10 Baris)
                                    </a>
                                    <a href="{{ route('data-tertagih.template', ['format' => 'csv', 'type' => 'contoh']) }}"
                                        class="btn btn-primary btn-sm">
                                        Download Contoh CSV (10 Baris)
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">List Data Tertagih</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Filter Tahun</label>
                                        <select class="form-control" id="filterYear">
                                            @foreach ($years as $year)
                                                <option value="{{ $year }}"
                                                    {{ (int) $year === (int) $defaultYear ? 'selected' : '' }}>
                                                    {{ $year }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Filter Status Terdata</label>
                                        <select class="form-control" id="filterStatus">
                                            <option value="">Semua</option>
                                            <option value="0">Belum Terdata</option>
                                            <option value="1">Terdata</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Cari No Polisi</label>
                                        <input type="text" class="form-control" id="filterNopol"
                                            placeholder="Contoh: H8121QY">
                                    </div>
                                    <div class="col-md-3 mt-4">
                                        <button class="btn btn-primary btn-sm" id="btnApplyFilter">Filter</button>
                                        <button class="btn btn-secondary btn-sm" id="btnResetFilter">Reset</button>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table id="tertagihTable" class="table table-bordered table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>No Polisi</th>
                                                <th>ID Lokasi Samsat</th>
                                                <th>Lokasi Layanan</th>
                                                <th>ID Kecamatan</th>
                                                <th>Nama Kecamatan</th>
                                                <th>ID Kelurahan</th>
                                                <th>Nama Kelurahan</th>
                                                <th>Tahun</th>
                                                <th>Status</th>
                                                <th>Alamat</th>
                                                <th>Opsi</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="content-backdrop fade"></div>
                </div>
            </div>
        </div>
        <div class="layout-overlay layout-menu-toggle"></div>
    </div>

    <div id="importLoadingOverlay"
        style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; color:#fff; text-align:center; padding-top:20%;">
        <h5>Proses import sedang berjalan...</h5>
        <p>Mohon tunggu sampai selesai.</p>
    </div>
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            const defaultYear = '{{ $defaultYear }}';
            const table = $('#tertagihTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: window.location.pathname,
                    data: function(d) {
                        d.year = $('#filterYear').val();
                        d.is_terdata = $('#filterStatus').val();
                        d.no_polisi = $('#filterNopol').val();
                    }
                },
                autoWidth: false,
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'no_polisi'
                    },
                    {
                        data: 'id_lokasi_samsat'
                    },
                    {
                        data: 'lokasi_layanan'
                    },
                    {
                        data: 'id_kecamatan'
                    },
                    {
                        data: 'nm_kecamatan'
                    },
                    {
                        data: 'id_kelurahan'
                    },
                    {
                        data: 'nm_kelurahan'
                    },
                    {
                        data: 'year'
                    },
                    {
                        data: 'status_terdata',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'alamat'
                    }, 
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false
                    },
                    
                ]
            });

            $('#btnApplyFilter').on('click', function() {
                table.ajax.reload();
            });

            $('#btnResetFilter').on('click', function() {
                $('#filterYear').val(defaultYear);
                $('#filterStatus').val('');
                $('#filterNopol').val('');
                table.ajax.reload();
            });

            $('#importForm').on('submit', function() {
                $('#importLoadingOverlay').show();
            });
        });

        function toggleTertagihStatus(id, status) {
            $.ajax({
                url: "{{ route('data-tertagih.update-status', ':id') }}".replace(':id', id),
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    is_terdata: status
                },
                success: function(response) {
                    Swal.fire({
                        title: 'Berhasil',
                        text: response.message,
                        icon: 'success'
                    });
                    $('#tertagihTable').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    Swal.fire({
                        title: 'Gagal',
                        text: xhr.responseJSON?.message || 'Terjadi kesalahan.',
                        icon: 'error'
                    });
                }
            });
        }

        function deleteTertagih(id) {
            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: 'Yakin ingin menghapus data ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                $.ajax({
                    url: "{{ route('data-tertagih.destroy', ':id') }}".replace(':id', id),
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire({
                            title: 'Berhasil',
                            text: response.message,
                            icon: 'success'
                        });
                        $('#tertagihTable').DataTable().ajax.reload();
                    },
                    error: function(xhr) {
                        Swal.fire({
                            title: 'Gagal',
                            text: xhr.responseJSON?.message || 'Terjadi kesalahan.',
                            icon: 'error'
                        });
                    }
                });
            });
        }
    </script>
@endpush
