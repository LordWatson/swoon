<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Support;

use RuntimeException;

/**
 * A social sign-in token that could not be trusted.
 *
 * The message says precisely what went wrong — expired, wrong audience, bad
 * signature, replayed nonce, no client id configured — and that message goes
 * to the **log**, not to the caller. `SocialAuthController` replaces it with
 * one generic reply, so the endpoint cannot be used to work out which check
 * failed. A developer debugging a misconfigured deploy still needs the detail,
 * and a log is not a reply.
 */
final class InvalidIdentityToken extends RuntimeException {}
