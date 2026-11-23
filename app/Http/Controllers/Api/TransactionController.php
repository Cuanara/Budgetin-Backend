<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\TransactionException;
use App\Helpers\AuthContextHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class TransactionController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    #[OA\Post(
        path: '/api/transactions',
        summary: 'Simpan transaksi baru',
        tags: ['Transactions'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['category_id', 'type', 'amount', 'transaction_date'],
                properties: [
                    new OA\Property(property: 'category_id', type: 'string', format: 'uuid', example: '0ba7394a-e4a7-11f1-9344-7cfa802770cb'),
                    new OA\Property(property: 'wallet_id', type: 'string', format: 'uuid', nullable: true, example: '0ba7394a-e4a7-11f1-9344-7cfa802770cc'),
                    new OA\Property(property: 'savings_goal_id', type: 'string', format: 'uuid', nullable: true, example: '0ba7394a-e4a7-11f1-9344-7cfa802770cd'),
                    new OA\Property(property: 'type', type: 'string', enum: ['income', 'expense'], example: 'income'),
                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 100000),
                    new OA\Property(property: 'transaction_date', type: 'string', format: 'date', example: '2026-11-20'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Makan / Bonus Januari'),
                    new OA\Property(property: 'notes', type: 'string', nullable: true, example: 'Catatan tambahan'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Transaksi berhasil disimpan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Transaksi berhasil disimpan.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', example: '01a1f7a2-cf56-71b0-acd3-c747f1420c7d'),
                                new OA\Property(property: 'category_id', type: 'string', example: '0ba7394a-e4a7-11f1-9344-7cfa802770cb'),
                                new OA\Property(property: 'wallet_id', type: 'string', example: '0ba7394a-e4a7-11f1-9344-7cfa802770cc'),
                                new OA\Property(property: 'type', type: 'string', example: 'income'),
                                new OA\Property(property: 'amount', type: 'number', example: 100000),
                                new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Makan / Bonus Januari'),
                                new OA\Property(property: 'transaction_date', type: 'string', example: '2026-11-20'),
                                new OA\Property(property: 'created_at', type: 'string', example: '2026-11-20T06:26:44.000000Z'),
                                new OA\Property(property: 'updated_at', type: 'string', example: '2026-11-20T06:26:44.000000Z'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent()),
            new OA\Response(response: 422, description: 'Validasi gagal atau saldo tidak mencukupi', content: new OA\JsonContent()),
            new OA\Response(response: 500, description: 'Terjadi kesalahan pada server', content: new OA\JsonContent()),
        ]
    )]
    public function store(StoreTransactionRequest $request): JsonResponse
    {
        try {
            $transaction = $this->transactionService->createTransaction(
                $request->validated()
            );

            return ResponseHelper::success(
                new TransactionResource($transaction),
                'Transaksi berhasil disimpan.',
                201
            );
        } catch (TransactionException $e) {
            return ResponseHelper::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('Error saat menyimpan transaksi: ' . $e->getMessage(), ['exception' => $e]);

            return ResponseHelper::error('Terjadi kesalahan pada server', 500);
        }
    }

    #[OA\Get(
        path: '/api/transactions',
        summary: 'Daftar riwayat transaksi',
        description: 'Mengembalikan daftar transaksi milik user yang terautentikasi. Dapat difilter dengan per_page, start_date, end_date, category_id.',
        tags: ['Transactions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Jumlah item per halaman (default 20)', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'start_date', in: 'query', description: 'Tanggal mulai (YYYY-MM-DD)', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', description: 'Tanggal akhir (YYYY-MM-DD)', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'category_id', in: 'query', description: 'ID kategori transaksi', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar transaksi berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Riwayat transaksi berhasil diambil'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', example: '01a1f7a2-cf56-71b0-acd3-c747f1420c7d'),
                                    new OA\Property(property: 'category_id', type: 'string', example: '0ba7394a-e4a7-11f1-9344-7cfa802770cb'),
                                    new OA\Property(property: 'type', type: 'string', example: 'income'),
                                    new OA\Property(property: 'amount', type: 'number', example: 50000),
                                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Membeli makan siang'),
                                    new OA\Property(property: 'transaction_date', type: 'string', example: '2026-11-20'),
                                    new OA\Property(property: 'created_at', type: 'string', example: '2026-11-20T06:26:44.000000Z'),
                                    new OA\Property(property: 'updated_at', type: 'string', example: '2026-11-20T06:26:44.000000Z'),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent()),
            new OA\Response(response: 500, description: 'Terjadi kesalahan pada server', content: new OA\JsonContent()),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->query('per_page', 20);
            $userId = AuthContextHelper::userLogin()->id();

            $filters = array_filter([
                'start_date' => $request->query('start_date'),
                'end_date' => $request->query('end_date'),
                'category_id' => $request->query('category_id'),
            ]);

            $transactions = $this->transactionService->getFilteredTransactions(
                $userId,
                $filters,
                $perPage
            );

            return ResponseHelper::success(
                TransactionResource::collection($transactions),
                'Riwayat transaksi berhasil diambil'
            );
        } catch (\Throwable $e) {
            Log::error('Error saat mengambil riwayat transaksi: ' . $e->getMessage(), ['exception' => $e]);

            return ResponseHelper::error('Terjadi kesalahan pada server', 500);
        }
    }
}
