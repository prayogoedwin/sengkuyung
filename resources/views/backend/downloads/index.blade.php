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
                            <div class="col-xl-6">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row align-items-center m-l-0">
                                            <div class="col-sm-6">

                                            </div>
                                            
                                        </div>

                                        
                                        <form>
                                        <div class="row mb-3">
                                            <div class="col-md-12">
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
                                            <div class="col-md-12">
                                                <label for="userKabkota">Kabupaten/Kota</label>
                                                <select class="form-control" id="userKabkota" name="kabkota_id" >
                                                    <option value="">Pilih Kabkota</option>
                                                    @foreach ($kabkotas as $kbkt)
                                                        <option value="{{ $kbkt->id }}">{{ $kbkt->nama }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-12 mb-2">
                                                <label for="kecamatan">Kecamatan</label>
                                                <select class="form-control" id="userDistrict" name="district_id" >
                                                    <option value="">Pilih Kecamatan</option>
                                                </select>
                                            </div>
                                           
                                            <div class="col-md-6">
                                                <label for="tanggal_start">Tanggal Akhir TNKB Start</label>
                                                <input type="date" id="tanggal_start" class="form-control">
                                            </div>

                                            <div class="col-md-6">
                                                <label for="tanggal_end">Tanggal Akhir TNKB End</label>
                                                <input type="date" id="tanggal_end" class="form-control">
                                            </div>
                                           
                                            <div class="col-md-12 mt-2">
                                                <button class="btn btn-primary mt-2" id="submitFilter">Download CSV</button>
                                                <button class="btn btn-success mt-2" id="submitFilterPdf">Download PDF</button>
                                                <button class="btn btn-secondary mt-2" id="resetFilter">Reset</button>
                                            </div>
                                        </div>
                                        </form>


                                        
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

<script>
document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("submitFilter").addEventListener("click", function (e) {
        e.preventDefault();

        let statusVerifikasi = document.getElementById("statusVerifikasi").value;
        let kabkota = document.getElementById("userKabkota").value;
        let district = document.getElementById("userDistrict").value;
        let tanggalStart = document.getElementById("tanggal_start").value;
        let tanggalEnd = document.getElementById("tanggal_end").value;

        let queryParams = new URLSearchParams({
            status_verifikasi_id: statusVerifikasi,
            kabkota_id: kabkota,
            district_id: district,
            tanggal_start: tanggalStart,
            tanggal_end: tanggalEnd
        }).toString();

        window.location.href = `/dapur/download-csv?${queryParams}`;
    });

    document.getElementById("resetFilter").addEventListener("click", function (e) {
        e.preventDefault();
        document.querySelector("form").reset();
    });
});
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        document.getElementById("submitFilterPdf").addEventListener("click", function (e) {
            e.preventDefault();
    
            let statusVerifikasi = document.getElementById("statusVerifikasi").value;
            let kabkota = document.getElementById("userKabkota").value;
            let district = document.getElementById("userDistrict").value;
            let tanggalStart = document.getElementById("tanggal_start").value;
            let tanggalEnd = document.getElementById("tanggal_end").value;
    
            let queryParams = new URLSearchParams({
                status_verifikasi_id: statusVerifikasi,
                kabkota_id: kabkota,
                district_id: district,
                tanggal_start: tanggalStart,
                tanggal_end: tanggalEnd
            }).toString();
    
            window.location.href = `/dapur/download-pdf?${queryParams}`;
        });
    
        document.getElementById("resetFilter").addEventListener("click", function (e) {
            e.preventDefault();
            document.querySelector("form").reset();
        });
    });
    </script>

 
@endpush
