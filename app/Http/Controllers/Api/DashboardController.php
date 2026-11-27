<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardResource;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;


class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    #[OA\Get(
        path: '/api/dashboard',
        summary: 'Ambil ringkasan data Beranda',
        description: 'Mengambil saldo saat ini (Main Wallet), pengeluaran bulan ini beserta kategori, persentase pengeluaran bulan ini, dan transaksi terbaru.',
        security: [['bearerAuth' => []]],
        tags: ['Dashboard'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data dashboard berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Data dashboard berhasil diambil'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(
                                    property: 'current_balance',
                                    properties: [
                                        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 5000000.0),
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(
                                    property: 'current_savings',
                                    properties: [
                                        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 10650000.0),
                                        new OA\Property(property: 'total_wishlist_price', type: 'number', format: 'float', example: 20650000.0),
                                        new OA\Property(property: 'remaining_for_wishlist', type: 'number', format: 'float', example: 10000000.0),
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(
                                    property: 'monthly_expense',
                                    properties: [
                                        new OA\Property(property: 'total', type: 'number', format: 'float', example: 1250000.0),
                                        new OA\Property(property: 'percentage_of_budget', type: 'number', format: 'float', example: 25.0),
                                        new OA\Property(
                                            property: 'by_category',
                                            type: 'array',
                                            items: new OA\Items(
                                                properties: [
                                                    new OA\Property(property: 'category_id', type: 'string', format: 'uuid', example: '9d123456-7890-4abc-def0-1234567890ab'),
                                                    new OA\Property(property: 'name', type: 'string', example: 'Makanan & Minuman'),
                                                    new OA\Property(property: 'color', type: 'string', example: '#FF5733', nullable: true),
                                                    new OA\Property(property: 'total', type: 'number', format: 'float', example: 750000.0),
                                                    new OA\Property(property: 'percentage', type: 'number', format: 'float', example: 60.0),
                                                ],
                                                type: 'object'
                                            )
                                        ),
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(
                                    property: 'expense_summary',
                                    properties: [
                                        new OA\Property(property: 'percentage_used', type: 'number', format: 'float', example: 25.0),
                                        new OA\Property(property: 'spent_amount', type: 'number', format: 'float', example: 1250000.0),
                                        new OA\Property(property: 'budget_amount', type: 'number', format: 'float', example: 5000000.0),
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(
                                    property: 'recent_transactions',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '9d123456-7890-4abc-def0-9876543210fe'),
                                            new OA\Property(property: 'title', type: 'string', example: 'Makan siang'),
                                            new OA\Property(property: 'category_name', type: 'string', example: 'Makanan & Minuman'),
                                            new OA\Property(property: 'type', type: 'string', example: 'expense'),
                                            new OA\Property(property: 'amount', type: 'number', format: 'float', example: 45000.0),
                                            new OA\Property(property: 'transaction_date', type: 'string', format: 'date', example: '2026-11-26'),
                                        ],
                                        type: 'object'
                                    )
                                ),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(): JsonResponse
    {
        $userId = (string) Auth::id();
        $data = $this->dashboardService->getDashboardData($userId);

        return (new DashboardResource($data))
            ->additional([
                'success' => true,
                'message' => 'Data dashboard berhasil diambil',
            ])
            ->response()
            ->setStatusCode(200);
    }
}