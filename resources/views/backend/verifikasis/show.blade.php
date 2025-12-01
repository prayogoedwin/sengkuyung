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
                                                        <tr>
                                                            <th width="30%">{{ $data->file0_ket }}</th>
                                                            <td>
                                                                @if(isset($decryptedFiles['file0']))
                                                                    <a href="{{ $decryptedFiles['file0'] }}" target="_blank">
                                                                        <img src="{{ $decryptedFiles['file0'] }}" alt="{{ $data->file0_ket }}" style="width: 50%;">
                                                                    </a>
                                                                @else
                                                                    <a href="{{ asset($data->file0_url) }}" target="_blank">
                                                                        <img src="{{ asset($data->file0_url) }}" alt="{{ $data->file0_ket }}" style="width: 50%;">
                                                                    </a>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>{{ $data->file1_ket }}</th>
                                                            <td>
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
                                                
                                                <form action="{{ route('verifikasi.status', ['id' => \App\Helpers\Helper::encodeId($data->id)]) }}" method="POST">
                                                @csrf
                                                    <table class="table table-bordered">
                                                        <tr>
                                                            <th width="30%">STATUS</th>
                                                            <td>{{ $data->status_name }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th width="30%">VERIFIKASI</th>
                                                            <td>
                                                                <input type="hidden" name="id" value="{{ \App\Helpers\Helper::encodeId($data->id) }}">

                                                                @php
                                                                    $backgroundColor = '';
                                                                    if ($data->status_verifikasi == 1) {
                                                                        $backgroundColor = 'background-color: yellow; color: black;';
                                                                    } elseif ($data->status_verifikasi == 2) {
                                                                        $backgroundColor = 'background-color: green; color: white;';
                                                                    } elseif ($data->status_verifikasi == 3) {
                                                                        $backgroundColor = 'background-color: red; color: white;';
                                                                    }
                                                                @endphp

                                                                <select class="form-control" id="statusVerifikasi" name="status_verifikasi_id" required style="{{ $backgroundColor }}">
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
                                                                <textarea name="keterangan" style="width:100%">{{ $data->file9_ket }}</textarea>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th colspan=2 style="text-align:right">
                                                                <button type="submit" class="btn btn-primary">Update Status</button>
                                                            </th>
                                                        </tr>
                                                    </table>
                                                </form>
                                            </div>
                                        </div>

                                        {{-- ACTIVITY LOGS SECTION - TOGGLE --}}
                                        <div class="row mt-4">
                                            <div class="col-12">
                                                <div class="card border">
                                                    <div class="card-header d-flex justify-content-between align-items-center py-3" 
                                                         style="cursor: pointer; background-color: #f8f9fa;" 
                                                         data-bs-toggle="collapse" 
                                                         data-bs-target="#activityLogsCollapse" 
                                                         aria-expanded="false" 
                                                         aria-controls="activityLogsCollapse">
                                                        <h5 class="fw-bold mb-0">
                                                            <i class="bx bx-chevron-right me-1" id="collapseIcon" style="transition: transform 0.3s;"></i>
                                                            RIWAYAT AKTIVITAS 
                                                            <span class="badge bg-primary ms-2">{{ $activityLogs->count() }}</span>
                                                        </h5>
                                                        <small class="text-muted">
                                                            <i class="bx bx-mouse"></i> Klik untuk melihat
                                                        </small>
                                                    </div>
                                                    
                                                    <div class="collapse" id="activityLogsCollapse">
                                                        <div class="card-body">
                                                            @if($activityLogs->isEmpty())
                                                                <div class="alert alert-info mb-0">
                                                                    <i class="bx bx-info-circle me-1"></i>
                                                                    Belum ada riwayat aktivitas.
                                                                </div>
                                                            @else
                                                                @foreach($activityLogs as $index => $log)
                                                                    @php
                                                                        $requestData = is_string($log->request_data) 
                                                                            ? json_decode($log->request_data, true) 
                                                                            : $log->request_data;
                                                                        
                                                                        if (is_array($requestData)) {
                                                                            unset($requestData['_token']);
                                                                        }
                                                                    @endphp
                                                                    
                                                                    <div class="card mb-3 border shadow-sm">
                                                                        <div class="card-header d-flex justify-content-between align-items-center" 
                                                                             style="background-color: #e9ecef;">
                                                                            <span>
                                                                                <strong>#{{ $index + 1 }}</strong> - 
                                                                                <span class="badge {{ $log->method == 'POST' ? 'bg-success' : 'bg-warning' }}">
                                                                                    {{ $log->method }}
                                                                                </span>
                                                                                <span class="badge bg-info ms-1">{{ $log->origin ?? 'unknown' }}</span>
                                                                            </span>
                                                                            <small class="text-muted">
                                                                                <i class="bx bx-time me-1"></i>
                                                                                {{ $log->created_at->format('d M Y H:i:s') }}
                                                                            </small>
                                                                        </div>
                                                                        <div class="card-body">
                                                                            <div class="row">
                                                                                {{-- Info Log --}}
                                                                                <div class="col-md-4">
                                                                                    <h6 class="fw-bold text-muted mb-2">
                                                                                        <i class="bx bx-user me-1"></i> Info
                                                                                    </h6>
                                                                                    <table class="table table-sm table-borderless mb-0">
                                                                                        <tr>
                                                                                            <th width="40%">User</th>
                                                                                            <td>
                                                                                                <span class="badge bg-secondary">
                                                                                                    {{ $log->user->name ?? 'Guest' }}
                                                                                                </span>
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <th>IP Address</th>
                                                                                            <td><code>{{ $log->ip_address }}</code></td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <th>Waktu</th>
                                                                                            <td>{{ $log->created_at->format('d M Y H:i:s') }}</td>
                                                                                        </tr>
                                                                                    </table>
                                                                                </div>
                                                                                
                                                                                {{-- Request Data --}}
                                                                                <div class="col-md-8">
                                                                                    <h6 class="fw-bold text-muted mb-2">
                                                                                        <i class="bx bx-data me-1"></i> Request Data
                                                                                    </h6>
                                                                                    @if(is_array($requestData) && count($requestData) > 0)
                                                                                        <div class="table-responsive">
                                                                                            <table class="table table-sm table-bordered mb-0">
                                                                                                <thead class="table-light">
                                                                                                    <tr>
                                                                                                        <th width="30%">Field</th>
                                                                                                        <th>Value</th>
                                                                                                    </tr>
                                                                                                </thead>
                                                                                                <tbody>
                                                                                                    @foreach($requestData as $key => $value)
                                                                                                        <tr>
                                                                                                            <td><code>{{ $key }}</code></td>
                                                                                                            <td>
                                                                                                                @if(is_array($value))
                                                                                                                    <pre class="mb-0 bg-light p-2 rounded" style="font-size: 12px;">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                                                                                                @elseif(is_null($value))
                                                                                                                    <em class="text-muted">null</em>
                                                                                                                @else
                                                                                                                    {{ $value }}
                                                                                                                @endif
                                                                                                            </td>
                                                                                                        </tr>
                                                                                                    @endforeach
                                                                                                </tbody>
                                                                                            </table>
                                                                                        </div>
                                                                                    @else
                                                                                        <p class="text-muted mb-0">
                                                                                            <em>Tidak ada data</em>
                                                                                        </p>
                                                                                    @endif
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        {{-- END ACTIVITY LOGS --}}

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
    document.addEventListener('DOMContentLoaded', function() {
        const collapseEl = document.getElementById('activityLogsCollapse');
        const icon = document.getElementById('collapseIcon');
        
        if (collapseEl && icon) {
            collapseEl.addEventListener('shown.bs.collapse', function () {
                icon.style.transform = 'rotate(90deg)';
            });
            
            collapseEl.addEventListener('hidden.bs.collapse', function () {
                icon.style.transform = 'rotate(0deg)';
            });
        }
    });
</script>
@endpush