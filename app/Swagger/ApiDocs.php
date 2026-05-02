<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(title: 'Sengkuyung API', version: '1.0.0', description: 'Dokumentasi API untuk aplikasi Sengkuyung.')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Masukkan token hasil login dalam format: Bearer {token}'
)]
class ApiDocs
{
    #[OA\Post(
        path: 'api/login',
        tags: ['Auth'],
        summary: 'Login menggunakan email dan password',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'petugas@example.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'rahasia123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login berhasil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Login successful'),
                        new OA\Property(property: 'token', type: 'string', example: '1|abcdeFGHIJKLmnOPqrSTUvwxYZ'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            description: 'Profil user. lokasi_samsat_name, kecamatan_samsat_name, kelurahan_samsat_name diisi dari lookup (bukan kolom users).',
                            properties: [
                                new OA\Property(property: 'lokasi_samsat_name', type: 'string', nullable: true, example: 'SAMSAT Gayamsari'),
                                new OA\Property(property: 'kecamatan_samsat_name', type: 'string', nullable: true, example: 'Gayamsari'),
                                new OA\Property(property: 'kelurahan_samsat_name', type: 'string', nullable: true, example: 'Gayamsari'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthorized'),
                    ]
                )
            ),
        ]
    )]
    public function login()
    {
    }

    #[OA\Post(
        path: 'api/login_with_otp',
        tags: ['Auth'],
        summary: 'Login dan generate OTP',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password', 'otp_method'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'petugas@example.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'rahasia123'),
                    new OA\Property(property: 'otp_method', type: 'string', enum: ['email', 'wa'], example: 'email'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'OTP berhasil digenerate',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'OTP Tergenerate, dengan expire 5 menit'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function loginWithOtp()
    {
    }

    #[OA\Post(
        path: 'api/verifikasi_otp',
        tags: ['Auth'],
        summary: 'Verifikasi OTP untuk login',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'otp'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'petugas@example.com'),
                    new OA\Property(property: 'otp', type: 'string', example: '123456'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'OTP valid, login berhasil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Login successful'),
                        new OA\Property(property: 'token', type: 'string', example: '1|abcdeFGHIJKLmnOPqrSTUvwxYZ'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            description: 'Sama seperti api/login; nama samsat/kec/kel hanya di response.',
                            properties: [
                                new OA\Property(property: 'lokasi_samsat_name', type: 'string', nullable: true),
                                new OA\Property(property: 'kecamatan_samsat_name', type: 'string', nullable: true),
                                new OA\Property(property: 'kelurahan_samsat_name', type: 'string', nullable: true),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'OTP invalid/expired'),
        ]
    )]
    public function verifyOtp()
    {
    }

    #[OA\Post(
        path: 'api/update_password',
        tags: ['Auth'],
        summary: 'Ubah password user',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['id', 'password_baru', 'konfirmasi_password'],
                properties: [
                    new OA\Property(property: 'id', type: 'string', example: 'VXNlcjox'),
                    new OA\Property(property: 'password_baru', type: 'string', example: 'passwordBaru123'),
                    new OA\Property(property: 'konfirmasi_password', type: 'string', example: 'passwordBaru123'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Password berhasil diubah'),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ]
    )]
    public function updatePassword()
    {
    }

    #[OA\Get(
        path: 'api/status',
        tags: ['API Master'],
        summary: 'Master: daftar status pendataan (kendaraan)',
        description: 'Data referensi status. Di server di-cache (grup `api:master:`, TTL default 24 jam; atur lewat `CACHE_TTL_MASTER_SECONDS`). ID di response di-encode.',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Berhasil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'List data ditemukan'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', example: 'abc123', description: 'ID terenkode'),
                                    new OA\Property(property: 'nama', type: 'string', example: 'DIMILIKI'),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function apiMasterStatus()
    {
    }

    #[OA\Get(
        path: 'api/status-verifikasi',
        tags: ['API Master'],
        summary: 'Master: daftar status verifikasi',
        description: 'Data referensi status verifikasi. Di server di-cache (grup `api:master:`, TTL default 24 jam).',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Berhasil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'List data ditemukan'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', description: 'ID terenkode'),
                                    new OA\Property(property: 'nama', type: 'string', example: 'MENUNGGU VERIFIKASI'),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function apiMasterStatusVerifikasi()
    {
    }

    #[OA\Get(
        path: 'api/wilayah',
        tags: ['API Master'],
        summary: 'Master: hierarki wilayah',
        description: 'Tanpa query `kode`: daftar root (biasanya provinsi/konteks aplikasi). Dengan `kode`: anak wilayah berdasarkan `id_up`. Di server di-cache per kombinasi parameter (grup `api:master:wilayah:`, TTL default 24 jam).',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'kode',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Opsional. ID parent untuk filter `id_up`. Kosong = daftar root.'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Berhasil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'List data ditemukan: Semua wilayah'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 33),
                                    new OA\Property(property: 'kode', type: 'string', nullable: true),
                                    new OA\Property(property: 'nama', type: 'string', example: 'JAWA TENGAH'),
                                    new OA\Property(property: 'id_up', type: 'integer', nullable: true),
                                    new OA\Property(property: 'kode_samsat', type: 'string', nullable: true),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function apiMasterWilayah()
    {
    }

    #[OA\Get(
        path: 'api/status-file',
        tags: ['API Master'],
        summary: 'Master: template/daftar file per status pendataan',
        description: 'Filter opsional `status` (encoded id status). Di server di-cache per nilai filter (grup `api:master:status-file:`, TTL default 24 jam).',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'status',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Opsional. Encoded ID status untuk filter `id_status`. Kosong = semua.'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Berhasil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'List data ditemukan'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'nama_file', type: 'string'),
                                    new OA\Property(property: 'type_file', type: 'string'),
                                    new OA\Property(property: 'ukuran_file', type: 'string', nullable: true),
                                    new OA\Property(property: 'keterangan_file', type: 'string', nullable: true),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function apiMasterStatusFile()
    {
    }

    #[OA\Post(
        path: 'api/data-tertagih/list',
        tags: ['Data Tertagih'],
        summary: 'Daftar data tertagih (is_terdata=0) dengan filter wilayah samsat + tahun + nopol',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['lokasi_samsat', 'kecamatan_samsat', 'kelurahan_samsat'],
                properties: [
                    new OA\Property(property: 'lokasi_samsat', type: 'string', example: '01', description: 'Wajib. Dicocokkan ke id_lokasi_samsat (nilai asli + tanpa leading zero, mis. 01 dan 1).'),
                    new OA\Property(property: 'kecamatan_samsat', type: 'string', example: '0105', description: 'Wajib. Dicocokkan ke id_kecamatan (mis. 0105 dan 105).'),
                    new OA\Property(property: 'kelurahan_samsat', type: 'string', example: '0105007', description: 'Wajib. Dicocokkan ke id_kelurahan (mis. 0105007 dan 105007).'),
                    new OA\Property(property: 'year', type: 'integer', example: 2026, description: 'Opsional. Default tahun berjalan.'),
                    new OA\Property(property: 'no_polisi', type: 'string', example: 'H8121QY', description: 'Opsional. Pencarian LIKE pada no_polisi.'),
                    new OA\Property(property: 'page', type: 'integer', example: 1),
                    new OA\Property(property: 'per_page', type: 'integer', example: 15),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Berhasil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Data ditemukan'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(
                            property: 'pagination',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'per_page', type: 'integer', example: 15),
                                new OA\Property(property: 'total', type: 'integer', example: 120),
                                new OA\Property(property: 'last_page', type: 'integer', example: 8),
                                new OA\Property(property: 'from', type: 'integer', example: 1),
                                new OA\Property(property: 'to', type: 'integer', example: 15),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ]
    )]
    public function dataTertagihList()
    {
    }

    #[OA\Get(
        path: 'api/data-tertagih/{id}',
        tags: ['Data Tertagih'],
        summary: 'Detail data tertagih by id + cek apakah nopol sudah didata user lain',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'ID data_tertagih'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Berhasil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Data ditemukan'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 12),
                                new OA\Property(property: 'no_polisi', type: 'string', example: 'H-8121-QY'),
                                new OA\Property(property: 'can_select', type: 'boolean', example: false),
                                new OA\Property(property: 'warning_message', type: 'string', nullable: true, example: 'Nopol ini tidak bisa dipilih, karena sudah didata oleh user lain.'),
                                new OA\Property(property: 'pendataan', type: 'object', nullable: true),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Data tidak ditemukan'),
        ]
    )]
    public function dataTertagihShow()
    {
    }

    #[OA\Get(
        path: 'api/pendataan',
        tags: ['Pendataan Kendaraan'],
        summary: 'List pendataan kendaraan milik user login',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10), description: 'Jumlah data per halaman'),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1), description: 'Nomor halaman'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Berhasil mendapatkan daftar data',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'List data ditemukan'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(
                            property: 'pagination',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'last_page', type: 'integer', example: 10),
                                new OA\Property(property: 'per_page', type: 'integer', example: 10),
                                new OA\Property(property: 'total', type: 'integer', example: 100),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function pendataanIndex()
    {
    }

    #[OA\Post(
        path: 'api/pendataan',
        tags: ['Pendataan Kendaraan'],
        summary: 'Tambah data pendataan kendaraan',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nohp', 'nopol', 'nama', 'alamat', 'status', 'status_verifikasi', 'kota', 'kec'],
                properties: [
                    new OA\Property(property: 'nohp', type: 'string', example: '081234567890'),
                    new OA\Property(property: 'nopol', type: 'string', example: 'H1234AB'),
                    new OA\Property(property: 'nama', type: 'string', example: 'Budi Santoso'),
                    new OA\Property(property: 'alamat', type: 'string', example: 'Jl. Merdeka No. 1'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'budi@mail.com'),
                    new OA\Property(property: 'nik', type: 'string', nullable: true, example: '3374010101010001', description: 'Wajib jika status mengarah ke data dengan KTP'),
                    new OA\Property(property: 'status', type: 'string', example: 'VXNlcjox', description: 'Encoded ID status'),
                    new OA\Property(property: 'status_verifikasi', type: 'string', example: 'VXNlcjo1', description: 'Encoded ID status verifikasi'),
                    new OA\Property(property: 'kota', type: 'string', example: '01'),
                    new OA\Property(property: 'kec', type: 'string', example: '3374010'),
                    new OA\Property(property: 'desa_name', type: 'string', nullable: true, example: 'Tembalang'),
                    new OA\Property(property: 'kec_name', type: 'string', nullable: true, example: 'Tembalang'),
                    new OA\Property(property: 'kota_name', type: 'string', nullable: true, example: 'Semarang'),
                    new OA\Property(property: 'merk', type: 'string', nullable: true, example: 'Honda'),
                    new OA\Property(property: 'tipe', type: 'string', nullable: true, example: 'Beat'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Data berhasil ditambahkan'),
            new OA\Response(response: 400, description: 'Validasi gagal / data tidak valid'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function pendataanStore()
    {
    }

    #[OA\Get(
        path: 'api/pendataan/{id}',
        tags: ['Pendataan Kendaraan'],
        summary: 'Detail data pendataan kendaraan',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'), description: 'Encoded ID pendataan'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Data ditemukan'),
            new OA\Response(response: 404, description: 'Data tidak ditemukan'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function pendataanShow()
    {
    }

    #[OA\Put(
        path: 'api/pendataan/{id}',
        tags: ['Pendataan Kendaraan'],
        summary: 'Update data pendataan kendaraan',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'), description: 'Encoded ID pendataan'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nohp', 'nopol', 'nama', 'alamat'],
                properties: [
                    new OA\Property(property: 'nohp', type: 'string', example: '081234567890'),
                    new OA\Property(property: 'nopol', type: 'string', example: 'H1234AB'),
                    new OA\Property(property: 'nama', type: 'string', example: 'Budi Santoso'),
                    new OA\Property(property: 'alamat', type: 'string', example: 'Jl. Merdeka No. 1'),
                    new OA\Property(property: 'nik', type: 'string', nullable: true, example: '3374010101010001'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'budi@mail.com'),
                    new OA\Property(property: 'status', type: 'integer', nullable: true, example: 2),
                    new OA\Property(property: 'status_verifikasi', type: 'integer', nullable: true, example: 5),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Data berhasil diperbarui'),
            new OA\Response(response: 400, description: 'Validasi gagal'),
            new OA\Response(response: 404, description: 'Data tidak ditemukan'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function pendataanUpdate()
    {
    }

    #[OA\Delete(
        path: 'api/pendataan/{id}',
        tags: ['Pendataan Kendaraan'],
        summary: 'Hapus data pendataan kendaraan',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'), description: 'Encoded ID pendataan'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Data berhasil dihapus'),
            new OA\Response(response: 404, description: 'Data tidak ditemukan'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function pendataanDestroy()
    {
    }

    #[OA\Post(
        path: 'api/pendataan/{id}/upload',
        tags: ['Pendataan Kendaraan'],
        summary: 'Upload berkas pendataan (mendukung enkripsi untuk KTP)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'), description: 'Encoded ID pendataan'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file_ke', 'file', 'keterangan'],
                    properties: [
                        new OA\Property(property: 'file_ke', type: 'string', enum: ['file0', 'file1', 'file2', 'file3', 'file4', 'file5', 'file6', 'file7', 'file8', 'file9'], example: 'file0'),
                        new OA\Property(property: 'keterangan', type: 'string', example: 'KTP', description: 'Jika bernilai KTP, file disimpan terenkripsi'),
                        new OA\Property(property: 'file', type: 'string', format: 'binary'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'File berhasil diunggah'),
            new OA\Response(response: 400, description: 'Validasi upload gagal'),
            new OA\Response(response: 404, description: 'Data tidak ditemukan'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function pendataanUpload()
    {
    }

    #[OA\Get(
        path: 'api/secure-file/{id}/{fileIndex}',
        tags: ['Pendataan Kendaraan'],
        summary: 'Ambil file terenkripsi hasil upload',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'), description: 'Encoded ID pendataan'),
            new OA\Parameter(name: 'fileIndex', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 0, maximum: 9), description: 'Index file (0-9)'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'File berhasil diambil'),
            new OA\Response(response: 403, description: 'File bukan file terenkripsi'),
            new OA\Response(response: 404, description: 'Data/file tidak ditemukan'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function pendataanSecureFile()
    {
    }
}
