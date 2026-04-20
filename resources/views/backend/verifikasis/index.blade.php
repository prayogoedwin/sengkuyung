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
                                            <div class="col-sm-6 text-end" hidden>
                                                <button class="btn btn-success btn-sm btn-round has-ripple"
                                                    data-bs-toggle="modal" data-bs-target="#modal-report"><i
                                                        class="feather icon-plus"></i> Add
                                                    Data</button>
                                            </div>
                                        </div>

                                        
                                        <div class="row mb-3">
                                            <div class="col-md-3">
                                                <label for="statusVerifikasi">Status Verifikasi</label>
                                                <select id="statusVerifikasi" name="status_verifikasi_id" class="form-control">
                                                    <option value="">Pilih Status</option>
                                                    @foreach ($status_verifikasis as $status)
                                                        <option value="{{ $status->id }}">
                                                            {{ $status->nama }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                           
                                            @php
                                                $userRoleId = Auth::user()->roles[0]->id ?? null;
                                                $userKotaId = Auth::user()->kota ?? null;
                                            @endphp

                                            {{-- {{ $userRoleId }} --}}

                                            <div class="col-md-3">
                                                <label for="userKabkota">Kabupaten/Kota</label>
                                                <select class="form-control" id="userKabkota" name="kota">
                                                    <option value="">Pilih Kabkota</option>
                                                    @foreach ($kabkotas as $kbkt)
                                                        @if ($userRoleId == 3 || $userRoleId == 4)
                                                            @if ($kbkt->id == $userKotaId)
                                                                <option value="{{ $kbkt->id }}" selected>{{ $kbkt->nama }}</option>
                                                            @endif
                                                        @else
                                                            <option value="{{ $kbkt->id }}">{{ $kbkt->nama }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </div>


                                            <div class="col-md-3">
                                                <label for="lokasiSamsat">Lokasi Samsat</label>
                                                <select class="form-control" id="lokasiSamsat" name="lokasi_samsat">
                                                    <option value="">Pilih Lokasi Samsat</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="kecamatanSamsat">Kecamatan Samsat</label>
                                                <select class="form-control" id="kecamatanSamsat" name="kecamatan_samsat">
                                                    <option value="">Pilih Kecamatan Samsat</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3 mt-2">
                                                <label for="kelurahanSamsat">Kelurahan Samsat</label>
                                                <select class="form-control" id="kelurahanSamsat" name="kelurahan_samsat">
                                                    <option value="">Pilih Kelurahan Samsat</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="nopol">Nopol</label>
                                                <input type="text" id="nopol" class="form-control" placeholder="Contoh: H5000ABG">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="tanggal_start">Tanggal Start</label>
                                                <input type="date" id="tanggal_start" class="form-control">
                                            </div>
                                            <div class="col-md-3 mt-2">
                                                <label for="tanggal_end">Tanggal End</label>
                                                <input type="date" id="tanggal_end" class="form-control">
                                            </div>
                                            <div class="col-md-3 mt-4">
                                                <button class="btn btn-primary mt-2" id="submitFilter">Submit</button>
                                                <button class="btn btn-secondary mt-2" id="resetFilter">Reset</button>
                                            </div>
                                        </div>


                                        <div class="table-responsive">
                                            <table id="simpletable" class="table table-bordered table-striped mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>No</th>
                                                        <th>Nopol</th>
                                                        <th>Tanggal Pendataan</th>
                                                        <th>Nama</th>
                                                        <th>No HP</th>
                                                        <th>Status</th>
                                                        <th>Verifikasi</th>
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

@endsection


@push('js')
    <script>
        $(document).ready(function() {
            // $('#simpletable').DataTable({
            let table = $('#simpletable').DataTable({
                processing: true,
                serverSide: true,
                // ajax: '{{ route('verifikasi.index') }}',
                ajax: {
                    url: '{{ route('verifikasi.index') }}',
                    data: function(d) {
                        d.status_verifikasi_id = $('#statusVerifikasi').val();
                        d.kota = $('#userKabkota').val();
                        d.lokasi_samsat = $('#lokasiSamsat').val();
                        d.kecamatan_samsat = $('#kecamatanSamsat').val();
                        d.kelurahan_samsat = $('#kelurahanSamsat').val();
                        d.nopol = $('#nopol').val();
                        d.tanggal_start = $('#tanggal_start').val();
                        d.tanggal_end = $('#tanggal_end').val();
                    }
                },
                autoWidth: false, // Menonaktifkan auto-width
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nopol'
                    },
                    {
                        data: 'tanggal_pendataan'
                    },
                    {
                        data: 'nama'
                    },
                    {
                        data: 'nohp'
                    },
                    {
                        data: 'status_name'
                    },
                    {
                        data: 'status_verifikasi_name'
                    },
                    {
                        data: 'options',
                        orderable: false,
                        searchable: false
                    },
                ]
            });

            $('#submitFilter').click(function() {
                table.ajax.reload(); // Reload datatable dengan filter
            });

            $('#resetFilter').click(function() {
                $('#statusVerifikasi').val('');
                $('#userKabkota').val('');
                $('#lokasiSamsat').html('<option value="">Pilih Lokasi Samsat</option>');
                $('#kecamatanSamsat').html('<option value="">Pilih Kecamatan Samsat</option>');
                $('#kelurahanSamsat').html('<option value="">Pilih Kelurahan Samsat</option>');
                $('#nopol').val('');
                $('#tanggal_start').val('');
                $('#tanggal_end').val('');
                table.ajax.reload(); // Reset dan reload datatable
            });

        });
    </script>

<script>
    $(document).ready(function() {
        $('#userKabkota').on('change', function() {
            var kabkotaId = $(this).val();
    
            if (kabkotaId) {
                $.ajax({
                    url: '{{ route("getSamsatByKabkota") }}',
                    type: 'GET',
                    data: { kabkota_id: kabkotaId },
                    success: function(response) {
                        if (response.success) {
                            var samsats = response.samsats;
                            var options = '<option value="">Pilih Lokasi Samsat</option>';
                            $.each(samsats, function(index, samsat) {
                                var value = samsat.id_wilayah_samsat ?? samsat.id;
                                var label = samsat.lokasi ?? '-';
                                options += '<option value="' + value + '">' + label + '</option>';
                            });
                            $('#lokasiSamsat').html(options);
                            $('#kecamatanSamsat').html('<option value="">Pilih Kecamatan Samsat</option>');
                            $('#kelurahanSamsat').html('<option value="">Pilih Kelurahan Samsat</option>');
                        } else {
                            $('#lokasiSamsat').html('<option value="">Lokasi Samsat tidak ditemukan</option>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching samsat:', error);
                        $('#lokasiSamsat').html('<option value="">Gagal mengambil lokasi samsat</option>');
                    }
                });
            } else {
                $('#lokasiSamsat').html('<option value="">Pilih Lokasi Samsat</option>');
                $('#kecamatanSamsat').html('<option value="">Pilih Kecamatan Samsat</option>');
                $('#kelurahanSamsat').html('<option value="">Pilih Kelurahan Samsat</option>');
            }
        });

        $('#lokasiSamsat').on('change', function() {
            var lokasiSamsatId = $(this).val();

            if (lokasiSamsatId) {
                $.ajax({
                    url: '{{ route("getSamsatKecamatan") }}',
                    type: 'GET',
                    data: { lokasi_samsat_id: lokasiSamsatId },
                    success: function(response) {
                        if (response.success) {
                            var kecamatans = response.kecamatans;
                            var options = '<option value="">Pilih Kecamatan Samsat</option>';
                            $.each(kecamatans, function(index, kecamatan) {
                                options += '<option value="' + kecamatan.id_kecamatan + '">' + kecamatan.kecamatan + '</option>';
                            });
                            $('#kecamatanSamsat').html(options);
                            $('#kelurahanSamsat').html('<option value="">Pilih Kelurahan Samsat</option>');
                        } else {
                            $('#kecamatanSamsat').html('<option value="">Kecamatan Samsat tidak ditemukan</option>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching kecamatan samsat:', error);
                        $('#kecamatanSamsat').html('<option value="">Gagal mengambil kecamatan samsat</option>');
                    }
                });
            } else {
                $('#kecamatanSamsat').html('<option value="">Pilih Kecamatan Samsat</option>');
                $('#kelurahanSamsat').html('<option value="">Pilih Kelurahan Samsat</option>');
            }
        });

        $('#kecamatanSamsat').on('change', function() {
            var kecamatanSamsatId = $(this).val();

            if (kecamatanSamsatId) {
                $.ajax({
                    url: '{{ route("getSamsatKelurahan") }}',
                    type: 'GET',
                    data: { kecamatan_samsat_id: kecamatanSamsatId },
                    success: function(response) {
                        if (response.success) {
                            var kelurahans = response.kelurahans;
                            var options = '<option value="">Pilih Kelurahan Samsat</option>';
                            $.each(kelurahans, function(index, kelurahan) {
                                options += '<option value="' + kelurahan.id_kelurahan + '">' + kelurahan.kelurahan + '</option>';
                            });
                            $('#kelurahanSamsat').html(options);
                        } else {
                            $('#kelurahanSamsat').html('<option value="">Kelurahan Samsat tidak ditemukan</option>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching kelurahan samsat:', error);
                        $('#kelurahanSamsat').html('<option value="">Gagal mengambil kelurahan samsat</option>');
                    }
                });
            } else {
                $('#kelurahanSamsat').html('<option value="">Pilih Kelurahan Samsat</option>');
            }
        });
    });
</script>

 
@endpush
