<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Models\User;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Admin authentication with Sanctum bearer tokens (D-023). No registration endpoint.
 *
 * Tokens, not SPA cookies: the frontend and the API live on different onrender.com
 * subdomains, and onrender.com is on the Public Suffix List, so cross-subdomain cookies fail.
 */
class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        // Same message for unknown email and wrong password: do not reveal which accounts exist.
        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages(['email' => 'Email hoặc mật khẩu không đúng.']);
        }

        $expiresAt = now()->addMinutes(config('sanctum.expiration'));
        $token = $user->createToken('admin', ['*'], $expiresAt);

        return ApiResponse::success([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toIso8601String(),
            'user' => $this->user($user),
        ]);
    }

    public function logout(Request $request): Response
    {
        // Revoke server-side: the token stops working even if a copy was kept somewhere.
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success($this->user($request->user()));
    }

    /**
     * @return array{id: int, name: string, email: string}
     */
    private function user(User $user): array
    {
        return ['id' => $user->id, 'name' => $user->name, 'email' => $user->email];
    }
}
