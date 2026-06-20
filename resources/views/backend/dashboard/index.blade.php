@extends('backend.template.backend')

@section('content')
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Layout container -->
            <div class="layout-page">
                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <!-- Content -->
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <div class="row">
                            
                            <div class="col-lg-12 mb-4 order-0" hidden>
                                <div class="card">
                                    <div class="d-flex align-items-end row">
                                        <div class="col-sm-7">
                                            <div class="card-body">
                                                <h5 class="card-title text-primary">Selamat Datang Admin! 🎉</h5>
                                                <p class="mb-4">
                                                    Selalu update user dan password anda untuk mejaga keamanan website
                                                </p>

                                                <a href="javascript:;" class="btn btn-sm btn-outline-primary">Update
                                                    Password</a>
                                            </div>
                                        </div>
                                        <div class="col-sm-5 text-center text-sm-left">
                                            <div class="card-body pb-0 px-0 px-md-4">
                                                <!-- <img
                                      src="../assets/img/illustrations/man-with-laptop-light.png"
                                      height="140"
                                      alt="View Badge User"
                                      data-app-dark-img="illustrations/man-with-laptop-dark.png"
                                      data-app-light-img="illustrations/man-with-laptop-light.png"
                                    /> -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                            
                            <div class="row" style="margin-bottom:14px">

                                <form id="dashboardWilayahFilterForm" method="GET" action="{{ route('dashboard') }}">
                                    <div class="row">
                                        {{-- <div class="col-md-2">
                                            <label for="kabupaten1">Kabupaten</label>
                                            <select class="form-control" id="userKabkota" name="kabkota_id">
                                                <option value="">Pilih Kabkota</option>
                                                @foreach ($kabkotas as $kbkt)
                                                    <option value="{{ $kbkt->id }}" {{ request('kabkota_id') == $kbkt->id ? 'selected' : '' }}>
                                                        {{ $kbkt->nama }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div> --}}

                                        @php
                                        $userRoleId = Auth::user()->roles[0]->id ?? null;
                                        $userKotaId = $scopedKabkotaId ?? (Auth::user()->kota ?? null);
                                        $userLokasiSamsat = $userLokasiSamsat ?? (Auth::user()->lokasi_samsat ?? null);
                                        $isScopedKabkota = $isScopedKabkota ?? false;
                                        $isKecamatanScope = $isKecamatanScope ?? false;
                                        $isKelurahanScope = $isKelurahanScope ?? false;
                                        $userKecamatanSamsat = $userKecamatanSamsat ?? (Auth::user()->kecamatan_samsat ?? Auth::user()->kecamatan ?? null);
                                        $userKelurahanSamsat = $userKelurahanSamsat ?? (Auth::user()->kelurahan_samsat ?? Auth::user()->kelurahan ?? null);
                                        @endphp

                                        <div class="col-md-2">
                                            <label for="userKabkota">Kabupaten/Kota</label>
                                            @if ($isScopedKabkota && ($userKotaId ?? null))
                                                <input type="hidden" name="kabkota_id" value="{{ $userKotaId }}">
                                            @endif
                                            <select class="form-control" id="userKabkota" name="{{ $isScopedKabkota ? '' : 'kabkota_id' }}" {{ $isScopedKabkota ? 'disabled' : '' }}>
                                                <option value="">Pilih Kabkota</option>
                                                @foreach ($kabkotas as $kbkt)
                                                    @if ($isScopedKabkota)
                                                        @if ($kbkt->id == $userKotaId)
                                                            <option value="{{ $kbkt->id }}" selected>{{ $kbkt->nama }}</option>
                                                        @endif
                                                    @else
                                                        <option value="{{ $kbkt->id }}" {{ request('kabkota_id') == $kbkt->id ? 'selected' : '' }}>{{ $kbkt->nama }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-2">
                                            <label for="lokasiSamsat">Lokasi Samsat</label>
                                            <select class="form-control" id="lokasiSamsat" name="lokasi_samsat">
                                                <option value="">Pilih Lokasi Samsat</option>
                                            </select>
                                        </div>
                        
                                        <div class="col-md-2">
                                            <label for="kecamatanSamsat">Kecamatan Samsat</label>
                                            <select class="form-control" id="kecamatanSamsat" name="kecamatan_samsat" {{ $isKecamatanScope ? 'disabled' : '' }}>
                                                <option value="">Pilih Kecamatan Samsat</option>
                                            </select>
                                        </div>

                                        <div class="col-md-2">
                                            <label for="kelurahanSamsat">Kelurahan Samsat</label>
                                            <select class="form-control" id="kelurahanSamsat" name="kelurahan_samsat" {{ $isKelurahanScope ? 'disabled' : '' }}>
                                                <option value="">Pilih Kelurahan Samsat</option>
                                            </select>
                                        </div>
                        
                                        <div class="col-md-2">
                                            <label for="status">Status</label>
                                            <select id="statusVerifikasi" name="status_verifikasi_id" class="form-control">
                                                <option value="">Pilih Status</option>
                                                @foreach ($statuss as $status)
                                                    <option value="{{ $status->id }}" {{ request('status_verifikasi_id') == $status->id ? 'selected' : '' }}>
                                                        {{ $status->nama }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-12"></div>

                                        <div class="col-md-2 mt-2">
                                            <label for="tanggal">Tanggal Start</label>
                                            <input type="date" class="form-control" name="tanggal_start" value="{{ request('tanggal_start') }}">
                                        </div>
                                        <div class="col-md-2 mt-2">
                                            <label for="tanggal">Tanggal End</label>
                                            <input type="date" class="form-control" name="tanggal_end" value="{{ request('tanggal_end') }}">
                                        </div>
                                        <div class="col-md-2 mt-4">
                                            <div class="d-flex gap-2">
                                                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                                                <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm">
                                                    <i class="menu-icon tf-icons bx bx-refresh"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
          
                            </div>


    

                          

                            <div class="col-lg-12 col-md-12 order-1">
                                @php
                                    // Counter D2D hanya tampil untuk role di atas kabkota.
                                    // BackController sudah mengirim flag $showD2dStats; fallback dihitung dari role login.
                                    $showD2dStats = $showD2dStats ?? (Auth::check() && ! Auth::user()->hasAnyRole(['kabkota', 'kecamatan', 'kelurahan']));

                                    $statRows = [
                                        [
                                            'title' => 'Pendataan Reguler',
                                            'cards' => [
                                                ['key' => 'jumlah_tunggakan', 'label' => 'Jumlah Tunggakan', 'color' => '#0d6efd'],
                                                ['key' => 'jumlah_sudah_pendataan', 'label' => 'Jumlah Sudah Pendataan', 'color' => '#198754'],
                                                ['key' => 'jumlah_belum_pendataan', 'label' => 'Jumlah Belum Pendataan', 'color' => '#fd7e14'],
                                                ['key' => 'menunggu_verifikasi', 'label' => 'Menunggu Verifikasi', 'color' => '#6f42c1'],
                                                ['key' => 'verifikasi', 'label' => 'Terverifikasi', 'color' => '#20c997'],
                                                ['key' => 'ditolak', 'label' => 'Verifikasi Ditolak', 'color' => '#dc3545'],
                                            ],
                                        ],
                                    ];

                                    if ($showD2dStats) {
                                        $statRows[] = [
                                            'title' => 'Pendataan D2D',
                                            'cards' => [
                                                ['key' => 'jumlah_tunggakan_d2d', 'label' => 'Jumlah Tunggakan D2D', 'color' => '#0d6efd'],
                                                ['key' => 'jumlah_sudah_pendataan_d2d', 'label' => 'Jumlah Sudah Pendataan D2D', 'color' => '#198754'],
                                                ['key' => 'jumlah_belum_pendataan_d2d', 'label' => 'Jumlah Belum Pendataan D2D', 'color' => '#fd7e14'],
                                                ['key' => 'menunggu_verifikasi_d2d', 'label' => 'Menunggu Verifikasi D2D', 'color' => '#6f42c1'],
                                                ['key' => 'verifikasi_d2d', 'label' => 'Terverifikasi D2D', 'color' => '#20c997'],
                                                ['key' => 'ditolak_d2d', 'label' => 'Verifikasi Ditolak D2D', 'color' => '#dc3545'],
                                            ],
                                        ];
                                    }
                                @endphp

                                @foreach ($statRows as $row)
                                    <div class="mb-2">
                                        <h6 class="fw-semibold text-uppercase text-muted mb-2" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                            {{ $row['title'] }}
                                        </h6>
                                        <div class="row g-3 mb-2">
                                            @foreach ($row['cards'] as $card)
                                                <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 col-12">
                                                    <div class="card h-100 border-0 shadow-sm">
                                                        <div class="card-body p-3 text-center">
                                                            <div class="text-uppercase fw-semibold mb-2"
                                                                style="font-size: 0.72rem; line-height: 1.3; color: {{ $card['color'] }};">
                                                                {{ $card['label'] }}
                                                            </div>
                                                            <h3 class="mb-0 fw-bold" style="color: {{ $card['color'] }};">
                                                                {{ number_format($data[$card['key']] ?? 0) }}
                                                            </h3>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
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
        <!-- Overlay -->
        <div class="layout-overlay layout-menu-toggle"></div>
    </div>
@endsection


@push('js')

<script>
    $(document).ready(function() {
        var profileLokasiSamsat = '{{ $profileLokasiSamsat ?? '' }}';
        var lockLokasiSamsat = @json($lockLokasiSamsat ?? false);

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

        function samsatDropdownValue(samsat) {
            return String(samsat.id || samsat.id_wilayah_samsat || '');
        }

        function isSamsatSelected(samsat, selectedSamsat) {
            if (!selectedSamsat) {
                return lockLokasiSamsat && profileLokasiSamsat && samsatMatchesProfile(samsat);
            }
            return String(selectedSamsat) === String(samsat.id || '');
        }

        function applyLokasiSamsatDropdownState(selectedSamsat) {
            if (lockLokasiSamsat && profileLokasiSamsat) {
                $('#lokasiSamsat').val(String(profileLokasiSamsat)).prop('disabled', true);
                return;
            }
            if (selectedSamsat) {
                var hasOption = $('#lokasiSamsat option').filter(function () {
                    return String($(this).val()) === String(selectedSamsat);
                }).length > 0;
                if (hasOption) {
                    $('#lokasiSamsat').val(String(selectedSamsat)).prop('disabled', false);
                    return;
                }
            }
            if (profileLokasiSamsat && !selectedSamsat) {
                $('#lokasiSamsat').val(String(profileLokasiSamsat)).prop('disabled', false);
                return;
            }
            $('#lokasiSamsat').prop('disabled', false);
        }
        var forcedKecamatanSamsat = '{{ $userKecamatanSamsat ?? '' }}';
        var forcedKelurahanSamsat = '{{ $userKelurahanSamsat ?? '' }}';
        var selectedKabkota = $('#userKabkota').val();
        var selectedLokasiSamsat = '{{ $selectedLokasiSamsat ?: request('lokasi_samsat') }}';
        var selectedKecamatanSamsat = '{{ request('kecamatan_samsat') }}' || forcedKecamatanSamsat;
        var selectedKelurahanSamsat = '{{ request('kelurahan_samsat') }}' || forcedKelurahanSamsat;

        if (selectedKabkota) {
            loadSamsats(selectedKabkota, selectedLokasiSamsat);
        }

        $('#userKabkota').on('change', function() {
            var kabkotaId = $(this).val();
            loadSamsats(kabkotaId, null);
        });

        $('#lokasiSamsat').on('change', function() {
            $('#kelurahanSamsat').html('<option value="">Pilih Kelurahan Samsat</option>');
        });

        $('#kecamatanSamsat').on('change', function() {
            var kecamatanSamsatId = $(this).val();
            loadKelurahanSamsat(kecamatanSamsatId, selectedKelurahanSamsat);
            selectedKelurahanSamsat = null;
        });

        function loadSamsats(kabkotaId, selectedSamsat) {
            if (!kabkotaId) {
                $('#lokasiSamsat').html('<option value="">Pilih Lokasi Samsat</option>');
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
                            var value = samsatDropdownValue(samsat);
                            var isSelected = isSamsatSelected(samsat, selectedSamsat) ? 'selected' : '';
                            options += '<option value="' + value + '" ' + isSelected + '>' + samsat.lokasi + ' [' + value + ']</option>';
                        }
                    });

                    $('#lokasiSamsat').html(options);

                    applyLokasiSamsatDropdownState(selectedSamsat);

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

        function loadDistricts(kabkotaId, selectedDistrict) {
            if (kabkotaId) {
                $.ajax({
                    url: '{{ route("getDistricts") }}',
                    type: 'GET',
                    data: { kabkota_id: kabkotaId },
                    success: function(response) {
                        var options = '<option value="">Pilih Kecamatan</option>';
                        $.each(response.districts, function(index, district) {
                            var isSelected = (selectedDistrict == district.id) ? 'selected' : '';
                            options += '<option value="' + district.id + '" ' + isSelected + '>' + district.nama + '</option>';
                        });
                        $('#userDistrict').html(options);
                    },
                    error: function() {
                        $('#userDistrict').html('<option value="">Error fetching districts</option>');
                    }
                });
            } else {
                $('#kecamatanSamsat').html('<option value="">Pilih Kecamatan Samsat</option>');
            }
        }

        if (lockLokasiSamsat && profileLokasiSamsat) {
            $('#userKabkota').prop('disabled', true);
        }

        // Select yang disabled tidak ikut submit (GET); aktifkan sebelum kirim agar kabkota_id & lokasi_samsat konsisten di URL/cache.
        $('#dashboardWilayahFilterForm').on('submit', function () {
            $(this).find('select:disabled').prop('disabled', false);
        });
    });
</script>

@endpush