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
                        <div class="row">
                            
                            <div class="row" style="margin-bottom:14px">
                            <div class="col-md-4">
                                <label for="kabupaten1">Kabupaten</label>
                                <select class="form-control" id="userKabkota" name="kabkota_id" >
                                    <option value="">Pilih Kabkota</option>
                                    @foreach ($kabkotas as $kbkt)
                                        <option value="{{ $kbkt->id }}">{{ $kbkt->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="kecamatan">Kecamatan</label>
                                <select class="form-control" id="userDistrict" name="district_id" >
                                    <option value="">Pilih Kecamatan</option>
                                    @if(request('kabkota_id'))
                                        @foreach ($districts as $district)
                                            <option value="{{ $district->id }}" {{ request('district_id') == $district->id ? 'selected' : '' }}>
                                                {{ $district->nama }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="status">Status</label>
                                <select id="statusVerifikasi" name="status_verifikasi_id" class="form-control">
                                    <option value="">Pilih Status</option>
                                    @foreach ($status_verifikasis as $status)
                                        <option value="{{ $status->id }}">
                                            {{ $status->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            </div>

                          

                        

                           
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
        $('#userKabkota').on('change', function() {
            var kabkotaId = $(this).val();
    
            if (kabkotaId) {
                $.ajax({
                    url: '{{ route("getDistricts") }}',
                    type: 'GET',
                    data: { kabkota_id: kabkotaId },
                    success: function(response) {
                        if (response.success) {
                            var districts = response.districts;
                            var options = '<option value="">Select Kecamatan</option>';
                            $.each(districts, function(index, district) {
                                options += '<option value="' + district.id + '">' + district.nama + '</option>';
                            });
                            $('#userDistrict').html(options);
                        } else {
                            $('#userDistrict').html('<option value="">No districts found</option>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching districts:', error);
                        $('#userDistrict').html('<option value="">Error fetching districts</option>');
                    }
                });
            } else {
                $('#userDistrict').html('<option value="">Select District</option>');
            }
        });
    });
</script>


@endpush