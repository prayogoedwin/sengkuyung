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
                                            <div class="col-md-6 mb-4">
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
                                                            <th>Provinsi</th>
                                                            <td>{{ $data->prov_name }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>Kabupaten/Kota</th>
                                                            <td>{{ $data->kota_name }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>Kecamatan</th>
                                                            <td>{{ $data->kec_name }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>Kelurahan</th>
                                                            <td>{{ $data->desa_name }}</td>
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
                                                <table class="table table-bordered">
                                                    <tr>
                                                        <th width="30%">Koordinat</th>
                                                        <td>
                                                            <a href="https://www.google.com/maps?q={{ $data->lat }},{{ $data->lng }}" target="_blank">
                                                                {{ $data->lat }}, {{ $data->lng }}
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    
                                                </table>


                                                <h5 class="fw-bold mt-4">LAMPIRAN</h5>
                                                @if($data->status != 2)
                                                    <table class="table table-bordered">
                                                        {{-- File 0 --}}
                                                        <tr>
                                                            <th width="30%">{{ $data->file0_ket }}</th>
                                                            <td>
                                                                @if(isset($decryptedFiles['file0']))
                                                                    {{-- File encrypted (KTP) - tampilkan dari base64 --}}
                                                                    <a href="{{ $decryptedFiles['file0'] }}" target="_blank">
                                                                        <img src="{{ $decryptedFiles['file0'] }}" alt="{{ $data->file0_ket }}" style="width: 50%;">
                                                                    </a>
                                                                @else
                                                                    {{-- File biasa - tampilkan normal --}}
                                                                    <a href="{{ asset($data->file0_url) }}" target="_blank">
                                                                        <img src="{{ asset($data->file0_url) }}" alt="{{ $data->file0_ket }}" style="width: 50%;">
                                                                    </a>
                                                                @endif
                                                            </td>
                                                        </tr>

                                                        {{-- File 1 --}}
                                                        <tr>
                                                            <th>{{ $data->file1_ket }}</th>
                                                            <td>
                                                                {{-- @if(isset($decryptedFiles['file1']))
                                                                   
                                                                    <a href="{{ $decryptedFiles['file1'] }}" target="_blank">
                                                                        <img src="{{ $decryptedFiles['file1'] }}" alt="{{ $data->file1_ket }}" style="width: 50%;">
                                                                    </a>
                                                                @else
                                                              
                                                                    <a href="{{ asset($data->file1_url) }}" target="_blank">
                                                                        <img src="{{ asset($data->file1_url) }}" alt="{{ $data->file1_ket }}" style="width: 50%;">
                                                                    </a>
                                                                @endif --}}

                                                                <a href="{{ asset($data->file1_url) }}" target="_blank">
                                                                        <img src="{{ asset($data->file1_url) }}" alt="{{ $data->file1_ket }}" style="width: 50%;">
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                @else
                                                    <iframe srcdoc="{!! htmlentities($html) !!}" style="width:100%; height:500px; border:1px solid #ddd;"></iframe>
                                                @endif

                                                <h5 class="fw-bold mt-4">VERIFIKASI STATUS KENDARAAN</h5>
                                                <form action="{{ route('verifikasi.status') }}" method="POST"> <!-- Replace with your actual route -->
                                                @csrf <!-- Include CSRF token for security -->
                                                    <table class="table table-bordered">
                                                        <tr>
                                                            <th width="30%">STATUS</th>
                                                            <td>{{ $data->status_name }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th width="30%">VERIFIKASI</th>
                                                            <td>
                                                                {{-- {{ $data->status_verifikasi_name }} --}}
                                                                <input  type="hidden" name="id" value="{{ \App\Helpers\Helper::encodeId($data->id) }}">

                                                                @php
                                                                    // Determine the background color based on the status
                                                                    $backgroundColor = '';
                                                                    if ($data->status_verifikasi == 1) {
                                                                        $backgroundColor = 'background-color: yellow; color: black;'; // Blue
                                                                    } elseif ($data->status_verifikasi == 2) {
                                                                        $backgroundColor = 'background-color: green; color: white;'; // green
                                                                    } elseif ($data->status_verifikasi == 3) {
                                                                        $backgroundColor = 'background-color: red; color: white;'; // Red
                                                                    }
                                                                @endphp

                                                                <select class="form-control" id="statusVerifikasi" name="status_verifikasi_id" required  style="{{ $backgroundColor }}">
                                                                    <option value="">PILIH STATUS</option>
                                                                    @foreach ($status_verifikasis as $status)
                                                                        <option value="{{ $status->id }}" 
                                                                            {{ $data->status_verifikasi == $status->id ? 'selected' : '' }}>
                                                                            {{ $status->nama }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>

                                                            </td>

                                                        </tr>
                                                        <tr>
                                                            <th>Keterangan</th>
                                                            <td>
                                                                <textarea name="keterangan" style="widht:100%">{{ $data->file9_ket }}</textarea>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th colspan=2 style="text-align:right"> <button type="submit" class="btn btn-primary">Update Status</button></th>

                                                        </tr>
                                                    </table>
                                                
                                                </form>
                                            </div>
                                        </div>
                        
                                        <!-- Tombol Aksi -->
                                        <div class="d-flex justify-content-end mt-4">
                                            <button class="btn btn-warning me-2" hidden>Batal</button>
                                            <button class="btn btn-info text-white" hidden>Simpan</button>
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
