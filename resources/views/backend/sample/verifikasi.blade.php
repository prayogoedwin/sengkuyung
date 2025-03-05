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
                                                    <label for="lokasi">Verifikasi</label>
                                                    <select id="lokasi" class="form-control">
                                                        <option selected>Belum</option>
                                                        {{-- <option>Kab Banyumas</option> --}}
                                                    </select>
                                                </div>
                                                <br/>
                                        
                                                <div class="form-group col-md-12">
                                                    <label for="kecamatan">Kecamatan</label>
                                                    <select id="kecamatan" class="form-control">
                                                        <option>Kec. Parangkusumo</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <br/>
                                       
                                            <div class="row">
                                                <br/>
                                                <div class="form-group col-md-6">
                                                    <label for="tglakhirpkb">Tgl Awal</label>
                                                    <input type="date" class="form-control" id="tglakhirpkb">
                                                </div>
                                                <br/>
                                                <div class="form-group col-md-6">
                                                    <label for="tglakhirpkb">Tgl Akhir</label>
                                                    <input type="date" class="form-control" id="tglakhirpkb">
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
                                                    <button type="button" class="btn btn-info btn-block">Tampilkan</button>
                                                </div>
                                            </div>
                                        </form>


                                    </div>

                                     
                                </div>
                            </div>

                           

                            <div class="col-xl-12" style="margin-top:10px">
                                <div class="card">
                                  
                                     <div class="card-body">
                                        <div class="row align-items-center m-l-0">
                                            <div class="col-sm-6">

                                            </div>
                                            <div class="col-sm-6 text-end">
                                                {{-- <button class="btn btn-success btn-sm btn-round has-ripple"
                                                    data-bs-toggle="modal" data-bs-target="#modal-report"><i
                                                        class="feather icon-plus"></i> Add
                                                    Data</button> --}}
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table id="simpletable" class="table table-bordered table-striped mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>No</th>
                                                        <th>Tanggal Pendataan</th>
                                                        <th>No Polisi</th>
                                                        <th>Nama</th>
                                                        <th>Alamat</th>
                                                        <th>Kecamatan</th>
                                                        <th>Status</th>
                                                        <th>Verifikasi</th>
                                                        <th>Options</th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    <tr>
                                                        <td>No</td>
                                                        <td>22/10/2025</td>
                                                        <td>H 1234  IJ</td>
                                                        <td>Hamdalah</td>
                                                        <td>Semarang Timur Permai</td>
                                                        <td>Genuk</td>
                                                        <td>Dimiliki</td>
                                                        <td>Belum</td>
                                                        <td><a href="{{ route('verifikasi-detail.index') }}"><i class="bx bx-file"></i></a></td>

                                                    </tr>
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
    </div>
@endsection


@push('js')

@endpush
