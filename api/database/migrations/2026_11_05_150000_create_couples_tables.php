<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The couple: the record every other module in the app hangs off.
 *
 * The important part of this file is the `active_user_id` generated column on
 * `couple_user`. **One active couple per user** is the invariant the whole
 * product rests on, and it is enforced here by the database rather than only in
 * PHP, because two simultaneous accepts of two different invites would
 * otherwise both pass a check-then-write in the application.
 *
 * MySQL has no partial unique index, so the usual trick does not apply: a plain
 * `unique(user_id, left_at)` would allow two rows with `left_at = NULL` for the
 * same user, since a UNIQUE index treats NULLs as distinct. Instead a stored
 * generated column holds `user_id` while the membership is live and NULL once
 * it ends, and that column is unique — so a user can hold exactly one live
 * membership, and any number of ended ones.
 *
 * Three details that are each a bug if got wrong:
 *
 * - The expression is CASE, not MySQL's `IF()`, so it is also valid on SQLite,
 *   which is what the test suite runs on by default (ADR-008).
 * - The column is VIRTUAL, not STORED. MySQL refuses a foreign key with
 *   ON DELETE CASCADE or SET NULL on a base column of a *stored* generated
 *   column, which `user_id` is — the migration fails outright with error 1215.
 *   Both engines index a virtual column happily.
 * - It is declared inside CREATE TABLE rather than added later, because SQLite
 *   cannot add a generated column with ALTER TABLE.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('couples', function (Blueprint $table): void {
            $table->id();
            // The public identifier. Ids in URLs and payloads are ULIDs so a
            // couple's existence cannot be inferred by counting.
            $table->ulid('ulid')->unique();

            $table->timestamp('linked_at');
            // `both_heart` = both partners must heart an idea before it locks.
            // `either_locks` = either may lock it. A couple setting, not a user one.
            $table->string('lock_in_mode', 16)->default('both_heart');
            $table->date('anniversary_on')->nullable();
            $table->date('first_date_on')->nullable();
            // Set on unlink. The couple record stays, closed, holding the
            // shared content neither partner may read any more (rule 4).
            $table->timestamp('unlinked_at')->nullable();

            $table->timestamps();
        });

        Schema::create('couple_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('couple_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // 'a' or 'b'. Drives the default partner colour, and gives the two
            // sides a stable identity in reveal screens.
            $table->string('role', 1);
            $table->timestamp('joined_at');
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            // THE INVARIANT. See the class docblock for why it is shaped this way.
            $table->unsignedBigInteger('active_user_id')
                ->nullable()
                ->virtualAs('case when left_at is null then user_id else null end');

            $table->unique('active_user_id', 'couple_user_one_active_couple');
            // One 'a' and one 'b' per couple, so a third member is impossible.
            $table->unique(['couple_id', 'role']);
            $table->index(['user_id', 'left_at']);
        });

        Schema::create('couple_invites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inviter_user_id')->constrained('users')->cascadeOnDelete();
            // 8 characters, ambiguous ones excluded — partners read these aloud.
            $table->string('code', 8)->unique();
            // Where the invite was emailed, if it was. Only ever shown back to
            // the inviter, and masked when it is.
            $table->string('email')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('accepted_by_user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            // The single reminder, three days in. Nullable so it can only
            // ever be sent once.
            $table->timestamp('reminded_at')->nullable();
            $table->timestamps();

            // One OPEN invite per user (rule 6), by the same generated-column
            // trick. Expiry cannot be part of it — a generated column may only
            // use deterministic expressions, and "now" is not one — so an
            // expired-but-open invite still blocks a new one, and CreateInvite
            // revokes it first. That is the behaviour rule 6 wants anyway.
            $table->unsignedBigInteger('open_inviter_id')->nullable()
                ->virtualAs(
                    'case when accepted_at is null and revoked_at is null '.
                    'then inviter_user_id else null end'
                );

            $table->unique('open_inviter_id', 'couple_invites_one_open_invite');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('couple_invites');
        Schema::dropIfExists('couple_user');
        Schema::dropIfExists('couples');
    }
};
