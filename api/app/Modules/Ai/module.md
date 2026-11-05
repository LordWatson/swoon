# AI Platform ✨

**Status:** in progress
**Last updated:** 2026-11-05 - P2.T01-T08 done. `AiService::run()` is the only path to the model, and the whole chain runs in a queued job. The eval fixtures and the live eval command (P2.T09) are what remain.
**Owner phase:** Phase 2 (see docs/PLAN.md)

> This is the **canonical** doc for this module and covers the full stack.
> Mobile-only detail lives in `n/a - backend only`.
> Signature: ✨ Lavender

## Purpose
The single door between Swoon and Claude. Every AI feature in the app goes through this module: it resolves a versioned prompt, builds the input from a privacy-filtered payload, calls the model in a queued job, validates the structured output, runs SafetyGuard, logs the cost, and enforces the fair-use cap. No other module talks to the model directly.

## User stories
- As a developer, I can add an AI feature without touching HTTP, retries, cost logging or safety.
- As a developer, I can run the whole test suite with no network and no spend.
- As the business, I can see exactly what every couple's AI use costs.
- As a couple, I never get a reply that ignores a crisis I just described.

## Data model
**ai_usage** - id, couple_id (nullable), user_id (nullable), module, task (matches a key in `config('ai.tasks')`), prompt_name, prompt_version, model (nullable), input_tokens, output_tokens, **cost_millipence**, outcome (`ok`|`invalid_output`|`safety_tripped`|`capped`|`error`), duration_ms, timestamps. Indexed on (couple_id, created_at) for the monthly cap query and on created_at for the global daily guard.

One row **per model call**, including the calls that failed - a reply in the wrong shape was billed exactly like a good one. Outcomes that never reached the model (`capped`) get a row with no model and no tokens, so "nothing happened" and "it was refused" are different facts rather than both being absence.

`cost_millipence`, not `cost_pence`, and the unit is in the name (**ADR-026**): a safety screen costs about 0.0055p, SafetyGuard runs twice on every generation, and rounded to pence every one of them would log zero.

A `safety_tripped` row carries **no couple and no user** (**ADR-027**). `UsageLogger` strips them whatever the caller passes. The cost stays true; nothing links a trip to a person; and a tripped generation does not spend the couple's allowance, which is right on its own merits.

The prompt reference is **split** into `prompt_name` and `prompt_version`, so "which prompt costs the most" and "did v2 get cheaper" are each one query.

**ai_generations** - id, ulid (the public identifier), couple_id, module, task, prompt_name, prompt_version, payload_hash, output (json), superseded_by (nullable - set when regenerated), timestamps. Keeps what the couple was actually shown, so a prompt change never rewrites history - which is why a released prompt file is never edited in place.

A regenerate **supersedes** rather than overwrites: the old row stays with `superseded_by` set, so "what did we show them in March" has an answer after they asked for something better in April.

There is **no `safety_outcome` column**. A row only exists when the output was shown, which means the screen said safe, so it could only ever hold one value. The doc described one before the guard was built; the guard settled it.

**ai_feedback** - id, ai_generation_id, user_id, rating (`up`|`down`), note (nullable), timestamps. Private to the rater, and unique on (ai_generation_id, user_id) so a double tap cannot read as two people disliking something. `user_id` is not fillable: `AiGeneration::rateBy(User, ...)` sets it from a `User` and never from request input.

Nothing here stores a partner's private content: the payload is hashed, not kept.

## API endpoints
| Method | Path | Auth | Purpose | Request | Response resource |
|---|---|---|---|---|---|
| POST | /api/ai/generations/{ulid}/feedback | sanctum | Rate an AI output | AiFeedbackRequest | 204 |
| POST | /api/ai/generations/{ulid}/regenerate | sanctum | Regenerate, counting against the cap | - | 202, or 200 with the cap's copy |
| GET | /api/ai/allowance | sanctum | What the couple has left this month | - | AllowanceResource |

`/api/ai/allowance` takes **no identifier**: a person has at most one live
couple and the database guarantees it, so an id would only be a way to ask
about somebody else's. A solo user gets a 404, because an allowance belongs to
a couple rather than a person. The numbers and the refusal copy come from the
same `CapGuard` the job uses, so the screen and the refusal cannot disagree -
being told "that's your date plans for this month" after filling in a form is
worse than seeing "1 left" beside the button.

## Business rules
1. **Server-side only.** The Anthropic key lives in `api/.env` and never ships in the app. There is no proxy endpoint that forwards arbitrary prompts.
2. **Every AI call runs in a queued job.** No AI call happens in an HTTP request, so a slow model never becomes a slow API.
3. **Prompts are versioned files**, not strings in code, under `resources/prompts/<name>/<version>.md`. The active version per prompt is pinned in `config('ai.prompts.active')`, so adding a file does not change behaviour.
4. **Structured output is validated** against a JSON schema before the couple ever sees it. A malformed response is retried once, then recorded as `invalid_output` and surfaced as a friendly 'that didn't work, try again'.
5. **SafetyGuard runs on every user-facing generation** - as a distinct, separately tested step, never a line in a prompt. If the input suggests abuse, crisis or harm, the normal output is discarded and replaced with a short, kind signpost to UK services (`config('ai.safety.signposts')`) and nothing else.
6. SafetyGuard is **fail-closed**: if the safety step itself errors, the generation is abandoned, not shipped unchecked. There is deliberately **no config flag to disable it** - see ADR-017. Work that must not reach a model uses `AI_DRIVER=fake`.
7. **Private data never enters a prompt that produces shared output.** The filter is a allow-list built per task, not a deny-list, so a new private field is excluded by default.
8. **Caps are checked before dispatch**, per couple per calendar month, from `config('ai.caps')` by entitlement tier. A capped request returns a kind message, not a 429.
9. A **runaway cost guard** sits above the caps (per couple, and global per day). Crossing it alerts and refuses - it should never be reached.
10. **Every call is logged to `ai_usage`**, including failures, with tokens and computed cost. An unlogged call is a bug. `cost_millipence` is computed by `Support\Cost` from `config('ai.pricing_usd_cents_per_mtok')` and `config('ai.usd_to_pence')` - provider prices are quoted in US cents, every budget in this app is in pence, the column is in millipence, and no two of those are interchangeable (ADR-016, ADR-026).
11. **Model ids come from config**, never a literal. `config('ai.tasks.<task>')` picks the model key and effort.
12. The **fake driver** (`AI_DRIVER=fake`) returns fixtures from `tests/Fixtures/Ai`. The test suite pins it; a test that makes a real call is a failing test.
13. AI output is **never** attributed to a partner. The API marks it `source: ai` and the app labels it (UX rule 6).

## AI usage (if any)
This module *is* the AI surface. See `docs/AI_PROMPTS.md` for the prompt index. Shape:

```
AiService::run(task, payload, couple)
  -> CapGuard        (entitlement + monthly cap + cost guard)
  -> PrivacyFilter   (allow-list per task)
  -> PromptRegistry  (name + pinned version -> rendered prompt)
  -> dispatch job    (queued, always)
       -> Driver     (anthropic | fake)
       -> SchemaValidator
       -> SafetyGuard (fail-closed)
       -> UsageLogger (ai_usage)
       -> persist ai_generations
```

## Drivers

`AiDriver` is the transport step and only the transport step: one rendered
prompt in, one `AiResult` out. It does not validate, screen, filter or log -
each of those is a separate step the job chains around it, which is what lets
the whole suite run on fixtures and still exercise every rule.

| Piece | What it does |
|---|---|
| `Contracts\AiDriver` | `generate(AiRequest): AiResult`. The only door to a model. |
| `Support\AiRequest` | One rendered prompt, plus the task's `model`, `effort` and `maxTokens` resolved from `config('ai.tasks')` **in the constructor**. This is why no model id appears anywhere else: a driver asks the request, the request asks config. An unknown task or model key throws before a cap is spent. |
| `Support\AiResult` | `structured`, `text`, `model`, `usage`. Nothing in it has been vouched for yet. `usage` is laravel/ai's own `TextUsage`, which already separates cached from uncached input tokens - what cost logging will need the day prompt caching is turned on. |
| `Support\TaskAgent` | The laravel/ai agent. laravel/ai expects one agent class per job with its model and ceiling as PHP attributes; we have one agent and many tasks, and an attribute cannot read config, so the per-task settings ride in as **provider options** (`max_tokens`, `output_config.effort`). No `thinking` key: these models take adaptive thinking by default and reject an explicit `budget_tokens` with a 400. |
| `Drivers\AnthropicDriver` | The real door, over laravel/ai. Refuses outright when `config('ai.providers.anthropic.key')` is blank. |
| `Drivers\FakeDriver` | Answers from `tests/Fixtures/Ai/<task>.json`, or from a queued response, and **records every request**. |

**A schema is required on every request.** A task with no schema would leave
`SchemaValidator` nothing to check, so `AiRequest` demands one and `TaskAgent`
refuses an empty one - laravel/ai would otherwise send no `output_config` at
all and the model would answer in free text.

**The API key has one home:** `config('ai.providers.anthropic.key')`, which
laravel/ai merges into our own `config/ai.php` under the same `ai` key. There
is deliberately no second copy of it in the file we wrote.

### Why there is a fake driver *and* laravel/ai's fake

Both, each where it fits - see ADR-024. Ours is the default for the suite and
records what the chain sent; laravel/ai's `TaskAgent::fake()` covers
`AnthropicDriver`'s own mapping, which ours by definition replaces.

Two belts hold "no test makes a real AI call": `AI_DRIVER=fake` means the
Anthropic driver is **never constructed**, and `ANTHROPIC_API_KEY` is
force-blanked in `phpunit.xml` so it would refuse if it were. The blanking is
not belt-and-braces theatre - `APP_ENV=testing` with no `.env.testing` loads
the developer's own `api/.env`, which holds a working key.

## Prompts

`PromptRegistry::resolve($name, $variables)` reads
`resources/prompts/<name>/<version>.md` at the version pinned in
`config('ai.prompts.active')` and returns a `RenderedPrompt` carrying the text,
the version and a `reference()` like `safety_screen/v1` - which is what goes on
every `ai_usage` row. The version travels with the text rather than being
looked up again later, so a config change mid-job cannot make a row disagree
with the prompt that produced it.

Dropping a `v2.md` beside `v1.md` changes nothing: promoting it is a config
edit. A released version is never edited in place, because `ai_generations`
rows point at it.

**`config('ai.prompts.active')` lists only prompts whose file exists.** A pin
without a file is a promise the code cannot keep, and it would surface the
first time a couple triggered the feature - so the suite fails on one, and each
phase adds its pin alongside the file it writes.

Slot rules, and why each is strict, are in `docs/AI_PROMPTS.md` and
`resources/prompts/README.md`. Short version: a slot with no value throws, a
value with no slot throws, `null` is rejected, and there is no template
language.

## Output validation

Every task declares its output shape in **`Schemas/TaskSchemas`** - one file,
so a reviewer can read every shape the product asks a model for in a single
sitting. Each phase adds its case next to the prompt file it writes.

That one declaration does two jobs. `AiRequest::jsonSchema()` turns it into
the `output_config.format` Anthropic receives, and `SchemaValidator` checks the
reply against **the same array**. There is nothing to keep in step.

`GenerateValidated` is the only caller of the driver:

```
GenerateValidated::handle(AiRequest): Generation
  -> driver->generate()      (attempt 1)
  -> SchemaValidator
  -> driver->generate()      (attempt 2, only if the shape was wrong)
  -> SchemaValidator
```

* **Exactly one retry.** A model that returns the wrong shape twice will not
  find it on the third go, and every attempt is billed.
* **The retry sends the same request**, unchanged. Telling the model what it
  got wrong would be a better repair and a worse record: the prompt that
  produced the output would no longer be the pinned version
  `ai_generations` points at.
* **`Generation` holds every attempt**, because every attempt cost money and
  `ai_usage` has to show what we paid even when the answer was useless.
* **`Generation::$structured` is null unless the reply passed.** So "malformed
  output never reaches a couple" is a property of the type rather than a rule
  somebody remembers.
* **Transport failures propagate untouched.** A connection failure is the job's
  to retry, on a different timescale and for a different reason - spending the
  shape retry on it would leave nothing for the shape.

**Extra fields are rejected, not ignored.** Every schema we send sets
`additionalProperties: false`, so a field we did not ask for means this is not
the reply we asked for, and dropping it quietly would hide that.

`SchemaValidator` understands a fixed list of JSON Schema keywords and
**throws on any other**, rather than skipping it. A constraint declared in a
task's schema and silently unchecked would be a hole that never announces
itself; the suite walks every declared schema and fails on an unsupported
keyword, so the gap cannot wait for production.

## The chain

`Actions\AiService::run()` is the only way in, and it returns a `CapVerdict`:
refused, with copy to show, or allowed, with a job queued. A feature module
never sees a driver, a prompt file or a token count.

```
AiService::run(module, task, payload, couple, user, screen, supersedes)
  -> CapGuard          refused? log `capped`, return the verdict, spend nothing
  -> PrivacyFilter     before dispatch, so the jobs table never holds a
                       un-allow-listed field
  -> dispatch RunAiTask
       -> SafetyGuard   each input, one at a time, before the expensive model
       -> PromptRegistry  pinned version, rendered
       -> GenerateValidated  one retry on a bad shape
       -> SafetyGuard   the draft
       -> persist       ai_generations, superseding if this is a redo
       -> UsageLogger   a row per call, on every path
       -> event         Completed | Failed | SafetyTripped
```

Two steps happen **before** dispatch on purpose. The cap, so a refusal costs
nothing at all - not a model call, not a queue slot. And the privacy filter:
the plan put it inside the job, and moving it one step earlier keeps
un-allow-listed fields out of a `jobs` row that sits on disk until a worker
picks it up. The guarantee is unchanged - nothing reaches a prompt that was
not allow-listed - and the queue is cleaner for it.

**Every exit logs.** There is no `return` in `RunAiTask` without an `ai_usage`
row behind it, and a test walks all six paths asserting each one left a row.

**Retries.** `$tries = 2`, so a transport failure escaping the expensive
generation gets one more go. A safety trip is a clean return and so is never
retried. Nor is a screen that *failed*: SafetyGuard has already decided the
generation is abandoned, and paying twice would not change that.

**Three events** (`Modules/Ai/Events`), and the third is separate for a
reason:

| Event | Carries | Why |
|---|---|---|
| `AiGenerationCompleted` | the generation | something new to look at |
| `AiGenerationFailed` | couple, user, outcome | "that didn't work, try again" |
| `AiSafetyTripped` | **one user id**, module, task | not a failure, and a listener treating it like one would say exactly the wrong thing |

`AiSafetyTripped` carries no couple, no category and no content. The signpost
goes to the person whose words tripped it and to nobody else; their partner
sees an ordinary failure (ADR-025).

### Regenerate

A generation keeps its **filtered** payload, which is what makes a generic
regenerate possible: `POST /api/ai/generations/{ulid}/regenerate` asks the same
question again with the payload already on the row. Keeping it is safe
*because* it went through the allow-list - by construction it holds no private
field - and a hash cannot ask a question again.

A regenerate counts against the cap like any other generation, and the old row
is **superseded** rather than overwritten, so what a couple was shown in March
survives them asking for something better in April. The reply is 202 and a
queued job, or 200 with the cap's own copy - a cap is not an error.

## Caps and the cost guards

`Actions\CapGuard::check($couple, $task)` is called **before** a job is
dispatched, so a refusal costs nothing. Three ceilings, in the order a couple
meets them:

1. the **fair-use cap** for their tier, per task, per calendar month, from
   `config('ai.caps')`;
2. a **per-couple cost guard** (`cost_guard.couple_monthly_pence`), far above
   the caps - in normal use unreachable, so hitting it means a bug or abuse
   rather than a keen couple;
3. a **global daily cost guard** (`cost_guard.global_daily_pence`), which is
   our problem and not theirs. Crossing it logs `critical` - that is the alert -
   and names no couple, because it is not about one.

The global guard is checked **first**: when everyone's spending has crossed the
ceiling, this couple's allowance is not the relevant fact.

**Only successful generations count against the cap.** A reply in the wrong
shape, a transport failure, a guard that broke - none of them gave the couple
anything, and charging their monthly allowance for our failure would be unkind.
The money those attempts cost is still logged, and noticing a lot of it is the
cost guard's job. The feature cap counts what they got; the cost guard counts
what we spent.

**The month ends at local midnight in the couple's timezone**, so a couple in
Auckland is not told their allowance resets eleven hours late.
`Couple::timezone()` takes the **earliest-joining live member's** - stable
rather than fair, because if it depended on who asked, two partners in
different zones would see different allowances either side of the first.

**Four refusals, four pieces of copy** (`CapReason`), because "that's your date
plans for this month" and "something's busy at our end" are not the same news:

| Reason | Says |
|---|---|
| `monthly_cap` | "That's your date plans for this month 💝 They come back on 1 December." |
| `not_on_this_tier` | "Weekly recaps come with Together 💛" - nothing to wait for, so it does not pretend a reset |
| `couple_cost_guard` | "That is all the AI we can do for you this month 💛 It comes back on 1 December." |
| `global_cost_guard` | "Something's busy at our end 💛 Give it a little while and try again." - true, vague, calm, and it does not mention money |

The copy travels with the verdict rather than being written wherever a cap is
checked, so there is one message per reason rather than one per caller. A test
asserts none of them contains "exceeded", "limit reached", "too many",
"denied", "quota" or "error" (UX rule 8).

**Entitlement** comes from `Billing\Support\Entitlement::tierFor()`, which is
the one stub saying everybody is on `spark` until Phase 8. `MeResource` used to
hold a second copy of it; a stub in two places is how it becomes two stubs that
disagree.

## The privacy filter

`Support\PrivacyFilter::apply($task, $payload)` keeps the allow-listed fields
and drops everything else. **Every** variable that reaches a prompt comes
through it, `safety_screen` included, because an exception is where a leak
would live.

What survives is declared in **`Schemas/TaskAllowList`** - one file, per task,
as dotted paths with `*` for a position in a list:

```
partners.*.first_name
partners.*.answers.outcode
recent_dates.*.title
```

**An allow-list, never a deny-list**, and that is the whole design. A deny-list
has to be updated every time a private column is added, by somebody who happens
to remember this file exists. An allow-list excludes a new column by not
mentioning it, which is the behaviour we want from the person who forgets. The
filter does not need to know a field is private; it only needs to not know it
at all.

**Every entry names a leaf.** `partners.*.answers` for "all the answers" would
quietly include whatever is added inside it later, so the filter throws when an
allow-listed path turns out to hold an object. A list of plain values
(`dietary_needs`) is fine: it can grow longer, but it cannot grow new fields.

**An undeclared task is refused outright**, so "we forgot to write the
allow-list" cannot become "it sent everything".

**A list keeps its length.** An item with nothing allow-listed in it stays, as
an empty object, because dropping it would change how many things the payload
describes and the model cannot tell that two partners became one. A list where
*nothing* survived collapses instead, so an unmentioned branch - a gift vault,
a set of check-in answers - disappears completely rather than arriving as a row
of empty objects.

**`safety_screen` is allow-listed to see raw text**, private text included, and
that is deliberate rather than an oversight: a locked love note is exactly
where somebody might say something that needs a signpost, and a screen that
skipped private text would skip the most important cases. It is safe because of
what the task *is* - a classification, nothing stored, nothing shown to the
partner (ADR-025) - not because of what it is given.

`date_plan`'s allow-list is declared now and used from Phase 3. The prompt is
not written yet; the list is, so the privacy tests exist before the feature
does. Note `outcode`, never `postcode`.

## SafetyGuard

`Actions\SafetyGuard::screen($text): SafetyVerdict`, run **twice** on every
user-facing generation: once on the free text a person wrote, before the
expensive model is asked anything, and once on the draft that comes back.
Screening the input first is not only cheaper - if somebody has just described
a crisis, asking Opus for three date ideas is the wrong thing to do with that
sentence.

It is a separate step with its own prompt (`safety_screen/v1`), never a
paragraph inside another prompt. A rule buried in a 2,000-token date-planning
prompt competes for the model's attention with the date planning.

**Three outcomes, not two** (`SafetyState`):

| State | Means | What a person sees |
|---|---|---|
| `safe` | The screen ran and found nothing | The normal output |
| `concerning` | Anything but `none`, **`unclear` included** | `signpost()` and nothing else |
| `unusable` | The screen itself failed | Nothing at all |

The third exists because a guard that *breaks* has told us nothing. An
unscreened date plan is the thing this module exists to prevent, and a crisis
helpline shown to someone who was choosing a restaurant is its own small harm.
Everything that can go wrong - transport failure, two malformed replies, an
unpinned prompt, a missing file - lands on `unusable`, and a test walks every
one of those routes asserting none of them can answer `safe`.

**What it keeps: nothing.** `SafetyVerdict` carries no part of the text, no
attribution, and the classification only in memory, for the eval command to
score against. It is never persisted and never logged - a row saying this
couple raised an abuse concern on this date is exactly the kind of record that
can hurt the person it is about. The cost is that we cannot report how often it
trips or whether a prompt change improved it; ADR-025 takes that trade
deliberately and names what replaces it.

**What a person sees** is `signpost()`: `message` and `signposts`, and nothing
else can be added to them. The copy breaks the brand voice on purpose - no
emoji, no "you two", no cheer, and no guess at what is happening. Tested, so a
later tidy-up cannot quietly restore the voice.

**Routing.** Because the guard screens one text at a time, the caller knows
whose words tripped it and sends the signpost to that person only. The other
partner sees an ordinary "that didn't work". A concern is never revealed to a
partner, in any form.

## Jobs, events & schedules
**Jobs:** `RunAiTask` (the one queued entry point; per-task payloads), retried once on a transport error, never retried on `invalid_output` after the first retry.

**Events:** `AiGenerationCompleted`, `AiGenerationFailed`, `AiSafetyTripped` (logged and alerted - we want to know, without storing what triggered it).

**Scheduled:** `ai:report-costs` daily, writing a per-couple and global cost summary to the log and alerting if the trend crosses the guard.

## Mobile
No mobile feature folder. The shared `AITag` component and the `useAiAllowance()` hook live in `mobile/src/components/` and `mobile/src/api/`.

## Permissions & privacy
This is the module where privacy is won or lost.

**Excluded from every prompt, always:** gift vault entries, individual check-in answers, unsent love notes, private moment notes, the other partner's un-revealed answers, auth credentials, email addresses, exact addresses or coordinates.

**The filter is an allow-list per task**, declared in `Schemas/TaskAllowList` and applied by `Support\PrivacyFilter` - see **The privacy filter** above. A task declares the leaves it may see; anything else is dropped. A newly added private column is therefore excluded without anyone remembering to exclude it, and there is a test that reads the real `users` columns to prove it.

`ai_feedback` is private to the rater - a partner never sees that the other thumbs-downed a plan.

## Tests
`api/tests/Feature/Ai/` and `api/tests/Unit/Ai/`.

Done so far (P2.T08): **every terminal outcome end to end**, each checking what the couple ends up with, what `ai_usage` says and which event fired. `ok` (three calls in the right order - screen, model, screen - a generation row, and the allow-list proven again at the prompt); `invalid_output` (nothing shown, both attempts billed, the allowance unspent); `safety_tripped` on the input (the expensive model never asked, the row naming nobody, the signpost going to the writer, and `AiGenerationFailed` *not* fired) and on the draft (billed for a plan nobody will see); `capped` (no call, nothing queued, a row with no model); `error` from an unpinned prompt and from a screen that broke. Plus: one test walking all six paths asserting each left a usage row; `RunAiTask` being a `ShouldQueue`; a regenerate answering 202 with the request itself having called nothing; supersede keeping both rows; a regenerate counting against the cap; the cap's own copy returned rather than an error; feedback recorded, changeable, kept apart between partners, and validated.

A grep gate too: nothing outside the AI module may mention `AiDriver`, `AnthropicDriver`, `GenerateValidated` or `AiRequest`, which is the phase's exit criterion as a test. `AppServiceProvider` is exempt, because the container binding has to live somewhere.

Done so far (P2.T07): the cap boundary at n-1, n and n+1; counted per couple and per task; only successful generations counted, with `invalid_output`, `error` and `capped` explicitly not; last month excluded. The month rolling over at **local midnight in Auckland** while UTC still says November, with the reset date landing on 1 January in the couple's own zone; the timezone taken from whoever joined first so it cannot wobble. The refusal naming the date; a no-guilt word list across all four reasons; a tier that does not include a feature saying so rather than pretending a reset; a task with no cap entry refused rather than unlimited. Both cost guards, including the global one alerting at `critical` and taking precedence over everything about the couple, and yesterday's spending not counting towards today. A refusal spending nothing.

The endpoint: per-task numbers, the written refusal, capped tasks only (no `safety_screen`), each couple's own figures, a 404 for a solo user, a 401 without a token, and no identifier in the route. Plus `AiAuthorisationTest`, the module's own sweep driven off the route table - proven to bite by adding an undeclared route.

Done so far (P2.T06): the cost arithmetic checked against sums worked out by hand for both models, including the Haiku screen that rounding to pence would lose entirely; a price change in config moving the answer; `toPence()` rounding up; a warning and a gate for a model with no price. A row for each of the five outcomes, including the refusal that never reached the model. For a safety trip: no couple, no user, the attribution dropped even when passed, and the allowance unspent. Supersede keeping both rows with their own prompt versions and outputs; a ULID on every generation; a payload hash and no payload; one rating per person per generation.

Done so far (P2.T05): **one test per private field, by name** - gift vault, individual check-in answers, private moment notes, private love-map entries, locked love notes, the partner's un-revealed answers, email addresses, full postcodes, shipping addresses, coordinates, password hashes and tokens. Each asserts the value is absent from the filtered payload, so widening the allow-list fails the test belonging to the field it exposed. Plus: the kept set equals the allow-list exactly; dietary and accessibility needs survive; both partners survive when one has nothing to say; a list stays a list; an undeclared task is refused; an allow-listed path holding an object throws; no allow-list entry anywhere names a postcode, address, coordinate, email, phone, password or token.

Two tests carry the future: a column nobody has heard of yet is dropped, and **every column of the real `users` table** is dropped unless allow-listed - read from `Schema::getColumnListing()`, so it grows with the table rather than with somebody's memory of it. Proven to bite by allow-listing `partners.*.email`: three tests failed, including the one that scans the list itself.

Done so far (P2.T04): **77 battery cases, one test each** - 32 concerning phrasings across every category including indirect and euphemistic forms, and 45 benign ones (ordinary rows, grumbles, dark humour, fiction, grief, surprises). Each asserts the handling *and* that the words reached the screen. Plus: `unclear` treated as a concern; every failure route landing on `unusable`; a single test asserting no route can answer `safe` by accident; empty text costing nothing; the pinned version, light model and low effort used; the signpost's keys fixed at two; no emoji and no diagnosis words in the copy; the verdict carrying none of the text; nothing written to the log on either the tripped or the failed path; and the battery's own integrity (categories covered, benign set larger, no concerning case expecting `none`).

Proven to bite by making `unclear` safe: four battery cases and two direct tests failed.

Done so far (P2.T03): a matching reply passes, including a nested list of objects; a missing field, a wrong type, an extra field and an out-of-enum value each reported in plain words, with the path to the item inside a list; every problem reported rather than the first; one complaint per mistake; list bounds; integer accepted where a number is wanted; `null` only where the schema allows it; `[]` taken as an empty object. Exactly one retry, proven by queueing three responses and asserting the third was never asked for; a transport failure passing straight through; both attempts kept when both failed.

Gates: every task with a **pinned prompt** has a declared schema, and every declared schema uses only keywords the validator checks - proven to bite by adding `format` on purpose.

Done so far (P2.T02): the pinned version wins over a newer file on disk; an unpinned prompt, a pin with no file, and a version that is not a version are each refused; every value type renders, including `false` as a word rather than nothing; a missing slot, a stray value and a `null` each throw; braces typed inside a value stay text. Plus the gate: every prompt the real config pins has a file.

Done so far (P2.T01): driver selection both ways and an unknown name refused; the real driver never constructed under the fake; the real driver refusing without a key, which is the *assertion* behind "no network"; the fake's recording, its fixture loading, its queued overrides and its loud failure on a missing fixture; `AiRequest` resolving model, effort and ceiling from config; `TaskAgent`'s provider options following config; `AnthropicDriver`'s mapping, via laravel/ai's own agent fake.

`ModelIdsTest` is a gate rather than a convention: it scans every PHP file under `app/`, `config/`, `database/`, `resources/`, `routes/` and `tests/` for a model id and fails on any outside `config/ai.php`. Proven to bite by adding one on purpose.

Still to cover: cap enforced before dispatch; cost guard refuses; fake driver used throughout; malformed output retried then recorded; **SafetyGuard trips on a battery of crisis and abuse phrasings and returns only the signpost**; SafetyGuard failing closed; privacy filter dropping each private field (one test per private field, so the list cannot rot); prompt version pinning; `ai_usage` written on success AND on every failure path.

**Prompt evaluation fixtures** live in `tests/Fixtures/Ai/evals/` - input payloads with expected-shape assertions, so a prompt edit shows its effect without a live call.

Run: `composer test -- --filter=Ai`

## Known gaps / TODO
- ANTHROPIC_API_KEY not yet set (by hand, by the founder).
- **How a signpost actually reaches somebody is unresolved.** `AiSafetyTripped` fires with one user id and nothing is stored, which is right (ADR-025) and leaves a gap: the app learns about a finished generation by asking, and there is nothing to ask about when the answer was a signpost. The first feature that needs it decides, and the constraint is that a record saying "this person was signposted on this date" must not exist. Phase 3's to answer.
- `AiFeedback` has no policy of its own. It is only ever reached through its generation, whose policy covers it, and a rating is never returned to anybody - not even its author. `AiUsage` has none either: it is operational and never reaches a client.
- Decide whether laravel/ai's own fake/record helpers replace the hand-rolled fake driver - evaluate during P2.T01.
- **SafetyGuard's battery needs review by someone with relevant expertise before launch** - domestic abuse and crisis support. 77 cases exist and the tests pass; neither fact makes the phrasings right. `tests/Fixtures/Ai/evals/safety_screen/README.md` says the same thing where the cases live.
- We deliberately store nothing about a safety trip, so there is no way to measure how often it happens in the wild (ADR-025). If that becomes a problem, the answer is a count with no couple, no category and no content attached - not a relaxation of the rule.
- Cost-per-couple model is unvalidated until real traffic exists.

## Changelog
- 2026-11-05 - **P2.T08**: `AiService`, `RunAiTask`, the three events, the
  feedback and regenerate endpoints with `AiGenerationPolicy`, and
  `ai:report-costs` on the daily schedule. `ai_generations` now keeps its
  **filtered payload** instead of a hash - a hash cannot ask the question
  again, and a generic regenerate has to; it is safe to keep because the
  allow-list has already been through it. `date_plan`'s output schema is
  declared ahead of its prompt, same as its allow-list was. `FakeDriver`
  gained `respondTo($task, ...)`, because one generation makes three calls and
  a single queue handed the date plan to the first safety screen that asked.
- 2026-11-05 - **P2.T07**: `CapGuard`, `CapVerdict`, `CapReason`,
  `GET /api/ai/allowance` and the module's authorisation sweep.
  `Billing\Support\Entitlement` absorbs the tier stub `MeResource` was
  holding, so there is one of it. `Couple::timezone()` added, derived from the
  earliest-joining live member. Capped tasks gained a `label` in config, which
  is the plural a couple reads in a refusal.
- 2026-11-05 - **P2.T06**: migrations for `ai_usage`, `ai_generations` and
  `ai_feedback`, their models, `Support\Cost` and `Support\UsageLogger`.
  Two decisions came out of the arithmetic rather than going in: **ADR-026**
  (millipence, because a safety screen rounds to zero in pence) and **ADR-027**
  (a `safety_tripped` row carries no couple, resolving the clash between
  "log every call" and "store nothing about a trip"). `ai_generations` lost the
  `safety_outcome` column this doc used to describe, because a row only exists
  when the screen said safe.
- 2026-11-05 - **P2.T05**: `PrivacyFilter` and `TaskAllowList`. `SafetyGuard`
  now builds its one variable through the filter like everything else, so the
  rule has no exceptions. `date_plan`'s allow-list is declared ahead of its
  prompt, which is what lets the privacy tests exist before the feature.
- 2026-11-05 - **P2.T04**: `SafetyGuard`, `SafetyVerdict`, `SafetyState`,
  `resources/prompts/safety_screen/v1.md` (now pinned), the signpost copy in
  `config('ai.safety.message')`, and the 77-case battery under
  `tests/Fixtures/Ai/evals/safety_screen/`. ADR-025 records the voice
  exception, the one-text-at-a-time screening that lets a signpost be
  directed, and the analytics we gave up to store nothing.
- 2026-11-05 - **P2.T03**: `TaskSchemas`, `SchemaValidator`, `GenerateValidated`
  and `Generation`. A task's schema is now resolved from its declaration rather
  than passed in, so the shape is a property of the task. `AiRequest` gained
  `jsonSchema()`, which is both what goes out and what comes back is checked
  against. Extra fields rejected; unsupported JSON Schema keywords throw.
- 2026-11-05 - **P2.T02**: `PromptRegistry` + `RenderedPrompt`.
  `config('ai.prompts.active')` was pinning four prompts that had no files;
  trimmed to the prompts that exist, with a test that fails on a pin without a
  file. Prompt fixtures for the registry's own tests live in
  `tests/Fixtures/Ai/prompts/`, so nothing invented sits in
  `resources/prompts/`.
- 2026-11-05 - **P2.T01**: `AiDriver` + `AnthropicDriver` + `FakeDriver`, bound
  by `config('ai.driver')` in `AppServiceProvider`. ADR-024 records why both
  our fake and laravel/ai's survive. `config/ai.php` lost its duplicate
  `anthropic.api_key` (one secret, one path), and `phpunit.xml` lost its
  placeholder key in favour of a forced blank - a placeholder would have let an
  accidental call go out and come back 401, a far quieter failure.
- 2026-11-05 - Module documented and scaffolded (folders + routes.php). No code yet.
- 2026-11-05 - Audit fixes to `config/ai.php`, before any code reads it: the
  `safety.enabled` flag removed (ADR-017), prices renamed to
  `pricing_usd_cents_per_mtok` as whole numbers with a `usd_to_pence` rate
  (ADR-016), and the Together/Forever caps defined once instead of copied.
