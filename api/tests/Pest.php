<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|------------------------------------------------------------------------------
| Test case binding
|------------------------------------------------------------------------------
|
| Feature tests get the full application. RefreshDatabase is applied per-file
| rather than globally, so tests that touch no database (the health check, pure
| unit tests) stay fast and need no migrations.
|
*/

pest()->extend(TestCase::class)->in('Feature');

/*
|------------------------------------------------------------------------------
| Shared helpers
|------------------------------------------------------------------------------
|
| `usingDatabase()` is sugar for the common case so individual test files read
| as one line of intent rather than a trait import.
|
*/

function usingDatabase(): void
{
    uses(RefreshDatabase::class);
}
