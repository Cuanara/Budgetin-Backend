<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSavingsGoalRequest;
use App\Services\SavingsGoalService;

class SavingsGoalController extends Controller
{
    protected SavingsGoalService $savingsGoalService;

    public function __construct(SavingsGoalService $savingsGoalService)
    {
        $this->savingsGoalService = $savingsGoalService;
    }

    public function index()
    {
        $savingsGoals = $this->savingsGoalService->getAll();

        return response()->json([
            'success' => true,
            'message' => 'Data tabungan berhasil diambil.',
            'data' => $savingsGoals,
        ]);
    }

    public function store(StoreSavingsGoalRequest $request)
    {
        $savingsGoal = $this->savingsGoalService->create(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Tabungan berhasil dibuat.',
            'data' => $savingsGoal,
        ], 201);
    }

    public function show(string $id)
    {
        $savingsGoal = $this->savingsGoalService->getById($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail tabungan berhasil diambil.',
            'data' => $savingsGoal,
        ]);
    }

    public function update(StoreSavingsGoalRequest $request, string $id)
    {
        $savingsGoal = $this->savingsGoalService->update(
            $id,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Tabungan berhasil diperbarui.',
            'data' => $savingsGoal,
        ]);
    }

    public function destroy(string $id)
    {
        $this->savingsGoalService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Tabungan berhasil dihapus.',
        ]);
    }
}
