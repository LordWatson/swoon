<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Http\Controllers;

use App\Modules\Accounts\Actions\AuthenticateUser;
use App\Modules\Accounts\Actions\RegisterUser;
use App\Modules\Accounts\Http\Requests\LoginRequest;
use App\Modules\Accounts\Http\Requests\RegisterRequest;
use App\Modules\Accounts\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Sign up, sign in, sign out.
 *
 * Controllers here only authorise, validate, call an Action and return a
 * Resource — the rules live in the Actions, which is what makes them testable
 * without a request.
 */
final class AuthController
{
    public function register(RegisterRequest $request, RegisterUser $action): JsonResponse
    {
        /** @var array{name: string, email: string, password: string, timezone?: string} $data */
        $data = $request->validated();

        $result = $action->handle($data, $this->deviceName($request));

        return response()->json([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 201);
    }

    public function login(LoginRequest $request, AuthenticateUser $action): JsonResponse
    {
        $result = $action->handle(
            (string) $request->string('email'),
            (string) $request->string('password'),
            $this->deviceName($request),
        );

        return response()->json([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    /**
     * Revoke the token this request arrived with — and only that one.
     *
     * Signing out on a phone must not sign the user out on their tablet. That
     * is why this deletes the current token rather than calling
     * `tokens()->delete()`, which is the easy mistake here.
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json(status: 204);
    }

    private function deviceName(Request $request): string
    {
        $name = trim((string) $request->string('device_name'));

        return $name === '' ? 'app' : $name;
    }
}
