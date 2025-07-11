<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user (interactive)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Интерактивный ввод
        $name = $this->ask('Enter user name');
        $email = $this->ask('Enter user email');
        $balance = $this->ask('Enter initial balance (default: 0.00)', '0.00');

        // Пароль с подтверждением и скрытым вводом
        do {
            $password = $this->secret('Enter password');
            $password_confirmation = $this->secret('Confirm password');

            if ($password !== $password_confirmation) {
                $this->error('Passwords do not match. Try again.');
            }
        } while ($password !== $password_confirmation);

        // Validate input
        $validator = validator([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'balance' => $balance,
        ], [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'balance' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return 1;
        }

        try {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'balance' => $balance,
            ]);

            $this->info("User created successfully!");
            $this->info("Name: {$user->name}");
            $this->info("Email: {$user->email}");
            $this->info("Balance: {$user->balance}");
            $this->info("ID: {$user->id}");

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to create user: " . $e->getMessage());
            return 1;
        }
    }
} 