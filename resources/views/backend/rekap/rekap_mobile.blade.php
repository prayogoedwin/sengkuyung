<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Preview Rekap Pelaporan</title>
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

    <h2 style="text-align:center;">REKAP PELAPORAN</h2>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Terdata</th>
                <th>Menunggu</th>
                <th>Terverifikasi</th>
                <th>Ditolak</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $no => $verifikasi)
            <tr>
                <td>{{ $no + 1 }}</td>
                <td>{{ $verifikasi->total ?? 'N/A' }}</td>
                <td>{{ $verifikasi->menunggu_verifikasi ?? 'N/A' }}</td>
                <td>'{{ $verifikasi->verifikasi ?? 'N/A' }}'</td>
                <td>{{ $verifikasi->ditolak ?? 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <button onclick="window.print()" class="download-button">
        Cetak
    </button>

</body>
</html>