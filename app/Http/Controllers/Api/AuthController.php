<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])
            ->where('role', 'staff')
            ->where('is_active', true)
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => ['The provided credentials are incorrect.']]);
        }

        if (! $user->email_verified_at) {
            $this->sendVerificationCode($user);

            return response()->json([
                'message' => 'Verify your email address using the code we sent you.',
                'verification_required' => true,
            ], 403);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'must_change_password' => $user->must_change_password,
            'user' => $user->only(['id', 'name', 'designation', 'email', 'phone', 'address']),
        ]);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
        ]);

        $user = User::where('email', $data['email'])
            ->where('role', 'staff')
            ->where('is_active', true)
            ->first();

        if (! $user || ! $user->email_otp_hash || ! $user->email_otp_expires_at?->isFuture()
            || ! Hash::check($data['otp'], $user->email_otp_hash)) {
            throw ValidationException::withMessages(['otp' => ['The verification code is invalid or expired.']]);
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'email_otp_hash' => null,
            'email_otp_expires_at' => null,
        ])->save();

        return response()->json(['message' => 'Email verified. You can now sign in.']);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'string', 'min:12', 'different:current_password'],
        ]);

        $request->user()->forceFill([
            'password' => $data['password'],
            'must_change_password' => false,
        ])->save();

        return response()->json(['message' => 'Password changed successfully.']);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Signed out.']);
    }

    private function sendVerificationCode(User $user): void
    {
        $otp = (string) random_int(100000, 999999);

        $user->forceFill([
            'email_otp_hash' => Hash::make($otp),
            'email_otp_expires_at' => now()->addMinutes(10),
        ])->save();

        Mail::raw("Your SL Logistics verification code is {$otp}. It expires in 10 minutes.", function ($message) use ($user): void {
            $message->to($user->email)->subject('Verify your SL Logistics account');
        });
    }
}
