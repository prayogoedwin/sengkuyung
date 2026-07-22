@extends('backend.template.backend')

@section('content')
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <div class="layout-page">
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Import Excel Data Bayar Pajak</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="#" enctype="multipart/form-data" id="importForm">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label class="form-label">Tahun</label>
                                            <input type="number" class="form-control" name="year"
                                                value="{{ $defaultYear }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">File Excel / CSV</label>
                                            <input type="file" class="form-control" name="excel_file"
                                                accept=".xlsx,.xls,.csv" required>
                                            <small class="text-muted">
                                                Kolom: NO_POLISI, NOPOL_LAMA, TGL_BAYAR, PKB_PROVINSI_JALAN,
                                                PKB_PROVINSI_TUNGGAKAN, PKB_OPSEN_JALAN, PKB_OPSEN_TUNGGAKAN
                                            </small>
                                        </div>
                                        <div class="col-md-3 mt-4">
                                            <button type="submit" class="btn btn-primary btn-sm">Import</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">List Data Bayar Pajak</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Filter Tahun</label>
                                        <select class="form-control" id="filterYear">
                                            @foreach ($years as $year)
                                                <option value="{{ $year }}"
                                                    {{ (int) $year === (int) $defaultYear ? 'selected' : '' }}>
                                                    {{ $year }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Cari Nopol</label>
                                        <input type="text" class="form-control" id="filterNopol"
                                            placeholder="Contoh: H4878XA / H-4878-XA">
                                    </div>
                                    <div class="col-md-3 mt-4">
                                        <button class="btn btn-primary btn-sm" id="btnApplyFilter">Filter</button>
                                        <button class="btn btn-secondary btn-sm" id="btnResetFilter">Reset</button>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table id="bayarPajakTable" class="table table-bordered table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Nopol</th>
                                                <th>Nopol_</th>
                                                <th>Nopol Lama</th>
                                                <th>Tgl Bayar</th>
                                                <th>PKB Prov Jalan</th>
                                                <th>PKB Prov Tunggakan</th>
                                                <th>PKB Opsen Jalan</th>
                                                <th>PKB Opsen Tunggakan</th>
                                                <th>Tahun</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="importLoadingOverlay"
        style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:9999; color:#fff; text-align:center; padding-top:20vh;">
        <div class="spinner-border text-light mb-3" role="status"></div>
        <p id="importProgressText">Mohon tunggu sampai selesai. Jangan tutup halaman ini.</p>
    </div>
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            const defaultYear = @json($defaultYear);

            let table = $('#bayarPajakTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('bayar-pajak.index') }}',
                    data: function(d) {
                        d.year = $('#filterYear').val();
                        d.nopol = $('#filterNopol').val();
                    }
                },
                autoWidth: false,
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    { data: 'nopol' },
                    { data: 'nopol_' },
                    { data: 'nopol_lama' },
                    { data: 'tgl_bayar_fmt' },
                    { data: 'pkb_provinsi_jalan_fmt' },
                    { data: 'pkb_provinsi_tunggakan_fmt' },
                    { data: 'pkb_opsen_jalan_fmt' },
                    { data: 'pkb_opsen_tunggakan_fmt' },
                    { data: 'year' },
                ]
            });

            $('#btnApplyFilter').on('click', function() {
                table.ajax.reload();
            });

            $('#btnResetFilter').on('click', function() {
                $('#filterYear').val(defaultYear);
                $('#filterNopol').val('');
                table.ajax.reload();
            });

            const importUploadUrl = @json(route('bayar-pajak.import.upload'));
            const importChunkUrl = @json(route('bayar-pajak.import.chunk'));
            const csrfToken = @json(csrf_token());

            async function parseJsonResponse(response) {
                const text = await response.text();
                try {
                    return JSON.parse(text);
                } catch (error) {
                    const snippet = text.replace(/\s+/g, ' ').trim().slice(0, 120);
                    let hint = 'Periksa log server atau hubungi admin.';
                    if (response.status === 413) {
                        hint = 'File terlalu besar untuk limit upload server.';
                    } else if (response.status === 419) {
                        hint = 'Sesi habis. Muat ulang halaman lalu coba lagi.';
                    } else if (response.status >= 500) {
                        hint = 'Server error. Pastikan PDO SQLite aktif dan timeout cukup besar.';
                    }
                    throw new Error('Respons server bukan JSON (HTTP ' + response.status + '). ' + hint + ' ' + snippet);
                }
            }

            $('#importForm').on('submit', async function(e) {
                e.preventDefault();

                const fileInput = this.querySelector('input[name="excel_file"]');
                if (!fileInput.files.length) {
                    alert('Pilih file Excel terlebih dahulu.');
                    return;
                }

                const formData = new FormData(this);
                const $overlay = $('#importLoadingOverlay');
                const $progress = $('#importProgressText');
                const $submitBtn = $(this).find('button[type="submit"]');

                $overlay.show();
                $submitBtn.prop('disabled', true);
                $progress.text('Mengunggah & mengonversi file Excel...');

                try {
                    const uploadResponse = await fetch(importUploadUrl, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                    });

                    const uploadData = await parseJsonResponse(uploadResponse);
                    if (!uploadResponse.ok || !uploadData.success) {
                        throw new Error(uploadData.message || 'Gagal mengunggah file.');
                    }

                    let done = false;
                    while (!done) {
                        const chunkResponse = await fetch(importChunkUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                import_id: uploadData.import_id,
                            }),
                        });

                        const chunkData = await parseJsonResponse(chunkResponse);
                        if (!chunkResponse.ok || !chunkData.success) {
                            throw new Error(chunkData.message || 'Gagal memproses chunk import.');
                        }

                        if (chunkData.seeding) {
                            $progress.text(chunkData.message || 'Menyiapkan indeks duplikat database...');
                        } else {
                            const stats = chunkData.stats || {};
                            $progress.text(
                                'Memproses... masuk: ' + (stats.inserted ?? 0) +
                                ' / diproses: ' + (stats.total_rows ?? 0)
                            );
                        }

                        done = !!chunkData.done;
                        if (done) {
                            $('#importForm').closest('.card-body').prepend(
                                $('<div class="alert alert-success"></div>').text(chunkData.message)
                            );
                            table.ajax.reload();
                        }
                    }
                } catch (error) {
                    $('#importForm').closest('.card-body').prepend(
                        $('<div class="alert alert-danger"></div>').text(error.message || 'Import gagal.')
                    );
                } finally {
                    $overlay.hide();
                    $submitBtn.prop('disabled', false);
                }
            });
        });
    </script>
@endpush
