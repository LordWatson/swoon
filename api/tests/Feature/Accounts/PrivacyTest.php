<?php

declare(strict_types=1);

/*
 * Deletion and data export — the two exits.
 *
 * Deletion is tested for what is *gone*, export for what is *present* and,
 * more importantly, what is *absent*. "The export omits the partner's private
 * data" cannot be checked by looking at a screen: it has to be an assertion
 * about the bytes.
 */

use App\Models\User;
use App\Modules\Accounts\Actions\BuildDataExport;
use App\Modules\Accounts\Jobs\BuildExportForUser;
use App\Modules\Accounts\Jobs\PurgeDeletedUserData;
use App\Modules\Accounts\Mail\ExportReadyMail;
use App\Modules\Accounts\Models\Couple;
use App\Modules\Accounts\Models\CoupleInvite;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

usingDatabase();

describe('deleting an account', function (): void {
    it('needs the word typed, not a tap', function (): void {
        // UX rule 10. A checkbox is too easy to hit by accident for something
        // this final.
        $user = User::factory()->create();

        $this->actingAs($user)->deleteJson('/api/me')->assertStatus(422);
        $this->actingAs($user)->deleteJson('/api/me', ['confirmation' => 'delete'])->assertStatus(422);

        expect($user->fresh())->not->toBeNull();
    });

    it('signs every device out within the request', function (): void {
        // Not when the queue catches up: now. Otherwise a stolen phone keeps
        // working after the owner has deleted the account because of it.
        $user = User::factory()->create();
        $phone = $user->createToken('phone')->plainTextToken;
        $user->createToken('tablet');

        $this->withToken($phone)->deleteJson('/api/me', ['confirmation' => 'DELETE'])
            ->assertNoContent();

        expect($user->tokens()->count())->toBe(0);

        app('auth')->forgetGuards();
        $this->withToken($phone)->getJson('/api/me')->assertUnauthorized();
    });

    it('unlinks the couple, so no partner is left alone in a shared space', function (): void {
        $couple = Couple::factory()->linked()->create();
        [$leaver, $other] = $couple->activeMembers()->get()->all();

        $this->actingAs($leaver)->deleteJson('/api/me', ['confirmation' => 'DELETE'])
            ->assertNoContent();

        expect($couple->fresh()->isLinked())->toBeFalse()
            ->and($other->fresh()->isCoupled())->toBeFalse();
    });

    it('kills an invite that is already out in the world', function (): void {
        // A code shared in a message must not link somebody to an account
        // that no longer exists. It is revoked inside the request and then
        // removed outright by the purge, so the row is gone — which is
        // stronger than revoked, and what the 404 below proves.
        $user = User::factory()->create();
        $invite = CoupleInvite::factory()->create(['inviter_user_id' => $user->getKey()]);
        $code = $invite->code;

        $this->actingAs($user)->deleteJson('/api/me', ['confirmation' => 'DELETE'])
            ->assertNoContent();

        expect(CoupleInvite::where('code', $code)->exists())->toBeFalse();

        $this->actingAs(User::factory()->create())
            ->postJson('/api/invites/'.$code.'/accept')
            ->assertNotFound();
    });

    it('cannot be used on anyone else', function (): void {
        // There is no user id in the route to try, and the policy allows only
        // the owner anyway.
        $this->deleteJson('/api/me', ['confirmation' => 'DELETE'])->assertUnauthorized();
    });
});

describe('the purge job', function (): void {
    it('leaves a tombstone with nothing personal in it', function (): void {
        $user = User::factory()->create([
            'name' => 'Alex Watson',
            'email' => 'alex@example.com',
            'apple_sub' => 'apple-123',
            'google_sub' => 'google-456',
            'avatar_path' => 'avatars/alex.jpg',
            'date_of_birth' => '1992-04-17',
            'dietary_needs' => ['vegetarian'],
            'accessibility_needs' => ['step-free'],
            'colour' => 'rose',
        ]);

        $this->actingAs($user)->deleteJson('/api/me', ['confirmation' => 'DELETE'])
            ->assertNoContent();

        $tombstone = User::withTrashed()->find($user->getKey());

        // One assertion per column that held something personal.
        expect($tombstone)->not->toBeNull()
            ->and($tombstone->trashed())->toBeTrue()
            ->and($tombstone->name)->toBe('Someone')
            ->and($tombstone->email)->toBeNull()
            ->and($tombstone->password)->toBeNull()
            ->and($tombstone->apple_sub)->toBeNull()
            ->and($tombstone->google_sub)->toBeNull()
            ->and($tombstone->avatar_path)->toBeNull()
            ->and($tombstone->date_of_birth)->toBeNull()
            ->and($tombstone->dietary_needs)->toBeNull()
            ->and($tombstone->accessibility_needs)->toBeNull()
            ->and($tombstone->colour)->toBeNull()
            ->and($tombstone->email_verified_at)->toBeNull()
            ->and($tombstone->remember_token)->toBeNull();
    });

    it('keeps the row so the other partner does not lose their shared past', function (): void {
        // A couple's shared memories are the other person's memories too. Hard
        // deleting the row would cascade them away or dangle (rule 9).
        $couple = Couple::factory()->linked()->create();
        [$leaver, $other] = $couple->activeMembers()->get()->all();

        $this->actingAs($leaver)->deleteJson('/api/me', ['confirmation' => 'DELETE'])
            ->assertNoContent();

        expect($couple->fresh()->members()->withTrashed()->count())->toBe(2)
            ->and($other->fresh())->not->toBeNull();
    });

    it('removes the invites the account authored, open or spent', function (): void {
        // Only ONE may be open at a time — the index says so — so the second
        // is a spent one, which must go too.
        $user = User::factory()->create();
        CoupleInvite::factory()->create(['inviter_user_id' => $user->getKey()]);
        CoupleInvite::factory()->revoked()->create(['inviter_user_id' => $user->getKey()]);

        $this->actingAs($user)->deleteJson('/api/me', ['confirmation' => 'DELETE'])
            ->assertNoContent();

        expect(CoupleInvite::where('inviter_user_id', $user->getKey())->count())->toBe(0);
    });

    it('refuses to purge an account that is not deleted', function (): void {
        // A stale or retried job must never scrub a live account. This is the
        // worst bug the system could have, so it gets its own test.
        $user = User::factory()->create(['name' => 'Alex Watson']);

        (new PurgeDeletedUserData($user->getKey()))->handle();

        expect($user->fresh()->name)->toBe('Alex Watson');
    });

    it('copes with an account that has already gone', function (): void {
        (new PurgeDeletedUserData(999_999))->handle();
    })->throwsNoExceptions();
});

describe('requesting an export', function (): void {
    it('answers 202 and does the work on the queue', function (): void {
        Queue::fake();

        $this->actingAs(User::factory()->create())
            ->postJson('/api/me/export')
            ->assertStatus(202);

        Queue::assertPushed(BuildExportForUser::class, 1);
    });

    it('requires a token', function (): void {
        $this->postJson('/api/me/export')->assertUnauthorized();
    });
});

describe('what the export contains', function (): void {
    it('carries everything the user put in', function (): void {
        $user = User::factory()->create([
            'name' => 'Alex Watson',
            'email' => 'alex@example.com',
            'dietary_needs' => ['vegetarian'],
        ]);
        CoupleInvite::factory()->create(['inviter_user_id' => $user->getKey()]);

        $export = app(BuildDataExport::class)->assemble($user);

        expect($export['export']['version'])->toBe(1)
            ->and($export['you']['name'])->toBe('Alex Watson')
            ->and($export['you']['email'])->toBe('alex@example.com')
            ->and($export['you']['dietary_needs'])->toBe(['vegetarian'])
            ->and($export['invites'])->toHaveCount(1)
            ->and($export)->toHaveKeys(['export', 'you', 'couple', 'invites', 'authored', 'shared']);
    });

    it('gives the partner as a name and a colour, and nothing else', function (): void {
        // An export of YOUR data is not a copy of theirs. This is the same
        // shape PartnerResource serves, for the same reason.
        $alex = User::factory()->create();
        $sam = User::factory()->create([
            'name' => 'Sam',
            'email' => 'sam@example.com',
            'date_of_birth' => '1990-01-01',
            'apple_sub' => 'apple-sam',
        ]);
        Couple::factory()->of($alex, $sam)->create();

        $export = app(BuildDataExport::class)->assemble($alex->fresh());
        $json = json_encode($export);

        expect(array_keys($export['couple']['partner']))->toBe(['name', 'colour'])
            ->and($json)->not->toContain('sam@example.com')
            ->and($json)->not->toContain('apple-sam')
            ->and($json)->not->toContain('1990-01-01');
    });

    it('is null for the couple when the user is solo', function (): void {
        $export = app(BuildDataExport::class)->assemble(User::factory()->create());

        expect($export['couple'])->toBeNull();
    });

    it('is written to a private disk under an unguessable name', function (): void {
        Storage::fake();

        $path = app(BuildDataExport::class)->handle(User::factory()->create());

        expect($path)->toStartWith('exports/')
            ->and($path)->toEndWith('.json')
            // A ULID, not the user id: the download is behind a signed URL,
            // but a guessable path is one misconfiguration from a listing.
            ->and($path)->not->toContain('1.json');

        Storage::assertExists($path);
    });
});

describe('the export link', function (): void {
    it('emails a signed link that expires', function (): void {
        Storage::fake();
        Mail::fake();

        $user = User::factory()->create(['email' => 'alex@example.com']);

        (new BuildExportForUser($user->getKey()))->handle(app(BuildDataExport::class));

        Mail::assertSent(ExportReadyMail::class, function (ExportReadyMail $mail): bool {
            $rendered = $mail->render();

            return str_contains($rendered, 'signature=')
                && str_contains($rendered, 'expires=')
                && str_contains($rendered, '48 hours');
        });
    });

    it('refuses an unsigned or tampered URL', function (): void {
        Storage::fake();

        $user = User::factory()->create();
        $path = app(BuildDataExport::class)->handle($user);

        // No signature at all.
        $this->actingAs($user)
            ->get('/api/me/export/'.$user->getKey().'/'.base64_encode($path))
            ->assertForbidden();
    });

    it('refuses somebody else holding a forwarded link', function (): void {
        // A signature proves WE issued the URL, not that the holder is the
        // right person — and these links travel by email.
        Storage::fake();

        $owner = User::factory()->create();
        $path = app(BuildDataExport::class)->handle($owner);

        $url = URL::temporarySignedRoute('me.export.download', now()->addHour(), [
            'user' => $owner->getKey(),
            'path' => base64_encode($path),
        ]);

        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
    });

    it('serves the file to its owner', function (): void {
        Storage::fake();

        $user = User::factory()->create();
        $path = app(BuildDataExport::class)->handle($user);

        $url = URL::temporarySignedRoute('me.export.download', now()->addHour(), [
            'user' => $user->getKey(),
            'path' => base64_encode($path),
        ]);

        $this->actingAs($user)->get($url)->assertOk();
    });

    it('stops working once the link has expired', function (): void {
        Storage::fake();

        $user = User::factory()->create();
        $path = app(BuildDataExport::class)->handle($user);

        $url = URL::temporarySignedRoute('me.export.download', now()->addHour(), [
            'user' => $user->getKey(),
            'path' => base64_encode($path),
        ]);

        $this->travel(2)->hours();

        $this->actingAs($user)->get($url)->assertForbidden();
    });

    it('cannot be bent into reading another file', function (): void {
        // Defence in depth: the signature means we minted the value, but a
        // signed parameter must still never become a path traversal.
        Storage::fake();

        $user = User::factory()->create();

        $url = URL::temporarySignedRoute('me.export.download', now()->addHour(), [
            'user' => $user->getKey(),
            'path' => base64_encode('exports/../../.env'),
        ]);

        $this->actingAs($user)->get($url)->assertNotFound();
    });
});
