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
                    
                                    {{-- <div class="col-md-2">
                                        <label for="kecamatan">Kecamatan</label>
                                        <select class="form-control" id="userDistrict" name="district_id">
                                            <option value="">Pilih Kecamatan</option>
                                        </select>
                                    </div> --}}
                    
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
                            <div class="col-md-2 col-sm-3 col-6">
                                <div class="card mb-2">
                                    <div class="card-body p-2">
                                        <h6 class="card-title">Potensi Kend</h6>
                                        <h4>{{ number_format($data['total']) }}</h4>
                                    </div>
                                </div>
                            </div>
                        
                            <div class="col-md-2 col-sm-3 col-6">
                                <div class="card mb-2">
                                    <div class="card-body p-2">
                                        <h6 class="card-title">PKB</h6>
                                        <h4>{{ number_format($data['pkb']) }}</h4>
                                    </div>
                                </div>
                            </div>
                        
                            <div class="col-md-2 col-sm-3 col-6">
                                <div class="card mb-2">
                                    <div class="card-body p-2">
                                        <h6 class="card-title">PKB Denda</h6>
                                        <h4>{{ number_format($data['pkb_denda']) }}</h4>
                                    </div>
                                </div>
                            </div>
                        
                            <div class="col-md-2 col-sm-3 col-6">
                                <div class="card mb-2">
                                    <div class="card-body p-2">
                                        <h6 class="card-title">PNBP</h6>
                                        <h4>{{ number_format($data['pnbp']) }}</h4>
                                    </div>
                                </div>
                            </div>
                        
                        
                            <div class="col-md-2 col-sm-3 col-6">
                                <div class="card mb-2">
                                    <div class="card-body p-2">
                                        <h6 class="card-title">Jasa Raharja</h6>
                                        <h4>{{ number_format($data['jr']) }}</h4>
                                    </div>
                                </div>
                            </div>
                        
                            <div class="col-md-2 col-sm-3 col-6">
                                <div class="card mb-2">
                                    <div class="card-body p-2">
                                        <h6 class="card-title"> Jasa Raharja Denda</h6>
                                        <h4>{{ number_format($data['jr_denda']) }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                      

                        <div class="row mt-2">
                            <div class="col-md-6">
                                <div id="map" style="height: 300px; width: 100%;"></div>
                            </div>
                
                            <div class="col-md-6">
                                <div id="potensiKendaraanChart" style="width:100%; height:300px;"></div>
                            </div>
                
                        </div>


                        <div class="row mt-3" >
                            <div id="chartKendaraanStatus"></div>
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
        var selectedDistrict = '{{ request('district_id') }}';

        if (selectedKabkota) {
            loadDistricts(selectedKabkota, selectedDistrict);
        }

        $('#userKabkota').on('change', function() {
            var kabkotaId = $(this).val();
            loadDistricts(kabkotaId, null);
        });

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
                $('#userDistrict').html('<option value="">Pilih Kecamatan</option>');
            }
        }
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Ambil data dari PHP (Blade)
        const potensiKend = @json($potensiKend);

        // Konversi data ke format Highcharts
        const categories = potensiKend.map(item => item.wilayah); // Kota di sumbu X
        const values = potensiKend.map(item => parseInt(item.total_vehicles) || 0); // Jumlah kendaraan di sumbu Y

        // Buat diagram garis (line chart)
        Highcharts.chart('potensiKendaraanChart', {
            chart: {
                type: 'line' // Ubah ke line chart
            },
            title: {
                text: 'Potensi Kendaraan Sengkuyung'
            },
            xAxis: {
                categories: categories, // Kota ada di sumbu X
                title: {
                    text: 'Kota'
                },
                labels: {
                    rotation: -45, // Miringkan teks agar terbaca
                    style: {
                        fontSize: '10px'
                    }
                }
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Jumlah Kendaraan',
                    align: 'high'
                }
            },
            series: [{
                name: 'Jumlah Kendaraan',
                data: values,
                marker: {
                    enabled: true, // Tampilkan titik-titik pada garis
                    radius: 4 // Ukuran titik
                }
            }]
        });
    });
</script>

<script>
    Highcharts.chart('chartKendaraanStatus', {
        chart: {
            type: 'column'
        },
        title: {
            text: 'Jumlah Kendaraan per wilayah berdasarkan Status'
        },
        xAxis: {
            categories: {!! json_encode($kotaList) !!}
        },
        yAxis: {
            min: 0,
            title: {
                text: 'Jumlah Kendaraan'
            },
            stackLabels: {
                enabled: true
            }
        },
        legend: {
            align: 'center',
            verticalAlign: 'top',
            floating: false,
            backgroundColor: Highcharts.defaultOptions.legend.backgroundColor || '#FFFFFF',
            borderWidth: 1
        },
        plotOptions: {
            column: {
                stacking: 'normal',
                dataLabels: {
                    enabled: true
                }
            }
        },
        series: {!! json_encode($seriesData) !!}
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
        var kotaData = @json($potensiKend);

        // kotaData.forEach(city => {
        //     if (city.lat && city.lng) {
        //         L.marker([city.lat, city.lng], { icon: motorIcon }) // Pakai ikon motor
        //             .addTo(map)
        //             .bindPopup(`<b>${city.wilayah}</b><br>Total Kendaraan: ${city.total_vehicles}`);
        //     }
        // });

        kotaData.forEach(city => {
            // Hanya tampilkan jika lat, lng ada dan total kendaraan lebih dari 0
            if (city.lat && city.lng && city.total_vehicles > 0) {
                L.marker([city.lat, city.lng], { icon: motorIcon }) // Pakai ikon motor
                    .addTo(map)
                    .bindPopup(`<b>${city.wilayah}</b><br>Total Kendaraan: ${city.total_vehicles}`);
            }
        });

    });
</script>



@endpush