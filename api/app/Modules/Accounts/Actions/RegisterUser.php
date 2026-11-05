<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Actions;

use App\Models\User;

/**
 * Create an email account and hand back a working token.
 *
 * No couple is created here. A new user is solo until an invite is accepted
 * (rule 2) — the invite flow is the only thing that makes a couple.
 */
final class RegisterUser
{
    /**
     * @param  array{name: string, email: string, password: string, timezone?: string}  $data
     * @return array{user: User, token: string}
     */
    public function handle(array $data, string $deviceName = 'app'): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            // Hashed by the model's cast, not here — one place, so no code path
            // can store a plaintext password by forgetting.
            'password' => $data['password'],
            // The DB default is Europe/London; an app that knows better says so,
            // and everything scheduled for this user is rendered in it.
            'timezone' => $data['timezone'] ?? config('app.timezone'),
        ]);

        return [
            'user' => $user,
            'token' => $user->createToken($deviceName)->plainTextToken,
        ];
    }
}
