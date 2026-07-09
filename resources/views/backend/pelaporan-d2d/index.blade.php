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
                        <div class="row mb-3">
                            <div class="col-12">
                                <h4 class="mb-0">Pelaporan D2D</h4>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xl-6">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row align-items-center m-l-0">
                                            <div class="col-sm-6">

                                            </div>
                                            
                                        </div>

                                        
                                        <form id="pelaporanFilterForm">
                                        <div class="row mb-3">
                                            <div class="col-md-12">
                                                <label for="userKabkota">Kabupaten/Kota</label>
                                                <select class="form-control" id="userKabkota" name="kabkota_id" {{ !empty($isScopedKabkota) && $isScopedKabkota ? 'disabled' : '' }}>
                                                    <option value="">Pilih Kabkota</option>
                                                    @foreach ($kabkotas as $kbkt)
                                                        <option value="{{ $kbkt->id }}" {{ (string) (request('kabkota_id', $selectedKabkotaId ?? '')) === (string) $kbkt->id ? 'selected' : '' }}>{{ $kbkt->nama }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-12">
                                                <label for="lokasiSamsat">Lokasi Samsat</label>
                                                <select class="form-control" id="lokasiSamsat" name="lokasi_samsat" {{ !empty($isLokasiSamsatLocked) && $isLokasiSamsatLocked ? 'disabled' : '' }}>
                                                    <option value="">Pilih Lokasi Samsat</option>
                                                </select>
                                            </div>

                                            <div class="col-md-12">
                                                <label for="kecamatanSamsat">Kecamatan Samsat</label>
                                                <select class="form-control" id="kecamatanSamsat" name="kecamatan_samsat" {{ !empty($isKecamatanSamsatLocked) && $isKecamatanSamsatLocked ? 'disabled' : '' }}>
                                                    <option value="">Pilih Kecamatan Samsat</option>
                                                </select>
                                            </div>

                                            <div class="col-md-12 mb-2">
                                                <label for="kelurahanSamsat">Kelurahan Samsat</label>
                                                <select class="form-control" id="kelurahanSamsat" name="kelurahan_samsat" {{ !empty($isKelurahanSamsatLocked) && $isKelurahanSamsatLocked ? 'disabled' : '' }}>
                                                    <option value="">Pilih Kelurahan Samsat</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="tanggal_start">Tanggal Pendataan Start</label>
                                                <input type="date" id="tanggal_start" name="tanggal_start" class="form-control" value="{{ request('tanggal_start') }}">
                                            </div>

                                            <div class="col-md-6">
                                                <label for="tanggal_end">Tanggal Pendataan End</label>
                                                <input type="date" id="tanggal_end" name="tanggal_end" class="form-control" value="{{ request('tanggal_end') }}">
                                            </div>

                                            <div class="col-md-12 mt-2 mb-2">
                                                <label for="kecamatan">Tipe Pelaporan</label>
                                                <select class="form-control" id="tipe" name="tipe">
                                                    @if (!empty($isRekapOnlyRole) && $isRekapOnlyRole)
                                                        <option value="2" {{ (string) request('tipe', '2') === '2' ? 'selected' : '' }}>Rekap</option>
                                                        <option value="3" {{ (string) request('tipe') === '3' ? 'selected' : '' }}>Rekap Per Orang</option>
                                                    @else
                                                        <option value="1" {{ (string) request('tipe', '1') === '1' ? 'selected' : '' }}>Jurnal</option>
                                                        <option value="2" {{ (string) request('tipe') === '2' ? 'selected' : '' }}>Rekap</option>
                                                        <option value="3" {{ (string) request('tipe') === '3' ? 'selected' : '' }}>Rekap Per Orang</option>
                                                    @endif
                                                </select>
                                            </div>
                                           
                                            <div class="col-md-12 mt-2">
                                                <button class="btn btn-primary mt-2" id="submitFilter">Download CSV</button>
                                                <button class="btn btn-info mt-2" id="submitFilterExcel">Download Excel</button>
                                                <button class="btn btn-success mt-2" id="submitFilterPdf">Download PDF</button>
                                                <button class="btn btn-warning mt-2" id="submitFilterView">Tampilkan</button>
                                                <button class="btn btn-secondary mt-2" id="resetFilter">Reset</button>
                                            </div>
                                        </div>
                                        </form>


                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                        @include('backend.pelaporan._table')
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
        var profileLokasiSamsat = '{{ $userLokasiSamsat ?? '' }}';
        var lockLokasiSamsat = @json((bool) ($isLokasiSamsatLocked ?? false));
        var forcedKecamatanSamsat = '{{ $selectedKecamatanSamsatId ?? '' }}';
        var forcedKelurahanSamsat = '{{ $selectedKelurahanSamsatId ?? '' }}';
        var selectedLokasiSamsat = @json(request('lokasi_samsat', ''));
        var selectedKecamatanSamsat = @json(request('kecamatan_samsat', '')) || forcedKecamatanSamsat || '';
        var selectedKelurahanSamsat = @json(request('kelurahan_samsat', '')) || forcedKelurahanSamsat || '';

        function samsatMatchesProfile(samsat) {
            if (!profileLokasiSamsat) {
                return true;
            }

            var id = String(samsat.id ?? '');
            var wilayahId = String(samsat.id_wilayah_samsat ?? '');
            var profile = String(profileLokasiSamsat);

            return profile === id || profile === wilayahId
                || (profile.replace(/^0+/, '') !== '' && profile.replace(/^0+/, '') === id.replace(/^0+/, ''))
                || (profile.replace(/^0+/, '') !== '' && profile.replace(/^0+/, '') === wilayahId.replace(/^0+/, ''));
        }

        function loadSamsatsByKabkota(kabkotaId, selectedSamsat) {
            if (!kabkotaId) {
                $('#lokasiSamsat').html('<option value="">Pilih Lokasi Samsat</option>');
                $('#kecamatanSamsat').html('<option value="">Pilih Kecamatan Samsat</option>');
                $('#kelurahanSamsat').html('<option value="">Pilih Kelurahan Samsat</option>');
                return;
            }

            $.ajax({
                url: '{{ route("getSamsatByKabkota") }}',
                type: 'GET',
                data: { kabkota_id: kabkotaId },
                success: function(response) {
                    if (!response.success) {
                        $('#lokasiSamsat').html('<option value="">Lokasi Samsat tidak ditemukan</option>');
                        return;
                    }

                    var options = '<option value="">Pilih Lokasi Samsat</option>';
                    $.each(response.samsats, function(index, samsat) {
                        if (!lockLokasiSamsat || samsatMatchesProfile(samsat)) {
                            var value = String(samsat.id || samsat.id_wilayah_samsat || '');
                            var isSelected = (selectedSamsat && String(selectedSamsat) === String(value))
                                || (!selectedSamsat && profileLokasiSamsat && samsatMatchesProfile(samsat))
                                ? 'selected'
                                : '';
                            options += '<option value="' + value + '" ' + isSelected + '>' + samsat.lokasi + ' [' + value + ']</option>';
                        }
                    });

                    $('#lokasiSamsat').html(options);

                    if (lockLokasiSamsat && profileLokasiSamsat) {
                        $('#lokasiSamsat').val(String(profileLokasiSamsat)).prop('disabled', true);
                    } else if (profileLokasiSamsat) {
                        $('#lokasiSamsat').val(String(profileLokasiSamsat)).prop('disabled', false);
                    } else {
                        $('#lokasiSamsat').prop('disabled', false);
                    }

                    loadKecamatanSamsat(kabkotaId, selectedKecamatanSamsat);
                },
                error: function() {
                    $('#lokasiSamsat').html('<option value="">Gagal mengambil lokasi samsat</option>');
                }
            });
        }

        function loadKecamatanSamsat(kabkotaId, selectedKecamatan) {
            if (!kabkotaId) {
                $('#kecamatanSamsat').html('<option value="">Pilih Kecamatan Samsat</option>');
                $('#kelurahanSamsat').html('<option value="">Pilih Kelurahan Samsat</option>');
                return;
            }

            $.ajax({
                url: '{{ route("getSamsatKecamatan") }}',
                type: 'GET',
                data: { kabkota_id: kabkotaId },
                success: function(response) {
                    var options = '<option value="">Pilih Kecamatan Samsat</option>';
                    if (response.success) {
                        $.each(response.kecamatans, function(index, kecamatan) {
                            var isSelected = (selectedKecamatan && String(selectedKecamatan) === String(kecamatan.id_kecamatan)) ? 'selected' : '';
                            options += '<option value="' + kecamatan.id_kecamatan + '" ' + isSelected + '>' + kecamatan.kecamatan + '</option>';
                        });
                    }
                    $('#kecamatanSamsat').html(options);

                    if (forcedKecamatanSamsat) {
                        $('#kecamatanSamsat').val(String(forcedKecamatanSamsat)).prop('disabled', true);
                    }

                    $('#kecamatanSamsat').trigger('change');
                },
                error: function() {
                    $('#kecamatanSamsat').html('<option value="">Gagal mengambil kecamatan samsat</option>');
                }
            });
        }

        function loadKelurahanSamsat(kecamatanSamsatId, selectedKelurahan) {
            if (!kecamatanSamsatId) {
                $('#kelurahanSamsat').html('<option value="">Pilih Kelurahan Samsat</option>');
                return;
            }

            $.ajax({
                url: '{{ route("getSamsatKelurahan") }}',
                type: 'GET',
                data: { kecamatan_samsat_id: kecamatanSamsatId },
                success: function(response) {
                    var options = '<option value="">Pilih Kelurahan Samsat</option>';
                    if (response.success) {
                        $.each(response.kelurahans, function(index, kelurahan) {
                            var isSelected = (selectedKelurahan && String(selectedKelurahan) === String(kelurahan.id_kelurahan)) ? 'selected' : '';
                            options += '<option value="' + kelurahan.id_kelurahan + '" ' + isSelected + '>' + kelurahan.kelurahan + '</option>';
                        });
                    }
                    $('#kelurahanSamsat').html(options);
                    if (forcedKelurahanSamsat) {
                        $('#kelurahanSamsat').val(String(forcedKelurahanSamsat)).prop('disabled', true);
                    }
                },
                error: function() {
                    $('#kelurahanSamsat').html('<option value="">Gagal mengambil kelurahan samsat</option>');
                }
            });
        }

        $('#userKabkota').on('change', function() {
            var kabkotaId = $(this).val();
            loadSamsatsByKabkota(kabkotaId, null);
        });

        $('#lokasiSamsat').on('change', function() {
            $('#kelurahanSamsat').html('<option value="">Pilih Kelurahan Samsat</option>');
        });

        $('#kecamatanSamsat').on('change', function() {
            loadKelurahanSamsat($(this).val(), selectedKelurahanSamsat);
            selectedKelurahanSamsat = null;
        });

        var initialKabkotaId = $('#userKabkota').val();
        if (initialKabkotaId) {
            loadSamsatsByKabkota(initialKabkotaId, selectedLokasiSamsat);
        }

    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const lockedKabkotaId = @json($selectedKabkotaId ?? '');
        const isKabkotaLocked = @json((bool) ($isScopedKabkota ?? false));
        const isRekapOnlyRole = @json((bool) ($isRekapOnlyRole ?? false));

        const buildQuery = () => {
            const kabkota = document.getElementById("userKabkota").value;
            const lokasiSamsat = document.getElementById("lokasiSamsat").value;
            const kecamatanSamsat = document.getElementById("kecamatanSamsat").value;
            const kelurahanSamsat = document.getElementById("kelurahanSamsat").value;
            const tanggalStart = document.getElementById("tanggal_start").value;
            const tanggalEnd = document.getElementById("tanggal_end").value;
            const tipe = document.getElementById("tipe").value;

            return new URLSearchParams({
                kabkota_id: kabkota,
                lokasi_samsat: lokasiSamsat,
                kecamatan_samsat: kecamatanSamsat,
                kelurahan_samsat: kelurahanSamsat,
                tanggal_start: tanggalStart,
                tanggal_end: tanggalEnd,
                tipe: tipe
            }).toString();
        };

        document.getElementById("submitFilter").addEventListener("click", function (e) {
            e.preventDefault();
            window.location.href = `{{ route($pelaporanRouteCsv ?? 'pelaporan-d2d.csv') }}?${buildQuery()}`;
        });

        document.getElementById("submitFilterExcel").addEventListener("click", function (e) {
            e.preventDefault();
            window.location.href = `{{ route($pelaporanRouteExcel ?? 'pelaporan-d2d.excel') }}?${buildQuery()}`;
        });

        document.getElementById("submitFilterPdf").addEventListener("click", function (e) {
            e.preventDefault();
            window.location.href = `{{ route($pelaporanRoutePdf ?? 'pelaporan-d2d.pdf') }}?${buildQuery()}`;
        });

        document.getElementById("submitFilterView").addEventListener("click", function (e) {
            e.preventDefault();
            window.location.href = `{{ route($pelaporanRouteIndex ?? 'pelaporan-d2d.index') }}?${buildQuery()}&tampilkan=1`;
        });

        document.getElementById("resetFilter").addEventListener("click", function (e) {
            e.preventDefault();
            window.location.href = `{{ route($pelaporanRouteIndex ?? 'pelaporan-d2d.index') }}`;
        });
    });
</script>

 
@endpush
