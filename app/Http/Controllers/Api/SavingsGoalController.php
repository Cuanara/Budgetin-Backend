<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSavingsGoalRequest;
use App\Http\Resources\SavingsGoalResource;
use App\Services\SavingsGoalService;
use OpenApi\Attributes as OA;

class SavingsGoalController extends Controller
{
    protected SavingsGoalService $savingsGoalService;

    public function __construct(SavingsGoalService $savingsGoalService)
    {
        $this->savingsGoalService = $savingsGoalService;
    }

    #[OA\Get(
        path: '/api/savings-goals',
        summary: 'Menampilkan semua data tabungan',
        description: 'Mengambil seluruh target tabungan milik pengguna yang sedang login.',
        tags: ['Savings Goals'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data tabungan berhasil diambil.'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated.'
            ),
        ]
    )]
    public function index()
    {
        $savingsGoals = $this->savingsGoalService->getAll();

        return SavingsGoalResource::collection($savingsGoals)
            ->additional([
                'message' => 'Data tabungan berhasil diambil.',
            ]);
    }

    #[OA\Post(
        path: '/api/savings-goals',
        summary: 'Membuat target tabungan',
        description: 'Membuat target tabungan baru untuk pengguna yang sedang login.',
        tags: ['Savings Goals'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'target_amount'],
                properties: [
                    new OA\Property(
                        property: 'name',
                        type: 'string',
                        maxLength: 150,
                        example: 'Dana Darurat'
                    ),
                    new OA\Property(
                        property: 'target_amount',
                        type: 'number',
                        format: 'float',
                        example: 1000000
                    ),
                    new OA\Property(
                        property: 'target_date',
                        type: 'string',
                        format: 'date',
                        nullable: true,
                        example: '2026-12-31'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Tabungan berhasil dibuat.'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated.'
            ),
            new OA\Response(
                response: 422,
                description: 'Validasi gagal.'
            ),
        ]
    )]
    public function store(StoreSavingsGoalRequest $request)
    {
        $savingsGoal = $this->savingsGoalService->create(
            $request->validated()
        );

        return (new SavingsGoalResource($savingsGoal))
            ->additional([
                'message' => 'Tabungan berhasil dibuat.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/savings-goals/{savings_goal}',
        summary: 'Menampilkan detail tabungan',
        description: 'Mengambil detail target tabungan berdasarkan ID.',
        tags: ['Savings Goals'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'savings_goal',
                in: 'path',
                required: true,
                description: 'UUID target tabungan',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail tabungan berhasil diambil.'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated.'
            ),
            new OA\Response(
                response: 404,
                description: 'Data tabungan tidak ditemukan.'
            ),
        ]
    )]
    public function show(string $id)
    {
        $savingsGoal = $this->savingsGoalService->getById($id);

        return (new SavingsGoalResource($savingsGoal))
            ->additional([
                'message' => 'Detail tabungan berhasil diambil.',
            ]);
    }

    #[OA\Put(
        path: '/api/savings-goals/{savings_goal}',
        summary: 'Memperbarui target tabungan',
        description: 'Memperbarui data target tabungan berdasarkan ID.',
        tags: ['Savings Goals'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'savings_goal',
                in: 'path',
                required: true,
                description: 'UUID target tabungan',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'target_amount'],
                properties: [
                    new OA\Property(
                        property: 'name',
                        type: 'string',
                        maxLength: 150,
                        example: 'Dana Pendidikan'
                    ),
                    new OA\Property(
                        property: 'target_amount',
                        type: 'number',
                        format: 'float',
                        example: 2000000
                    ),
                    new OA\Property(
                        property: 'target_date',
                        type: 'string',
                        format: 'date',
                        nullable: true,
                        example: '2026-12-31'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tabungan berhasil diperbarui.'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated.'
            ),
            new OA\Response(
                response: 422,
                description: 'Validasi gagal.'
            ),
        ]
    )]
    #[OA\Patch(
        path: '/api/savings-goals/{savings_goal}',
        summary: 'Memperbarui target tabungan',
        description: 'Memperbarui data target tabungan berdasarkan ID.',
        tags: ['Savings Goals'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'savings_goal',
                in: 'path',
                required: true,
                description: 'UUID target tabungan',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'target_amount'],
                properties: [
                    new OA\Property(
                        property: 'name',
                        type: 'string',
                        maxLength: 150,
                        example: 'Dana Pendidikan'
                    ),
                    new OA\Property(
                        property: 'target_amount',
                        type: 'number',
                        format: 'float',
                        example: 2000000
                    ),
                    new OA\Property(
                        property: 'target_date',
                        type: 'string',
                        format: 'date',
                        nullable: true,
                        example: '2026-12-31'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tabungan berhasil diperbarui.'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated.'
            ),
            new OA\Response(
                response: 422,
                description: 'Validasi gagal.'
            ),
        ]
    )]
    public function update(StoreSavingsGoalRequest $request, string $id)
    {
        $savingsGoal = $this->savingsGoalService->update(
            $id,
            $request->validated()
        );

        return (new SavingsGoalResource($savingsGoal))
            ->additional([
                'message' => 'Tabungan berhasil diperbarui.',
            ]);
    }

    #[OA\Delete(
        path: '/api/savings-goals/{savings_goal}',
        summary: 'Menghapus target tabungan',
        description: 'Menghapus target tabungan berdasarkan ID.',
        tags: ['Savings Goals'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'savings_goal',
                in: 'path',
                required: true,
                description: 'UUID target tabungan',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Tabungan berhasil dihapus.'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated.'
            ),
            new OA\Response(
                response: 404,
                description: 'Data tabungan tidak ditemukan.'
            ),
        ]
    )]
    public function destroy(string $id)
    {
        $this->savingsGoalService->delete($id);

        return response()->noContent();
    }
}
