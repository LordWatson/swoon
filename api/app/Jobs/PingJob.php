<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Proves the queue works, end to end, without involving a feature.
 *
 * Dispatched only by `artisan swoon:queue-test`; nothing in the app sends it.
 * Every AI call and every email in this app runs in a queued job, and Herd runs
 * no worker — so "is a worker actually running?" is the first question when
 * something never finishes, and this is how you answer it.
 */
final class PingJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $token) {}

    public function handle(): void
    {
        Log::info("swoon:ping handled [{$this->token}]");
    }
}
