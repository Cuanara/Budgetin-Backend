<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSavingsDepositRequest;
use App\Http\Resources\SavingsDepositResource;
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
        $deposits = $this->savingsDepositService->getAllBySavingsGoal(
            $savingsGoalId
        );

        return ResponseHelper::success(
            SavingsDepositResource::collection($deposits),
            'Riwayat setoran berhasil diambil.'
        );
    }

    public function store(
        StoreSavingsDepositRequest $request,
        string $savingsGoalId
    ) {
        $deposit = $this->savingsDepositService->create(
            $savingsGoalId,
            $request->validated()
        );

        return ResponseHelper::success(
            new SavingsDepositResource($deposit),
            'Setoran tabungan berhasil disimpan.',
            201
        );
    }
}
