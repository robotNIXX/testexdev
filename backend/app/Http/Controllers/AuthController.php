<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Неверные учетные данные.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
            'message' => 'Успешный вход'
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Успешный выход'
        ]);
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        $userStats = $this->userService->getUserStats($user);
        
        return response()->json($userStats);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $request->user()->id,
            'password' => 'sometimes|string|min:8',
        ]);

        $user = $request->user();
        $updated = $this->userService->updateUser($user, $request->only(['name', 'email', 'password']));

        if ($updated) {
            return response()->json([
                'message' => 'Профиль обновлен',
                'user' => $user->fresh()
            ]);
        }

        return response()->json(['message' => 'Ошибка обновления профиля'], 500);
    }
} 