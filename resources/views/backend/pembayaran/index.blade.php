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
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div id="tarikAlert"></div>

                        <div class="row">
                            <div class="col-lg-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5 class="mb-0">Tarik manual by tanggal</h5>
                                    </div>
                                    <div class="card-body">
                                        <form id="formTarikSatu">
                                            <label class="form-label">Tanggal penetapan</label>
                                            <input type="date" name="tanggal" class="form-control" required max="{{ now()->toDateString() }}">
                                            <p class="form-text">Mengisi <code>seng_bayar_pajak</code> dari API Jasa Raharja untuk satu hari. Nopol yang sudah ada di tanggal yang sama dilewati.</p>
                                            <button type="submit" class="btn btn-primary">Tarik tanggal ini</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5 class="mb-0">Tarik data lama</h5>
                                    </div>
                                    <div class="card-body">
                                        <form id="formTarikRentang">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Tanggal mulai</label>
                                                    <input type="date" name="tanggal_mulai" class="form-control" required max="{{ now()->toDateString() }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Tanggal selesai</label>
                                                    <input type="date" name="tanggal_selesai" class="form-control" required max="{{ now()->toDateString() }}">
                                                </div>
                                            </div>
                                            <p class="form-text">Ditarik berurutan per hari, maksimal {{ \App\Support\PembayaranTarik::MAX_RANGE_DAYS }} hari. Jangan tutup halaman sampai selesai.</p>
                                            <button type="submit" class="btn btn-primary">Tarik rentang</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Jadwal harian</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('pembayaran.jadwal') }}">
                                    @csrf
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-3">
                                            <label class="form-label">Status</label>
                                            <select name="aktif" class="form-select" required>
                                                <option value="1" @selected(old('aktif', $jadwal->aktif ? '1' : '0') === '1')>Aktif</option>
                                                <option value="0" @selected(old('aktif', $jadwal->aktif ? '1' : '0') === '0')>Mati</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Jam (WIB)</label>
                                            <input type="time" name="jam" class="form-control" required value="{{ old('jam', $jadwal->jam) }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Tanggal yang ditarik</label>
                                            <select name="tanggal_sumber" class="form-select" required>
                                                <option value="kemarin" @selected(old('tanggal_sumber', $jadwal->tanggal_sumber) === 'kemarin')>Kemarin (H-1)</option>
                                                <option value="hari_ini" @selected(old('tanggal_sumber', $jadwal->tanggal_sumber) === 'hari_ini')>Hari ini</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <button type="submit" class="btn btn-success">Simpan jadwal</button>
                                        </div>
                                    </div>
                                </form>
                                <p class="mt-3 mb-1 text-muted">
                                    Terakhir:
                                    <strong>{{ $jadwal->last_status ?: 'belum pernah' }}</strong>
                                    @if ($jadwal->last_tanggal)
                                        · tanggal {{ $jadwal->last_tanggal->toDateString() }}
                                    @endif
                                    @if ($jadwal->last_run_at)
                                        · {{ $jadwal->last_run_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB
                                    @endif
                                </p>
                                <p class="mb-0 small text-muted">{{ $jadwal->last_message ?: 'Belum ada tarikan jadwal.' }}</p>
                                <p class="mt-2 mb-0 small text-muted">
                                    Cron server harus menjalankan <code>php artisan schedule:run</code> tiap menit.
                                    Slot punya jendela {{ \App\Support\PembayaranTarik::SLOT_TOLERANCE_MINUTES }} menit setelah jam yang dipilih.
                                </p>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Riwayat tarikan</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>Mulai</th>
                                                <th>Jenis</th>
                                                <th>Periode</th>
                                                <th>Status</th>
                                                <th>Masuk</th>
                                                <th>Duplikat</th>
                                                <th>Keterangan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($logs as $log)
                                                <tr>
                                                    <td>{{ $log->started_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</td>
                                                    <td>{{ $log->jenis }}</td>
                                                    <td>{{ $log->tanggal_mulai?->toDateString() }} – {{ $log->tanggal_selesai?->toDateString() }}</td>
                                                    <td>{{ $log->status }}</td>
                                                    <td>{{ number_format($log->inserted, 0, ',', '.') }}</td>
                                                    <td>{{ number_format($log->skipped_duplicate, 0, ',', '.') }}</td>
                                                    <td>{{ $log->message }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center">Belum ada tarikan.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="layout-overlay layout-menu-toggle"></div>
    </div>

    <div id="tarikOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:9999; color:#fff; text-align:center; padding-top:20vh;">
        <div class="spinner-border text-light mb-3" role="status"></div>
        <p id="tarikProgress">Menarik data. Jangan tutup halaman ini.</p>
    </div>
@endsection

@push('js')
    <script>
        $(function () {
            const tarikUrl = @json(route('pembayaran.tarik'));
            const csrfToken = @json(csrf_token());

            async function parseJson(response) {
                const text = await response.text();
                try {
                    return JSON.parse(text);
                } catch (error) {
                    throw new Error('Respons server bukan JSON (HTTP ' + response.status + ').');
                }
            }

            function showAlert(type, message) {
                $('#tarikAlert').html('<div class="alert alert-' + type + '">' + $('<div>').text(message).html() + '</div>');
            }

            async function runTarik(payload) {
                const $overlay = $('#tarikOverlay');
                const $progress = $('#tarikProgress');
                $overlay.show();
                let runId = null;
                let done = false;

                try {
                    while (!done) {
                        const body = Object.assign({}, payload);
                        if (runId) {
                            body.run_id = runId;
                        }
                        const response = await fetch(tarikUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(body),
                        });
                        const data = await parseJson(response);
                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'Tarikan gagal.');
                        }
                        runId = data.run_id;
                        done = !!data.done;
                        $progress.text(data.message || 'Memproses...');
                    }
                    showAlert('success', $progress.text());
                    window.setTimeout(function () {
                        window.location.reload();
                    }, 800);
                } catch (error) {
                    showAlert('danger', error.message || 'Tarikan gagal.');
                } finally {
                    $overlay.hide();
                }
            }

            $('#formTarikSatu').on('submit', function (e) {
                e.preventDefault();
                const tanggal = this.tanggal.value;
                if (!tanggal) {
                    return;
                }
                runTarik({ mode: 'satu', tanggal: tanggal });
            });

            $('#formTarikRentang').on('submit', function (e) {
                e.preventDefault();
                const mulai = this.tanggal_mulai.value;
                const selesai = this.tanggal_selesai.value;
                if (!mulai || !selesai) {
                    return;
                }
                if (selesai < mulai) {
                    showAlert('danger', 'Tanggal selesai harus sama atau setelah tanggal mulai.');
                    return;
                }
                const days = Math.round((new Date(selesai) - new Date(mulai)) / 86400000) + 1;
                if (days > 31 && !window.confirm('Rentang ' + days + ' hari bisa lama. Lanjutkan?')) {
                    return;
                }
                runTarik({ mode: 'rentang', tanggal_mulai: mulai, tanggal_selesai: selesai });
            });
        });
    </script>
@endpush
