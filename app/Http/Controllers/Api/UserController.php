<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, string $id)
    {
        return new UserResource($request->validated());
    }

    #[OA\Delete(
        path: '/api/users/{id}',
        summary: 'Menghapus user',
        description: 'Menghapus user berdasarkan ID.',
        security: [['bearerAuth' => []]],
        tags: ['User'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID user yang akan dihapus',
                schema: new OA\Schema(type: 'string', example: '01a1fdd4-b997-73bf-b43b-5cafcd4279df')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Berhasil menghapus user',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Berhasil menghapus user'),
                        new OA\Property(property: 'data', type: 'object', nullable: true, example: null),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Belum login / token tidak valid',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Akun tidak sesuai dengan yang login',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Akun tidak sesuai dengan yang login'),
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Terjadi kesalahan pada server',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Terjadi kesalahan pada server'),
                    ]
                )
            ),
        ]
    )]
    public function destroy(string $id): JsonResponse
    {
        try {
            $result = $this->userService->delete($id);

            if (! $result) {
                return ResponseHelper::error('Akun telah terhapus atau belum terdaftar', 401);
            }

            return ResponseHelper::success(null, 'Berhasil menghapus user');
        } catch (\Throwable $e) {
            Log::error('Error saat menghapus user: '.$e->getMessage(), ['exception' => $e]);

            return ResponseHelper::error('Terjadi kesalahan pada server', 500);
        }
    }
}
