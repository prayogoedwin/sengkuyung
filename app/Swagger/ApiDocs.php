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
}
