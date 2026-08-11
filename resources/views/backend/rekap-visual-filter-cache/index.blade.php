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

                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                            <h4 class="mb-0">Cache Rekap Visual Filter</h4>
                            <div class="d-flex gap-2">
                                <form method="POST" action="{{ route('rekap-visual-filter-cache.warm') }}" onsubmit="return confirm('Jalankan warm sekarang? Proses berurutan dan bisa lama.');">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">Warm Sekarang</button>
                                </form>
                                <form method="POST" action="{{ route('rekap-visual-filter-cache.clear-all') }}" onsubmit="return confirm('Hapus semua cache berawalan rvf: ? Cache aplikasi lain tidak ikut terhapus.');">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger">Hapus Semua Cache</button>
                                </form>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header"><h5 class="mb-0">Pengaturan</h5></div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('rekap-visual-filter-cache.settings') }}">
                                    @csrf
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Dashboard manfaatkan cache</label>
                                            <select name="use_cache" class="form-select" required>
                                                <option value="1" @selected($settings->use_cache)>Ya (cache-first)</option>
                                                <option value="0" @selected(!$settings->use_cache)>Tidak (selalu query live)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Jadwal background</label>
                                            <select name="schedule_enabled" class="form-select" required>
                                                <option value="1" @selected($settings->schedule_enabled ?? true)>ON (ikut slot aktif)</option>
                                                <option value="0" @selected(!($settings->schedule_enabled ?? true))>OFF (semua slot mati)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Channel warm</label>
                                            <select name="warm_channel" class="form-select" required>
                                                <option value="semua" @selected($settings->warm_channel === 'semua')>Semua</option>
                                                <option value="reguler" @selected($settings->warm_channel === 'reguler')>Reguler</option>
                                                <option value="d2d" @selected($settings->warm_channel === 'd2d')>D2D</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">TTL (jam)</label>
                                            <input type="number" name="ttl_hours" class="form-control" min="1" max="72" value="{{ old('ttl_hours', $settings->ttl_hours ?: 12) }}" required>
                                            <div class="form-text">Provinsi / Kabkota</div>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">TTL kec/kel (menit)</label>
                                            <input type="number" name="ttl_detail_minutes" class="form-control" min="5" max="1440" value="{{ old('ttl_detail_minutes', $settings->ttl_detail_minutes ?: 60) }}" required>
                                            <div class="form-text">Default 60 menit</div>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Tahun warm</label>
                                            <input type="number" name="warm_year" class="form-control" min="2020" max="2100" placeholder="{{ date('Y') }}" value="{{ old('warm_year', $settings->warm_year) }}">
                                            <div class="form-text">Kosong = tahun berjalan</div>
                                        </div>
                                    </div>

                                    @php
                                        $slots = $slotDefs ?? $defaultSlots;
                                        while (count($slots) < 4) {
                                            $slots[] = ['time' => '', 'enabled' => false];
                                        }
                                    @endphp
                                    <div class="row g-3 mt-1">
                                        <div class="col-12">
                                            <label class="form-label mb-1">Slot jadwal warm (Asia/Jakarta)</label>
                                            <div class="form-text mb-2">
                                                Centang <strong>ON</strong> per slot supaya ikut jalan. Contoh: matikan 1 slot → hanya 3 scheduler aktif.
                                                Slot aktif sekarang:
                                                <code>{{ count($activeSlots) ? implode(', ', $activeSlots) : '(tidak ada)' }}</code>
                                            </div>
                                        </div>
                                        @foreach ([1,2,3,4] as $i)
                                            @php
                                                $slot = $slots[$i-1] ?? ['time' => '', 'enabled' => false];
                                                $time = old('slot'.$i, $slot['time'] ?? '');
                                                $enabled = (string) old('slot'.$i.'_enabled', ($slot['enabled'] ?? false) ? '1' : '0') === '1';
                                            @endphp
                                            <div class="col-md-3">
                                                <div class="border rounded p-2 h-100">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <strong>Slot {{ $i }}</strong>
                                                        <div class="form-check form-switch m-0">
                                                            <input type="hidden" name="slot{{ $i }}_enabled" value="0">
                                                            <input class="form-check-input" type="checkbox" role="switch" name="slot{{ $i }}_enabled" value="1" id="slot{{ $i }}_enabled" @checked($enabled)>
                                                            <label class="form-check-label" for="slot{{ $i }}_enabled">ON</label>
                                                        </div>
                                                    </div>
                                                    <input type="time" name="slot{{ $i }}" class="form-control" value="{{ $time }}">
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="mt-3">
                                        <button type="submit" class="btn btn-success">Simpan Pengaturan</button>
                                    </div>
                                </form>

                                <hr>
                                <p class="mb-1 text-muted">
                                    Status warm terakhir:
                                    <strong>{{ $settings->last_warm_status ?: '-' }}</strong>
                                    @if (($settings->last_warm_status ?? '') === 'running' && $settings->last_warm_started_at)
                                        · mulai {{ $settings->last_warm_started_at->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') }}
                                    @elseif ($settings->last_warm_finished_at)
                                        · selesai {{ $settings->last_warm_finished_at->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') }}
                                    @elseif ($settings->last_warm_started_at)
                                        · mulai {{ $settings->last_warm_started_at->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') }}
                                    @endif
                                </p>
                                <p class="mb-0 small text-muted">{{ $settings->last_warm_message ?: 'Belum ada log warm.' }}</p>
                                <p class="mt-2 mb-0 small text-muted">
                                    Prewarm: <strong>Provinsi + Kabkota</strong>.
                                    Filter kecamatan/kelurahan di-cache on-demand (TTL terpisah, tanpa notif lambat).
                                    Jika cache Provinsi/Kabkota kosong saat Terapkan: query live + notifikasi “membutuhkan waktu”, lalu hasil disimpan ke cache.
                                    Pastikan cron <code>* * * * * php artisan schedule:run</code> aktif.
                                    Slot dijalankan dalam jendela {{ \App\Support\RekapVisualFilterCache::SLOT_TOLERANCE_MINUTES }} menit setelah jam slot
                                    (mis. 11:00–11:04) agar tidak terlewat jika cron telat 1–2 menit.
                                </p>
                                <p class="mt-2 mb-0 small text-danger">
                                    Cukup tekan <strong>Warm Sekarang</strong> sekali (jalan di background, <em>skip</em> key yang sudah ada).
                                    Jadwal slot memakai <code>--refresh</code> (hitung ulang semua agar data &amp; TTL diperbarui).
                                    Urutan: Reguler lengkap → D2D → Semua (merge) → Kabkota.
                                    CLI lanjut sisa: <code>php artisan rvf:warm-cache --force</code>
                                    · refresh penuh: <code>php artisan rvf:warm-cache --force --refresh</code>
                                </p>
                                <p class="mt-2 mb-0 small text-muted">
                                    <strong>Hapus Semua Cache</strong> hanya menghapus key berawalan <code>rvf:</code> (aman untuk cache lain).
                                </p>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Daftar Cache Prewarm ({{ count($keys) }})</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('rekap-visual-filter-cache.clear-selected') }}">
                                    @csrf
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped align-middle">
                                            <thead>
                                                <tr>
                                                    <th style="width:36px"><input type="checkbox" onclick="document.querySelectorAll('.rvf-key').forEach(c => c.checked = this.checked)"></th>
                                                    <th>Key</th>
                                                    <th>Disimpan</th>
                                                    <th>TTL (detik)</th>
                                                    <th>Ada?</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($keys as $row)
                                                    <tr>
                                                        <td><input class="rvf-key" type="checkbox" name="keys[]" value="{{ $row['key'] }}"></td>
                                                        <td><code class="small">{{ $row['key'] }}</code></td>
                                                        <td>{{ $row['stored_at'] ?: '-' }}</td>
                                                        <td>{{ $row['ttl_seconds'] ?? '-' }}</td>
                                                        <td>
                                                            @if ($row['exists'])
                                                                <span class="badge bg-success">ya</span>
                                                            @else
                                                                <span class="badge bg-secondary">tidak</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="5" class="text-muted">Belum ada cache prewarm.</td></tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    @if (count($keys))
                                        <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Hapus key terpilih?')">Hapus Terpilih</button>
                                    @endif
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
