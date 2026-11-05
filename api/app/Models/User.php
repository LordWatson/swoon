<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

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
}
