<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class TokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($data['email']).'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return response()->json([
                'message' => 'Terlalu banyak percobaan masuk. Coba lagi dalam '.RateLimiter::availableIn($throttleKey).' detik.',
            ], 429);
        }

        $email = Str::lower($data['email']);
        $user = User::query()->where('email', $email)->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            return response()->json([
                'message' => 'Kredensial yang diberikan tidak valid.',
            ], 422);
        }

        $abilities = $user->getRoleNames()
            ->intersect(['guru', 'admin'])
            ->values()
            ->all();

        if ($abilities === []) {
            return response()->json(['message' => 'Akun tidak memiliki akses API.'], 403);
        }

        RateLimiter::clear($throttleKey);
        $token = $user->createToken($data['device_name'], $abilities, now()->addDays(30));

        return response()->json([
            'data' => [
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'abilities' => $abilities,
                'expires_at' => $token->accessToken->expires_at->toISOString(),
            ],
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $accessToken = $request->user()->currentAccessToken();
        if (! $accessToken instanceof PersonalAccessToken) {
            return response()->json(['message' => 'Logout API memerlukan token Bearer Sanctum.'], 409);
        }

        $accessToken->delete();

        return response()->json(['message' => 'Token berhasil dicabut.']);
    }
}
