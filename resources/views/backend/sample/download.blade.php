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
                            <div class="col-xl-8">
                                <div class="card">
                                    <div class="card-body">
                                        
                                        <form>
                                            <div class="form-row">
                                            
                                                <div class="form-group col-md-12">
                                                    <label for="lokasi">Lokasi</label>
                                                    <select id="lokasi" class="form-control">
                                                        <option selected>Pilih Lokasi</option>
                                                        <option>Kab Banyumas</option>
                                                    </select>
                                                </div>
                                                <br/>
                                        
                                                <div class="form-group col-md-12">
                                                    <label for="kecamatan">Kecamatan</label>
                                                    <select id="kecamatan" class="form-control">
                                                        <option selected>Pilih Kecamatan</option>
                                                        <option>Kec. Parangkusumo</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <br/>
                                                <div class="form-group col-md-12">
                                                    <label for="tglakhirpkb">Tgl Akhir PKB</label>
                                                    <input type="date" class="form-control" id="tglakhirpkb">
                                                </div>
                                                <br/>
                                                <div class="form-group col-md-12">
                                                    <label for="warnatnkb">Warna TNKB</label>
                                                    <input type="text" class="form-control" id="warnatnkb" placeholder="Masukkan Warna TNKB">
                                                </div>
                                            </div>
                                            <br/>
                                            <br/>
                                            <div class="row">
                                                <br/>
                                                <div class="form-group col-md-6">
                                                    <button type="button" class="btn btn-warning btn-block">Filter Ulang</button>
                                                </div>
                                                <br/>
                                                <div class="form-group col-md-6">
                                                    <button type="button" class="btn btn-info btn-block">Download</button>
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

@endpush
