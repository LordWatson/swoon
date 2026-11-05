<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\PingJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

/**
 * Dispatches one ping job, then says whether anything collected it.
 *
 * That second half is the point: with no worker running, a dispatch succeeds
 * silently and the job simply waits, which is exactly what a broken local setup
 * looks like from the outside.
 */
final class QueueTest extends Command
{
    protected $signature = 'swoon:queue-test';

    protected $description = 'Dispatch a ping job to confirm a queue worker is running';

    public function handle(): int
    {
        $token = Str::upper(Str::random(6));
        $connection = (string) config('queue.default');

        PingJob::dispatch($token);

        $this->components->twoColumnDetail('Dispatched', "swoon:ping [{$token}]");
        $this->components->twoColumnDetail('Connection', $connection);

        if ($connection === 'sync') {
            $this->components->info('The sync connection runs jobs inline, so it proves nothing about a worker.');

            return self::SUCCESS;
        }

        // A worker polls rather than being pushed to, so give it a moment before
        // concluding nothing is listening — checking immediately after dispatch
        // reports "no worker" even when one is running and about to collect it.
        $deadline = microtime(true) + 3.0;

        while (Queue::size() > 0 && microtime(true) < $deadline) {
            usleep(200_000);
        }

        // Nothing left in the queue means a worker took it; a job still sitting
        // there means nothing is listening. Counts the whole default queue, so
        // it is only trustworthy on a quiet local machine — which is where it
        // is used.
        if (Queue::size() === 0) {
            $this->components->info("A worker collected it. Look for [{$token}] in the log.");

            return self::SUCCESS;
        }

        $this->components->warn('Still queued — no worker is running. Start one with: composer dev');

        return self::SUCCESS;
    }
}
