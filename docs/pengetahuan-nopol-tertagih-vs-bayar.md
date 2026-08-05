# Pengetahuan: Selisih Nopol Bayar vs Tertagih (Rekap Visual)

Dokumen ini menyimpan hasil cek **read-only** di database production (5 Agustus 2026).
Tidak ada `UPDATE`/`INSERT`/`DELETE` yang dijalankan saat investigasi.
Dipakai sebagai acuan perbaikan nanti (backfill / migration / query).

---

## Ringkasan singkat

1. Selisih **Transaksi Terbayar** vs **Sudah Bayar (nopol)** di dashboard adalah wajar: satu nopol bisa punya lebih dari satu transaksi bayar di tahun yang sama.
2. Ada masalah yang lebih serius: **angka Sudah Bayar kurang hitung ~3%** karena sebagian `no_polisi` di `data_tertagih` / `data_tertagih_d2d` tersimpan **tanpa strip** (`G3452C`), sementara `seng_bayar_pajak.nopol_` hampir seluruhnya **berstrip** (`G-3452-C`). Join exact string gagal match.
3. Import CSV tertagih **sudah menormalisasi** ke format berstrip, tapi data yang bermasalah diimpor **18 Juni 2026** saat regex masih mensyaratkan akhiran **2–3 huruf**. Regex dilonggarkan ke **1–3 huruf** baru pada **22 Juli 2026**. Tidak ada backfill `no_polisi` setelahnya.

---

## Akses server (saat cek)

| Item | Nilai |
|------|--------|
| Host | `web.bapenda.jatengprov.go.id` |
| Port SSH | `2020` |
| User | `sengkuyun6` |
| App path | `/home/sengkuyun6/htdocs/web.bapenda.jatengprov.go.id` |
| Cara query | PHP bootstrap Laravel via stdin (`php` + script), bukan MySQL langsung |
| Tahun data | `2026` |

> Password tidak disimpan di dokumen ini.

---

## Volume data (year = 2026)

| Tabel | Baris | Catatan |
|-------|------:|---------|
| `seng_bayar_pajak` | 2.037.389 | nopol unik: 2.035.168 |
| `data_tertagih` | 1.306.880 | reguler |
| `data_tertagih_d2d` | 1.924.643 | door-to-door |
| `seng_pendataan_kendaraan` (aktif 2026) | 27.326 | 100% berstrip |
| `seng_pendataan_kendaraan_d2d` (aktif 2026) | 136.976 | 100% berstrip |

Semua baris `data_tertagih` dan `data_tertagih_d2d` punya `created_at` di bulan **2026-06** (batch import tunggal 18 Juni).

---

## 1. Kenapa Transaksi Terbayar ≠ Sudah Bayar (nopol)

Dashboard memakai dua metrik berbeda setelah filter “nopol ada di tertagih”:

| Metrik | Definisi di kode | Reguler | D2D |
|--------|------------------|--------:|----:|
| Transaksi Terbayar | `COUNT(*)` baris bayar yang match tertagih | 157.222 | 134.514 |
| Sudah Bayar (nopol) | `COUNT(DISTINCT nopol_)` yang match tertagih | 157.132 | 133.458 |
| Selisih | transaksi − nopol unik | 90 | 1.056 |

Contoh nopol dengan >1 transaksi bayar (reguler, match tertagih):

- `R-5371-LN` → 3×
- `H-6713-PL` → 3×
- `H-1667-UF` → 3×
- beberapa lain 2×

Ini **bukan bug**. Kode relevan: `RekapVisualController::buildBayarStats()` —
`jumlah_terbayar` di-increment tiap baris, `jumlah_nopol_bayar` dari unique map nopol.

Join match saat ini:

```sql
t.no_polisi = b.nopol_   -- exact string, year sama
```

---

## 2. Masalah utama: format `no_polisi` tidak konsisten

### Pola format di DB

| Sumber | Pakai strip (`H-1234-AB`) | Tanpa pemisah (`H1234AB`) | Spasi / huruf kecil |
|--------|--------------------------:|--------------------------:|---------------------|
| `seng_bayar_pajak.nopol_` | 2.037.148 | 241 | 0 / 0 |
| `data_tertagih.no_polisi` | 1.267.288 | **39.592** | 0 / 0 |
| `data_tertagih_d2d.no_polisi` | 1.871.789 | **52.854** | 0 / 0 |
| Pendataan reguler & D2D | 100% | 0 | 0 / 0 |

Format sample tertagih yang benar: `H-3524-XA`, `AD-1640-RV`.
Format sample yang bermasalah: `G3452C`, `AA6840D`, `K8695Y`.

### Kolom & index relevan

- `seng_bayar_pajak`: `nopol`, `nopol_`, `nopol_lama` — index di `year`+`nopol_`, `nopol_`, `tgl_bayar`, `year`
- `data_tertagih` / `_d2d`: hanya `no_polisi` (index ada) — **tidak punya kolom `nopol_key`** di production saat dicek (atau tidak dipakai di join rekap visual)
- Pendataan: punya `nopol` + `nopol_key` (banyak `nopol_key` masih `NULL`)

### Dampak ke angka Sudah Bayar

Nopol tertagih tanpa strip dikonversi ke format berstrip, lalu dicek ke `seng_bayar_pajak`:

| | Tanpa strip di tertagih | Sudah bayar tapi tidak terhitung | Sudah Bayar sekarang | Seharusnya (estimasi) | Kenaikan |
|--|------------------------:|---------------------------------:|---------------------:|----------------------:|---------:|
| Reguler | 39.592 | **4.667** | 157.132 | ~161.799 | +2,97% |
| D2D | 52.854 | **3.396** | 133.458 | ~136.854 | +2,54% |

Tidak ada duplikat “versi berstrip + tanpa strip” untuk nopol yang sama di tertagih (duplikat = 0). Jadi 4.667 / 3.396 adalah tambahan bersih.

### Sample baris tertagih tanpa strip (reguler)

| id | no_polisi (DB) | normalize() sekarang | created_at | lokasi | terdata |
|----|----------------|----------------------|------------|--------|---------|
| 13 | G3452C | G-3452-C | 2026-06-18 02:13:29 | 23 | 0 |
| 19 | G2367U | G-2367-U | 2026-06-18 02:13:29 | 27 | 0 |
| 38 | G2976F | G-2976-F | 2026-06-18 02:13:29 | 26 | 0 |
| 49 | G5143N | G-5143-N | 2026-06-18 02:13:29 | 25 | 0 |
| 112 | AA6840D | AA-6840-D | 2026-06-18 02:13:29 | 35 | 0 |
| 141 | R4149G | R-4149-G | 2026-06-18 02:13:29 | 28 | 0 |
| 190 | AD2018R | AD-2018-R | 2026-06-18 02:13:29 | 15 | 0 |
| 498 | AA4778A | AA-4778-A | 2026-06-18 02:13:29 | 32 | 0 |
| 634 | AD4423D | AD-4423-D | 2026-06-18 02:13:29 | 12 | 0 |

### Sample D2D tanpa strip

| id | no_polisi (DB) | normalize() sekarang | created_at | lokasi |
|----|----------------|----------------------|------------|--------|
| 15 | K8695Y | K-8695-Y | 2026-06-18 02:30:23 | 42 |
| 109 | K1969B | K-1969-B | 2026-06-18 02:30:23 | 17 |
| 259 | AA9226E | AA-9226-E | 2026-06-18 02:30:23 | 36 |
| 317 | AD1964N | AD-1964-N | 2026-06-18 02:30:23 | 13 |
| 322 | R8726L | R-8726-L | 2026-06-18 02:30:23 | 30 |

### Contoh nopol tanpa-strip yang sebenarnya sudah bayar (tidak terhitung)

Reguler: `AA-2038-D`, `AA-2051-Z`, `AA-2501-W`, `AA-2778-G`, `AA-3056-Y`, …
D2D: `AA-1091-A`, `AA-1178-B`, `AA-1363-A`, `AA-1596-J`, `AA-56-C`, …

### Analisa pola baris tanpa strip

| Pola | Reguler | D2D |
|------|--------:|----:|
| Cocok regex **lama** (akhiran 2–3 huruf) | 0 | 0 |
| Akhiran **1 huruf** (`G3452C`) | 39.444 | 52.585 |
| Tanpa akhiran huruf (`AA7770`, `H454`) | 148 | 269 |
| Gagal `NopolFormatter::normalize()` versi sekarang | 148 | 269 |
| Sebaran `created_at` | 100% 2026-06 | 100% 2026-06 |

Contoh yang tetap gagal normalize sekarang (tanpa akhiran huruf):  
`AA7770`, `AA44`, `K52`, `H8118`, `H454`, `G99`, `AD16`, …

Dampak bayar untuk pola tanpa akhiran huruf: **0** (tidak ada yang match ke `seng_bayar_pajak`).  
Di tabel bayar, pola `^[A-Z]{1,2}-[0-9]+$` juga **0 baris**. Boleh diperbaiki belakangan.

### Pembayar yang memang tidak ada di tertagih

Contoh nopol bayar yang tidak ada di tertagih bahkan setelah hapus strip:  
`H-4878-XA`, `H-5969-YS`, `H-1570-UO`, `H-1847-Z`, `H-3381-APG`, …

Ini wajar: `seng_bayar_pajak` ~2jt pembayaran se-Jateng; tertagih hanya subset target. Dashboard memang memfilter “hanya yang ada di tertagih”.

---

## 3. Akar masalah di kode import

### Jalur INSERT aktif

Satu-satunya INSERT ke `data_tertagih` / `data_tertagih_d2d`:

- Route: `POST /data-tertagih/import/*` dan `/data-tertagih-d2d/import/*`
- Concern: `HandlesChunkedTertagihImport`
- Service: `DataTertagihCsvImporter`
- Normalisasi: `NopolFormatter::normalize($raw)` → kolom `no_polisi`
- `::insert()` massal **melewati** Eloquent `SyncsNopolKey` → `nopol_key` di-set manual di importer jika kolom ada

Tidak ada API/sync/seeder yang INSERT baris baru ke tertagih. API pendataan hanya UPDATE `is_terdata`.

### `NopolFormatter` (saat ini)

File: `app/Support/NopolFormatter.php`

- `normalize()` → format berstrip `PREFIX-ANGKA-SUFFIX` jika cocok  
  regex: `/^([A-Z]{1,2})(\d{1,4})([A-Z]{1,3})$/`  
  contoh: `H4878XA` → `H-4878-XA`, `H1847Z` → `H-1847-Z`  
  jika tidak cocok → cleaned uppercase tanpa strip
- `matchKey()` → tanpa strip/spasi: `H-1234-AB` → `H1234AB`

### Riwayat regex (penting)

| Periode | Commit (approx) | Perilaku |
|---------|-----------------|----------|
| Awal import tertagih | ~`ecc76e2` Apr | Tanpa normalisasi — `trim` apa adanya |
| Fitur normalisasi | ~`af85ed1` / `74d8f2b` Mei | Regex akhiran **`[A-Z]{2,3}`** — suffix 1 huruf **tidak** di-strip |
| Import batch production | **2026-06-18** | Seluruh tertagih reguler + D2D masuk di sini |
| Longgarkan regex | `8240213` **2026-07-22** | Akhiran **`[A-Z]{1,3}`** — `H1847Z` → `H-1847-Z` |
| Index `nopol_key` | `2026_07_23_014000_...` | Backfill **`nopol_key` saja**, **bukan** `no_polisi` |

Kesimpulan: importer **sudah benar sekarang**; data lama 18 Juni belum pernah di-backfill `no_polisi`.

### Join rekap visual yang terdampak

File: `app/Http/Controllers/RekapVisualController.php`

- `buildBayarStats()`: `whereColumn('t.no_polisi', 'b.nopol_')`
- `buildMapKabkota()`: `INNER JOIN ... ON t.no_polisi = x.nopol_`
- Match pendataan ↔ bayar: `p.nopol = b.nopol_` (pendataan sudah 100% berstrip → aman untuk baris yang sudah berstrip)

---

## 4. Performa (konteks timeout dashboard)

Query match di production sangat lambat (cek 5 Agu 2026):

| Query | ~waktu |
|-------|--------|
| Count bayar match tertagih reguler | ~48–50 detik |
| Tertagih vs sudah bayar (EXISTS) | ~311 detik |
| Analisa pola tanpa strip | ~50 detik |

Ini relevan dengan keluhan Rekap Visual lambat / kadang gagal load (terutama D2D). Perbaikan format nopol + (idealnya) join via `nopol_key` berindex akan membantu angka **dan** kecepatan.

---

## 5. Yang sudah disingkirkan sebagai penyebab

- Duplikat baris berstrip + tanpa-strip untuk nopol yang sama di tertagih → **tidak ada**
- Format pendataan beda → **tidak**; 100% berstrip
- Transaksi vs nopol unik → **bukan bug**, hanya beda definisi metrik
- Pembayar di luar daftar tertagih → **wajar**, memang di luar scope filter dashboard

---

## 6. Rekomendasi perbaikan (belum dikerjakan)

Urutan disarankan:

1. **Backfill `no_polisi`** di `data_tertagih` dan `data_tertagih_d2d`  
   - Hanya baris `no_polisi NOT LIKE '%-%'` (atau yang `normalize(no_polisi) <> no_polisi`)  
   - Set `no_polisi = NopolFormatter::normalize(no_polisi)` (atau SQL setara)  
   - Dry-run dulu: tampilkan before/after, hitung baris, cek bentrok unik jika ada unique constraint
2. **Sinkron ulang `nopol_key`** (jika kolom sudah ada / ditambahkan)  
   - `nopol_key = UPPER(REGEXP_REPLACE(no_polisi, '[^A-Za-z0-9]', ''))`
3. **Opsional longgarkan regex** untuk nopol tanpa akhiran huruf (`AA7770` → `AA-7770`) — dampak bayar saat ini 0, prioritas rendah
4. **Tambah `nopol_key` + index** di `data_tertagih` & `data_tertagih_d2d` jika belum, lalu ubah join rekap visual ke `nopol_key` agar tahan format dan lebih cepat
5. Setelah backfill: bump cache prefix Rekap Visual (`cachePrefix`) agar angka baru muncul
6. Verifikasi ulang angka:
   - Reguler Sudah Bayar (nopol) diharapkan naik ~**+4.667**
   - D2D diharapkan naik ~**+3.396**

**Jangan** sembarangan re-import CSV penuh tanpa cek duplikat tracker / truncate — backfill targeted lebih aman.

---

## 7. File kode terkait (referensi cepat)

| File | Peran |
|------|--------|
| `app/Support/NopolFormatter.php` | `normalize` / `matchKey` |
| `app/Services/DataTertagihCsvImporter.php` | Import CSV + normalisasi `no_polisi` |
| `app/Http/Controllers/Concerns/HandlesChunkedTertagihImport.php` | Upload/chunk import |
| `app/Http/Controllers/RekapVisualController.php` | Stats + map join nopol |
| `app/Http/Controllers/RekapVisualD2dController.php` | Override model D2D |
| `app/Models/DataTertagih.php` / `DataTertagihD2d.php` | Model + `SyncsNopolKey` |
| `database/migrations/2026_07_23_014000_add_nopol_key_indexes_for_rekap_visual.php` | Backfill `nopol_key` (bukan `no_polisi`) |

---

## 8. Status

| Item | Status |
|------|--------|
| Investigasi DB (read-only) | Selesai 2026-08-05 |
| Backfill `no_polisi` | **Belum** — menunggu update nanti |
| Ubah join ke `nopol_key` | Belum |
| Perbaiki regex tanpa akhiran huruf | Belum (prioritas rendah) |

Catatan terakhir: simpan dokumen ini sebelum eksekusi perbaikan; update bagian Status + angka verifikasi setelah backfill dijalankan.
