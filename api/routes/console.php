<?php

declare(strict_types=1);

use App\Modules\Accounts\Console\RemindInvites;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|------------------------------------------------------------------------------
| Scheduled work
|------------------------------------------------------------------------------
|
| The full intended schedule is in docs/ARCHITECTURE.md § 5. Entries appear
| here as the features that need them land.
|
*/

// One nudge, three days after an invite goes unanswered, and never a second.
Schedule::command(RemindInvites::class)->daily();
