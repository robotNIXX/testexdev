<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\BalanceService;
use App\Jobs\ProcessUserOperation;

class CreateUserOperation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:operation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a user operation (deposit/withdraw) and process it via queue (interactive)';

    protected BalanceService $balanceService;

    public function __construct(BalanceService $balanceService)
    {
        parent::__construct();
        $this->balanceService = $balanceService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Интерактивный ввод
        $email = $this->ask('Enter user email');
        $amount = (float)$this->ask('Enter amount');
        $action = $this->choice('Select action', ['deposit', 'withdraw'], 'deposit');
        $description = $this->ask('Enter description (optional)', '');

        // Валидация операции
        $validation = $this->balanceService->validateOperation($amount, $action);
        if (!$validation['valid']) {
            $this->error('Validation errors:');
            foreach ($validation['errors'] as $error) {
                $this->error("- {$error}");
            }
            return 1;
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("User with email '{$email}' not found.");
            return 1;
        }

        // Показываем текущий баланс пользователя
        $this->info("Current balance: {$user->balance}");

        // Проверяем, будет ли баланс отрицательным после операции
        $newBalance = $action === 'deposit' ? $user->balance + $amount : $user->balance - $amount;
        if ($newBalance < 0) {
            $this->warn("Warning: Balance will be negative ({$newBalance}) after this operation!");
            if (!$this->confirm('Continue anyway?')) {
                return 1;
            }
        }

        // Ставим задачу в очередь
        ProcessUserOperation::dispatch($user->id, $amount, $action, $description);
        $this->info("Operation queued for user {$user->email} ({$action} {$amount})");
        
        if ($newBalance >= 0) {
            $this->info("New balance will be: {$newBalance}");
        }
        
        return 0;
    }
} 