<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Pernyataan</title>
    <style>
        /* CSS Anda di sini */
    </style>
</head>
<body>
    <div class="header">
        <h2>PENDATAAN STATUS KENDARAAN</h2>
        <h3>SURAT PERNYATAAN</h3>
    </div>

    <div class="content">
        <p>Yang bertanda tangan di bawah ini:</p>
        <p>Nama&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $nama }}</p>
        <p>Alamat&nbsp;&nbsp;&nbsp;&nbsp;: {{ $alamat }}</p>
        <p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $kota }}</p>

        <p>Menerangkan bahwa Kendaraan:<br>
        Nomor Polisi&nbsp;&nbsp;&nbsp;: {{ $no_polisi }}<br>
        Merk/Tipe&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $merk }} / {{ $tipe }}<br>
        Adalah benar telah berganti kepemilikan</p>

        <p>Demikian surat keterangan dibuat untuk dipergunakan sebagaimana mestinya</p>
    </div>

    <div class="footer">
        <p>{{ $kota }}, {{ $tanggal }}</p>
    </div>

    <div class="signature">
        <p>Yang Membuat Pernyataan</p>
        <div class="signature-line"></div>
        <p>{{ $nama }}</p>
    </div>
</body>
</html>