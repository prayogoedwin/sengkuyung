<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Preview Jurnal Pelaporan</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid black; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }

        .download-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #007bff;
            color: white;
            padding: 12px 18px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
        }

        .download-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

    <h2 style="text-align:center;">JURNAL PELAPORAN</h2>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal Pendataan</th>
                <th>Nopol</th>
                <th>Nama</th>
                <th>No HP</th>
                <th>Kota</th>
                <th>Kecamatan</th>
                <th>Kelurahan</th>
                <th>Alamat</th>
                <th>Status Kendaraan</th>
                <th>Tanggal Akhir PKB</th>
                <th>Nama Petugas</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $no => $verifikasi)
            <tr>
                <td>{{ $no + 1 }}</td>
                <td>{{ optional($verifikasi->created_at)->format('Y-m-d') ?? 'N/A' }}</td>
                <td>{{ $verifikasi->nopol ?? 'N/A' }}</td>
                <td>{{ $verifikasi->nama ?? 'N/A' }}</td>
                <td>'{{ $verifikasi->nohp ?? 'N/A' }}'</td>
                <td>{{ $verifikasi->kota_name ?? 'N/A' }}</td>
                <td>{{ $verifikasi->kec_name ?? 'N/A' }}</td>
                <td>{{ $verifikasi->desa_name ?? 'N/A' }}</td>
                <td>{{ $verifikasi->alamat ?? 'N/A' }}</td>
                <td>{{ $verifikasi->status_name ?? 'N/A' }}</td>
                <td>{{ optional($verifikasi->tanggal_akhir_Pkb)->format('Y-m-d') ?? 'N/A' }}</td>
                <td>{{ optional($verifikasi->createdByUser)->name ?? 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <button onclick="window.print()" class="download-button">
        Cetak
    </button>

</body>
</html>