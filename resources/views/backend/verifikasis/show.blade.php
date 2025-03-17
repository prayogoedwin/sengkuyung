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
                                                <table class="table table-bordered">
                                                    <tbody>
                                                        <tr>
                                                            <th width="30%">Nama</th>
                                                            <td>{{ $data->nama }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>Alamat</th>
                                                            <td>{{ $data->alamat }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>Kecamatan</th>
                                                            <td>{{ $data->kecamatan }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>Kelurahan</th>
                                                            <td>{{ $data->kelurahan }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>NPWPD/NIK</th>
                                                            <td>{{ $data->nik }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                        
                                                <h5 class="fw-bold mt-4">DATA OBJEK PAJAK</h5>
                                                <table class="table table-bordered">
                                                    <tbody>
                                                        <tr>
                                                            <th width="30%">No Polisi</th>
                                                            <td>{{ $data->nopol }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>NOPD</th>
                                                            <td>{{ $data->nopd }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>Jenis Kendaraan</th>
                                                            <td>{{ $data->jenis_kbm }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>Merk</th>
                                                            <td>{{ $data->merk }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>Tipe</th>
                                                            <td>{{ $data->tipe }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>Tahun Pembuatan</th>
                                                            <td>{{ $data->tahun }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
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
