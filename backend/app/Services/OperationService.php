<?php

namespace App\Services;

use App\Models\User;
use App\Models\Operation;
use Illuminate\Support\Facades\DB;

class OperationService
{
    public function createOperation(User $user, array $data): Operation
    {
        return DB::transaction(function () use ($user, $data) {
            $operation = $user->operations()->create([
                'amount' => $data['amount'],
                'action' => $data['action'],
                'description' => $data['description'] ?? null,
            ]);

            $this->updateUserBalance($user, $data['amount'], $data['action']);

            return $operation;
        });
    }

    public function getRecentOperations(User $user, int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return $user->operations()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getOperations(User $user, int $perPage = 10, string $sort = 'desc', string $search = '', int $page = 1): array
    {
        $query = $user->operations();

        if ($search) {
            $query->where('description', 'like', "%{$search}%");
        }

        $operations = $query->orderBy('created_at', $sort)
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $operations->items(),
            'meta' => [
                'current_page' => $operations->currentPage(),
                'last_page' => $operations->lastPage(),
                'per_page' => $operations->perPage(),
                'total' => $operations->total(),
                'from' => $operations->firstItem(),
                'to' => $operations->lastItem(),
            ]
        ];
    }

    public function getStatistics(User $user): array
    {
        $operations = $user->operations();

        return [
            'total_operations' => $operations->count(),
            'total_deposits' => $operations->where('action', 'deposit')->count(),
            'total_withdrawals' => $operations->where('action', 'withdraw')->count(),
            'total_deposited' => $operations->where('action', 'deposit')->sum('amount'),
            'total_withdrawn' => $operations->where('action', 'withdraw')->sum('amount'),
            'current_balance' => $user->balance,
            'average_deposit' => $operations->where('action', 'deposit')->avg('amount'),
            'average_withdrawal' => $operations->where('action', 'withdraw')->avg('amount'),
        ];
    }

    public function getOperationsByDateRange(User $user, string $startDate, string $endDate): \Illuminate\Database\Eloquent\Collection
    {
        return $user->operations()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getMonthlySummary(User $user, int $year): array
    {
        $operations = $user->operations()
            ->whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) as month, action, SUM(amount) as total')
            ->groupBy('month', 'action')
            ->get();

        $summary = [];
        for ($month = 1; $month <= 12; $month++) {
            $summary[$month] = [
                'deposits' => 0,
                'withdrawals' => 0,
                'net' => 0
            ];
        }

        foreach ($operations as $operation) {
            $month = $operation->month;
            $amount = $operation->total;

            if ($operation->action === 'deposit') {
                $summary[$month]['deposits'] = $amount;
            } else {
                $summary[$month]['withdrawals'] = $amount;
            }

            $summary[$month]['net'] = $summary[$month]['deposits'] - $summary[$month]['withdrawals'];
        }

        return $summary;
    }

    private function updateUserBalance(User $user, float $amount, string $action): void
    {
        $currentBalance = $user->balance;
        
        if ($action === 'deposit') {
            $newBalance = $currentBalance + $amount;
        } else {
            $newBalance = $currentBalance - $amount;
        }

        if ($newBalance < 0) {
            throw new \Exception('Недостаточно средств для выполнения операции');
        }

        $user->update(['balance' => $newBalance]);
    }
} 