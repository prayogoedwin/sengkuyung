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
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        :root {
            --ink: #0f1c2e;
            --ink-soft: #1a2f4a;
            --panel: rgba(255, 255, 255, 0.96);
            --line: rgba(15, 28, 46, 0.12);
            --accent: #0d9488;
            --accent-2: #0369a1;
            --warn: #ea580c;
            --good: #16a34a;
            --muted: #64748b;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body {
            font-family: "IBM Plex Sans", system-ui, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(900px 420px at 8% -10%, rgba(13, 148, 136, 0.16), transparent 55%),
                radial-gradient(700px 360px at 95% 0%, rgba(3, 105, 161, 0.12), transparent 50%),
                linear-gradient(165deg, #e8eef5 0%, #d7e5ea 45%, #c9d8e0 100%);
        }
        .wrap { max-width: 1400px; margin: 0 auto; padding: 14px 16px 28px; display: grid; gap: 12px; }
        .top { display: flex; flex-wrap: wrap; gap: 10px 16px; justify-content: space-between; align-items: flex-start; }
        .brand { min-width: 0; flex: 1 1 320px; }
        .brand h1 { margin: 4px 0 0; font-size: clamp(1.15rem, 1.9vw, 1.65rem); line-height: 1.2; white-space: nowrap; }
        .meta { color: var(--muted); font-size: 0.95rem; margin-top: 6px; }
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
        .back-link { color: var(--muted); text-decoration: none; font-size: 0.9rem; }
        .actions {
            display: flex; flex-wrap: wrap; gap: 8px; align-items: center;
            justify-content: flex-end; flex: 0 1 auto;
        }
        .actions a, .actions select, .actions button {
            border: 1px solid var(--line); background: var(--panel); color: var(--ink);
            border-radius: 999px; padding: 7px 14px; font: inherit; font-size: 0.92rem; text-decoration: none; cursor: pointer;
        }
        .actions a.active { background: var(--ink); color: #fff; border-color: var(--ink); }
        .actions button:disabled,
        .actions select:disabled { opacity: 0.55; cursor: wait; }

        .title-row {
            display: flex; flex-wrap: wrap; gap: 8px 10px; align-items: center;
            margin-top: 4px;
        }
        .title-row h1 { margin: 0; }
        .title-row select,
        .title-row button {
            border: 1px solid var(--line); background: var(--panel); color: var(--ink);
            border-radius: 999px; padding: 5px 12px; font: inherit; font-size: 0.86rem; cursor: pointer;
        }
        .title-row select {
            max-width: 168px; min-width: 118px;
            white-space: nowrap; text-overflow: ellipsis;
        }
        .title-row select.wide { max-width: 190px; min-width: 140px; }
        .title-row select:disabled,
        .title-row button:disabled { opacity: 0.55; cursor: wait; }
        .title-row button.primary {
            background: var(--ink); color: #fff; border-color: var(--ink);
        }
        .title-row button.primary.is-loading {
            background: #334155;
            min-width: 108px;
        }

        .mid { display: grid; grid-template-columns: 1.1fr 0.75fr 1.35fr; gap: 10px; }
        .card {
            background: var(--panel); border: 1px solid var(--line); border-radius: 12px;
            padding: 12px; box-shadow: 0 6px 16px rgba(15, 28, 46, 0.05); min-height: 0;
        }
        .card.dark { background: linear-gradient(145deg, #0f1c2e, #16324f); color: #e8eef5; border: none; }
        .card.teal { background: linear-gradient(145deg, #0f766e, #0e7490); color: #ecfeff; border: none; }
        .card h2 {
            margin: 0 0 10px; font-size: 0.92rem; font-weight: 600;
            letter-spacing: 0.04em; text-transform: uppercase; opacity: 0.9;
        }
        .metric { display: grid; gap: 8px; }
        .metric-row { display: grid; grid-template-columns: 1fr auto; gap: 8px; align-items: end; }
        .metric-row .label { font-size: 1rem; opacity: 0.9; }
        .metric-row .value { font-size: 1.25rem; font-weight: 700; font-variant-numeric: tabular-nums; }
        .metric-row .value .pct { font-size: 0.95rem; font-weight: 600; opacity: 0.85; margin-left: 4px; }
        .bar { height: 5px; border-radius: 999px; background: rgba(255,255,255,0.2); overflow: hidden; }
        .bar > span { display: block; height: 100%; border-radius: inherit; background: #5eead4; }

        .pills { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .pill { border-radius: 8px; padding: 8px 10px; background: rgba(255,255,255,0.12); }
        .pill .k { font-size: 0.9rem; opacity: 0.85; }
        .pill .v { font-size: 1.35rem; font-weight: 700; margin-top: 2px; }

        .pay-grid { display: grid; grid-template-columns: 0.85fr 1.35fr; gap: 8px; }
        .money-box { border: 1px solid rgba(15,28,46,0.12); border-radius: 8px; padding: 8px 10px; background: #f8fafc; }
        .money-box .title { font-size: 0.82rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.03em; font-weight: 600; }
        .money-box .big { font-size: clamp(1.5rem, 2.1vw, 2rem); font-weight: 800; margin-top: 2px; color: var(--accent-2); line-height: 1; }
        .money-box .big .unit { font-size: 0.95rem; font-weight: 600; color: var(--muted); margin-left: 2px; }
        .nominal-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 4px; }
        .nominal-cell .k { font-size: 0.78rem; color: var(--muted); text-transform: uppercase; }
        .nominal-cell .v { font-size: clamp(1.2rem, 1.6vw, 1.5rem); font-weight: 800; white-space: nowrap; }
        .pay-lines { display: grid; gap: 2px; margin-top: 4px; font-size: 0.95rem; color: var(--ink-soft); }
        .pay-lines strong { color: var(--ink); }
        .pay-ratio {
            grid-column: 1 / -1; display: grid; grid-template-columns: auto 1fr; gap: 12px; align-items: center;
            border: 1px solid rgba(15,28,46,0.12); border-radius: 8px; padding: 8px 12px;
            background: linear-gradient(90deg, rgba(13,148,136,0.08), #f8fafc);
        }
        .pay-ratio .pct { font-size: clamp(1.7rem, 2.3vw, 2.15rem); font-weight: 800; color: var(--accent); line-height: 1; }
        .pay-ratio .meta { display: grid; gap: 2px; font-size: 0.95rem; color: var(--ink-soft); }
        .money-box.warn { border-color: rgba(234,88,12,0.25); }
        .money-box.good { border-color: rgba(22,163,74,0.25); }

        .panels {
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            gap: 10px;
            min-height: 420px;
        }
        .map-card, .table-card {
            background: var(--panel); border: 1px solid var(--line); border-radius: 12px; padding: 12px;
            display: flex; flex-direction: column; min-height: 0;
        }
        .panel-head {
            display: flex; flex-wrap: wrap; justify-content: space-between; gap: 8px;
            align-items: center; margin-bottom: 8px;
        }
        .panel-head h2 { margin: 0; font-size: 0.95rem; letter-spacing: 0.04em; text-transform: uppercase; }
        #rvMap {
            flex: 1; min-height: 340px; border-radius: 8px; border: 1px solid var(--line); overflow: hidden;
            background: #eef2f7;
        }
        .legend {
            display: flex; flex-wrap: wrap; gap: 10px; margin-top: 8px;
            font-size: 0.88rem; color: var(--muted);
        }
        .legend span { display: inline-flex; align-items: center; gap: 5px; }
        .swatch { width: 12px; height: 12px; border-radius: 2px; display: inline-block; }
        .tabs { display: inline-flex; gap: 2px; padding: 2px; border-radius: 999px; background: rgba(15,28,46,0.06); }
        .tabs button {
            border: 0; background: transparent; color: var(--muted); font: inherit; font-size: 0.85rem;
            font-weight: 600; text-transform: uppercase; padding: 6px 12px; border-radius: 999px; cursor: pointer;
        }
        .tabs button.active { background: var(--ink); color: #fff; }
        .table-wrap { flex: 1; max-height: 380px; overflow: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 0.98rem; }
        th, td { padding: 6px 8px; border-bottom: 1px solid var(--line); text-align: left; }
        th { color: var(--muted); font-weight: 600; position: sticky; top: 0; background: var(--panel); }
        td:not(:first-child), th:not(:first-child) { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        tfoot td { font-weight: 700; border-top: 2px solid var(--ink); }
        .muted { color: var(--muted); }
        .err { color: #b91c1c; }

        @media (max-width: 1100px) {
            .mid, .panels { grid-template-columns: 1fr; }
            #rvMap { min-height: 260px; }
            .brand h1 { white-space: normal; }
        }
        @media (max-width: 700px) {
            .pay-grid { grid-template-columns: 1fr; }
            .actions { justify-content: flex-start; width: 100%; }
            .title-row select { max-width: none; min-width: 0; flex: 1 1 140px; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <div class="brand">
            <a class="back-link" href="{{ route('dashboard') }}">← Dashboard</a>
            <div class="title-row">
                <h1>{{ $pageTitle }} · {{ $year }}</h1>
                <select id="fKabkota" class="wide" title="Kab/Kota">
                    <option value="">Seluruh Provinsi</option>
                    @foreach ($kabkotas as $kab)
                        <option value="{{ $kab->id }}">{{ $kab->nama }}</option>
                    @endforeach
                </select>
                <select id="fKecamatan" title="Kecamatan" disabled>
                    <option value="">Semua Kecamatan</option>
                </select>
                <select id="fKelurahan" title="Kelurahan" disabled>
                    <option value="">Semua Kelurahan</option>
                </select>
                <button type="button" class="primary" id="btnApply">Terapkan</button>
                <button type="button" id="btnReset">Reset</button>
            </div>
            <div class="meta" id="rvMeta">Channel {{ $channelLabel }} · Siap memuat…</div>
        </div>
        <div class="actions">
            <a href="{{ route('rekap-visual-filter.index', ['year' => $year]) }}" class="{{ !$isD2d ? 'active' : '' }}">Reguler</a>
            <a href="{{ route('rekap-visual-filter-d2d.index', ['year' => $year]) }}" class="{{ $isD2d ? 'active' : '' }}">D2D</a>
            <form method="GET" action="{{ route($routeIndex) }}" style="display:flex;gap:6px;align-items:center;">
                <select name="year" id="yearSelect" onchange="this.form.submit()">
                    @for ($y = (int) date('Y'); $y >= (int) date('Y') - 3; $y--)
                        <option value="{{ $y }}" @selected($y === (int) $year)>{{ $y }}</option>
                    @endfor
                </select>
            </form>
            <button type="button" id="btnReload">Muat Ulang</button>
        </div>
    </div>

    <div class="mid">
        <div class="card dark">
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
        <div class="card teal">
            <h2>Verifikasi</h2>
            <div class="pills">
                <div class="pill"><div class="k">Menunggu</div><div class="v" id="vMenunggu">…</div></div>
                <div class="pill"><div class="k">Diverifikasi</div><div class="v" id="vVerifikasi">…</div></div>
                <div class="pill"><div class="k">Ditolak/Revisi</div><div class="v" id="vDitolak">…</div></div>
                <div class="pill"><div class="k">% Dikunjungi</div><div class="v" id="vPct">…</div></div>
            </div>
        </div>
        <div class="card">
            <h2>Pembayaran</h2>
            <div class="pay-grid">
                <div class="money-box">
                    <div class="title">Nopol Terbayar (unik)</div>
                    <div class="big" id="vTrx">…<span class="unit">Obyek</span></div>
                </div>
                <div class="money-box">
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
            </div>
        </div>
    </div>

    <div class="panels">
        <div class="map-card">
            <div class="panel-head">
                <h2 id="mapTitle">Peta Kab/Kota Jawa Tengah</h2>
            </div>
            <div id="rvMap"><div id="rvMapLoading" style="padding:12px;color:#64748b;">Memuat peta…</div></div>
            <div class="legend" id="mapLegend"></div>
        </div>
        <div class="table-card">
            <div class="panel-head">
                <h2 id="tableTitle">Ringkasan per Kab/Kota</h2>
                <div class="tabs" role="tablist">
                    <button type="button" class="active" data-mode="potensi">Potensi Pembayaran</button>
                    <button type="button" data-mode="kinerja">Progress Pendataan</button>
                </div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead id="tableHead"></thead>
                    <tbody id="tableBody"><tr><td class="muted">Memuat…</td></tr></tbody>
                    <tfoot id="tableFoot"></tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    const statsUrl = @json($statsUrl);
    const breakdownUrl = @json($breakdownUrl);
    const mapUrl = @json($mapUrl);
    const optionsUrl = @json($optionsUrl);
    const geoUrl = @json($geoUrl);
    const channelLabel = @json($channelLabel);
    const year = @json((int) $year);

    let tableMode = 'potensi';
    let breakdownRows = [];
    let breakdownLevel = 'kabkota';
    let mapKabkotaRows = [];
    let lastStats = null;
    let loading = false;
    let loadSeq = 0;
    let activeControllers = [];
    let geoLayer = null;
    let geojsonCache = null;
    let fallbackMarkers = [];

    const elKab = document.getElementById('fKabkota');
    const elKec = document.getElementById('fKecamatan');
    const elKel = document.getElementById('fKelurahan');
    const btnApply = document.getElementById('btnApply');
    const btnApplyDefault = btnApply.textContent;

    const jatengCenter = [-7.15, 110.15];
    const jatengZoom = 8;
    const map = L.map('rvMap', { zoomControl: true, scrollWheelZoom: true }).setView(jatengCenter, jatengZoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 18,
    }).addTo(map);

    function fmt(n) { return Number(n || 0).toLocaleString('id-ID'); }
    function fmtPct(n, d) {
        const digits = d == null ? 2 : d;
        return Number(n || 0).toFixed(digits).replace('.', ',') + '%';
    }
    function ratioPct(a, b) {
        const den = Number(b || 0);
        if (den <= 0) return 0;
        return (Number(a || 0) / den) * 100;
    }
    function setBar(id, width) {
        const el = document.getElementById(id);
        if (el) el.style.width = Math.max(0, Math.min(100, Number(width) || 0)) + '%';
    }
    function filterParams() {
        const p = new URLSearchParams();
        p.set('year', String(year));
        if (elKab.value) p.set('kabkota_id', elKab.value);
        if (elKec.value) p.set('kecamatan_id', elKec.value);
        if (elKel.value) p.set('kelurahan_id', elKel.value);
        return p;
    }
    function expectedLevel() {
        if (elKel.value || elKec.value) return 'kelurahan';
        if (elKab.value) return 'kecamatan';
        return 'kabkota';
    }
    function levelTitle(level) {
        if (level === 'kecamatan') return 'Ringkasan per Kecamatan';
        if (level === 'kelurahan') return 'Ringkasan per Kelurahan';
        return 'Ringkasan per Kab/Kota';
    }
    function setMeta(html, isErr) {
        const el = document.getElementById('rvMeta');
        if (!el) return;
        el.innerHTML = html;
        el.className = 'meta' + (isErr ? ' err' : '');
    }
    function abortActiveLoads() {
        activeControllers.forEach(function (c) {
            try { c.abort(); } catch (e) {}
        });
        activeControllers = [];
    }
    function sleep(ms) {
        return new Promise(function (resolve) { setTimeout(resolve, ms); });
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
                if (btnApply) {
                    btnApply.classList.add('is-loading');
                    btnApply.textContent = 'Mengulang…';
                }
            }
            return fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
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
                if (err && err.name === 'AbortError') throw err;
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
            '<button type="button" class="retry-link" id="btnRetryNow">Coba lagi</button>',
            true
        );
        const btn = document.getElementById('btnRetryNow');
        if (btn) btn.addEventListener('click', function () { loadAll(true); });
    }
    function ensureMapLoadingOverlay(text) {
        const mapEl = document.getElementById('rvMap');
        if (!mapEl) return;
        let loadingEl = document.getElementById('rvMapLoading');
        if (!loadingEl) {
            loadingEl = document.createElement('div');
            loadingEl.id = 'rvMapLoading';
            loadingEl.style.cssText = 'padding:12px;color:#64748b;font-size:1rem;';
            mapEl.insertBefore(loadingEl, mapEl.firstChild);
        }
        loadingEl.textContent = text || 'Memuat peta…';
    }
    function setLoading(on) {
        loading = on;
        btnApply.disabled = on;
        document.getElementById('btnReload').disabled = on;
        document.getElementById('btnReset').disabled = on;
        elKab.disabled = on;
        if (!elKab.value) {
            elKec.disabled = true;
            elKel.disabled = true;
        } else {
            elKec.disabled = on;
            elKel.disabled = on || !elKec.value;
        }
        if (on) {
            btnApply.classList.add('is-loading');
            btnApply.textContent = 'Memuat…';
            document.getElementById('tableTitle').textContent = levelTitle(expectedLevel());
            document.getElementById('tableBody').innerHTML = '<tr><td class="muted">Sedang memuat data filter…</td></tr>';
            document.getElementById('tableFoot').innerHTML = '';
            ensureMapLoadingOverlay('Memuat peta…');
        } else {
            btnApply.classList.remove('is-loading');
            btnApply.textContent = btnApplyDefault;
        }
    }

    function fetchJson(url, signal) {
        return fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            signal: signal,
            cache: 'no-store',
        }).then(function (res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        });
    }

    function resetSelect(sel, placeholder, enabled) {
        sel.innerHTML = '';
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = placeholder;
        sel.appendChild(opt);
        sel.disabled = !enabled;
        sel.value = '';
    }

    async function loadKecamatan() {
        resetSelect(elKec, 'Semua Kecamatan', !!elKab.value);
        resetSelect(elKel, 'Semua Kelurahan', false);
        if (!elKab.value) return;
        const p = new URLSearchParams({ level: 'kecamatan', kabkota_id: elKab.value });
        const data = await fetchJson(optionsUrl + '?' + p.toString());
        (data.items || []).forEach(function (item) {
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.nama;
            elKec.appendChild(opt);
        });
    }

    async function loadKelurahan() {
        resetSelect(elKel, 'Semua Kelurahan', !!elKec.value);
        if (!elKec.value) return;
        const p = new URLSearchParams({ level: 'kelurahan', kecamatan_id: elKec.value });
        const data = await fetchJson(optionsUrl + '?' + p.toString());
        (data.items || []).forEach(function (item) {
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.nama;
            elKel.appendChild(opt);
        });
    }

    function successColor(pctVal) {
        if (pctVal >= 10) return '#22c55e';
        if (pctVal >= 5) return '#eab308';
        return '#ef4444';
    }
    function progressColor(pctVal) {
        if (pctVal > 85) return '#22c55e';
        if (pctVal >= 51) return '#eab308';
        if (pctVal >= 26) return '#f97316';
        return '#ef4444';
    }
    function rowColor(row) {
        if (tableMode === 'kinerja') {
            return progressColor(Number(row.success_rate || 0));
        }
        return successColor(Number(row.bayar_pct || 0));
    }
    function renderLegend() {
        const el = document.getElementById('mapLegend');
        if (!el) return;
        if (tableMode === 'kinerja') {
            el.innerHTML =
                '<span><i class="swatch" style="background:#ef4444"></i> &lt;25%</span>' +
                '<span><i class="swatch" style="background:#f97316"></i> 26–50%</span>' +
                '<span><i class="swatch" style="background:#eab308"></i> 51–84%</span>' +
                '<span><i class="swatch" style="background:#22c55e"></i> &gt;85%</span>';
            return;
        }
        el.innerHTML =
            '<span><i class="swatch" style="background:#22c55e"></i> ≥10% bayar/potensi</span>' +
            '<span><i class="swatch" style="background:#eab308"></i> 5–10%</span>' +
            '<span><i class="swatch" style="background:#ef4444"></i> &lt;5%</span>';
    }
    function popupHtml(row, nama) {
        if (tableMode === 'kinerja') {
            return '<strong>' + nama + '</strong>' +
                '<br>Sudah Pendataan: ' + fmt(row.pendataan) +
                '<br>Sudah Bayar: ' + fmt(row.bayar_sesudah) +
                '<br>Success Rate: <strong>' + fmtPct(row.success_rate, 2) + '</strong>';
        }
        return '<strong>' + nama + '</strong>' +
            '<br>Obyek Potensi: ' + fmt(row.tagihan) +
            '<br>Sudah Pendataan: ' + fmt(row.pendataan) +
            '<br>Sudah Bayar: ' + fmt(row.bayar) +
            '<br>Potensi Pembayaran: <strong>' + fmtPct(row.bayar_pct, 2) + '</strong>';
    }

    function selectedKabRowForMap() {
        if (!elKab.value || !lastStats) return null;
        const s = lastStats.stats || {};
        const b = lastStats.bayar || {};
        const tagihan = Number(s.jumlah_tunggakan || 0);
        const pendataan = Number(s.jumlah_sudah_pendataan || 0);
        const bayar = Number(b.jumlah_terbayar || 0);
        const bayarSesudah = Number(b.sesudah_pendataan || 0);
        const nama = elKab.options[elKab.selectedIndex]
            ? elKab.options[elKab.selectedIndex].textContent
            : elKab.value;
        return {
            id: elKab.value,
            nama: nama,
            tagihan: tagihan,
            pendataan: pendataan,
            bayar: bayar,
            bayar_sesudah: bayarSesudah,
            bayar_pct: ratioPct(bayar, tagihan),
            success_rate: ratioPct(bayarSesudah, pendataan),
        };
    }

    function paintMap() {
        const loadingEl = document.getElementById('rvMapLoading');
        if (loadingEl) loadingEl.remove();

        const selectedKab = elKab.value;
        const byId = {};
        mapKabkotaRows.forEach(function (row) { byId[String(row.id)] = row; });
        if (selectedKab) {
            const focused = selectedKabRowForMap();
            if (focused) byId[String(selectedKab)] = focused;
        }

        function styleFor(id) {
            const row = byId[String(id || '')];
            if (selectedKab) {
                if (String(id) === String(selectedKab) && row) {
                    return {
                        color: '#0f1c2e',
                        weight: 2,
                        fillColor: rowColor(row),
                        fillOpacity: 0.85,
                    };
                }
                return {
                    color: '#94a3b8',
                    weight: 1,
                    fillColor: '#cbd5e1',
                    fillOpacity: 0.25,
                };
            }
            return {
                color: '#0f1c2e',
                weight: 1,
                fillColor: row ? rowColor(row) : '#94a3b8',
                fillOpacity: 0.78,
            };
        }

        function buildGeoLayer(geo) {
            if (geoLayer) {
                map.removeLayer(geoLayer);
                geoLayer = null;
            }
            fallbackMarkers.forEach(function (m) { map.removeLayer(m); });
            fallbackMarkers = [];

            geoLayer = L.geoJSON(geo, {
                style: function (feature) {
                    return styleFor(feature.properties && feature.properties.id);
                },
                onEachFeature: function (feature, lyr) {
                    const id = String((feature.properties && feature.properties.id) || '');
                    const row = byId[id];
                    const nama = row ? row.nama : ((feature.properties && feature.properties.nama) || id);
                    if (selectedKab && id !== String(selectedKab)) {
                        lyr.bindPopup('<strong>' + nama + '</strong><br><span style="color:#64748b">Di luar filter</span>');
                        return;
                    }
                    if (row) lyr.bindPopup(popupHtml(row, nama));
                    else lyr.bindPopup('<strong>' + nama + '</strong>');
                },
            }).addTo(map);

            if (selectedKab && geoLayer) {
                let focusLayer = null;
                geoLayer.eachLayer(function (lyr) {
                    const id = String((lyr.feature && lyr.feature.properties && lyr.feature.properties.id) || '');
                    if (id === String(selectedKab)) focusLayer = lyr;
                });
                if (focusLayer && focusLayer.getBounds) {
                    map.fitBounds(focusLayer.getBounds(), { padding: [24, 24], maxZoom: 10 });
                } else {
                    map.setView(jatengCenter, jatengZoom);
                }
            } else if (geoLayer) {
                map.fitBounds(geoLayer.getBounds(), { padding: [12, 12] });
            }
            document.getElementById('mapTitle').textContent = selectedKab
                ? 'Peta Kab/Kota Terpilih'
                : 'Peta Kab/Kota Jawa Tengah';
            renderLegend();
            requestAnimationFrame(function () { map.invalidateSize(); });
        }

        if (geojsonCache) {
            buildGeoLayer(geojsonCache);
            return;
        }

        fetch(geoUrl).then(function (r) { return r.json(); }).then(function (geo) {
            geojsonCache = geo;
            buildGeoLayer(geo);
        }).catch(function () {
            if (geoLayer) { map.removeLayer(geoLayer); geoLayer = null; }
            fallbackMarkers.forEach(function (m) { map.removeLayer(m); });
            fallbackMarkers = [];
            const bounds = [];
            Object.keys(byId).forEach(function (id) {
                const row = byId[id];
                if (!row || row.lat == null || row.lng == null) return;
                if (selectedKab && String(id) !== String(selectedKab)) return;
                const marker = L.circleMarker([row.lat, row.lng], {
                    radius: 10,
                    color: '#0f1c2e',
                    weight: 1,
                    fillColor: rowColor(row),
                    fillOpacity: 0.85,
                }).addTo(map).bindPopup(popupHtml(row, row.nama));
                fallbackMarkers.push(marker);
                bounds.push([row.lat, row.lng]);
            });
            if (bounds.length) map.fitBounds(bounds, { padding: [30, 30], maxZoom: 10 });
            else map.setView(jatengCenter, jatengZoom);
            renderLegend();
        });
    }

    function renderStats(payload) {
        lastStats = payload;
        const s = payload.stats || {};
        const b = payload.bayar || {};
        const total = Number(s.jumlah_tunggakan || 0);
        const sudahPendataan = Number(s.jumlah_sudah_pendataan || 0);
        const belumPendataan = Number(s.jumlah_belum_pendataan || 0);
        const sudahBayar = Number(b.jumlah_terbayar || 0);
        const belumBayar = Math.max(0, total - sudahBayar);
        const pctBayar = ratioPct(sudahBayar, total);
        const pctBelumBayar = ratioPct(belumBayar, total);

        document.getElementById('vPotensi').textContent = fmt(total);
        document.getElementById('vDikunjungi').innerHTML =
            fmt(sudahPendataan) + ' <span class="pct">(' + fmtPct(s.pct_dikunjungi) + ')</span>';
        document.getElementById('vBelum').innerHTML =
            fmt(belumPendataan) + ' <span class="pct">(' + fmtPct(ratioPct(belumPendataan, total)) + ')</span>';
        document.getElementById('vBayarNopol').innerHTML =
            fmt(sudahBayar) + ' <span class="pct">(' + fmtPct(pctBayar) + ')</span>';
        document.getElementById('vBelumBayar').innerHTML =
            fmt(belumBayar) + ' <span class="pct">(' + fmtPct(pctBelumBayar) + ')</span>';
        setBar('bDikunjungi', s.pct_dikunjungi);
        setBar('bBelum', ratioPct(belumPendataan, total));
        setBar('bBayarNopol', pctBayar);
        setBar('bBelumBayar', pctBelumBayar);

        document.getElementById('vMenunggu').textContent = fmt(s.menunggu_verifikasi);
        document.getElementById('vVerifikasi').textContent = fmt(s.verifikasi);
        document.getElementById('vDitolak').textContent = fmt(s.ditolak);
        document.getElementById('vPct').textContent = fmtPct(s.pct_dikunjungi);

        document.getElementById('vTrx').innerHTML = fmt(sudahBayar) + '<span class="unit">Obyek</span>';
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
    }

    function renderTable() {
        const head = document.getElementById('tableHead');
        const body = document.getElementById('tableBody');
        const foot = document.getElementById('tableFoot');
        document.getElementById('tableTitle').textContent = levelTitle(breakdownLevel);

        if (!breakdownRows.length) {
            head.innerHTML = '';
            body.innerHTML = '<tr><td class="muted">Tidak ada data</td></tr>';
            foot.innerHTML = '';
            return;
        }

        if (tableMode === 'kinerja') {
            head.innerHTML = '<tr><th>Wilayah</th><th>Sudah Pendataan</th><th>Sudah Bayar</th><th>Success Rate</th></tr>';
            let totalPendataan = 0, totalBayar = 0;
            const rows = breakdownRows.slice().sort(function (a, b) {
                return Number(b.success_rate || 0) - Number(a.success_rate || 0);
            });
            body.innerHTML = rows.map(function (row) {
                totalPendataan += Number(row.pendataan || 0);
                totalBayar += Number(row.bayar_sesudah || 0);
                return '<tr>' +
                    '<td>' + row.nama + '</td>' +
                    '<td>' + fmt(row.pendataan) + '</td>' +
                    '<td>' + fmt(row.bayar_sesudah) + '</td>' +
                    '<td>' + fmtPct(row.success_rate, 2) + '</td>' +
                    '</tr>';
            }).join('');
            foot.innerHTML = '<tr><td>Total</td><td>' + fmt(totalPendataan) + '</td><td>' + fmt(totalBayar) +
                '</td><td>' + fmtPct(ratioPct(totalBayar, totalPendataan), 2) + '</td></tr>';
            return;
        }

        head.innerHTML = '<tr><th>Wilayah</th><th>Obyek Potensi</th><th>Sudah Pendataan</th><th>Sudah Bayar</th><th>Potensi Pembayaran</th></tr>';
        let totalTagihan = 0, totalPendataan = 0, totalBayar = 0;
        const rows = breakdownRows.slice().sort(function (a, b) {
            return Number(b.bayar_pct || 0) - Number(a.bayar_pct || 0);
        });
        body.innerHTML = rows.map(function (row) {
            totalTagihan += Number(row.tagihan || 0);
            totalPendataan += Number(row.pendataan || 0);
            totalBayar += Number(row.bayar || 0);
            return '<tr>' +
                '<td>' + row.nama + '</td>' +
                '<td>' + fmt(row.tagihan) + '</td>' +
                '<td>' + fmt(row.pendataan) + '</td>' +
                '<td>' + fmt(row.bayar) + '</td>' +
                '<td>' + fmtPct(row.bayar_pct, 2) + '</td>' +
                '</tr>';
        }).join('');
        foot.innerHTML = '<tr><td>Total</td><td>' + fmt(totalTagihan) + '</td><td>' + fmt(totalPendataan) +
            '</td><td>' + fmt(totalBayar) + '</td><td>' + fmtPct(ratioPct(totalBayar, totalTagihan), 2) + '</td></tr>';
    }

    async function loadAll(force) {
        abortActiveLoads();
        const seq = ++loadSeq;
        setLoading(true);
        setMeta('Channel ' + channelLabel + ' · Memuat statistik, ringkasan & peta…');

        const qs = filterParams().toString();
        const statsCtrl = new AbortController();
        const breakdownCtrl = new AbortController();
        const mapCtrl = new AbortController();
        const needMap = force === true || !mapKabkotaRows.length;
        activeControllers = needMap
            ? [statsCtrl, breakdownCtrl, mapCtrl]
            : [statsCtrl, breakdownCtrl];

        let statsOk = false;
        let breakdownOk = false;
        let mapOk = !needMap;

        const statsPromise = fetchJsonWithRetry(statsUrl + '?' + qs, 'stats', statsCtrl.signal, 3)
            .then(function (payload) {
                if (seq !== loadSeq) return;
                renderStats(payload);
                statsOk = true;
            })
            .catch(function (err) {
                if (seq !== loadSeq || (err && err.name === 'AbortError')) return;
                showRetryHint('Gagal memuat statistik (' + (err && err.message ? err.message : 'error') + ').');
            });

        const breakdownPromise = fetchJsonWithRetry(breakdownUrl + '?' + qs, 'ringkasan', breakdownCtrl.signal, 3)
            .then(function (payload) {
                if (seq !== loadSeq) return;
                breakdownRows = payload.rows || [];
                breakdownLevel = payload.level || expectedLevel();
                renderTable();
                breakdownOk = true;
            })
            .catch(function (err) {
                if (seq !== loadSeq || (err && err.name === 'AbortError')) return;
                document.getElementById('tableBody').innerHTML =
                    '<tr><td class="err">Gagal memuat ringkasan (' +
                    (err && err.message ? err.message : 'error') +
                    '). <button type="button" class="retry-link" id="btnRetryBreakdown">Coba lagi</button></td></tr>';
                const b = document.getElementById('btnRetryBreakdown');
                if (b) b.addEventListener('click', function () { loadAll(true); });
            });

        const mapPromise = needMap
            ? fetchJsonWithRetry(mapUrl + '?year=' + year, 'map', mapCtrl.signal, 3)
                .then(function (payload) {
                    if (seq !== loadSeq) return;
                    mapKabkotaRows = payload.mapKabkota || [];
                    mapOk = true;
                })
                .catch(function (err) {
                    if (seq !== loadSeq || (err && err.name === 'AbortError')) return;
                    ensureMapLoadingOverlay('Gagal memuat peta. Klik Muat Ulang / Coba lagi.');
                    mapOk = false;
                })
            : Promise.resolve();

        await Promise.allSettled([statsPromise, breakdownPromise, mapPromise]);
        if (seq !== loadSeq) return;

        if (statsOk || breakdownOk || mapOk) {
            paintMap();
        }
        if (statsOk && breakdownOk) {
            const refreshed = (lastStats && lastStats.refreshedAt) ? lastStats.refreshedAt : '';
            setMeta('Channel ' + channelLabel + ' · Diperbarui ' + refreshed);
        } else if (!document.getElementById('btnRetryNow')) {
            showRetryHint('Sebagian data gagal dimuat. Coba lagi.');
        }
        setLoading(false);
        activeControllers = [];
    }

    elKab.addEventListener('change', function () {
        loadKecamatan()
            .catch(function () { resetSelect(elKec, 'Gagal memuat kecamatan', false); })
            .finally(function () { loadAll(false); });
    });
    elKec.addEventListener('change', function () {
        loadKelurahan()
            .catch(function () { resetSelect(elKel, 'Gagal memuat kelurahan', false); })
            .finally(function () { loadAll(false); });
    });
    elKel.addEventListener('change', function () { loadAll(false); });

    btnApply.addEventListener('click', function () { loadAll(false); });
    document.getElementById('btnReload').addEventListener('click', function () { loadAll(true); });
    document.getElementById('btnReset').addEventListener('click', function () {
        elKab.value = '';
        resetSelect(elKec, 'Semua Kecamatan', false);
        resetSelect(elKel, 'Semua Kelurahan', false);
        loadAll(false);
    });

    document.querySelectorAll('.tabs button').forEach(function (btn) {
        btn.addEventListener('click', function () {
            tableMode = btn.getAttribute('data-mode') || 'potensi';
            document.querySelectorAll('.tabs button').forEach(function (b) {
                b.classList.toggle('active', b === btn);
            });
            renderTable();
            paintMap();
        });
    });

    loadAll(true);
})();
</script>
</body>
</html>
