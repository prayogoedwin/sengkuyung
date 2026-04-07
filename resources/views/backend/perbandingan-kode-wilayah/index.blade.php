@extends('backend.template.backend')

@section('content')
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <div class="layout-page">
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Perbandingan Kode Wilayah</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="mb-3">Sumber: SengWilayah</h6>

                                        <div class="mb-3">
                                            <label class="form-label">Pilih Kota</label>
                                            <select class="form-control" id="leftKota">
                                                <option value="">Pilih Kota</option>
                                                @foreach ($kotas as $kota)
                                                    <option value="{{ $kota->id }}">{{ $kota->nama }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <form method="POST" action="{{ route('perbandingan-kode-wilayah.update-wilayah') }}"
                                            class="border rounded p-3 mb-3" id="formKota" style="display: none;">
                                            @csrf
                                            <input type="hidden" name="level" value="kota">
                                            <input type="hidden" name="wilayah_id" id="kotaWilayahId">
                                            <div class="mb-2">
                                                <label class="form-label">Kode Kota (readonly)</label>
                                                <input type="text" class="form-control" name="kode_wilayah"
                                                    id="kotaKodeReadonly" readonly>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label">Kode Samsat</label>
                                                <input type="text" class="form-control" name="kode_samsat"
                                                    id="kotaKodeSamsatInput" required>
                                            </div>
                                            <button class="btn btn-primary btn-sm" type="submit">Submit Update Kota</button>
                                        </form>

                                        <div class="mb-3">
                                            <label class="form-label">Pilih Kecamatan</label>
                                            <select class="form-control" id="leftKecamatan">
                                                <option value="">Pilih Kecamatan</option>
                                            </select>
                                        </div>

                                        <form method="POST" action="{{ route('perbandingan-kode-wilayah.update-wilayah') }}"
                                            class="border rounded p-3 mb-3" id="formKecamatan" style="display: none;">
                                            @csrf
                                            <input type="hidden" name="level" value="kecamatan">
                                            <input type="hidden" name="wilayah_id" id="kecamatanWilayahId">
                                            <div class="mb-2">
                                                <label class="form-label">Kode Kecamatan (readonly)</label>
                                                <input type="text" class="form-control" name="kode_wilayah"
                                                    id="kecamatanKodeReadonly" readonly>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label">Kode Samsat</label>
                                                <input type="text" class="form-control" name="kode_samsat"
                                                    id="kecamatanKodeSamsatInput" required>
                                            </div>
                                            <button class="btn btn-primary btn-sm" type="submit">Submit Update
                                                Kecamatan</button>
                                        </form>

                                        <div class="mb-3">
                                            <label class="form-label">Pilih Kelurahan</label>
                                            <select class="form-control" id="leftKelurahan">
                                                <option value="">Pilih Kelurahan</option>
                                            </select>
                                        </div>

                                        <form method="POST" action="{{ route('perbandingan-kode-wilayah.update-wilayah') }}"
                                            class="border rounded p-3 mb-3" id="formKelurahan" style="display: none;">
                                            @csrf
                                            <input type="hidden" name="level" value="kelurahan">
                                            <input type="hidden" name="wilayah_id" id="kelurahanWilayahId">
                                            <div class="mb-2">
                                                <label class="form-label">Kode Kelurahan (readonly)</label>
                                                <input type="text" class="form-control" name="kode_wilayah"
                                                    id="kelurahanKodeReadonly" readonly>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label">Kode Samsat</label>
                                                <input type="text" class="form-control" name="kode_samsat"
                                                    id="kelurahanKodeSamsatInput" required>
                                            </div>
                                            <button class="btn btn-primary btn-sm" type="submit">Submit Update
                                                Kelurahan</button>
                                        </form>
                                    </div>

                                    <div class="col-md-6">
                                        <h6 class="mb-3">Sumber: SengSaamsat + Wilayah Samsat</h6>

                                        <div class="mb-3">
                                            <label class="form-label">Pilih Kota (Samsat)</label>
                                            <select class="form-control" id="rightSamsat">
                                                <option value="">Pilih Kota / Samsat</option>
                                                @foreach ($samsats as $samsat)
                                                    <option value="{{ $samsat->id }}">
                                                        {{ $samsat->lokasi }} ({{ $samsat->id }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">ID Samsat (readonly)</label>
                                            <input type="text" class="form-control" id="rightSamsatIdReadonly" readonly>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Pilih Kecamatan</label>
                                            <select class="form-control" id="rightKecamatan">
                                                <option value="">Pilih Kecamatan</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">ID Kecamatan (readonly)</label>
                                            <input type="text" class="form-control" id="rightKecamatanIdReadonly"
                                                readonly>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Pilih Kelurahan</label>
                                            <select class="form-control" id="rightKelurahan">
                                                <option value="">Pilih Kelurahan</option>
                                            </select>
                                        </div>

                                        <form method="POST"
                                            action="{{ route('perbandingan-kode-wilayah.update-kelurahan') }}"
                                            class="border rounded p-3" id="rightFormKelurahan" style="display: none;">
                                            @csrf
                                            <div class="mb-2">
                                                <label class="form-label">ID Kelurahan (readonly)</label>
                                                <input type="text" class="form-control" name="id_kelurahan"
                                                    id="rightKelurahanIdReadonly" readonly required>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label">kode_dagri_kelelurahan</label>
                                                <input type="text" class="form-control" name="kode_dagri_kelelurahan"
                                                    id="rightKodeDagriKelurahan">
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label">kode_dagri_kecamatan</label>
                                                <input type="text" class="form-control" name="kode_dagri_kecamatan"
                                                    id="rightKodeDagriKecamatan">
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label">kode_dagri_kabkota</label>
                                                <input type="number" class="form-control" name="kode_dagri_kabkota"
                                                    id="rightKodeDagriKabkota">
                                            </div>
                                            <button class="btn btn-primary btn-sm" type="submit">Submit Update
                                                Kelurahan</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="content-backdrop fade"></div>
                </div>
            </div>
        </div>
        <div class="layout-overlay layout-menu-toggle"></div>
    </div>
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            const leftKota = $('#leftKota');
            const leftKecamatan = $('#leftKecamatan');
            const leftKelurahan = $('#leftKelurahan');

            function resetLeftBelow(level) {
                if (level === 'kota') {
                    leftKecamatan.html('<option value="">Pilih Kecamatan</option>');
                    leftKelurahan.html('<option value="">Pilih Kelurahan</option>');
                    $('#formKecamatan').hide();
                    $('#formKelurahan').hide();
                }
                if (level === 'kecamatan') {
                    leftKelurahan.html('<option value="">Pilih Kelurahan</option>');
                    $('#formKelurahan').hide();
                }
            }

            function fillWilayahForm(prefix, item) {
                $('#' + prefix + 'WilayahId').val(item.id);
                $('#' + prefix + 'KodeReadonly').val(item.kode || '');
                $('#' + prefix + 'KodeSamsatInput').val(item.kode_samsat || '');
                $('#form' + prefix.charAt(0).toUpperCase() + prefix.slice(1)).show();
            }

            function loadChildren(parentId, targetSelect, placeholder) {
                return $.get('{{ route('perbandingan-kode-wilayah.wilayah-children') }}', {
                    parent_id: parentId
                }).done(function(response) {
                    let options = '<option value="">' + placeholder + '</option>';
                    $.each(response.items, function(_, item) {
                        options += '<option value="' + item.id + '">' + item.nama + '</option>';
                    });
                    targetSelect.html(options);
                });
            }

            function loadWilayahDetail(id, prefix) {
                $.get('{{ route('perbandingan-kode-wilayah.wilayah-detail') }}', {
                    id: id
                }).done(function(response) {
                    fillWilayahForm(prefix, response.item);
                });
            }

            leftKota.on('change', function() {
                const id = $(this).val();
                $('#formKota').hide();
                resetLeftBelow('kota');

                if (!id) {
                    return;
                }

                loadWilayahDetail(id, 'kota');
                loadChildren(id, leftKecamatan, 'Pilih Kecamatan');
            });

            leftKecamatan.on('change', function() {
                const id = $(this).val();
                $('#formKecamatan').hide();
                resetLeftBelow('kecamatan');

                if (!id) {
                    return;
                }

                loadWilayahDetail(id, 'kecamatan');
                loadChildren(id, leftKelurahan, 'Pilih Kelurahan');
            });

            leftKelurahan.on('change', function() {
                const id = $(this).val();
                $('#formKelurahan').hide();

                if (!id) {
                    return;
                }

                loadWilayahDetail(id, 'kelurahan');
            });

            const rightSamsat = $('#rightSamsat');
            const rightKecamatan = $('#rightKecamatan');
            const rightKelurahan = $('#rightKelurahan');

            rightSamsat.on('change', function() {
                const samsatId = $(this).val();
                $('#rightSamsatIdReadonly').val(samsatId || '');
                $('#rightKecamatanIdReadonly').val('');
                rightKecamatan.html('<option value="">Pilih Kecamatan</option>');
                rightKelurahan.html('<option value="">Pilih Kelurahan</option>');
                $('#rightFormKelurahan').hide();

                if (!samsatId) {
                    return;
                }

                $.get('{{ route('perbandingan-kode-wilayah.kecamatan-by-samsat') }}', {
                    id_lokasi_samsat: samsatId
                }).done(function(response) {
                    let options = '<option value="">Pilih Kecamatan</option>';
                    $.each(response.items, function(_, item) {
                        options += '<option value="' + item.id_kecamatan + '">' + item.kecamatan + '</option>';
                    });
                    rightKecamatan.html(options);
                });
            });

            rightKecamatan.on('change', function() {
                const kecId = $(this).val();
                $('#rightKecamatanIdReadonly').val(kecId || '');
                rightKelurahan.html('<option value="">Pilih Kelurahan</option>');
                $('#rightFormKelurahan').hide();

                if (!kecId) {
                    return;
                }

                $.get('{{ route('perbandingan-kode-wilayah.kelurahan-by-kecamatan') }}', {
                    id_kecamatan: kecId
                }).done(function(response) {
                    let options = '<option value="">Pilih Kelurahan</option>';
                    $.each(response.items, function(_, item) {
                        options += '<option value="' + item.id_kelurahan + '">' + item.kelurahan + '</option>';
                    });
                    rightKelurahan.html(options);
                });
            });

            rightKelurahan.on('change', function() {
                const kelId = $(this).val();
                $('#rightFormKelurahan').hide();

                if (!kelId) {
                    return;
                }

                $.get('{{ route('perbandingan-kode-wilayah.kelurahan-detail') }}', {
                    id_kelurahan: kelId
                }).done(function(response) {
                    const item = response.item;
                    $('#rightKelurahanIdReadonly').val(item.id_kelurahan || '');
                    $('#rightKodeDagriKelurahan').val(item.kode_dagri_kelelurahan || '');
                    $('#rightKodeDagriKecamatan').val(item.kode_dagri_kecamatan || '');
                    $('#rightKodeDagriKabkota').val(item.kode_dagri_kabkota || '');
                    $('#rightFormKelurahan').show();
                });
            });
        });
    </script>
@endpush
