<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BudgetException;
use App\Helpers\AuthContextHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Budget\StoreBudgetAllocationRequest;
use App\Http\Resources\BudgetAllocationResource;
use App\Http\Resources\BudgetResource;
use App\Services\BudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class BudgetController extends Controller
{
    public function __construct(
        protected BudgetService $budgetService
    ) {}


    #[OA\Get(
        path: '/api/budgets',
        summary: 'Ambil data budget dan alokasi',
        description: 'Mengembalikan data budget, total pemasukan, alokasi kategori, persentase diagram, dan nominal terpakai untuk bulan yang dipilih.',
        tags: ['Budget'],
        security: [['bearerAuth' => []]],

        responses: [
            new OA\Response(
                response: 200,
                description: 'Data budget berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Data budget berhasil diambil'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', example: '01a1f7a2-cf56-71b0-acd3-c747f1420c7d'),
                                new OA\Property(property: 'month', type: 'integer', example: 11),
                                new OA\Property(property: 'year', type: 'integer', example: 2026),
                                new OA\Property(property: 'total_income', type: 'number', example: 3000000),
                                new OA\Property(property: 'total_allocated_percentage', type: 'number', example: 100),
                                new OA\Property(property: 'available_percentage', type: 'number', example: 0),
                                new OA\Property(property: 'total_allocated_amount', type: 'number', example: 3000000),
                                new OA\Property(property: 'total_spent_amount', type: 'number', example: 380000),
                                new OA\Property(
                                    property: 'allocations',
                                    type: 'array',
                                    items: new OA\Items(
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'id', type: 'string', example: '01a1f7a2-cf56-71b0-acd3-c747f1420c7e'),
                                            new OA\Property(property: 'name', type: 'string', example: 'Makan'),
                                            new OA\Property(property: 'percentage', type: 'number', example: 40),
                                            new OA\Property(property: 'allocated_amount', type: 'number', example: 1200000),
                                            new OA\Property(property: 'spent_amount', type: 'number', example: 380000),
                                            new OA\Property(property: 'remaining_amount', type: 'number', example: 820000),
                                            new OA\Property(property: 'percentage_used', type: 'number', example: 31.7),
                                        ]
                                    )
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent()),
            new OA\Response(response: 500, description: 'Terjadi kesalahan pada server', content: new OA\JsonContent()),
        ]
    )]
    public function index(): JsonResponse
    {
        try {
            $userId = AuthContextHelper::userLogin()->id();
            $month = null;
            $year = null;

            $budget = $this->budgetService->getBudget($userId, $month, $year);

            return ResponseHelper::success(
                new BudgetResource($budget),
                'Data budget berhasil diambil'
            );
        }
 catch (\Throwable $e) {
            Log::error('Error saat mengambil data budget: ' . $e->getMessage(), ['exception' => $e]);
            return ResponseHelper::error('Terjadi kesalahan pada server', 500);
        }

    }


    #[OA\Post(
        path: '/api/budgets/allocations',
        summary: 'Tambah kategori ke alokasi budget',
        description: 'Menambahkan kategori budget baru. Jika total alokasi > 100%, sistem akan memotong jatah dari source_category_id / source_allocation_id yang dipilih.',
        tags: ['Budget'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['percentage'],
                properties: [
                    new OA\Property(property: 'month', type: 'integer', example: 11),
                    new OA\Property(property: 'year', type: 'integer', example: 2026),
                    new OA\Property(property: 'category_id', type: 'string', format: 'uuid', nullable: true, example: '0ba7394a-e4a7-11f1-9344-7cfa802770cb'),
                    new OA\Property(property: 'name', type: 'string', nullable: true, example: 'Hiburan'),
                    new OA\Property(property: 'percentage', type: 'number', format: 'float', example: 10),
                    new OA\Property(property: 'source_category_id', type: 'string', format: 'uuid', nullable: true, example: '0ba7394a-e4a7-11f1-9344-7cfa802770cc'),
                    new OA\Property(property: 'source_allocation_id', type: 'string', format: 'uuid', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Kategori budget berhasil ditambahkan', content: new OA\JsonContent()),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent()),
            new OA\Response(response: 422, description: 'Validasi gagal atau alokasi melebihi 100%', content: new OA\JsonContent()),
            new OA\Response(response: 500, description: 'Terjadi kesalahan pada server', content: new OA\JsonContent()),
        ]
    )]
    public function storeAllocation(StoreBudgetAllocationRequest $request): JsonResponse
    {
        try {
            $userId = AuthContextHelper::userLogin()->id();
            $allocation = $this->budgetService->addAllocation($userId, $request->validated());

            return ResponseHelper::success(
                new BudgetAllocationResource($allocation),
                'Kategori budget berhasil ditambahkan',
                201
            );
        }
 catch (BudgetException $e) {
            return ResponseHelper::error($e->getMessage(), $e->getStatusCode());
        }
 catch (\Throwable $e) {
            Log::error('Error saat menambahkan kategori budget: ' . $e->getMessage(), ['exception' => $e]);
            return ResponseHelper::error('Terjadi kesalahan pada server', 500);
        }

    }


    #[OA\Delete(
        path: '/api/budgets/allocations/{id}',
        summary: 'Hapus alokasi kategori budget',
        tags: ['Budget'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID alokasi budget', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Alokasi kategori budget berhasil dihapus', content: new OA\JsonContent()),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent()),
            new OA\Response(response: 404, description: 'Data tidak ditemukan', content: new OA\JsonContent()),
            new OA\Response(response: 500, description: 'Terjadi kesalahan pada server', content: new OA\JsonContent()),
        ]
    )]
    public function destroyAllocation(string $id): JsonResponse
    {
        try {
            $userId = AuthContextHelper::userLogin()->id();
            $this->budgetService->deleteAllocation($userId, $id);

            return ResponseHelper::success(null, 'Alokasi kategori budget berhasil dihapus');
        }
 catch (BudgetException $e) {
            return ResponseHelper::error($e->getMessage(), $e->getStatusCode());
        }
 catch (\Throwable $e) {
            Log::error('Error saat menghapus alokasi budget: ' . $e->getMessage(), ['exception' => $e]);
            return ResponseHelper::error('Terjadi kesalahan pada server', 500);
        }

    }


}