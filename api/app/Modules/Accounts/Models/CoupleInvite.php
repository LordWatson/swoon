<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Models;

use App\Models\User;
use Database\Factories\CoupleInviteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One partner's outstanding invitation.
 *
 * A code is single-use and expires after 14 days. A user holds at most one
 * **open** invite, which the database enforces with a unique index on a
 * generated column — see the migration.
 *
 * @property Carbon $expires_at
 * @property Carbon|null $accepted_at
 * @property Carbon|null $revoked_at
 * @property Carbon|null $reminded_at
 */
#[Fillable(['code', 'email', 'expires_at'])]
final class CoupleInvite extends Model
{
    /** @use HasFactory<CoupleInviteFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
            'reminded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by_user_id');
    }

    /**
     * Neither accepted nor revoked — which is what the database's unique index
     * means by "open". Note that this says nothing about expiry.
     *
     * @param  Builder<CoupleInvite>  $query
     * @return Builder<CoupleInvite>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('accepted_at')->whereNull('revoked_at');
    }

    public function isOpen(): bool
    {
        return $this->accepted_at === null && $this->revoked_at === null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Open AND still in date. This is the only check that should gate an
     * accept — `isOpen()` alone would let a two-month-old code through.
     */
    public function isUsable(): bool
    {
        return $this->isOpen() && ! $this->isExpired();
    }
}
