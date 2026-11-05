<?php

declare(strict_types=1);

namespace App\Modules\Ai\Actions;

use App\Modules\Ai\Support\AiRequest;
use App\Modules\Ai\Support\PrivacyFilter;
use App\Modules\Ai\Support\PromptRegistry;
use App\Modules\Ai\Support\SafetyVerdict;
use Throwable;

/**
 * The step between a couple and anything the model says.
 *
 * It runs on **every** user-facing generation, twice: once on the free text a
 * person wrote, before the expensive model is asked anything, and once on the
 * draft the model produced. Screening the input first is not only cheaper —
 * if somebody has just described a crisis, asking Opus for three date ideas
 * is the wrong thing to do with that sentence.
 *
 * It is a separate step with its own prompt, never a paragraph inside another
 * prompt. A rule buried in a 2,000-token date-planning prompt is a rule that
 * competes for the model's attention with the date planning.
 *
 * **It fails closed, in two different ways.** A classification of anything but
 * `none` — `unclear` included — stops the generation and replaces it with the
 * signpost. A guard that *breaks* stops the generation and shows nothing at
 * all: we do not know whether that text was safe, and both "here are three
 * restaurants" and "here is a crisis helpline" would be wrong.
 *
 * **It screens one text at a time, on purpose.** The caller knows whose words
 * it handed over, which is the only way a signpost can reach the person who
 * needs it without telling their partner anything (ADR-025).
 */
final readonly class SafetyGuard
{
    public function __construct(
        private PromptRegistry $prompts,
        private PrivacyFilter $privacy,
        private GenerateValidated $generate,
    ) {}

    public function screen(string $text): SafetyVerdict
    {
        if (trim($text) === '') {
            // Nothing to screen, and nothing to pay for.
            return SafetyVerdict::from('none', []);
        }

        try {
            // Through the filter like every other prompt, even though the
            // allow-list is one entry. No exceptions: an exception is where a
            // leak lives, and `safety_screen` being allowed to see raw text
            // is a line somebody can read rather than an omission.
            $variables = $this->privacy->apply('safety_screen', ['text' => $text]);

            $prompt = $this->prompts->resolve('safety_screen', $variables);

            $generation = $this->generate->handle(new AiRequest(
                task: 'safety_screen',
                prompt: $prompt->text,
                promptVersion: $prompt->reference(),
            ));
        } catch (Throwable) {
            // Deliberately bare. A transport failure, a missing prompt file, a
            // bad config value — from here they are the same fact: this text
            // was not screened. Nothing is logged about it, because the only
            // thing worth logging would be the text.
            return SafetyVerdict::unusable();
        }

        if (! $generation->isValid()) {
            // Two malformed replies in a row. We have the token cost and no
            // answer, so the generation is abandoned and the cost still logged.
            return SafetyVerdict::unusable($generation->attempts);
        }

        $concern = $generation->structured['concern'] ?? null;

        if (! is_string($concern)) {
            // The schema says this is a required string, so reaching here
            // means the schema and the validator disagree. Fail closed.
            return SafetyVerdict::unusable($generation->attempts);
        }

        return SafetyVerdict::from($concern, $generation->attempts);
    }

    /**
     * The whole of what a person sees when the guard trips.
     *
     * Two keys, and nothing else can be added to them: no reason, no category,
     * no "we noticed", no partial date plan alongside. The person is told we
     * cannot help and who can, and that is all we know how to do well.
     *
     * @return array{message: string, signposts: list<array<string, string>>}
     */
    public function signpost(): array
    {
        /** @var list<array<string, string>> $signposts */
        $signposts = array_values((array) config('ai.safety.signposts'));

        return [
            'message' => (string) config('ai.safety.message'),
            'signposts' => $signposts,
        ];
    }
}
