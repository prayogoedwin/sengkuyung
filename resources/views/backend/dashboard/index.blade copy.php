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

                            <div class="row" style="margin-bottom:14px">
                            <div class="col-md-4">
                                <label for="kabupaten1">Kabupaten</label>
                                <select class="form-control" id="kabupaten1">
                                    <option value="">Pilih Kabupaten</option>
                                    <option value="kab1">Kabupaten 1</option>
                                    <option value="kab2">Kabupaten 2</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="kecamatan">Kecamatan</label>
                                <select class="form-control" id="kecamatan">
                                    <option value="">Pilih Kecamatan</option>
                                    <option value="kab1">Kec 1</option>
                                    <option value="kab2">Kec 2</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="status">Status</label>
                                <select class="form-control" id="status">
                                    <option value="">Pilih Status</option>
                                    <option value="aktif">Aktif</option>
                                    <option value="nonaktif">Nonaktif</option>
                                </select>
                            </div>
                            </div>

                          

                            <div class="col-lg-12 col-md-4 order-1">
                                <div class="row">

                                    <div class="col-lg-3 col-md-12 col-4 mb-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="card-title d-flex align-items-start justify-content-between">
                                                    <div class="avatar flex-shrink-0">
                                                        <img src="{{ asset('assets/nakerbisa_be/img/icons/unicons/chart-success.png') }}"
                                                            alt="chart success" class="rounded" />
                                                    </div>

                                                </div>
                                                <span class="fw-semibold d-block mb-1">Jumlah Pelaporan</span>
                                                <h3 class="card-title mb-2">10</h3>
                                                <!-- <small class="text-success fw-semibold"><i class="bx bx-up-arrow-alt"></i> +72.80%</small> -->
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-3 col-md-12 col-4 mb-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="card-title d-flex align-items-start justify-content-between">
                                                    <div class="avatar flex-shrink-0">
                                                        <img src="{{ asset('assets/nakerbisa_be/img/icons/unicons/chart-success.png') }}"
                                                            alt="chart success" class="rounded" />
                                                    </div>

                                                </div>
                                                <span class="fw-semibold d-block mb-1">Pelaporan Hari Ini</span>
                                                <h3 class="card-title mb-2">18x</h3>
                                                <!-- <small class="text-success fw-semibold"><i class="bx bx-up-arrow-alt"></i> +72.80%</small> -->
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-3 col-md-12 col-4 mb-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="card-title d-flex align-items-start justify-content-between">
                                                    <div class="avatar flex-shrink-0">
                                                        <img src="{{ asset('assets/nakerbisa_be/img/icons/unicons/chart-success.png') }}"
                                                            alt="chart success" class="rounded" />
                                                    </div>

                                                </div>
                                                <span class="fw-semibold d-block mb-1">Pelaporan bulan ini</span>
                                                <h3 class="card-title mb-2">2500x</h3>
                                                <!-- <small class="text-success fw-semibold"><i class="bx bx-up-arrow-alt"></i> +72.80%</small> -->
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-3 col-md-12 col-4 mb-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="card-title d-flex align-items-start justify-content-between">
                                                    <div class="avatar flex-shrink-0">
                                                        <img src="{{ asset('assets/nakerbisa_be/img/icons/unicons/chart-success.png') }}"
                                                            alt="chart success" class="rounded" />
                                                    </div>

                                                </div>
                                                <span class="fw-semibold d-block mb-1">Pelaporan tahun ini</span>
                                                <h3 class="card-title mb-2">19000x</h3>
                                                <!-- <small class="text-success fw-semibold"><i class="bx bx-up-arrow-alt"></i> +72.80%</small> -->
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            {{-- <div class="col-lg-12 col-md-4 order-1">
                                <div class="row">

                                    <div class="col-lg-3 col-md-12 col-4 mb-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="card-title d-flex align-items-start justify-content-between">
                                                    <div class="avatar flex-shrink-0">
                                                        <img src="{{ asset('assets/nakerbisa_be/img/icons/unicons/chart-success.png') }}"
                                                            alt="chart success" class="rounded" />
                                                    </div>

                                                </div>
                                                <span class="fw-semibold d-block mb-1">Jumlah Verifikasi</span>
                                                <h3 class="card-title mb-2">10</h3>
                                                <!-- <small class="text-success fw-semibold"><i class="bx bx-up-arrow-alt"></i> +72.80%</small> -->
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-3 col-md-12 col-4 mb-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="card-title d-flex align-items-start justify-content-between">
                                                    <div class="avatar flex-shrink-0">
                                                        <img src="{{ asset('assets/nakerbisa_be/img/icons/unicons/chart-success.png') }}"
                                                            alt="chart success" class="rounded" />
                                                    </div>

                                                </div>
                                                <span class="fw-semibold d-block mb-1">Verifikasi Hari Ini</span>
                                                <h3 class="card-title mb-2">18x</h3>
                                                <!-- <small class="text-success fw-semibold"><i class="bx bx-up-arrow-alt"></i> +72.80%</small> -->
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-3 col-md-12 col-4 mb-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="card-title d-flex align-items-start justify-content-between">
                                                    <div class="avatar flex-shrink-0">
                                                        <img src="{{ asset('assets/nakerbisa_be/img/icons/unicons/chart-success.png') }}"
                                                            alt="chart success" class="rounded" />
                                                    </div>

                                                </div>
                                                <span class="fw-semibold d-block mb-1">Verifikasi bulan ini</span>
                                                <h3 class="card-title mb-2">2500x</h3>
                                                <!-- <small class="text-success fw-semibold"><i class="bx bx-up-arrow-alt"></i> +72.80%</small> -->
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-3 col-md-12 col-4 mb-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="card-title d-flex align-items-start justify-content-between">
                                                    <div class="avatar flex-shrink-0">
                                                        <img src="{{ asset('assets/nakerbisa_be/img/icons/unicons/chart-success.png') }}"
                                                            alt="chart success" class="rounded" />
                                                    </div>

                                                </div>
                                                <span class="fw-semibold d-block mb-1">Verifikasi tahun ini</span>
                                                <h3 class="card-title mb-2">19000x</h3>
                                                <!-- <small class="text-success fw-semibold"><i class="bx bx-up-arrow-alt"></i> +72.80%</small> -->
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div> --}}

                           
                        </div>

                        <div id="chartContainer" class="mt-4" style="height: 400px;"></div>
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

<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $(document).ready(function() {
        let initialData = {
            kabupaten1: 10,
            kabupaten2: 5,
            status: 16
        };

        let chart = Highcharts.chart('chartContainer', {
            chart: { type: 'column' },
            title: { text: 'Statistik Pelaporan ' },
            xAxis: { categories: ['Kabupaten'] },
            yAxis: { title: { text: 'Jumlah Pelaporan' } },
            series: [
                { name: 'Semarang', data: [initialData.kabupaten1] },
                { name: 'Kudus', data: [initialData.kabupaten2] },
                { name: 'Demak', data: [initialData.status] }
            ]
        });

        $('#updateChart').click(function() {
            let newData = {
                kabupaten1: parseInt($('#kabupaten1').val()),
                kabupaten2: parseInt($('#kabupaten2').val()),
                status: parseInt($('#status').val())
            };

            chart.series[0].setData([newData.kabupaten1]);
            chart.series[1].setData([newData.kabupaten2]);
            chart.series[2].setData([newData.status]);
        });
    });
</script>


@endpush