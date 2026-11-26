<?php

namespace App\Swagger;

/**
 * @OA\Info(
 *     title="Budgetin API",
 *     version="1.0.0",
 *     description="Dokumentasi API untuk aplikasi Budgetin."
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Masukkan token autentikasi."
 * )
 */
class OpenApiSpec
{
}