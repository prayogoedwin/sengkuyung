<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Pernyataan</title>
    <style>
        body {
            width: 400px;
            margin: auto;
            padding: 20px;
            border: 2px solid #000;
            border-radius: 10px;
            font-family: Arial, sans-serif;
            text-align: center;
        }
        .content {
            text-align: left;
            margin-top: 10px;
        }
        p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <h3>SURAT PERNYATAAN</h3>
    <div class="content">
        <p>Yang bertanda tangan di bawah ini:</p>
        <p>Nama : {{ $nama }}</p>
        <p>Alamat : {{ $alamat }}</p>
        @if($desa)
        <p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $desa }}</p>
        @endif
        @if($kecamatan)
        <p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $kecamatan }} {{ $kota }}</p>
        @endif
        
        <p style="margin-top: 15px;">Menerangkan bahwa Kendaraan:</p>
        <p>Nomor Polisi : {{ $no_polisi }}</p>
        <p>Merk/Tipe : {{ $merk }} / {{ $tipe }}</p>
        <p>Adalah benar telah berganti kepemilikan</p>
        <p>Demikian surat keterangan dibuat untuk digunakan sebagaimana mestinya</p>
        
        <p style="text-align: center; margin-top: 30px;">{{ $kota }}, {{ $tanggal }}</p>
        <p style="text-align: center;">Yang Membuat Pernyataan</p>
        <p style="text-align: center; margin-top: 60px;">{{ $nama }}</p>
    </div>
</body>
</html>