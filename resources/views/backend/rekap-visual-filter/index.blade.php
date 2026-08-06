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
        .top { display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: flex-start; }
        .brand h1 { margin: 4px 0 0; font-size: clamp(1.2rem, 2vw, 1.7rem); }
        .meta { color: var(--muted); font-size: 0.95rem; }
        .back-link { color: var(--muted); text-decoration: none; font-size: 0.9rem; }
        .actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .actions a, .actions select, .actions button {
            border: 1px solid var(--line); background: var(--panel); color: var(--ink);
            border-radius: 999px; padding: 7px 14px; font: inherit; font-size: 0.95rem; text-decoration: none; cursor: pointer;
        }
        .actions a.active { background: var(--ink); color: #fff; border-color: var(--ink); }
        .actions button:disabled { opacity: 0.55; cursor: wait; }

        .filters {
            display: grid;
            grid-template-columns: repeat(4, minmax(140px, 1fr)) auto;
            gap: 8px;
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 10px 12px;
            align-items: end;
        }
        .filters label { display: grid; gap: 4px; font-size: 0.78rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.04em; font-weight: 600; }
        .filters select {
            width: 100%; border: 1px solid var(--line); border-radius: 8px; padding: 8px 10px;
            font: inherit; background: #fff; color: var(--ink);
        }
        .filters .filter-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .filters .filter-actions button {
            border: 1px solid var(--line); background: var(--ink); color: #fff;
            border-radius: 999px; padding: 8px 14px; font: inherit; cursor: pointer;
        }
        .filters .filter-actions button.ghost { background: #fff; color: var(--ink); }

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

        .bottom { background: var(--panel); border: 1px solid var(--line); border-radius: 12px; padding: 12px; }
        .bottom-head { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 8px; align-items: center; margin-bottom: 8px; }
        .bottom-head h2 { margin: 0; font-size: 0.95rem; letter-spacing: 0.04em; text-transform: uppercase; }
        .tabs { display: inline-flex; gap: 2px; padding: 2px; border-radius: 999px; background: rgba(15,28,46,0.06); }
        .tabs button {
            border: 0; background: transparent; color: var(--muted); font: inherit; font-size: 0.85rem;
            font-weight: 600; text-transform: uppercase; padding: 6px 12px; border-radius: 999px; cursor: pointer;
        }
        .tabs button.active { background: var(--ink); color: #fff; }
        .table-wrap { max-height: 420px; overflow: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 0.98rem; }
        th, td { padding: 6px 8px; border-bottom: 1px solid var(--line); text-align: left; }
        th { color: var(--muted); font-weight: 600; position: sticky; top: 0; background: var(--panel); }
        td:not(:first-child), th:not(:first-child) { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        tfoot td { font-weight: 700; border-top: 2px solid var(--ink); }
        .muted { color: var(--muted); }
        .err { color: #b91c1c; }
        .badge {
            display: inline-block; margin-left: 8px; padding: 2px 8px; border-radius: 999px;
            background: rgba(13,148,136,0.12); color: var(--accent); font-size: 0.75rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.03em; vertical-align: middle;
        }

        @media (max-width: 1100px) {
            .mid { grid-template-columns: 1fr; }
            .filters { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 700px) {
            .filters { grid-template-columns: 1fr; }
            .pay-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <div class="brand">
            <a class="back-link" href="{{ route('dashboard') }}">← Dashboard</a>
            <h1>{{ $pageTitle }} · {{ $year }} <span class="badge">uji filter</span></h1>
            <div class="meta" id="rvMeta">Channel {{ $channelLabel }} · Siap memuat…</div>
        </div>
        <div class="actions">
            <a href="{{ route('rekap-visual-filter.index', ['year' => $year]) }}" class="{{ !$isD2d ? 'active' : '' }}">Reguler</a>
            <a href="{{ route('rekap-visual-filter-d2d.index', ['year' => $year]) }}" class="{{ $isD2d ? 'active' : '' }}">D2D</a>
            <a href="{{ route($isD2d ? 'rekap-visual-d2d.index' : 'rekap-visual.index', ['year' => $year]) }}">Rekap Lama</a>
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

    <div class="filters">
        <label>Kab/Kota
            <select id="fKabkota">
                <option value="">Seluruh Provinsi</option>
                @foreach ($kabkotas as $kab)
                    <option value="{{ $kab->id }}">{{ $kab->nama }}</option>
                @endforeach
            </select>
        </label>
        <label>Kecamatan
            <select id="fKecamatan" disabled>
                <option value="">Pilih Kab/Kota dulu</option>
            </select>
        </label>
        <label>Kelurahan
            <select id="fKelurahan" disabled>
                <option value="">Pilih Kecamatan dulu</option>
            </select>
        </label>
        <label style="visibility:hidden">spacer<select disabled></select></label>
        <div class="filter-actions">
            <button type="button" id="btnApply">Terapkan</button>
            <button type="button" class="ghost" id="btnReset">Reset</button>
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

    <div class="bottom">
        <div class="bottom-head">
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

<script>
(function () {
    const statsUrl = @json($statsUrl);
    const breakdownUrl = @json($breakdownUrl);
    const optionsUrl = @json($optionsUrl);
    const channelLabel = @json($channelLabel);
    const year = @json((int) $year);

    let tableMode = 'potensi';
    let breakdownRows = [];
    let breakdownLevel = 'kabkota';
    let loading = false;

    const elKab = document.getElementById('fKabkota');
    const elKec = document.getElementById('fKecamatan');
    const elKel = document.getElementById('fKelurahan');

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
    function setMeta(text, isErr) {
        const el = document.getElementById('rvMeta');
        el.textContent = text;
        el.className = 'meta' + (isErr ? ' err' : '');
    }
    function setLoading(on) {
        loading = on;
        document.getElementById('btnApply').disabled = on;
        document.getElementById('btnReload').disabled = on;
        document.getElementById('btnReset').disabled = on;
    }

    async function fetchJson(url) {
        const res = await fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
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
        resetSelect(elKel, 'Pilih Kecamatan dulu', false);
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

    function renderStats(payload) {
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

    function levelTitle(level) {
        if (level === 'kecamatan') return 'Ringkasan per Kecamatan';
        if (level === 'kelurahan') return 'Ringkasan per Kelurahan';
        return 'Ringkasan per Kab/Kota';
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

    async function loadAll() {
        if (loading) return;
        setLoading(true);
        setMeta('Channel ' + channelLabel + ' · Memuat data filter…');
        try {
            const qs = filterParams().toString();
            const [stats, breakdown] = await Promise.all([
                fetchJson(statsUrl + '?' + qs),
                fetchJson(breakdownUrl + '?' + qs),
            ]);
            renderStats(stats);
            breakdownRows = breakdown.rows || [];
            breakdownLevel = breakdown.level || 'kabkota';
            renderTable();
            setMeta('Channel ' + channelLabel + ' · Diperbarui ' + (stats.refreshedAt || ''));
        } catch (err) {
            setMeta('Gagal memuat data: ' + (err && err.message ? err.message : 'error'), true);
            document.getElementById('tableBody').innerHTML = '<tr><td class="err">Gagal memuat</td></tr>';
        } finally {
            setLoading(false);
        }
    }

    elKab.addEventListener('change', function () {
        loadKecamatan().catch(function () {
            resetSelect(elKec, 'Gagal memuat kecamatan', false);
        });
    });
    elKec.addEventListener('change', function () {
        loadKelurahan().catch(function () {
            resetSelect(elKel, 'Gagal memuat kelurahan', false);
        });
    });

    document.getElementById('btnApply').addEventListener('click', loadAll);
    document.getElementById('btnReload').addEventListener('click', loadAll);
    document.getElementById('btnReset').addEventListener('click', function () {
        elKab.value = '';
        resetSelect(elKec, 'Pilih Kab/Kota dulu', false);
        resetSelect(elKel, 'Pilih Kecamatan dulu', false);
        loadAll();
    });

    document.querySelectorAll('.tabs button').forEach(function (btn) {
        btn.addEventListener('click', function () {
            tableMode = btn.getAttribute('data-mode') || 'potensi';
            document.querySelectorAll('.tabs button').forEach(function (b) {
                b.classList.toggle('active', b === btn);
            });
            renderTable();
        });
    });

    loadAll();
})();
</script>
</body>
</html>
