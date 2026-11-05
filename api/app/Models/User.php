<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Modules\Accounts\Models\Couple;
use App\Modules\Accounts\Models\CoupleInvite;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * Static analysis cannot read the cast types off the schema, so the cast
 * attributes are declared here. Plain string columns need no entry.
 *
 * @property Carbon|null $date_of_birth
 * @property array<int, string>|null $dietary_needs
 * @property array<int, string>|null $accessibility_needs
 */
#[Fillable([
    'name', 'email', 'password',
    'timezone', 'locale', 'colour', 'avatar_path',
    'dietary_needs', 'accessibility_needs', 'date_of_birth',
])]
// apple_sub and google_sub are never exposed, to the partner or to the owner:
// they identify the account to the provider and have no business in a payload.
#[Hidden(['password', 'remember_token', 'apple_sub', 'google_sub'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'dietary_needs' => 'array',
            'accessibility_needs' => 'array',
        ];
    }

    /**
     * Every couple this user has ever been in, ended memberships included.
     *
     * @return BelongsToMany<Couple, $this>
     */
    public function couples(): BelongsToMany
    {
        return $this->belongsToMany(Couple::class, 'couple_user')
            ->withPivot(['role', 'joined_at', 'left_at'])
            ->withTimestamps();
    }

    /**
     * The active couple, or null.
     *
     * A METHOD, not a relationship — call `$user->couple()`, never
     * `$user->couple`, which Eloquent would try to resolve as a relation and
     * reject. There can only ever be one, and the database guarantees it: see
     * the unique index on `couple_user.active_user_id`.
     */
    public function couple(): ?Couple
    {
        return $this->couples()->wherePivotNull('left_at')->first();
    }

    public function isCoupled(): bool
    {
        return $this->couples()->wherePivotNull('left_at')->exists();
    }

    /** The other half, or null when solo. */
    public function partner(): ?User
    {
        return $this->couple()?->partnerFor($this);
    }

    /**
     * Invites this user has sent out.
     *
     * @return HasMany<CoupleInvite, $this>
     */
    public function invites(): HasMany
    {
        return $this->hasMany(CoupleInvite::class, 'inviter_user_id');
    }
}
