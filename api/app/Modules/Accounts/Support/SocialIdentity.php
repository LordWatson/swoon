<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Support;

/**
 * A verified person, as a provider describes them.
 *
 * Note how little is in here. The provider's subject id is the only field we
 * key an account on; the email may be a relay address or missing entirely, and
 * the name is offered **once**, on first authorisation, and never again.
 */
final readonly class SocialIdentity
{
    public function __construct(
        /** `apple` or `google`. */
        public string $provider,
        /** The provider's stable id for this person. Never the email. */
        public string $subject,
        public ?string $email,
        /** Apple relay addresses end @privaterelay.appleid.com. */
        public bool $isPrivateRelayEmail,
        /** Offered on first authorisation only — capture it then or never. */
        public ?string $name,
    ) {}

    /** The `users` column this provider's subject id lives in. */
    public function subjectColumn(): string
    {
        return $this->provider === 'apple' ? 'apple_sub' : 'google_sub';
    }
}
