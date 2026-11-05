<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Modules\Accounts\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // The Resend package registers POST /resend/webhook and attaches its
        // signature middleware ONLY when a webhook secret is set — with no
        // secret it accepts any payload from anyone. Nothing here handles
        // Resend webhooks yet, so the route is off by default in code rather
        // than by an env line a hand-built deploy can omit. Turn it on
        // together with RESEND_WEBHOOK_SECRET when bounce handling is built.
        //
        // This has to be register(), not boot(): the package reads the flag in
        // its own boot(), and every register() runs before any boot().
        config(['resend.routes' => false]);
    }

    public function boot(): void
    {
        // config/swoon.php is the one place the product is named. Laravel's own
        // mail theme prints config('app.name') as the header and footer of
        // every email, and notifications (password reset, email verification)
        // take their from-name from mail.from.name — neither passes through
        // App\Mail\SwoonMailable, so both are pointed at the real source here.
        //
        // Not done by interpolating ${SWOON_NAME} in the env file: Dotenv only
        // substitutes variables defined EARLIER in the same file, and silently
        // leaves the literal '${SWOON_NAME}' when they are not, which would
        // put that text in the header of a real email.
        config([
            'app.name' => config('swoon.name'),
            'mail.from.name' => config('swoon.name'),
        ]);

        // Laravel discovers a policy by swapping \Models\ for \Policies\ in
        // the model's namespace, which finds every module policy on its own —
        // App\Modules\Accounts\Models\Couple resolves to that module's
        // CouplePolicy without help. `User` is the exception: it lives in
        // app/Models because the framework's auth scaffolding puts it there,
        // while the Accounts module owns it, so the convention looks in
        // App\Policies and finds nothing. One explicit line beats moving the
        // model or inventing a second guesser.
        Gate::policy(User::class, UserPolicy::class);
    }
}
