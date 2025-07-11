<?php

namespace App\Services;

use App\Models\User;
use App\Models\Operation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BalanceService
{
    protected OperationService $operationService;
    protected UserService $userService;

    public function __construct(OperationService $operationService, UserService $userService)
    {
        $this->operationService = $operationService;
        $this->userService = $userService;
    }

    /**
     * Process operation and update user balance
     */
    public function processOperation(
        User $user,
        float $amount,
        string $action,
        ?string $description = null
    ): array {
        return DB::transaction(function () use ($user, $amount, $action, $description) {
            // Calculate new balance
            $currentBalance = $user->balance;
            $newBalance = $this->calculateNewBalance($currentBalance, $amount, $action);

            // Check if balance would go negative
            if ($newBalance < 0) {
                throw new \Exception('Недостаточно средств для выполнения операции');
            }

            // Create operation
            $operation = $this->operationService->createOperation($user, $amount, $action, $description);

            // Update user balance
            $this->userService->updateBalance($user, $newBalance);

            Log::info('Operation processed', [
                'user_id' => $user->id,
                'operation_id' => $operation->id,
                'action' => $action,
                'amount' => $amount,
                'old_balance' => $currentBalance,
                'new_balance' => $newBalance,
            ]);

            return [
                'operation' => $operation,
                'old_balance' => $currentBalance,
                'new_balance' => $newBalance,
                'success' => true,
            ];
        });
    }

    /**
     * Calculate new balance based on operation
     */
    private function calculateNewBalance(float $currentBalance, float $amount, string $action): float
    {
        return match ($action) {
            'deposit' => $currentBalance + $amount,
            'withdraw' => $currentBalance - $amount,
            default => throw new \InvalidArgumentException('Invalid action: ' . $action),
        };
    }

    /**
     * Get balance history for user
     */
    public function getBalanceHistory(User $user, int $limit = 50): array
    {
        $operations = $user->operations()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $history = [];
        $runningBalance = $user->balance;

        foreach ($operations->reverse() as $operation) {
            $runningBalance = $this->calculateNewBalance(
                $runningBalance,
                $operation->amount,
                $operation->action === 'withdraw' ? 'deposit' : 'withdraw'
            );

            $history[] = [
                'operation_id' => $operation->id,
                'date' => $operation->created_at,
                'action' => $operation->action,
                'amount' => $operation->amount,
                'description' => $operation->description,
                'balance_after' => $runningBalance,
            ];
        }

        return array_reverse($history);
    }

    /**
     * Get balance statistics for user
     */
    public function getBalanceStatistics(User $user): array
    {
        $operations = $user->operations();

        $deposits = $operations->where('action', 'deposit');
        $withdrawals = $operations->where('action', 'withdraw');

        return [
            'current_balance' => $user->balance,
            'total_deposited' => $deposits->sum('amount'),
            'total_withdrawn' => $withdrawals->sum('amount'),
            'deposits_count' => $deposits->count(),
            'withdrawals_count' => $withdrawals->count(),
            'average_deposit' => $deposits->count() > 0 ? $deposits->avg('amount') : 0,
            'average_withdrawal' => $withdrawals->count() > 0 ? $withdrawals->avg('amount') : 0,
            'largest_deposit' => $deposits->max('amount'),
            'largest_withdrawal' => $withdrawals->max('amount'),
        ];
    }

    /**
     * Validate operation before processing
     */
    public function validateOperation(float $amount, string $action): array
    {
        $errors = [];

        if ($amount <= 0) {
            $errors[] = 'Сумма должна быть больше нуля';
        }

        if (!in_array($action, ['deposit', 'withdraw'])) {
            $errors[] = 'Недопустимый тип операции';
        }

        if ($amount > 1000000) {
            $errors[] = 'Сумма не может превышать 1,000,000';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Get balance trends for user
     */
    public function getBalanceTrends(User $user, int $days = 30): array
    {
        $operations = $user->operations()
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at')
            ->get();

        $trends = [];
        $runningBalance = 0;

        // Calculate starting balance
        $startingBalance = $user->balance;
        foreach ($operations->reverse() as $operation) {
            $runningBalance = $this->calculateNewBalance(
                $runningBalance,
                $operation->amount,
                $operation->action === 'withdraw' ? 'deposit' : 'withdraw'
            );
        }

        foreach ($operations as $operation) {
            $runningBalance = $this->calculateNewBalance($runningBalance, $operation->amount, $operation->action);
            
            $trends[] = [
                'date' => $operation->created_at->format('Y-m-d'),
                'balance' => $runningBalance,
                'operation' => $operation->action,
                'amount' => $operation->amount,
            ];
        }

        return $trends;
    }
} 