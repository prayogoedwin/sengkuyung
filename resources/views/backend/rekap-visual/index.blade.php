<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }} — {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('LOGO_SENGKUYUNG/icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        :root {
            --ink: #0f1c2e;
            --ink-soft: #1a2f4a;
            --panel: rgba(255, 255, 255, 0.92);
            --line: rgba(15, 28, 46, 0.12);
            --accent: #0d9488;
            --accent-2: #0369a1;
            --warn: #ea580c;
            --good: #16a34a;
            --muted: #64748b;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "IBM Plex Sans", "DM Sans", system-ui, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(1200px 600px at 10% -10%, rgba(13, 148, 136, 0.22), transparent 55%),
                radial-gradient(900px 500px at 95% 5%, rgba(3, 105, 161, 0.18), transparent 50%),
                linear-gradient(165deg, #e8eef5 0%, #d7e5ea 40%, #c9d8e0 100%);
            min-height: 100vh;
        }

        .rv-wrap {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px 20px 40px;
        }

        .rv-top {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .rv-brand {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .rv-brand h1 {
            margin: 0;
            font-size: clamp(1.15rem, 2.2vw, 1.55rem);
            letter-spacing: 0.02em;
            font-weight: 700;
        }

        .rv-brand .meta {
            color: var(--muted);
            font-size: 0.85rem;
        }

        .rv-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .rv-actions a,
        .rv-actions button,
        .rv-actions select {
            border: 1px solid var(--line);
            background: var(--panel);
            color: var(--ink);
            border-radius: 999px;
            padding: 8px 14px;
            font: inherit;
            font-size: 0.9rem;
            text-decoration: none;
            cursor: pointer;
        }

        .rv-actions a.active,
        .rv-actions button.primary {
            background: var(--ink);
            color: #fff;
            border-color: var(--ink);
        }

        .rv-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 14px;
            margin-bottom: 14px;
        }

        @media (max-width: 960px) {
            .rv-grid { grid-template-columns: 1fr; }
        }

        .rv-card {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 18px;
            box-shadow: 0 10px 30px rgba(15, 28, 46, 0.06);
        }

        .rv-card.dark {
            background: linear-gradient(145deg, #0f1c2e, #16324f);
            color: #e8eef5;
            border: none;
        }

        .rv-card.teal {
            background: linear-gradient(145deg, #0f766e, #0e7490);
            color: #ecfeff;
            border: none;
        }

        .rv-card h2 {
            margin: 0 0 14px;
            font-size: 0.95rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            opacity: 0.9;
        }

        .metric {
            display: grid;
            gap: 10px;
        }

        .metric-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 8px;
            align-items: end;
        }

        .metric-row .label { font-size: 0.9rem; opacity: 0.9; }
        .metric-row .value { font-size: 1.35rem; font-weight: 700; font-variant-numeric: tabular-nums; }

        .bar {
            height: 8px;
            border-radius: 999px;
            background: rgba(255,255,255,0.2);
            overflow: hidden;
        }

        .bar > span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: #5eead4;
        }

        .rv-card:not(.dark):not(.teal) .bar {
            background: rgba(15, 28, 46, 0.08);
        }

        .rv-card:not(.dark):not(.teal) .bar > span {
            background: linear-gradient(90deg, var(--accent), var(--accent-2));
        }

        .stat-pills {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .pill {
            border-radius: 14px;
            padding: 12px;
            background: rgba(255,255,255,0.12);
        }

        .pill .k { font-size: 0.78rem; opacity: 0.85; }
        .pill .v { font-size: 1.2rem; font-weight: 700; margin-top: 4px; }

        .split-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        @media (max-width: 700px) {
            .split-2, .stat-pills { grid-template-columns: 1fr; }
        }

        .money-box {
            border: 1px dashed rgba(15,28,46,0.2);
            border-radius: 14px;
            padding: 14px;
            background: #f8fafc;
        }

        .money-box .title { font-size: 0.8rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.04em; }
        .money-box .big { font-size: 1.6rem; font-weight: 700; margin: 6px 0; color: var(--accent-2); }
        .money-box .sub { font-size: 0.9rem; color: var(--ink-soft); }
        .money-box .break {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed rgba(15,28,46,0.15);
            font-size: 0.82rem;
            color: var(--muted);
            display: grid;
            gap: 4px;
        }
        .money-box .break strong { color: var(--ink-soft); font-weight: 600; }

        #rvMap {
            height: 420px;
            border-radius: 14px;
            border: 1px solid var(--line);
            overflow: hidden;
        }

        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
            font-size: 0.82rem;
        }

        .legend span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .swatch {
            width: 12px;
            height: 12px;
            border-radius: 3px;
            display: inline-block;
        }

        .kab-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            margin-top: 12px;
        }

        .kab-table th, .kab-table td {
            padding: 8px 10px;
            border-bottom: 1px solid var(--line);
            text-align: left;
        }

        .kab-table th { color: var(--muted); font-weight: 600; }

        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }

        .back-link {
            color: var(--muted);
            text-decoration: none;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
<div class="rv-wrap">
    <div class="rv-top">
        <div class="rv-brand">
            <a class="back-link" href="{{ route('dashboard') }}">← Kembali ke Dashboard</a>
            <h1>{{ $pageTitle }} TAHUN {{ $year }}</h1>
            <div class="meta">Channel {{ $channelLabel }} · Diperbarui {{ $refreshedAt }}</div>
        </div>
        <div class="rv-actions">
            <a href="{{ route('rekap-visual.index', ['year' => $year]) }}" class="{{ !$isD2d ? 'active' : '' }}">Reguler</a>
            <a href="{{ route('rekap-visual-d2d.index', ['year' => $year]) }}" class="{{ $isD2d ? 'active' : '' }}">D2D</a>
            <form method="GET" action="{{ route($routeIndex) }}" style="display:flex;gap:8px;align-items:center;">
                <select name="year" onchange="this.form.submit()">
                    @for ($y = (int) date('Y'); $y >= (int) date('Y') - 3; $y--)
                        <option value="{{ $y }}" @selected($y === (int) $year)>{{ $y }}</option>
                    @endfor
                </select>
            </form>
            <a href="{{ route($routeIndex, ['year' => $year]) }}">Refresh</a>
        </div>
    </div>

    <div class="rv-grid">
        <div class="rv-card dark">
            <h2>Capaian Kegiatan</h2>
            <div class="metric">
                <div>
                    <div class="metric-row">
                        <div class="label">Obyek Potensi (Unit)</div>
                        <div class="value">{{ number_format($stats['jumlah_tunggakan'], 0, ',', '.') }}</div>
                    </div>
                    <div class="bar"><span style="width:100%"></span></div>
                </div>
                <div>
                    <div class="metric-row">
                        <div class="label">Sudah Pendataan / Dikunjungi</div>
                        <div class="value">{{ number_format($stats['jumlah_sudah_pendataan'], 0, ',', '.') }}</div>
                    </div>
                    <div class="bar"><span style="width: {{ min(100, $stats['pct_dikunjungi']) }}%"></span></div>
                </div>
                <div>
                    <div class="metric-row">
                        <div class="label">Belum Pendataan</div>
                        <div class="value">{{ number_format($stats['jumlah_belum_pendataan'], 0, ',', '.') }}</div>
                    </div>
                    <div class="bar"><span style="width: {{ $stats['jumlah_tunggakan'] > 0 ? min(100, round($stats['jumlah_belum_pendataan'] / $stats['jumlah_tunggakan'] * 100, 1)) : 0 }}%"></span></div>
                </div>
                <div>
                    <div class="metric-row">
                        <div class="label">Sudah Bayar (Nopol unik)</div>
                        <div class="value">{{ number_format($bayar['jumlah_nopol_bayar'], 0, ',', '.') }}</div>
                    </div>
                    <div class="bar"><span style="width: {{ $stats['jumlah_tunggakan'] > 0 ? min(100, round($bayar['jumlah_nopol_bayar'] / $stats['jumlah_tunggakan'] * 100, 1)) : 0 }}%"></span></div>
                </div>
            </div>
        </div>

        <div class="rv-card teal">
            <h2>Verifikasi & Keberhasilan</h2>
            <div class="stat-pills">
                <div class="pill">
                    <div class="k">Menunggu Verifikasi</div>
                    <div class="v">{{ number_format($stats['menunggu_verifikasi'], 0, ',', '.') }}</div>
                </div>
                <div class="pill">
                    <div class="k">Diverifikasi</div>
                    <div class="v">{{ number_format($stats['verifikasi'], 0, ',', '.') }}</div>
                </div>
                <div class="pill">
                    <div class="k">Ditolak / Revisi</div>
                    <div class="v">{{ number_format($stats['ditolak'], 0, ',', '.') }}</div>
                </div>
                <div class="pill">
                    <div class="k">% Dikunjungi</div>
                    <div class="v">{{ number_format($stats['pct_dikunjungi'], 2, ',', '.') }}%</div>
                </div>
            </div>
        </div>
    </div>

    <div class="rv-card" style="margin-bottom:14px;">
        <h2>Pembayaran Pajak</h2>
        <div class="split-2">
            <div class="money-box">
                <div class="title">Jumlah Transaksi Terbayar</div>
                <div class="big">{{ number_format($bayar['jumlah_terbayar'], 0, ',', '.') }}</div>
                <div class="sub">Nopol unik: {{ number_format($bayar['jumlah_nopol_bayar'], 0, ',', '.') }}</div>
            </div>
            <div class="money-box">
                <div class="title">Nominal Total Terbayar</div>
                <div class="big">{{ $bayar['nominal_total_fmt'] }}</div>
                <div class="sub">
                    Provinsi: <strong>{{ $bayar['nominal_provinsi_fmt'] }}</strong>
                    &nbsp;·&nbsp;
                    Opsen: <strong>{{ $bayar['nominal_opsen_fmt'] }}</strong>
                </div>
            </div>
        </div>

        <div class="split-2" style="margin-top:12px;">
            <div class="money-box">
                <div class="title">Bayar sebelum pendataan</div>
                <div class="big" style="color:var(--warn);">{{ number_format($bayar['sebelum_pendataan'], 0, ',', '.') }}</div>
                <div class="sub">Nominal: {{ $bayar['sebelum_pendataan_nominal_fmt'] }}</div>
                <div class="break">
                    <div>Sebelum tanggal pendataan: <strong>{{ number_format($bayar['sebelum_pendataan_murni'], 0, ',', '.') }}</strong> ({{ $bayar['sebelum_pendataan_murni_nominal_fmt'] }})</div>
                    <div>Belum ada pendataan: <strong>{{ number_format($bayar['tanpa_pendataan'], 0, ',', '.') }}</strong> ({{ $bayar['tanpa_pendataan_nominal_fmt'] }})</div>
                </div>
            </div>
            <div class="money-box">
                <div class="title">Bayar sesudah pendataan</div>
                <div class="big" style="color:var(--good);">{{ number_format($bayar['sesudah_pendataan'], 0, ',', '.') }}</div>
                <div class="sub">Nominal: {{ $bayar['sesudah_pendataan_nominal_fmt'] }}</div>
            </div>
        </div>
    </div>

    <div class="rv-grid">
        <div class="rv-card">
            <h2>Peta Kab/Kota Jawa Tengah</h2>
            <div id="rvMap"><div id="rvMapLoading" style="padding:24px;color:#64748b;font-size:0.9rem;">Memuat peta…</div></div>
            <div class="legend">
                <span><i class="swatch" style="background:#22c55e"></i> Sisa ≤ 25%</span>
                <span><i class="swatch" style="background:#eab308"></i> 26–50%</span>
                <span><i class="swatch" style="background:#f97316"></i> 51–75%</span>
                <span><i class="swatch" style="background:#ef4444"></i> &gt; 75%</span>
            </div>
            <p style="margin:10px 0 0;font-size:0.82rem;color:var(--muted);">
                Warna = sisa tagihan belum bayar: (Total Tagihan − Nopol Bayar) / Total Tagihan.
                Peta memakai batas kab/kota Jawa Tengah.
            </p>
        </div>
        <div class="rv-card">
            <h2>Ringkasan per Kab/Kota</h2>
            <div style="max-height:460px;overflow:auto;">
                <table class="kab-table">
                    <thead>
                        <tr>
                            <th>Kab/Kota</th>
                            <th>Tagihan</th>
                            <th>Bayar</th>
                            <th>Sisa %</th>
                        </tr>
                    </thead>
                    <tbody id="kabTableBody">
                        <tr><td colspan="4" style="color:#64748b;">Memuat ringkasan kab/kota…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const mapUrl = @json($mapUrl);
    const geoUrl = @json(asset('geo/jateng-kabkota.geojson'));
    const map = L.map('rvMap').setView([-7.15, 110.14], 8);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    function renderTable(mapData) {
        const tbody = document.getElementById('kabTableBody');
        if (!mapData.length) {
            tbody.innerHTML = '<tr><td colspan="4">Tidak ada data</td></tr>';
            return;
        }
        tbody.innerHTML = mapData.map(function (row) {
            return '<tr>' +
                '<td><span class="dot" style="background:' + row.color + '"></span>' + row.nama + '</td>' +
                '<td>' + Number(row.tagihan).toLocaleString('id-ID') + '</td>' +
                '<td>' + Number(row.bayar).toLocaleString('id-ID') + '</td>' +
                '<td>' + Number(row.sisa_pct).toFixed(1).replace('.', ',') + '%</td>' +
                '</tr>';
        }).join('');
    }

    function paintMap(mapData) {
        const loading = document.getElementById('rvMapLoading');
        if (loading) loading.remove();

        const byId = {};
        mapData.forEach(function (row) { byId[String(row.id)] = row; });

        fetch(geoUrl)
            .then(function (r) { return r.json(); })
            .then(function (geo) {
                const layer = L.geoJSON(geo, {
                    style: function (feature) {
                        const id = String(feature.properties.id || '');
                        const row = byId[id];
                        return {
                            color: '#0f1c2e',
                            weight: 1,
                            fillColor: row ? row.color : '#94a3b8',
                            fillOpacity: 0.78,
                        };
                    },
                    onEachFeature: function (feature, lyr) {
                        const id = String(feature.properties.id || '');
                        const row = byId[id];
                        const nama = row ? row.nama : (feature.properties.nama || id);
                        if (!row) {
                            lyr.bindPopup('<strong>' + nama + '</strong><br>Tidak ada data tagihan');
                            return;
                        }
                        lyr.bindPopup(
                            '<strong>' + nama + '</strong><br>' +
                            'Tagihan: ' + Number(row.tagihan).toLocaleString('id-ID') + '<br>' +
                            'Bayar: ' + Number(row.bayar).toLocaleString('id-ID') + '<br>' +
                            'Sisa: ' + Number(row.sisa_pct).toFixed(1) + '%'
                        );
                    }
                }).addTo(map);
                map.fitBounds(layer.getBounds(), { padding: [24, 24] });
            })
            .catch(function () {
                const bounds = [];
                mapData.forEach(function (row) {
                    if (row.lat == null || row.lng == null) return;
                    const radius = Math.max(8, Math.min(28, 8 + Math.sqrt(row.tagihan || 0) / 8));
                    L.circleMarker([row.lat, row.lng], {
                        radius: radius,
                        color: '#0f1c2e',
                        weight: 1,
                        fillColor: row.color,
                        fillOpacity: 0.85,
                    }).addTo(map).bindPopup(
                        '<strong>' + row.nama + '</strong><br>' +
                        'Tagihan: ' + Number(row.tagihan).toLocaleString('id-ID') + '<br>' +
                        'Bayar: ' + Number(row.bayar).toLocaleString('id-ID') + '<br>' +
                        'Sisa: ' + Number(row.sisa_pct).toFixed(1) + '%'
                    );
                    bounds.push([row.lat, row.lng]);
                });
                if (bounds.length) map.fitBounds(bounds, { padding: [24, 24] });
            });
    }

    fetch(mapUrl, { headers: { 'Accept': 'application/json' } })
        .then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(function (payload) {
            const mapData = payload.mapKabkota || [];
            renderTable(mapData);
            paintMap(mapData);
        })
        .catch(function () {
            document.getElementById('kabTableBody').innerHTML =
                '<tr><td colspan="4" style="color:#b91c1c;">Gagal memuat peta/ringkasan. Coba refresh.</td></tr>';
            const loading = document.getElementById('rvMapLoading');
            if (loading) loading.textContent = 'Gagal memuat data peta. Statistik di atas tetap bisa dipakai.';
        });
</script>
</body>
</html>
