<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function createUser(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'balance' => $data['balance'] ?? 0,
        ]);
    }

    public function updateBalance(User $user, float $newBalance): bool
    {
        return $user->update(['balance' => $newBalance]);
    }

    public function getUserWithOperations(User $user, int $limit = 10): User
    {
        return $user->load(['operations' => function ($query) use ($limit) {
            $query->orderBy('created_at', 'desc')->limit($limit);
        }]);
    }

    public function getUserStats(User $user): array
    {
        $operations = $user->operations();

        return [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'current_balance' => $user->balance,
            'total_operations' => $operations->count(),
            'last_operation_date' => $operations->latest()->first()?->created_at,
            'created_at' => $user->created_at,
        ];
    }

    public function getUsersWithOperationCounts(): \Illuminate\Database\Eloquent\Collection
    {
        return User::withCount('operations')->get();
    }

    public function getTopUsersByBalance(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return User::orderBy('balance', 'desc')->limit($limit)->get();
    }

    public function getUsersWithOperationsInRange(string $startDate, string $endDate): \Illuminate\Database\Eloquent\Collection
    {
        return User::whereHas('operations', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        })->with(['operations' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }])->get();
    }

    public function updateUser(User $user, array $data): bool
    {
        $updateData = [];

        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }

        if (isset($data['email'])) {
            $updateData['email'] = $data['email'];
        }

        if (isset($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        return $user->update($updateData);
    }

    public function deleteUser(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            $user->operations()->delete();
            return $user->delete();
        });
    }
} 