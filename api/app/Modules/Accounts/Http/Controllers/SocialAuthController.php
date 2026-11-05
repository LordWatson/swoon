<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Http\Controllers;

use App\Modules\Accounts\Actions\FindOrCreateSocialUser;
use App\Modules\Accounts\Http\Requests\SocialSignInRequest;
use App\Modules\Accounts\Http\Resources\UserResource;
use App\Modules\Accounts\Support\InvalidIdentityToken;
use App\Modules\Accounts\Support\SocialIdentity;
use App\Modules\Accounts\Support\SocialIdentityProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Sign in with Apple and Google.
 *
 * The provider's token is verified here and then thrown away: it is proof of
 * identity for this one request and is **never** used as our session token.
 * What the app gets back is a Sanctum token of ours, exactly as it would from
 * an email sign-in.
 */
final class SocialAuthController
{
    public function apple(
        SocialSignInRequest $request,
        SocialIdentityProvider $provider,
        FindOrCreateSocialUser $action,
    ): JsonResponse {
        return $this->signIn(
            $request,
            $action,
            fn (): SocialIdentity => $provider->apple(
                (string) $request->string('identity_token'),
                $request->string('nonce')->toString() ?: null,
                $request->string('name')->toString() ?: null,
            ),
        );
    }

    public function google(
        SocialSignInRequest $request,
        SocialIdentityProvider $provider,
        FindOrCreateSocialUser $action,
    ): JsonResponse {
        return $this->signIn(
            $request,
            $action,
            fn (): SocialIdentity => $provider->google(
                (string) $request->string('identity_token'),
                $request->string('nonce')->toString() ?: null,
            ),
        );
    }

    /**
     * @param  callable(): SocialIdentity  $verify
     *
     * @throws ValidationException
     */
    private function signIn(
        SocialSignInRequest $request,
        FindOrCreateSocialUser $action,
        callable $verify,
    ): JsonResponse {
        try {
            $identity = $verify();
        } catch (InvalidIdentityToken $e) {
            // One message for every cause. Expired, forged, wrong audience,
            // replayed nonce and "nobody configured a client id" are all the
            // same 422 to the caller: which check failed is useful to someone
            // probing the endpoint and useless to a real person, whose only
            // move is to try again.
            //
            // The specific reason goes to the log, because a developer facing
            // a misconfigured deploy does need it — and a log is not a reply.
            Log::warning('Social sign-in rejected.', ['reason' => $e->getMessage()]);

            throw ValidationException::withMessages([
                'identity_token' => ['That sign-in could not be verified. Give it another go.'],
            ]);
        }

        $name = trim((string) $request->string('device_name'));

        $result = $action->handle(
            $identity,
            $request->boolean('link_confirmed'),
            $name === '' ? 'app' : $name,
        );

        return response()->json([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ]);
    }
}
