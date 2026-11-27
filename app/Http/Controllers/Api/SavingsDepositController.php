<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSavingsDepositRequest;
use App\Http\Resources\SavingsDepositResource;
use App\Services\SavingsDepositService;
use OpenApi\Attributes as OA;

class SavingsDepositController extends Controller
{
    protected SavingsDepositService $savingsDepositService;

    public function __construct(SavingsDepositService $savingsDepositService)
    {
        $this->savingsDepositService = $savingsDepositService;
    }

    #[OA\Get(
        path: '/api/savings-goals/{savingsGoalId}/deposits',
        summary: 'Menampilkan riwayat setoran tabungan',
        description: 'Mengambil seluruh riwayat setoran berdasarkan ID target tabungan.',
        tags: ['Savings Deposits'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'savingsGoalId',
                in: 'path',
                required: true,
                description: 'UUID target tabungan',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Riwayat setoran berhasil diambil.'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated.'
            ),
            new OA\Response(
                response: 404,
                description: 'Target tabungan tidak ditemukan.'
            ),
        ]
    )]
    public function index(string $savingsGoalId)
    {
        $deposits = $this->savingsDepositService->getAllBySavingsGoal($savingsGoalId);

        return SavingsDepositResource::collection($deposits)
            ->additional([
                'message' => 'Riwayat setoran berhasil diambil.',
            ]);
    }

    #[OA\Post(
        path: '/api/savings-goals/{savingsGoalId}/deposits',
        summary: 'Menambahkan setoran tabungan',
        description: 'Menyimpan setoran ke target tabungan dan memperbarui saldo Wallet.',
        tags: ['Savings Deposits'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'savingsGoalId',
                in: 'path',
                required: true,
                description: 'UUID target tabungan',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['amount', 'deposit_date'],
                properties: [
                    new OA\Property(
                        property: 'wishlist_id',
                        type: 'string',
                        format: 'uuid',
                        nullable: true,
                        description: 'Opsional: isi jika setoran ditujukan untuk item wishlist tertentu',
                        example: '01a1f7a2-cf56-71b0-acd3-c747f1420c7e'
                    ),
                    new OA\Property(
                        property: 'amount',
                        type: 'number',
                        format: 'float',
                        example: 50000
                    ),
                    new OA\Property(
                        property: 'deposit_date',
                        type: 'string',
                        format: 'date',
                        example: '2026-11-25'
                    ),
                    new OA\Property(
                        property: 'notes',
                        type: 'string',
                        nullable: true,
                        maxLength: 255,
                        example: 'Setoran tabungan bulanan'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Setoran tabungan berhasil disimpan.'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated.'
            ),
            new OA\Response(
                response: 404,
                description: 'Target tabungan tidak ditemukan.'
            ),
            new OA\Response(
                response: 422,
                description: 'Validasi gagal atau saldo Wallet tidak mencukupi.'
            ),
        ]
    )]
    public function store(
        StoreSavingsDepositRequest $request,
        string $savingsGoalId
    ) {
        $deposit = $this->savingsDepositService->create(
            $savingsGoalId,
            $request->validated()
        );

        return (new SavingsDepositResource($deposit))
            ->additional([
                'message' => 'Setoran tabungan berhasil disimpan.',
            ])
            ->response()
            ->setStatusCode(201);
    }
}
