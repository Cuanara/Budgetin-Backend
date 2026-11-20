<?php

namespace App\Services;

use App\Models\SavingsGoal;
use Illuminate\Support\Facades\Auth;

class SavingsGoalService
{
    public function getAll()
    {
        return SavingsGoal::where('user_id', Auth::id())
            ->latest()
            ->get();
    }

    public function create(array $data)
    {
        $data['user_id'] = Auth::id();

        return SavingsGoal::create($data);
    }

    public function getById(string $id)
    {
        return SavingsGoal::where('user_id', Auth::id())
            ->with('deposits')
            ->findOrFail($id);
    }

    public function update(string $id, array $data)
    {
        $savingsGoal = $this->getById($id);

        $savingsGoal->update($data);

        return $savingsGoal->fresh();
    }

    public function delete(string $id): bool
    {
        $savingsGoal = $this->getById($id);

        return $savingsGoal->delete();
    }
}
