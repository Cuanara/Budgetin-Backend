<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSavingsDepositRequest;
use App\Services\SavingsDepositService;

class SavingsDepositController extends Controller
{
    protected SavingsDepositService $savingsDepositService;

    public function __construct(SavingsDepositService $savingsDepositService)
    {
        $this->savingsDepositService = $savingsDepositService;
    }

    public function index(string $savingsGoalId)
    {
        $deposits = $this->savingsDepositService->getAll($savingsGoalId);

        return response()->json([
            'success' => true,
            'message' => 'Riwayat setoran berhasil diambil.',
            'data' => $deposits,
        ]);
    }

    public function store(
        StoreSavingsDepositRequest $request,
        string $savingsGoalId
    ) {
        $deposit = $this->savingsDepositService->create(
            $savingsGoalId,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Setoran tabungan berhasil disimpan.',
            'data' => $deposit,
        ], 201);
    }
}
