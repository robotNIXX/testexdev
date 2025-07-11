<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UpdateUserBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:update-balance {email} {balance}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update user balance by email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $balance = $this->argument('balance');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email '{$email}' not found.");
            return 1;
        }

        try {
            $user->update(['balance' => $balance]);
            $this->info("User '{$user->name}' balance updated to {$balance}");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to update user balance: " . $e->getMessage());
            return 1;
        }
    }
} 