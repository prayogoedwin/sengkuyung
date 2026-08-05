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
            --panel: rgba(255, 255, 255, 0.94);
            --line: rgba(15, 28, 46, 0.12);
            --accent: #0d9488;
            --accent-2: #0369a1;
            --warn: #ea580c;
            --good: #16a34a;
            --muted: #64748b;
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; overflow: hidden; }
        body {
            font-family: "IBM Plex Sans", "DM Sans", system-ui, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(900px 420px at 8% -10%, rgba(13, 148, 136, 0.18), transparent 55%),
                radial-gradient(700px 360px at 95% 0%, rgba(3, 105, 161, 0.14), transparent 50%),
                linear-gradient(165deg, #e8eef5 0%, #d7e5ea 45%, #c9d8e0 100%);
        }
        .rv-wrap {
            height: 100vh;
            max-width: none;
            margin: 0 auto;
            padding: 8px 12px 10px;
            display: grid;
            grid-template-rows: auto auto minmax(0, 1fr);
            gap: 8px;
            overflow: hidden;
        }
        .rv-top {
            display: flex;
            flex-wrap: nowrap;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
            min-height: 0;
        }
        .rv-brand { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
        .rv-brand h1 {
            margin: 0;
            font-size: clamp(1.35rem, 2.1vw, 1.85rem);
            letter-spacing: 0.01em;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .rv-brand .meta { color: var(--muted); font-size: clamp(0.9rem, 1.05vw, 1.05rem); }
        .rv-actions { display: flex; flex-wrap: nowrap; gap: 8px; align-items: center; flex-shrink: 0; }
        .rv-actions a, .rv-actions select, .rv-actions button {
            border: 1px solid var(--line); background: var(--panel); color: var(--ink);
            border-radius: 999px; padding: 6px 14px; font: inherit; font-size: clamp(0.92rem, 1.05vw, 1.05rem); text-decoration: none; cursor: pointer;
        }
        .rv-actions a.active { background: var(--ink); color: #fff; border-color: var(--ink); }
        .rv-actions button:disabled { opacity: 0.55; cursor: wait; }
        .back-link { color: var(--muted); text-decoration: none; font-size: clamp(0.88rem, 1vw, 1rem); }
        .meta .retry-link,
        .retry-link {
            margin-left: 8px;
            color: var(--accent-2);
            font-weight: 600;
            cursor: pointer;
            text-decoration: underline;
            background: none;
            border: 0;
            padding: 0;
            font: inherit;
        }

        .rv-mid {
            display: grid;
            grid-template-columns: 1.15fr 0.7fr 1.55fr;
            gap: 8px;
            min-height: 0;
        }
        .rv-bottom {
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            gap: 8px;
            min-height: 0;
            overflow: hidden;
        }

        .rv-card {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 10px 12px;
            box-shadow: 0 6px 16px rgba(15, 28, 46, 0.05);
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .rv-card.dark { background: linear-gradient(145deg, #0f1c2e, #16324f); color: #e8eef5; border: none; }
        .rv-card.teal { background: linear-gradient(145deg, #0f766e, #0e7490); color: #ecfeff; border: none; }
        .rv-card h2 {
            margin: 0 0 8px;
            font-size: clamp(0.88rem, 1.05vw, 1.05rem);
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            opacity: 0.9;
            flex-shrink: 0;
        }

        .metric { display: grid; gap: 6px; }
        .metric-row { display: grid; grid-template-columns: 1fr auto; gap: 8px; align-items: end; }
        .metric-row .label { font-size: clamp(0.95rem, 1.15vw, 1.12rem); opacity: 0.9; }
        .metric-row .value { font-size: clamp(1.2rem, 1.55vw, 1.45rem); font-weight: 700; font-variant-numeric: tabular-nums; }
        .metric-row .value .pct { font-size: clamp(0.95rem, 1.15vw, 1.12rem); font-weight: 600; opacity: 0.85; margin-left: 4px; }
        .bar { height: 5px; border-radius: 999px; background: rgba(255,255,255,0.2); overflow: hidden; }
        .bar > span { display: block; height: 100%; border-radius: inherit; background: #5eead4; }

        .stat-pills { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .pill { border-radius: 8px; padding: 8px 10px; background: rgba(255,255,255,0.12); }
        .pill .k { font-size: clamp(0.88rem, 1.05vw, 1.02rem); opacity: 0.85; }
        .pill .v { font-size: clamp(1.3rem, 1.7vw, 1.55rem); font-weight: 700; margin-top: 2px; }

        .pay-grid {
            display: grid;
            grid-template-columns: 0.85fr 1.35fr;
            grid-template-rows: auto auto auto;
            gap: 6px;
            flex: 1;
            min-height: 0;
            align-content: start;
        }
        .money-box {
            border: 1px solid rgba(15,28,46,0.12);
            border-radius: 8px;
            padding: 6px 10px;
            background: #f8fafc;
            min-height: 0;
        }
        .money-box .title {
            font-size: clamp(0.82rem, 0.95vw, 0.95rem);
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.03em;
            font-weight: 600;
        }
        .money-box .big {
            font-size: clamp(1.7rem, 2.3vw, 2.15rem);
            font-weight: 800;
            margin: 1px 0 0;
            color: var(--accent-2);
            line-height: 1;
        }
        .money-box .big .unit {
            font-size: clamp(0.88rem, 1.05vw, 1.02rem);
            font-weight: 600;
            color: var(--muted);
            margin-left: 2px;
        }
        .money-box.warn .big { color: var(--warn); }
        .money-box.good .big { color: var(--good); }
        .money-box.nominal {
            display: flex;
            align-items: center;
        }
        .nominal-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 4px;
            align-items: end;
            width: 100%;
        }
        .nominal-cell .k {
            font-size: clamp(0.78rem, 0.9vw, 0.9rem);
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .nominal-cell .v {
            font-size: clamp(1.3rem, 1.7vw, 1.6rem);
            font-weight: 800;
            color: var(--ink);
            line-height: 1;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }
        .pay-lines {
            display: grid;
            gap: 1px;
            margin-top: 3px;
            font-size: clamp(0.9rem, 1.1vw, 1.05rem);
            color: var(--ink-soft);
            line-height: 1.25;
        }
        .pay-lines strong { color: var(--ink); font-weight: 700; }
        .pay-ratio {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 12px;
            align-items: center;
            border: 1px solid rgba(15,28,46,0.12);
            border-radius: 8px;
            padding: 8px 12px;
            background: linear-gradient(90deg, rgba(13,148,136,0.08), #f8fafc);
        }
        .pay-ratio .pct {
            font-size: clamp(1.7rem, 2.3vw, 2.15rem);
            font-weight: 800;
            color: var(--accent);
            line-height: 1;
            font-variant-numeric: tabular-nums;
        }
        .pay-ratio .meta {
            display: grid;
            gap: 1px;
            font-size: clamp(0.9rem, 1.1vw, 1.05rem);
            color: var(--ink-soft);
            line-height: 1.25;
        }
        .pay-ratio .meta strong { color: var(--ink); }
        .pay-note {
            grid-column: 1 / -1;
            font-size: clamp(0.8rem, 0.95vw, 0.92rem);
            color: var(--muted);
            line-height: 1.3;
            padding: 0 2px;
        }
        .pay-note strong { color: var(--ink-soft); font-weight: 600; }

        #rvMap {
            flex: 1;
            min-height: 0;
            border-radius: 8px;
            border: 1px solid var(--line);
            overflow: hidden;
        }
        .map-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 4px 8px;
            flex-shrink: 0;
            margin-bottom: 4px;
        }
        .map-head h2 { margin: 0; }
        .map-tabs,
        .kab-tabs {
            display: inline-flex;
            gap: 2px;
            padding: 2px;
            border-radius: 999px;
            background: rgba(15, 28, 46, 0.06);
            flex-shrink: 0;
            position: relative;
            z-index: 5;
            pointer-events: auto;
        }
        .map-tabs button,
        .kab-tabs button {
            border: 0;
            background: transparent;
            color: var(--muted);
            font: inherit;
            font-size: clamp(0.82rem, 0.98vw, 0.95rem);
            font-weight: 600;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            padding: 6px 11px;
            border-radius: 999px;
            cursor: pointer;
            white-space: nowrap;
            pointer-events: auto;
        }
        .map-tabs button.active,
        .kab-tabs button.active {
            background: var(--ink);
            color: #fff;
        }
        .kab-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 4px 8px;
            flex-shrink: 0;
            margin-bottom: 4px;
        }
        .kab-head h2 { margin: 0; }
        .legend {
            display: flex; flex-wrap: wrap; gap: 10px; margin-top: 4px;
            font-size: clamp(0.88rem, 1.05vw, 1.02rem); flex-shrink: 0;
        }
        .legend span { display: inline-flex; align-items: center; gap: 5px; }
        .swatch { width: 12px; height: 12px; border-radius: 2px; display: inline-block; }

        .kab-scroll { flex: 1; min-height: 0; overflow: auto; }
        .progress-note {
            margin: 6px 0 0;
            flex-shrink: 0;
            font-size: clamp(0.82rem, 0.98vw, 0.95rem);
            font-weight: 600;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .kab-table { width: 100%; border-collapse: collapse; font-size: clamp(0.95rem, 1.15vw, 1.12rem); table-layout: fixed; }
        .kab-table th, .kab-table td { padding: 3px 8px; border-bottom: 1px solid var(--line); text-align: left; line-height: 1.25; }
        .kab-table th:first-child,
        .kab-table td:first-child {
            width: 38%;
            white-space: nowrap;
        }
        .kab-table th:not(:first-child),
        .kab-table td:not(:first-child) {
            width: 16.5%;
            text-align: right;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }
        .kab-table th { color: var(--muted); font-weight: 600; position: sticky; top: 0; background: var(--panel); z-index: 1; }
        .kab-table th:not(:first-child) { text-align: right; }
        .kab-table tfoot td {
            font-weight: 700;
            border-top: 2px solid var(--ink);
            border-bottom: 0;
            position: sticky;
            bottom: 0;
            background: var(--panel);
            z-index: 1;
        }
        .dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 6px; }
        .muted { color: var(--muted); }
        .err { color: #b91c1c; }

        @media (max-width: 1100px) {
            html, body { overflow: auto; }
            .rv-wrap { height: auto; min-height: 100vh; overflow: visible; }
            .rv-mid, .rv-bottom { grid-template-columns: 1fr; }
            #rvMap { height: 260px; flex: none; }
            .kab-scroll { max-height: 280px; }
        }
    </style>
</head>
<body>
<div class="rv-wrap">
    <div class="rv-top">
        <div class="rv-brand">
            <a class="back-link" href="{{ route('dashboard') }}">← Dashboard</a>
            <h1>{{ $pageTitle }} · {{ $year }}</h1>
            <div class="meta" id="rvMeta">Channel {{ $channelLabel }} · Memuat data…</div>
        </div>
        <div class="rv-actions">
            <a href="{{ route('rekap-visual.index', ['year' => $year]) }}" class="{{ !$isD2d ? 'active' : '' }}">Reguler</a>
            <a href="{{ route('rekap-visual-d2d.index', ['year' => $year]) }}" class="{{ $isD2d ? 'active' : '' }}">D2D</a>
            <form method="GET" action="{{ route($routeIndex) }}" style="display:flex;gap:6px;align-items:center;">
                <select name="year" onchange="this.form.submit()">
                    @for ($y = (int) date('Y'); $y >= (int) date('Y') - 3; $y--)
                        <option value="{{ $y }}" @selected($y === (int) $year)>{{ $y }}</option>
                    @endfor
                </select>
            </form>
            <a href="{{ route($routeIndex, ['year' => $year]) }}" id="btnHardRefresh" title="Refresh penuh halaman">Refresh</a>
            <button type="button" id="btnReloadData" title="Muat ulang data tanpa reload halaman">Muat Ulang</button>
        </div>
    </div>

    <div class="rv-mid">
        <div class="rv-card dark">
            <h2>Capaian Kegiatan</h2>
            <div class="metric">
                <div>
                    <div class="metric-row"><div class="label">Obyek Potensi</div><div class="value" id="vPotensi">…</div></div>
                    <div class="bar"><span id="bPotensi" style="width:100%"></span></div>
                </div>
                <div>
                    <div class="metric-row"><div class="label">Sudah Pendataan</div><div class="value" id="vDikunjungi">…</div></div>
                    <div class="bar"><span id="bDikunjungi" style="width:0%"></span></div>
                </div>
                <div>
                    <div class="metric-row"><div class="label">Belum Pendataan</div><div class="value" id="vBelum">…</div></div>
                    <div class="bar"><span id="bBelum" style="width:0%"></span></div>
                </div>
                <div>
                    <div class="metric-row"><div class="label">Sudah Bayar</div><div class="value" id="vBayarNopol">…</div></div>
                    <div class="bar"><span id="bBayarNopol" style="width:0%"></span></div>
                </div>
                <div>
                    <div class="metric-row"><div class="label">Belum Bayar</div><div class="value" id="vBelumBayar">…</div></div>
                    <div class="bar"><span id="bBelumBayar" style="width:0%"></span></div>
                </div>
            </div>
        </div>
        <div class="rv-card teal">
            <h2>Verifikasi</h2>
            <div class="stat-pills">
                <div class="pill"><div class="k">Menunggu</div><div class="v" id="vMenunggu">…</div></div>
                <div class="pill"><div class="k">Diverifikasi</div><div class="v" id="vVerifikasi">…</div></div>
                <div class="pill"><div class="k">Ditolak/Revisi</div><div class="v" id="vDitolak">…</div></div>
                <div class="pill"><div class="k">% Dikunjungi</div><div class="v" id="vPct">…</div></div>
            </div>
        </div>
        <div class="rv-card">
            <h2>Pembayaran</h2>
            <div class="pay-grid">
                <div class="money-box">
                    <div class="title">Nopol Terbayar (Unik)</div>
                    <div class="big" id="vTrx">…<span class="unit">Obyek</span></div>
                </div>
                <div class="money-box nominal">
                    <div class="nominal-row">
                        <div class="nominal-cell"><div class="k">Provinsi</div><div class="v" id="vNomProv">…</div></div>
                        <div class="nominal-cell"><div class="k">Opsen</div><div class="v" id="vNomOps">…</div></div>
                        <div class="nominal-cell"><div class="k">Total</div><div class="v" id="vNominal">…</div></div>
                    </div>
                </div>
                <div class="money-box warn">
                    <div class="title">Bayar sebelum pendataan</div>
                    <div class="pay-lines">
                        <div>Provinsi : <strong id="vSebelumProv">…</strong></div>
                        <div>Opsen : <strong id="vSebelumOps">…</strong></div>
                        <div>Obyek : <strong id="vSebelum">…</strong></div>
                    </div>
                </div>
                <div class="money-box good">
                    <div class="title">Bayar sesudah pendataan</div>
                    <div class="pay-lines">
                        <div>Provinsi : <strong id="vSesudahProv">…</strong></div>
                        <div>Opsen : <strong id="vSesudahOps">…</strong></div>
                        <div>Obyek : <strong id="vSesudah">…</strong></div>
                    </div>
                </div>
                <div class="pay-ratio">
                    <div class="pct" id="vBayarPct">…</div>
                    <div class="meta">
                        <div>Total Bayar ( <strong id="vBayarTotal">…</strong> )</div>
                        <div>Total Potensi ( <strong id="vPotensiTotal">…</strong> )</div>
                    </div>
                </div>
                <div class="pay-note">
                    <strong>Catatan:</strong> TOTAL POTENSI adalah PKB provinsi ditambah dengan PKB opsen dari obyek potensi.
                </div>
            </div>
        </div>
    </div>

    <div class="rv-bottom">
        <div class="rv-card">
            <div class="map-head">
                <h2 id="mapTitle">Peta Kab/Kota Jawa Tengah</h2>
                <div class="map-tabs" role="tablist">
                    <button type="button" class="active" data-map-tab="potensi" role="tab" aria-selected="true">Potensi Pembayaran</button>
                    <button type="button" data-map-tab="kinerja" role="tab" aria-selected="false">Progress Pendataan</button>
                </div>
            </div>
            <div id="rvMap"><div id="rvMapLoading" style="padding:12px;color:#64748b;font-size:1rem;">Memuat peta…</div></div>
            <div class="legend" id="mapLegend"></div>
        </div>
        <div class="rv-card">
            <div class="kab-head">
                <h2 id="kabTitle">Ringkasan per Kab/Kota</h2>
                <div class="kab-tabs" role="tablist">
                    <button type="button" class="active" data-map-tab="potensi" role="tab" aria-selected="true">Potensi Pembayaran</button>
                    <button type="button" data-map-tab="kinerja" role="tab" aria-selected="false">Progress Pendataan</button>
                </div>
            </div>
            <div class="kab-scroll">
                <table class="kab-table">
                    <thead id="kabTableHead">
                        <tr>
                            <th>Kab/Kota</th>
                            <th>Obyek Potensi</th>
                            <th>Sudah Pendataan</th>
                            <th>Sudah Bayar</th>
                            <th>Potensi Pembayaran</th>
                        </tr>
                    </thead>
                    <tbody id="kabTableBody">
                        <tr><td colspan="5" class="muted">Memuat…</td></tr>
                    </tbody>
                    <tfoot id="kabTableFoot"></tfoot>
                </table>
            </div>
            <p class="progress-note" id="progressNote" style="display:none;">Catatan : Obyek yang bayar setelah pendataan</p>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const statsUrl = @json($statsUrl);
    const mapUrl = @json($mapUrl);
    const geoUrl = @json(asset('geo/jateng-kabkota.geojson'));
    const channelLabel = @json($channelLabel);

    function fmt(n) {
        return Number(n || 0).toLocaleString('id-ID');
    }
    function pct(n) {
        return Number(n || 0).toFixed(2).replace('.', ',');
    }
    function setBar(id, width) {
        const el = document.getElementById(id);
        if (el) el.style.width = Math.max(0, Math.min(100, Number(width) || 0)) + '%';
    }

    function fmtPct(n, digits) {
        const d = digits == null ? 2 : digits;
        return Number(n || 0).toFixed(d).replace('.', ',') + '%';
    }
    function ratioPct(part, total) {
        if (!total) return 0;
        return (Number(part) || 0) / Number(total) * 100;
    }

    function renderStats(payload) {
        const s = payload.stats || {};
        const b = payload.bayar || {};
        const total = Number(s.jumlah_tunggakan || 0);
        const sudahBayar = Number(b.jumlah_nopol_bayar || 0);
        const belumBayar = Math.max(0, total - sudahBayar);
        const pctBayar = ratioPct(sudahBayar, total);
        const pctBelumBayar = ratioPct(belumBayar, total);

        document.getElementById('vPotensi').textContent = fmt(s.jumlah_tunggakan);
        document.getElementById('vDikunjungi').innerHTML =
            fmt(s.jumlah_sudah_pendataan) + ' <span class="pct">(' + fmtPct(s.pct_dikunjungi) + ')</span>';
        document.getElementById('vBelum').innerHTML =
            fmt(s.jumlah_belum_pendataan) +
            ' <span class="pct">(' + fmtPct(ratioPct(s.jumlah_belum_pendataan, total)) + ')</span>';
        document.getElementById('vBayarNopol').innerHTML =
            fmt(sudahBayar) + ' <span class="pct">(' + fmtPct(pctBayar) + ')</span>';
        document.getElementById('vBelumBayar').innerHTML =
            fmt(belumBayar) + ' <span class="pct">(' + fmtPct(pctBelumBayar) + ')</span>';
        setBar('bDikunjungi', s.pct_dikunjungi);
        setBar('bBelum', total > 0 ? (s.jumlah_belum_pendataan / total * 100) : 0);
        setBar('bBayarNopol', pctBayar);
        setBar('bBelumBayar', pctBelumBayar);
        document.getElementById('vMenunggu').textContent = fmt(s.menunggu_verifikasi);
        document.getElementById('vVerifikasi').textContent = fmt(s.verifikasi);
        document.getElementById('vDitolak').textContent = fmt(s.ditolak);
        document.getElementById('vPct').textContent = pct(s.pct_dikunjungi) + '%';

        document.getElementById('vTrx').innerHTML = fmt(b.jumlah_terbayar) + ' <span class="unit">Obyek</span>';
        document.getElementById('vNomProv').textContent = b.nominal_provinsi_fmt || '0';
        document.getElementById('vNomOps').textContent = b.nominal_opsen_fmt || '0';
        document.getElementById('vNominal').textContent = b.nominal_total_fmt || '0';
        document.getElementById('vSebelumProv').textContent = b.sebelum_pendataan_provinsi_fmt || '0';
        document.getElementById('vSebelumOps').textContent = b.sebelum_pendataan_opsen_fmt || '0';
        document.getElementById('vSebelum').textContent = fmt(b.sebelum_pendataan) + ' Obyek';
        document.getElementById('vSesudahProv').textContent = b.sesudah_pendataan_provinsi_fmt || '0';
        document.getElementById('vSesudahOps').textContent = b.sesudah_pendataan_opsen_fmt || '0';
        document.getElementById('vSesudah').textContent = fmt(b.sesudah_pendataan) + ' Obyek';
        document.getElementById('vBayarPct').textContent = fmtPct(b.pct_bayar_vs_potensi, 2);
        document.getElementById('vBayarTotal').textContent = b.nominal_total_fmt || '0';
        document.getElementById('vPotensiTotal').textContent = b.potensi_total_fmt || '0';

        document.getElementById('rvMeta').textContent =
            'Channel ' + channelLabel + ' · Diperbarui ' + (payload.refreshedAt || '');
    }

    // Target zoom seperti yang Anda zoom manual (gb2).
    const jatengCenter = [-7.05, 110.15];
    const jatengZoom = 9;
    const jatengBounds = L.latLngBounds(
        L.latLng(-8.25, 108.70),
        L.latLng(-5.85, 111.70)
    );

    const map = L.map('rvMap', {
        maxBounds: jatengBounds.pad(0.35),
        maxBoundsViscosity: 0.7,
        minZoom: 8,
    }).setView(jatengCenter, jatengZoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18, attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    function focusJateng(bounds) {
        const apply = function () {
            map.invalidateSize();
            if (bounds && bounds.isValid && bounds.isValid()) {
                map.fitBounds(bounds, { padding: [4, 4], maxZoom: 10 });
                if (map.getZoom() < jatengZoom) {
                    map.setView(bounds.getCenter(), jatengZoom);
                }
            } else {
                map.setView(jatengCenter, jatengZoom);
            }
        };
        apply();
        requestAnimationFrame(apply);
        setTimeout(apply, 150);
    }

    let mapMode = 'potensi';
    let cachedMapData = [];
    let geoLayer = null;
    let fallbackMarkers = [];
    let geojsonCache = null;

    function potensiBayarPct(row) {
        // Tab "Potensi Pembayaran": bayar / potensi
        return ratioPct(row.bayar, row.tagihan);
    }

    function progressSudahBayar(row) {
        // Obyek yang bayar setelah pendataan
        if (row.bayar_sesudah != null) {
            return Number(row.bayar_sesudah) || 0;
        }
        return Number(row.bayar) || 0;
    }

    function progressPendataanPct(row) {
        // Success rate: sudah bayar / sudah pendataan * 100%
        return ratioPct(progressSudahBayar(row), row.pendataan);
    }

    function successColor(pctVal) {
        if (pctVal >= 10) return '#22c55e';
        if (pctVal >= 5) return '#eab308';
        return '#ef4444';
    }

    function progressPendataanColor(pctVal) {
        // Semakin tinggi semakin bagus
        if (pctVal > 85) return '#22c55e';
        if (pctVal >= 51) return '#eab308';
        if (pctVal >= 26) return '#f97316';
        return '#ef4444';
    }

    function rowColor(row) {
        if (mapMode === 'potensi') {
            return successColor(potensiBayarPct(row));
        }
        return progressPendataanColor(progressPendataanPct(row));
    }

    function renderLegend() {
        const el = document.getElementById('mapLegend');
        if (!el) return;
        if (mapMode === 'potensi') {
            el.innerHTML =
                '<span><i class="swatch" style="background:#22c55e"></i> ≥10% bayar/potensi</span>' +
                '<span><i class="swatch" style="background:#eab308"></i> 5–10%</span>' +
                '<span><i class="swatch" style="background:#ef4444"></i> &lt;5%</span>';
            return;
        }
        el.innerHTML =
            '<span><i class="swatch" style="background:#ef4444"></i> &lt;25%</span>' +
            '<span><i class="swatch" style="background:#f97316"></i> 26–50%</span>' +
            '<span><i class="swatch" style="background:#eab308"></i> 51–84%</span>' +
            '<span><i class="swatch" style="background:#22c55e"></i> &gt;85%</span>';
    }

    function popupHtml(row, nama) {
        const vsPotensi = potensiBayarPct(row);
        const success = progressPendataanPct(row);
        const sudahBayar = progressSudahBayar(row);
        if (mapMode === 'kinerja') {
            return '<strong>' + nama + '</strong>' +
                '<br>Sudah Pendataan: ' + fmt(row.pendataan) +
                '<br>Sudah Bayar: ' + fmt(sudahBayar) +
                '<br>Success Rate: <strong>' + fmtPct(success, 2) + '</strong>';
        }
        return '<strong>' + nama + '</strong>' +
            '<br>Obyek Potensi: ' + fmt(row.tagihan) +
            '<br>Sudah Pendataan: ' + fmt(row.pendataan) +
            '<br>Sudah Bayar: ' + fmt(row.bayar) +
            '<br>Potensi Pembayaran: <strong>' + fmtPct(vsPotensi, 2) + '</strong> (bayar / potensi)';
    }

    function syncPanelTitles() {
        const mapTitle = document.getElementById('mapTitle');
        const kabTitle = document.getElementById('kabTitle');
        const note = document.getElementById('progressNote');
        if (mapMode === 'kinerja') {
            if (mapTitle) mapTitle.textContent = 'Peta Progress Pendataan';
            if (kabTitle) kabTitle.textContent = 'Progress Pendataan';
            if (note) note.style.display = '';
            return;
        }
        if (mapTitle) mapTitle.textContent = 'Peta Kab/Kota Jawa Tengah';
        if (kabTitle) kabTitle.textContent = 'Ringkasan per Kab/Kota';
        if (note) note.style.display = 'none';
    }

    function renderTableHead() {
        const thead = document.getElementById('kabTableHead');
        if (!thead) return;
        if (mapMode === 'kinerja') {
            thead.innerHTML = '<tr>' +
                '<th>Kab / Kota</th>' +
                '<th>Sudah Pendataan</th>' +
                '<th>Sudah Bayar</th>' +
                '<th>Success Rate</th>' +
                '</tr>';
            return;
        }
        thead.innerHTML = '<tr>' +
            '<th>Kab/Kota</th>' +
            '<th>Obyek Potensi</th>' +
            '<th>Sudah Pendataan</th>' +
            '<th>Sudah Bayar</th>' +
            '<th>Potensi Pembayaran</th>' +
            '</tr>';
    }

    function syncModeTabs() {
        document.querySelectorAll('.map-tabs button[data-map-tab], .kab-tabs button[data-map-tab]').forEach(function (b) {
            const on = b.getAttribute('data-map-tab') === mapMode;
            b.classList.toggle('active', on);
            b.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        syncPanelTitles();
    }

    function setMapMode(next) {
        const mode = next || 'potensi';
        if (mode === mapMode) return;
        mapMode = mode;
        syncModeTabs();
        applyMapColors();
        requestAnimationFrame(function () { map.invalidateSize(); });
    }

    function renderTable(mapData) {
        const tbody = document.getElementById('kabTableBody');
        const tfoot = document.getElementById('kabTableFoot');
        renderTableHead();
        if (!mapData.length) {
            tbody.innerHTML = '<tr><td colspan="' + (mapMode === 'kinerja' ? 4 : 5) + '">Tidak ada data</td></tr>';
            tfoot.innerHTML = '';
            return;
        }

        if (mapMode === 'kinerja') {
            let totalPendataan = 0;
            let totalBayar = 0;
            const rows = mapData.slice().sort(function (a, b) {
                return progressPendataanPct(b) - progressPendataanPct(a);
            });
            tbody.innerHTML = rows.map(function (row) {
                const pendataan = Number(row.pendataan) || 0;
                const bayar = progressSudahBayar(row);
                const success = progressPendataanPct(row);
                totalPendataan += pendataan;
                totalBayar += bayar;
                return '<tr>' +
                    '<td><span class="dot" style="background:' + rowColor(row) + '"></span>' + row.nama + '</td>' +
                    '<td>' + fmt(pendataan) + '</td>' +
                    '<td>' + fmt(bayar) + '</td>' +
                    '<td>' + fmtPct(success, 2) + '</td>' +
                    '</tr>';
            }).join('');
            tfoot.innerHTML = '<tr>' +
                '<td>Total</td>' +
                '<td>' + fmt(totalPendataan) + '</td>' +
                '<td>' + fmt(totalBayar) + '</td>' +
                '<td>' + fmtPct(ratioPct(totalBayar, totalPendataan), 2) + '</td>' +
                '</tr>';
            return;
        }

        let totalTagihan = 0;
        let totalPendataan = 0;
        let totalBayar = 0;
        const rows = mapData.slice().sort(function (a, b) {
            return potensiBayarPct(b) - potensiBayarPct(a);
        });
        tbody.innerHTML = rows.map(function (row) {
            const tagihan = Number(row.tagihan) || 0;
            const pendataan = Number(row.pendataan) || 0;
            const bayar = Number(row.bayar) || 0;
            const pctBayar = potensiBayarPct(row);
            totalTagihan += tagihan;
            totalPendataan += pendataan;
            totalBayar += bayar;
            return '<tr>' +
                '<td><span class="dot" style="background:' + rowColor(row) + '"></span>' + row.nama + '</td>' +
                '<td>' + fmt(row.tagihan) + '</td>' +
                '<td>' + fmt(row.pendataan) + '</td>' +
                '<td>' + fmt(row.bayar) + '</td>' +
                '<td>' + fmtPct(pctBayar, 2) + '</td>' +
                '</tr>';
        }).join('');
        tfoot.innerHTML = '<tr>' +
            '<td>Total</td>' +
            '<td>' + fmt(totalTagihan) + '</td>' +
            '<td>' + fmt(totalPendataan) + '</td>' +
            '<td>' + fmt(totalBayar) + '</td>' +
            '<td>' + fmtPct(ratioPct(totalBayar, totalTagihan), 2) + '</td>' +
            '</tr>';
    }

    function applyMapColors() {
        const byId = {};
        cachedMapData.forEach(function (row) { byId[String(row.id)] = row; });

        if (geoLayer) {
            geoLayer.eachLayer(function (lyr) {
                const id = String((lyr.feature && lyr.feature.properties && lyr.feature.properties.id) || '');
                const row = byId[id];
                const nama = row ? row.nama : ((lyr.feature && lyr.feature.properties && lyr.feature.properties.nama) || id);
                lyr.setStyle({
                    color: '#0f1c2e',
                    weight: 1,
                    fillColor: row ? rowColor(row) : '#94a3b8',
                    fillOpacity: 0.78,
                });
                if (row) {
                    lyr.bindPopup(popupHtml(row, nama));
                }
            });
        }

        fallbackMarkers.forEach(function (marker) {
            const row = marker._rvRow;
            if (!row) return;
            marker.setStyle({ fillColor: rowColor(row) });
            marker.bindPopup(popupHtml(row, row.nama));
        });

        renderLegend();
        renderTable(cachedMapData);
    }

    function paintMap(mapData) {
        cachedMapData = mapData || [];
        const loading = document.getElementById('rvMapLoading');
        if (loading) loading.remove();
        const byId = {};
        cachedMapData.forEach(function (row) { byId[String(row.id)] = row; });

        function buildGeoLayer(geo) {
            if (geoLayer) {
                map.removeLayer(geoLayer);
                geoLayer = null;
            }
            fallbackMarkers.forEach(function (m) { map.removeLayer(m); });
            fallbackMarkers = [];

            geoLayer = L.geoJSON(geo, {
                style: function (feature) {
                    const row = byId[String(feature.properties.id || '')];
                    return {
                        color: '#0f1c2e',
                        weight: 1,
                        fillColor: row ? rowColor(row) : '#94a3b8',
                        fillOpacity: 0.78,
                    };
                },
                onEachFeature: function (feature, lyr) {
                    const row = byId[String(feature.properties.id || '')];
                    const nama = row ? row.nama : (feature.properties.nama || feature.properties.id);
                    if (!row) {
                        lyr.bindPopup('<strong>' + nama + '</strong>');
                        return;
                    }
                    lyr.bindPopup(popupHtml(row, nama));
                },
            }).addTo(map);
            focusJateng(geoLayer.getBounds());
            renderLegend();
        }

        if (geojsonCache) {
            buildGeoLayer(geojsonCache);
            return;
        }

        fetch(geoUrl).then(function (r) { return r.json(); }).then(function (geo) {
            geojsonCache = geo;
            buildGeoLayer(geo);
        }).catch(function () {
            if (geoLayer) {
                map.removeLayer(geoLayer);
                geoLayer = null;
            }
            fallbackMarkers.forEach(function (m) { map.removeLayer(m); });
            fallbackMarkers = [];
            const bounds = [];
            cachedMapData.forEach(function (row) {
                if (row.lat == null || row.lng == null) return;
                const marker = L.circleMarker([row.lat, row.lng], {
                    radius: Math.max(6, Math.min(22, 6 + Math.sqrt(row.tagihan || 0) / 8)),
                    color: '#0f1c2e',
                    weight: 1,
                    fillColor: rowColor(row),
                    fillOpacity: 0.85,
                }).addTo(map).bindPopup(popupHtml(row, row.nama));
                marker._rvRow = row;
                fallbackMarkers.push(marker);
                bounds.push([row.lat, row.lng]);
            });
            focusJateng(bounds.length ? L.latLngBounds(bounds) : null);
            renderLegend();
        });
    }

    document.querySelectorAll('.map-tabs, .kab-tabs').forEach(function (tabs) {
        tabs.addEventListener('click', function (e) {
            const btn = e.target.closest('button[data-map-tab]');
            if (!btn || !tabs.contains(btn)) return;
            e.preventDefault();
            e.stopPropagation();
            setMapMode(btn.getAttribute('data-map-tab') || 'potensi');
        });
    });

    syncModeTabs();
    renderLegend();
    renderTableHead();

    let loadSeq = 0;
    let activeControllers = [];
    let statsReady = false;
    let mapReady = false;

    function sleep(ms) {
        return new Promise(function (resolve) { setTimeout(resolve, ms); });
    }

    function setMeta(html) {
        const el = document.getElementById('rvMeta');
        if (el) el.innerHTML = html;
    }

    function setReloadBusy(busy) {
        const btn = document.getElementById('btnReloadData');
        if (btn) {
            btn.disabled = !!busy;
            btn.textContent = busy ? 'Memuat…' : 'Muat Ulang';
        }
    }

    function abortActiveLoads() {
        activeControllers.forEach(function (c) {
            try { c.abort(); } catch (e) {}
        });
        activeControllers = [];
    }

    function ensureMapLoadingOverlay(text) {
        const mapEl = document.getElementById('rvMap');
        if (!mapEl) return;
        let loading = document.getElementById('rvMapLoading');
        if (!loading) {
            loading = document.createElement('div');
            loading.id = 'rvMapLoading';
            loading.style.cssText = 'padding:12px;color:#64748b;font-size:1rem;';
            mapEl.insertBefore(loading, mapEl.firstChild);
        }
        loading.textContent = text || 'Memuat peta…';
    }

    function isRetryableHttp(status) {
        return status === 408 || status === 429 || status === 500 || status === 502 || status === 503 || status === 504;
    }

    function fetchJsonWithRetry(url, label, signal, attempts) {
        const maxAttempts = attempts == null ? 3 : attempts;

        function attempt(n) {
            if (signal && signal.aborted) {
                return Promise.reject(Object.assign(new Error('Dibatalkan'), { name: 'AbortError' }));
            }

            if (n > 1) {
                setMeta(
                    'Channel ' + channelLabel +
                    ' · Mengulang ' + label + ' (' + n + '/' + maxAttempts + ')…'
                );
            }

            return fetch(url, {
                headers: { 'Accept': 'application/json' },
                signal: signal,
                cache: 'no-store',
            }).then(function (r) {
                if (!r.ok) {
                    const err = new Error(label.toUpperCase() + ' HTTP ' + r.status);
                    err.status = r.status;
                    err.retryable = isRetryableHttp(r.status);
                    throw err;
                }
                return r.json();
            }).catch(function (err) {
                if (err && err.name === 'AbortError') {
                    throw err;
                }
                const retryable = !err.status || err.retryable === true || isRetryableHttp(err.status);
                if (n < maxAttempts && retryable) {
                    return sleep(700 * n).then(function () { return attempt(n + 1); });
                }
                throw err;
            });
        }

        return attempt(1);
    }

    function showRetryHint(message) {
        setMeta(
            '<span class="err">' + message + '</span> ' +
            '<button type="button" class="retry-link" id="btnRetryNow">Coba lagi</button>'
        );
        const btn = document.getElementById('btnRetryNow');
        if (btn) btn.addEventListener('click', function () { loadDashboardData(); });
    }

    function loadDashboardData() {
        abortActiveLoads();
        const seq = ++loadSeq;
        statsReady = false;
        mapReady = false;
        setReloadBusy(true);

        const statsCtrl = new AbortController();
        const mapCtrl = new AbortController();
        activeControllers = [statsCtrl, mapCtrl];

        setMeta('Channel ' + channelLabel + ' · Memuat statistik & peta (bisa agak lama di D2D)…');
        document.getElementById('kabTableBody').innerHTML =
            '<tr><td colspan="5" class="muted">Memuat ringkasan kab/kota…</td></tr>';
        document.getElementById('kabTableFoot').innerHTML = '';
        ensureMapLoadingOverlay('Memuat peta…');

        const statsPromise = fetchJsonWithRetry(statsUrl, 'stats', statsCtrl.signal, 3)
            .then(function (payload) {
                if (seq !== loadSeq) return;
                renderStats(payload);
                statsReady = true;
            })
            .catch(function (err) {
                if (seq !== loadSeq || (err && err.name === 'AbortError')) return;
                showRetryHint('Gagal memuat statistik (' + (err && err.message ? err.message : 'error') + ').');
            });

        const mapPromise = fetchJsonWithRetry(mapUrl, 'map', mapCtrl.signal, 3)
            .then(function (payload) {
                if (seq !== loadSeq) return;
                const mapData = payload.mapKabkota || [];
                renderTable(mapData);
                paintMap(mapData);
                requestAnimationFrame(function () { map.invalidateSize(); });
                setTimeout(function () { map.invalidateSize(); }, 200);
                mapReady = true;
            })
            .catch(function (err) {
                if (seq !== loadSeq || (err && err.name === 'AbortError')) return;
                document.getElementById('kabTableBody').innerHTML =
                    '<tr><td colspan="5" class="err">Gagal memuat ringkasan/peta (' +
                    (err && err.message ? err.message : 'error') +
                    '). <button type="button" class="retry-link" id="btnRetryMap">Coba lagi</button></td></tr>';
                const mapBtn = document.getElementById('btnRetryMap');
                if (mapBtn) mapBtn.addEventListener('click', function () { loadDashboardData(); });
                ensureMapLoadingOverlay('Gagal memuat peta. Klik Muat Ulang / Coba lagi.');
                if (!statsReady) {
                    showRetryHint('Gagal memuat ringkasan/peta (' + (err && err.message ? err.message : 'error') + ').');
                }
            });

        Promise.allSettled([statsPromise, mapPromise]).then(function () {
            if (seq !== loadSeq) return;
            setReloadBusy(false);
            activeControllers = [];
        });
    }

    const reloadBtn = document.getElementById('btnReloadData');
    if (reloadBtn) {
        reloadBtn.addEventListener('click', function () {
            loadDashboardData();
        });
    }

    loadDashboardData();
</script>
</body>
</html>
