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

                                <form method="GET" action="{{ route('dashboard') }}">
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
                                        $userKotaId = Auth::user()->kota ?? null;
                                        @endphp

                                        <div class="col-md-2">
                                            <label for="userKabkota">Kabupaten/Kota</label>
                                            <select class="form-control" id="userKabkota" name="kabkota_id">
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
                        
                                        <div class="col-md-2">
                                            <label for="kecamatan">Kecamatan</label>
                                            <select class="form-control" id="userDistrict" name="district_id">
                                                <option value="">Pilih Kecamatan</option>
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
                                            <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm"><i class="menu-icon tf-icons bx bx-refresh" style="padding-left:7px"></i></a>
                                        </div>
                                    </div>
                                </form>
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
                                                <span class="fw-semibold d-block mb-1">Jumlah Pendataan</span>
                                                <h3 class="card-title mb-2">{{ $data['total'] }}</h3>
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
                                                <span class="fw-semibold d-block mb-1">Menunggu  Verifikasi</span>
                                                <h3 class="card-title mb-2">{{ $data['menunggu_verifikasi'] }}</h3>
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
                                                <span class="fw-semibold d-block mb-1">Terverifikasi</span>
                                                <h3 class="card-title mb-2">{{ $data['verifikasi'] }}</h3>
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
                                                <span class="fw-semibold d-block mb-1">Verifikasi Ditolak</span>
                                                <h3 class="card-title mb-2">{{ $data['ditolak'] }}</h3>
                                                <!-- <small class="text-success fw-semibold"><i class="bx bx-up-arrow-alt"></i> +72.80%</small> -->
                                            </div>
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
        <div class="layout-overlay layout-menu-toggle"></div>
    </div>
@endsection


@push('js')

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

@endpush