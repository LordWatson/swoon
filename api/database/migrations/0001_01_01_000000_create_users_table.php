<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            // Nullable because Sign in with Apple may return a private-relay
            // address, or none at all. Still unique: two accounts must never
            // share an address.
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            // Nullable for social-only accounts, which have no password to set.
            $table->string('password')->nullable();
            $table->rememberToken();

            // Social identities. Keyed on the provider's subject id, never on
            // email: an Apple user can change their relay address, and an
            // email match is not proof of being the same person (P1.T03).
            $table->string('apple_sub')->nullable()->unique();
            $table->string('google_sub')->nullable()->unique();

            // Profile fields every later phase assumes. Kept on `users` rather
            // than a profile table: one row per person, always loaded with them.
            $table->string('timezone')->default('Europe/London');
            $table->string('locale', 10)->default('en-GB');
            // One of the four partner colours in mobile/src/theme/colours.ts.
            // Null until onboarding picks one (partners must differ).
            $table->string('colour', 16)->nullable();
            $table->string('avatar_path')->nullable();
            // Feeds the date planner's allow-list; never free-text to the model.
            $table->json('dietary_needs')->nullable();
            $table->json('accessibility_needs')->nullable();
            // Private until the user shares it for birthday milestones.
            $table->date('date_of_birth')->nullable();

            $table->timestamps();
            // Deletion is immediate from the user's perspective and the cascade
            // finishes on the queue, so the row is tombstoned first (P1.T09).
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
