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


                                        <div class="row">
                                            <!-- Data Subjek Pajak -->
                                            <div class="col-md-6">
                                                <h5 class="fw-bold">DATA SUBJEK PAJAK</h5>
                                                <p>Nama : </p>
                                                <p>Alamat : </p>
                                                <p>Kecamatan : </p>
                                                <p>Kelurahan : </p>
                                                <p>NPWPD/NIK : </p>
                                                <p>No Telp : </p>
                                                <p>E-Mail : </p>
                        
                                                <h5 class="fw-bold mt-4">DATA OBJEK PAJAK</h5>
                                                <p>No Polisi : </p>
                                                <p>NOPD : </p>
                                                <p>Jenis Kendaraan : </p>
                                                <p>Merk : </p>
                                                <p>Tipe : </p>
                                                <p>Tahun Pembuatan : </p>
                                                <p>Warna Kendaraan : </p>
                                                <p>No Rangka : </p>
                                                <p>No Mesin : </p>
                                                <p>Tgl Akhir PKB : </p>
                                            </div>
                        
                                            <!-- Lokasi Geotagging & Verifikasi -->
                                            <div class="col-md-6">
                                                <h5 class="fw-bold">LOKASI GEOTAGGING GMAPS</h5>
                                                <p>LAMPIRAN FOTO KTP</p>
                                                <p>LAMPIRAN FOTO KENDARAAN</p>
                                                <p>LAMPIRAN FOTO DATA DUKUNG</p>
                                                <p>LAMPIRAN SURAT PERNYATAAN</p>
                        
                                                <h5 class="fw-bold mt-4">VERIFIKASI STATUS KENDARAAN</h5>
                                                <p>No Polisi : </p>
                                                <p>Tgl Pendataan : </p>
                                                <p>Status Objek Pajak : </p>
                        
                                                <div class="d-flex align-items-center">
                                                    <input type="text" class="form-control me-2" readonly>
                                                    <button class="btn btn-danger">DITOLAK</button>
                                                </div>
                        
                                                <label class="mt-2">Alasan* (Bila ditolak)</label>
                                                <textarea class="form-control" rows="2"></textarea>
                                            </div>
                                        </div>
                        
                                        <!-- Tombol Aksi -->
                                        <div class="d-flex justify-content-end mt-4">
                                            <button class="btn btn-warning me-2">Batal</button>
                                            <button class="btn btn-info text-white">Simpan</button>
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
    </div>
@endsection


@push('js')

@endpush
