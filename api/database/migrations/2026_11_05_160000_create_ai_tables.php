<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What every AI call cost, what a couple was actually shown, and what they
 * thought of it.
 *
 * Three things here are decisions rather than details.
 *
 * **`cost_millipence`, not `cost_pence`** (ADR-026). The project keeps money in
 * integer pence, but a `safety_screen` call costs about 0.006p and there are two
 * of them on every generation — rounded to pence they would all log zero, and
 * the cost guard would never see the volume that actually adds up. The unit is
 * in the column name, as it is in `config('ai.pricing_usd_cents_per_mtok')`.
 *
 * **A `safety_tripped` row carries no couple and no user** (ADR-027). The cost
 * still has to be logged, because we paid for the screen, but nothing in this
 * database should say that this couple tripped the safety guard on this date.
 * `UsageLogger` refuses to set either column for that outcome, and a test
 * asserts it. The side effect is the right one: a generation that tripped does
 * not spend the couple's monthly allowance either.
 *
 * **`ai_generations` keeps its filtered payload.** The module doc said the
 * payload was hashed and not kept; a hash cannot ask the question again, and a
 * generic regenerate needs to. It is safe to keep *because* it has already been
 * through the allow-list, so it holds no private field by construction.
 *
 * **`ai_generations` has no `safety_outcome` column.** A generation row only
 * exists when the output was shown, which means the screen said safe, so the
 * column could only ever hold one value. The module doc described one before
 * the guard was built; the guard is what settled it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage', function (Blueprint $table): void {
            $table->id();

            // Nullable because a safety trip is logged without either, and
            // because a deleted account must not take the cost history with
            // it — these rows hold counts, never content.
            $table->foreignId('couple_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // The module that asked, e.g. "DatePlanner". Not a foreign key:
            // modules are folders, not rows.
            $table->string('module', 64);
            // A key in config('ai.tasks').
            $table->string('task', 64);

            // Split rather than stored as "date_plan/v1", so "which prompt
            // costs the most" and "did v2 get cheaper" are both one query.
            $table->string('prompt_name', 64)->nullable();
            $table->string('prompt_version', 16)->nullable();

            // The model that actually answered, which is not always the one
            // asked for. Null when no call was made.
            $table->string('model', 64)->nullable();

            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('cost_millipence')->default(0);

            // ok | invalid_output | safety_tripped | capped | error
            $table->string('outcome', 24);
            $table->unsignedInteger('duration_ms')->default(0);

            $table->timestamps();

            // The monthly cap query: one couple, this calendar month.
            $table->index(['couple_id', 'created_at']);
            // The global daily guard, which has no couple to narrow by.
            $table->index('created_at');
        });

        Schema::create('ai_generations', function (Blueprint $table): void {
            $table->id();
            // The public identifier, for the feedback and regenerate
            // endpoints. Ids in URLs are ULIDs so nothing can be counted.
            $table->ulid('ulid')->unique();

            $table->foreignId('couple_id')->constrained()->cascadeOnDelete();
            $table->string('module', 64);
            $table->string('task', 64);
            $table->string('prompt_name', 64);
            $table->string('prompt_version', 16);

            // The **filtered** payload: what the prompt was actually built
            // from. Safe to keep precisely because it went through the
            // allow-list, so by construction it holds no private field - and
            // keeping it is what makes a generic regenerate possible. A hash
            // alone cannot ask the question again, and every feature would
            // otherwise have to rebuild the payload itself.
            $table->json('payload');

            // What the couple was shown, exactly as they saw it. A prompt
            // change must never rewrite this.
            $table->json('output');

            // Set when a regenerate replaces this one. The old row stays, so
            // history is preserved rather than overwritten.
            $table->foreignId('superseded_by')->nullable()
                ->constrained('ai_generations')->nullOnDelete();

            $table->timestamps();

            $table->index(['couple_id', 'task', 'created_at']);
        });

        Schema::create('ai_feedback', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ai_generation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // up | down
            $table->string('rating', 4);
            $table->text('note')->nullable();
            $table->timestamps();

            // One opinion per person per generation, changeable. Without this
            // a double tap would read as two people disliking it.
            $table->unique(['ai_generation_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_feedback');
        Schema::dropIfExists('ai_generations');
        Schema::dropIfExists('ai_usage');
    }
};
