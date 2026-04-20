<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(title: 'Sengkuyung API', version: '1.0.0', description: 'Dokumentasi API untuk aplikasi Sengkuyung.')]
#[OA\Server(url: '/', description: 'Server utama')]
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
        path: '/api/login',
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
        path: '/api/login_with_otp',
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
        path: '/api/verifikasi_otp',
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
            new OA\Response(response: 200, description: 'OTP valid, login berhasil'),
            new OA\Response(response: 401, description: 'OTP invalid/expired'),
        ]
    )]
    public function verifyOtp()
    {
    }

    #[OA\Post(
        path: '/api/update_password',
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
}
