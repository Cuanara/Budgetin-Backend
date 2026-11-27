<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AuthException;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\IndexNotificationRequest;
use App\Http\Requests\Notification\UpdateNotificationRequest;
use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    #[OA\Get(
        path: '/api/notifications',
        summary: 'Daftar notifikasi',
        description: 'Mengambil daftar notifikasi milik pengguna yang sedang login dengan pagination.',
        security: [['bearerAuth' => []]],
        tags: ['Notifications'],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Jumlah data per halaman (maksimal 100)',
                schema: new OA\Schema(type: 'integer', default: 10, minimum: 1, maximum: 100)
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                description: 'Nomor halaman',
                schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notifikasi berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Notifikasi berhasil diambil'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(
                                    property: 'data',
                                    type: 'array',
                                    items: new OA\Items(ref: '#/components/schemas/Notification')
                                ),
                                new OA\Property(property: 'per_page', type: 'integer', example: 10),
                                new OA\Property(property: 'total', type: 'integer', example: 42),
                                new OA\Property(property: 'last_page', type: 'integer', example: 5),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Tidak terautentikasi', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validasi gagal', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function index(IndexNotificationRequest $request): JsonResponse
    {
        $perPage = min((int) $request->validated('per_page', 10), 100);

        $notifications = $this->notificationService->getByUser(
            $perPage
        );

        return ResponseHelper::success(new NotificationResource($notifications), 'Notifikasi berhasil diambil');
    }

    #[OA\Patch(
        path: '/api/notifications/{id}',
        summary: 'Tandai notifikasi sudah dibaca',
        description: 'Menandai satu notifikasi sebagai sudah dibaca berdasarkan ID.',
        security: [['bearerAuth' => []]],
        tags: ['Notifications'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID notifikasi (UUID)',
                schema: new OA\Schema(type: 'string', format: 'uuid', example: '9f1c2b7e-4a6d-4c1e-9b0a-3d5e8f7a1b2c')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notifikasi ditandai sudah dibaca',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Notifikasi ditandai sudah dibaca'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Notification'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Tidak terautentikasi', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'ID notifikasi tidak ditemukan', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validasi gagal', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Kesalahan server', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(UpdateNotificationRequest $request): JsonResponse
    {
        try {
            $notification = $this->notificationService->markAsRead(
                $request->validated()
            );

            if ($notification === null) {
                return ResponseHelper::error('Notifikasi tidak ada', 404);
            }

            return ResponseHelper::success(new NotificationResource($notification), 'Notifikasi ditandai sudah dibaca');
        } catch (AuthException $e) {
            return ResponseHelper::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('Error saat update notifikasi: '.$e->getMessage(), ['exception' => $e]);

            return ResponseHelper::error('Terjadi kesalahan pada server', 500);
        }
    }
}
