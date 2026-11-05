<?php

declare(strict_types=1);

/*
 * Every AI call and every email in this app runs in a queued job, and Herd runs
 * no worker — so `swoon:queue-test` is the first thing anyone reaches for when
 * something never finishes. Its contract is the token it prints: the docs tell
 * you to grep the log for it, so the dispatch and that log line are asserted
 * here rather than trusted.
 */

use App\Jobs\PingJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

it('dispatches exactly one ping job', function (): void {
    Queue::fake();

    $this->artisan('swoon:queue-test')->assertSuccessful();

    Queue::assertPushed(PingJob::class, 1);
});

it('logs the token it was given, so the printed token can be found', function (): void {
    Log::shouldReceive('info')->once()->with('swoon:ping handled [ABC123]');

    (new PingJob('ABC123'))->handle();
});
