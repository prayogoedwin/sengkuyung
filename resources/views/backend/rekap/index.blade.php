@extends('backend.template.plain')

@section('content')
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Layout container -->
            <div class="full-page-container">
                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <!-- Content -->
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <div class="row mb-5">
                       

                            <form method="GET" action="{{ route('rekap.index') }}">
                                <div class="row">
                                    <div class="col-md-2">
                                        <label for="kabupaten1">Kabupaten</label>
                                        <select class="form-control" id="userKabkota" name="kabkota_id">
                                            <option value="">Semua Kabkota</option>
                                            @foreach ($kabkotas as $kbkt)
                                                <option value="{{ $kbkt->id }}" {{ request('kabkota_id') == $kbkt->id ? 'selected' : '' }}>
                                                    {{ $kbkt->nama }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-2">
                                        <label for="lokasiSamsat">Lokasi Samsat</label>
                                        <select class="form-control" id="lokasiSamsat" name="lokasi_samsat">
                                            <option value="">Semua Lokasi Samsat</option>
                                        </select>
                                    </div>

                                    <div class="col-md-2">
                                        <label for="kecamatanSamsat">Kecamatan Samsat</label>
                                        <select class="form-control" id="kecamatanSamsat" name="kecamatan_samsat">
                                            <option value="">Semua Kecamatan Samsat</option>
                                        </select>
                                    </div>

                                    <div class="col-md-2">
                                        <label for="kelurahanSamsat">Kelurahan Samsat</label>
                                        <select class="form-control" id="kelurahanSamsat" name="kelurahan_samsat">
                                            <option value="">Semua Kelurahan Samsat</option>
                                        </select>
                                    </div>
                    
                                    <div class="col-md-2">
                                        <label for="status">Status</label>
                                        <select id="statusKend" name="status_id" class="form-control">
                                            <option value="">Semua Status</option>
                                            @foreach ($statuss as $status)
                                                <option value="{{ $status->id }}" {{ request('status_id') == $status->id ? 'selected' : '' }}>
                                                    {{ $status->nama }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-2">
                                        <label for="status">Status Verifikasi</label>
                                        <select id="statusVerifikasi" name="status_verifikasi_id" class="form-control">
                                            <option value="">Semua Status Verifikasi</option>
                                            @foreach ($status_verifikasis as $status_ver)
                                                <option value="{{ $status_ver->id }}" {{ request('status_verifikasi_id') == $status_ver->id ? 'selected' : '' }}>
                                                    {{ $status_ver->nama }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                    
                                    <div class="col-md-2">
                                        <label for="tanggal">Tanggal Start</label>
                                        <input type="date" class="form-control" name="tanggal_start" value="{{ request('tanggal_start') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="tanggal">Tanggal End</label>
                                        <input type="date" class="form-control" name="tanggal_end" value="{{ request('tanggal_end') }}">
                                    </div>
                                    <div class="col-md-2 mt-4">
                                        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                                        <a href="{{ route('rekap.index') }}" class="btn btn-warning btn-sm"><i class="menu-icon tf-icons bx bx-refresh" style="padding-left:7px"></i></a>
                                        <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm"><i class="menu-icon tf-icons bx bx-home" style="padding-left:7px"></i></a>
                                    </div>
                                </div>
                            </form>

                          

                        

                           
                        </div>

                        <div class="row text-center" style="margin-top:-25px">
                            <div class="col-md-4 col-sm-6 col-12">
                                <div class="card mb-2">
                                    <div class="card-body p-2">
                                        <h6 class="card-title">Potensi Kend</h6>
                                        <h4>{{ number_format($data['total_potensi']) }}</h4>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 col-sm-6 col-12">
                                <div class="card mb-2">
                                    <div class="card-body p-2">
                                        <h6 class="card-title">Jumlah Sudah Terdata</h6>
                                        <h4>{{ number_format($data['total_terdata']) }}</h4>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 col-sm-6 col-12">
                                <div class="card mb-2">
                                    <div class="card-body p-2">
                                        <h6 class="card-title">Nominal PKB</h6>
                                        <h4>{{ number_format($data['pkb']) }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                      

                        <div class="row mt-2">
                            <div class="col-md-6">
                                <div id="chartPerbandinganNominal" style="width:100%; height:320px;"></div>
                            </div>
                            <div class="col-md-6">
                                <div id="map" style="height: 320px; width: 100%;"></div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">Top 5 Pendataan Kota</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Kota</th>
                                                        <th>Jumlah</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($topKota as $item)
                                                        <tr>
                                                            <td>{{ $item->wilayah }}</td>
                                                            <td>{{ number_format($item->total) }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="2" class="text-center">Tidak ada data</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">Top 5 Pendataan Kecamatan</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Kecamatan</th>
                                                        <th>Jumlah</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($topKecamatan as $item)
                                                        <tr>
                                                            <td>{{ $item->wilayah }}</td>
                                                            <td>{{ number_format($item->total) }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="2" class="text-center">Tidak ada data</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">Top 5 Pendataan Kelurahan</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Kelurahan</th>
                                                        <th>Jumlah</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($topKelurahan as $item)
                                                        <tr>
                                                            <td>{{ $item->wilayah }}</td>
                                                            <td>{{ number_format($item->total) }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="2" class="text-center">Tidak ada data</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
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
        <!-- Overlay -->
        
        
    </div>
@endsection


@push('js')

<!-- Load Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>


<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


<script>
    $(document).ready(function() {
        var selectedKabkota = $('#userKabkota').val();
        var selectedLokasiSamsat = '{{ request('lokasi_samsat') }}';
        var selectedKecamatanSamsat = '{{ request('kecamatan_samsat') }}';
        var selectedKelurahanSamsat = '{{ request('kelurahan_samsat') }}';

        if (selectedKabkota) {
            loadLokasiSamsat(selectedKabkota, selectedLokasiSamsat, function() {
                if (selectedLokasiSamsat) {
                    loadKecamatanSamsat(selectedLokasiSamsat, selectedKecamatanSamsat, function() {
                        if (selectedKecamatanSamsat) {
                            loadKelurahanSamsat(selectedKecamatanSamsat, selectedKelurahanSamsat);
                        }
                    });
                }
            });
        }

        $('#userKabkota').on('change', function() {
            var kabkotaId = $(this).val();
            if (kabkotaId) {
                loadLokasiSamsat(kabkotaId, null);
            } else {
                resetLokasiChain();
            }
        });

        $('#lokasiSamsat').on('change', function() {
            var lokasiSamsatId = $(this).val();
            if (lokasiSamsatId) {
                loadKecamatanSamsat(lokasiSamsatId, null);
            } else {
                $('#kecamatanSamsat').html('<option value="">Semua Kecamatan Samsat</option>');
                $('#kelurahanSamsat').html('<option value="">Semua Kelurahan Samsat</option>');
            }
        });

        $('#kecamatanSamsat').on('change', function() {
            var kecamatanSamsatId = $(this).val();
            if (kecamatanSamsatId) {
                loadKelurahanSamsat(kecamatanSamsatId, null);
            } else {
                $('#kelurahanSamsat').html('<option value="">Semua Kelurahan Samsat</option>');
            }
        });

        function resetLokasiChain() {
            $('#lokasiSamsat').html('<option value="">Semua Lokasi Samsat</option>');
            $('#kecamatanSamsat').html('<option value="">Semua Kecamatan Samsat</option>');
            $('#kelurahanSamsat').html('<option value="">Semua Kelurahan Samsat</option>');
        }

        function loadLokasiSamsat(kabkotaId, selectedValue, callback) {
            $.ajax({
                url: '{{ route("getSamsatByKabkota") }}',
                type: 'GET',
                data: { kabkota_id: kabkotaId },
                success: function(response) {
                    var options = '<option value="">Semua Lokasi Samsat</option>';
                    if (response.success) {
                        $.each(response.samsats, function(index, samsat) {
                            var value = samsat.id_wilayah_samsat ?? samsat.id;
                            var isSelected = (selectedValue == value) ? 'selected' : '';
                            var label = samsat.lokasi ?? '-';
                            options += '<option value="' + value + '" ' + isSelected + '>' + label + '</option>';
                        });
                    }
                    $('#lokasiSamsat').html(options);
                    $('#kecamatanSamsat').html('<option value="">Semua Kecamatan Samsat</option>');
                    $('#kelurahanSamsat').html('<option value="">Semua Kelurahan Samsat</option>');
                    if (typeof callback === 'function') callback();
                },
                error: function() {
                    resetLokasiChain();
                }
            });
        }

        function loadKecamatanSamsat(lokasiSamsatId, selectedValue, callback) {
            $.ajax({
                url: '{{ route("getSamsatKecamatan") }}',
                type: 'GET',
                data: { lokasi_samsat_id: lokasiSamsatId },
                success: function(response) {
                    var options = '<option value="">Semua Kecamatan Samsat</option>';
                    if (response.success) {
                        $.each(response.kecamatans, function(index, kecamatan) {
                            var isSelected = (selectedValue == kecamatan.id_kecamatan) ? 'selected' : '';
                            options += '<option value="' + kecamatan.id_kecamatan + '" ' + isSelected + '>' + kecamatan.kecamatan + '</option>';
                        });
                    }
                    $('#kecamatanSamsat').html(options);
                    $('#kelurahanSamsat').html('<option value="">Semua Kelurahan Samsat</option>');
                    if (typeof callback === 'function') callback();
                },
                error: function() {
                    $('#kecamatanSamsat').html('<option value="">Semua Kecamatan Samsat</option>');
                    $('#kelurahanSamsat').html('<option value="">Semua Kelurahan Samsat</option>');
                }
            });
        }

        function loadKelurahanSamsat(kecamatanSamsatId, selectedValue, callback) {
            $.ajax({
                url: '{{ route("getSamsatKelurahan") }}',
                type: 'GET',
                data: { kecamatan_samsat_id: kecamatanSamsatId },
                success: function(response) {
                    var options = '<option value="">Semua Kelurahan Samsat</option>';
                    if (response.success) {
                        $.each(response.kelurahans, function(index, kelurahan) {
                            var isSelected = (selectedValue == kelurahan.id_kelurahan) ? 'selected' : '';
                            options += '<option value="' + kelurahan.id_kelurahan + '" ' + isSelected + '>' + kelurahan.kelurahan + '</option>';
                        });
                    }
                    $('#kelurahanSamsat').html(options);
                    if (typeof callback === 'function') callback();
                },
                error: function() {
                    $('#kelurahanSamsat').html('<option value="">Semua Kelurahan Samsat</option>');
                }
            });
        }
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const barChartData = @json($barChartData);
        const categories = (barChartData && Array.isArray(barChartData.categories) && barChartData.categories.length)
            ? barChartData.categories
            : ['-'];
        const potensiData = (barChartData && Array.isArray(barChartData.potensi) && barChartData.potensi.length)
            ? barChartData.potensi
            : [0];
        const terdataData = (barChartData && Array.isArray(barChartData.terdata) && barChartData.terdata.length)
            ? barChartData.terdata
            : [0];
        const pkbData = (barChartData && Array.isArray(barChartData.pkb) && barChartData.pkb.length)
            ? barChartData.pkb
            : [0];

        function renderNominalChart() {
            if (typeof Highcharts === 'undefined') {
                return;
            }

            Highcharts.chart('chartPerbandinganNominal', {
                chart: {
                    type: 'column'
                },
                title: {
                    text: (barChartData && barChartData.title) ? barChartData.title : 'Perbandingan Data'
                },
                xAxis: {
                    categories: categories,
                    crosshair: true
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: 'Nilai / Jumlah'
                    }
                },
                tooltip: {
                    shared: true
                },
                plotOptions: {
                    column: {
                        grouping: true,
                        dataLabels: {
                            enabled: false
                        }
                    }
                },
                series: [{
                    name: 'Potensi Kendaraan',
                    data: potensiData
                }, {
                    name: 'Sudah Terdata',
                    data: terdataData
                }, {
                    name: 'Nominal PKB',
                    data: pkbData
                }]
            });
        }

        function loadHighchartsFallbackAndRender() {
            const fallback = document.createElement('script');
            fallback.src = 'https://cdn.jsdelivr.net/npm/highcharts@11/highcharts.js';
            fallback.onload = renderNominalChart;
            document.head.appendChild(fallback);
        }

        if (typeof Highcharts === 'undefined') {
            loadHighchartsFallbackAndRender();
        } else {
            renderNominalChart();
        }
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        

        var koordinat = @json($koordinats);

        var map = L.map('map').setView([koordinat.lat, koordinat.lng], 8);

        // Tambahkan Tile Layer dari OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // Buat Custom Icon Motor
        var motorIcon = L.icon({
            iconUrl: "{{ asset('assets/icon-mtr.png') }}", // Path ikon di public/
            iconSize: [64, 64], // Ukuran ikon
            iconAnchor: [16, 32], // Posisi ikon di titik koordinat
            popupAnchor: [0, -32] // Posisi popup saat ikon diklik
        });

        // Data dari Laravel (dikirim melalui compact di controller)
        var kotaData = @json($mapPoints);

        kotaData.forEach(city => {
            // Hanya tampilkan jika lat, lng ada dan total kendaraan lebih dari 0
            if (city.lat && city.lng && city.total_vehicles > 0) {
                L.marker([city.lat, city.lng], { icon: motorIcon }) // Pakai ikon motor
                    .addTo(map)
                    .bindPopup(`<b>${city.wilayah}</b><br>Total Kendaraan: ${city.total_vehicles}`);
            }
        });

        setTimeout(function () {
            map.invalidateSize();
        }, 250);

    });
</script>



@endpush