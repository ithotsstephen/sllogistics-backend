<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $admin = User::where('role', 'admin')
            ->where('is_active', true)
            ->where(fn ($query) => $query->where('username', $data['login'])->orWhere('email', $data['login']))
            ->first();

        if (! $admin || ! Hash::check($data['password'], $admin->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided administrator credentials are incorrect.'],
            ]);
        }

        return response()->json([
            'token' => $admin->createToken('admin-api', ['admin'])->plainTextToken,
            'token_type' => 'Bearer',
            'admin' => $admin->only(['id', 'name', 'username', 'email']),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Signed out.']);
    }
}
