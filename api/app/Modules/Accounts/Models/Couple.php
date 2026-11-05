<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Models;

use App\Models\User;
use Database\Factories\CoupleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * The couple: the record every other module in the app hangs off.
 *
 * Created only when an invite is accepted — before that the inviter is a solo
 * user (rule 2). Unlinking **closes** a couple rather than deleting it: the
 * row stays, `unlinked_at` is set, and the shared content stays with it where
 * neither ex-partner can read it (rule 4).
 *
 * @property Carbon $linked_at
 * @property Carbon|null $unlinked_at
 * @property Carbon|null $anniversary_on
 * @property Carbon|null $first_date_on
 */
#[Fillable(['lock_in_mode', 'anniversary_on', 'first_date_on', 'linked_at'])]
final class Couple extends Model
{
    /** @use HasFactory<CoupleFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        // The public identifier. Allocated here rather than in the Action so
        // no code path can create a couple without one.
        self::creating(function (Couple $couple): void {
            $couple->ulid ??= (string) Str::ulid();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'linked_at' => 'datetime',
            'unlinked_at' => 'datetime',
            'anniversary_on' => 'date',
            'first_date_on' => 'date',
        ];
    }

    /**
     * Everyone who has ever been in this couple, ended memberships included.
     *
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'couple_user')
            ->withPivot(['role', 'joined_at', 'left_at'])
            ->withTimestamps();
    }

    /**
     * The members who have not left.
     *
     * @return BelongsToMany<User, $this>
     */
    public function activeMembers(): BelongsToMany
    {
        return $this->members()->wherePivotNull('left_at');
    }

    /** True while the couple is live. False once either partner unlinks. */
    public function isLinked(): bool
    {
        return $this->unlinked_at === null;
    }

    /** The other half. Null if the couple is closed or the user is not in it. */
    public function partnerFor(User $user): ?User
    {
        if (! $this->isLinked()) {
            return null;
        }

        return $this->activeMembers()
            ->where('users.id', '!=', $user->getKey())
            ->first();
    }

    /** True if this user is a live member — the basis of every policy here. */
    public function hasActiveMember(User $user): bool
    {
        return $this->activeMembers()->where('users.id', $user->getKey())->exists();
    }
}
