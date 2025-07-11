<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\BalanceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessUserOperation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $amount;
    protected $action;
    protected $description;

    /**
     * Create a new job instance.
     */
    public function __construct($userId, $amount, $action, $description = null)
    {
        $this->userId = $userId;
        $this->amount = $amount;
        $this->action = $action;
        $this->description = $description;
    }

    /**
     * Execute the job.
     */
    public function handle(BalanceService $balanceService): void
    {
        $user = User::find($this->userId);
        if (!$user) {
            Log::error('User not found for operation', [
                'user_id' => $this->userId,
                'amount' => $this->amount,
                'action' => $this->action,
            ]);
            throw new \Exception('User not found');
        }

        try {
            $result = $balanceService->processOperation(
                $user,
                $this->amount,
                $this->action,
                $this->description
            );

            Log::info('Operation processed successfully', [
                'user_id' => $user->id,
                'operation_id' => $result['operation']->id,
                'action' => $this->action,
                'amount' => $this->amount,
                'old_balance' => $result['old_balance'],
                'new_balance' => $result['new_balance'],
            ]);
        } catch (\Exception $e) {
            Log::error('Operation processing failed', [
                'user_id' => $user->id,
                'amount' => $this->amount,
                'action' => $this->action,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
