<?php

declare(strict_types=1);

namespace App\Modules\Ai\Actions;

use App\Modules\Ai\Contracts\AiDriver;
use App\Modules\Ai\Support\AiRequest;
use App\Modules\Ai\Support\Generation;
use App\Modules\Ai\Support\SchemaValidator;

/**
 * Ask the model, check the shape, and ask once more if it was wrong.
 *
 * This is the only place the driver is called from, which is what makes
 * "malformed output never reaches a couple" a property of the code rather than
 * a rule people remember: the thing this returns has no valid output in it
 * unless the schema said so.
 *
 * It does not log and it does not persist. Transport failures propagate
 * untouched — those are the job's to retry (`RunAiTask`), on a different
 * timescale and for a different reason.
 */
final readonly class GenerateValidated
{
    /**
     * One try, plus exactly one retry.
     *
     * A model that returns the wrong shape twice is not going to find it on
     * the third go, and every attempt is billed. Two is enough to ride out a
     * blip and cheap enough not to think about.
     */
    private const ATTEMPTS = 2;

    public function __construct(
        private AiDriver $driver,
        private SchemaValidator $validator,
    ) {}

    public function handle(AiRequest $request): Generation
    {
        // Built once: it is both what goes out as output_config.format and
        // what the reply is checked against.
        $schema = $request->jsonSchema();

        $attempts = [];
        $problems = [];

        for ($try = 1; $try <= self::ATTEMPTS; $try++) {
            // The retry sends the *same* request, deliberately. Telling the
            // model what it got wrong would be a better repair and a worse
            // record: the prompt that produced the output would no longer be
            // the pinned version `ai_generations` points at.
            $result = $this->driver->generate($request);

            $attempts[] = $result;

            $problems = $this->validator->problems($schema, $result->structured);

            if ($problems === []) {
                return new Generation($attempts, $result->structured, []);
            }
        }

        return new Generation($attempts, null, $problems);
    }
}
