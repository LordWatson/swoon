# AI prompts — Swoon

**Last updated:** 2026-11-05
**Status:** the registry works (P2.T02); the prompts themselves are written in
Phase 2 (`safety_screen`) and Phase 3 onward (the features). The **Status**
column in the index below is the truth about what exists.

`config('ai.prompts.active')` lists only prompts whose **file exists**, and the
suite fails on a pin without a file. So a prompt described here but not pinned
there is a specification, not a working feature.

This is the index of **every** prompt in the product. A prompt that is not listed
here does not exist as far as review is concerned.

---

## How prompts work

* Prompts are **files**, not strings in code:
  `api/resources/prompts/<name>/<version>.md`
* The active version per prompt is **pinned** in `config('ai.prompts.active')`.
  Adding a file does not change behaviour; promoting it is a deliberate config
  edit.
* Every prompt declares a **JSON output schema**, validated before the couple
  sees anything. The schema lives in `app/Modules/Ai/Schemas/TaskSchemas.php`,
  one case per task, and the **same declaration** becomes both the
  `output_config.format` Anthropic receives and the array the reply is checked
  against - so the shape we ask for and the shape we accept cannot drift.
* **Extra fields are rejected, not ignored.** Every schema sets
  `additionalProperties: false`, so a field we did not ask for means this is
  not the reply we asked for.
* A reply of the wrong shape is retried **exactly once**, with the same prompt.
  Then the outcome is `invalid_output`, and both attempts are logged, because
  both were billed. A repair turn would be a better fix and a worse record: the
  prompt that produced the output would no longer be the pinned version.
* Every user-facing generation passes **SafetyGuard** (fail-closed).
* Inputs are built by an **allow-list per task** — never a deny-list.
* Model and effort come from `config('ai.tasks.<task>')`, never a literal.

### Slots

`PromptRegistry::resolve($name, $variables)` fills `{{ slot_name }}`. It is
strict in both directions, and on purpose:

* **A slot with no value throws.** A prompt sent with a hole in it comes back
  with a confident answer to a question nobody asked, and nothing downstream
  can tell.
* **A value with no slot throws too.** A task's allow-list names exactly what
  it may see, so a value the prompt never asks for means the allow-list and the
  prompt have drifted apart — and a reviewer cannot tell which is right.
* **`null` is rejected.** Decide what absence should say and pass that.
* Strings go in as written, numbers as numbers, booleans as `yes`/`no`, arrays
  as pretty JSON. No loops, filters or conditionals: a prompt is a document,
  and logic belongs in the Action that builds the values, where it is tested.
* Values are filled in **one pass**, so `{{ ... }}` typed inside a love note is
  text and cannot reach another variable.

Values are untrusted — they are what two people typed. Nothing escapes them,
because escaping prose for a language model is theatre. The defences are the
declared output schema and SafetyGuard, both after the fact and both tested.

### Changing a prompt

1. Copy the current version to a new one (`v1.md` → `v2.md`). **Never edit a
   released version in place** — `ai_generations` rows reference it, and history
   must stay true.
2. Update the eval battery in `api/tests/Fixtures/Ai/evals/<name>/` if the
   change is meant to alter what the prompt answers. If it is not, leave the
   battery alone: an eval edited to match a new prompt measures nothing.
3. **Measure the current version first**, so there is something to compare
   against:

   ```
   cd api
   php artisan swoon:ai-eval <name> --live
   ```

4. Promote the new version by changing `config('ai.prompts.active')`, measure
   again, and compare the two reports. Worse on the battery means the old one
   stays pinned.
5. **Bump the row in this file** with the date, what changed, and the scores
   either side.
6. Add a `docs/DECISIONS.md` entry if the change alters behaviour the couple
   would notice.

### Running an eval

`php artisan swoon:ai-eval <name>` has two modes, and the difference is the
point.

**Dry, by default.** It loads the battery, checks every case is well formed and
renders each one through the privacy filter and the prompt registry. It calls
nothing and spends nothing. This is what CI runs, and what it catches is the
thing that rots silently: a prompt that gains a slot no case supplies. It does
**not** tell you whether the prompt is any good — nothing free can.

**`--live` calls the real model**, once per case, and prints what it spent.
Four gates stand in front of it, and all four have to be open: the flag, an
interactive confirmation (so `--no-interaction` means no), `AI_DRIVER=anthropic`,
and a real key. A fifth refuses outright if it is running from a test, because
a test that spends money is a failure whatever it was trying to prove.
`--limit=5` takes a cheap sample first.

**Battery format** is in
`api/tests/Fixtures/Ai/evals/safety_screen/README.md`. Each file names an
`expect_field`; each case has an `id`, an `expect`, optionally `accept` for
other answers that are also right, and either `text` or a `variables` map.

---

## Index

| Prompt | Version | Task key | Model key | Effort | Used by | Status |
|---|---|---|---|---|---|---|
| [`date_plan`](#date_plan) | v1 | `date_plan` | strong | high | DatePlanner | planned |
| [`weekly_recap`](#weekly_recap) | v1 | `weekly_recap` | strong | medium | Recap | planned |
| [`insights`](#insights) | v1 | `insights` | strong | medium | Insights | planned |
| [`year_in_moments`](#year_in_moments) | v1 | `year_in_moments` | strong | high | YearInMoments | planned |
| [`safety_screen`](#safety_screen) | v1 | `safety_screen` | light | low | Ai (SafetyGuard) | **live** |
| [`moment_tagging`](#moment_tagging) | v1 | `moment_tagging` | light | low | Moments | planned |
| [`date_pack`](#date_pack) | v1 | `date_pack` | strong | medium | Challenges (authoring) | planned |
| [`spark_question`](#spark_question) | v1 | `spark_question` | light | low | DailySpark (authoring) | planned |

**Deliberately no prompt:** Date Roulette. It is the zero-AI-cost fallback and
must stay instant and free (see `api/app/Modules/DateRoulette/module.md`).

---

## `date_plan`

**v1** · planned · Phase 3 · strong / high · cap 20 per month (1 on Spark)

Produce three date options that fairly compromise between two partners'
independently-given answers.

**Inputs (allow-listed)**

| Field | Source |
|---|---|
| `partners[].first_name` | `users.name` |
| `partners[].answers` | `date_plan_answers` — when, area, **outcode only**, duration, car, budget, vibe, romance 1–5, energy 1–5, notes |
| `partners[].dietary_needs` | `users.dietary_needs` + love map (always included) |
| `partners[].accessibility_needs` | `users.accessibility_needs` (always included) |
| `recent_dates[]` | titles and vibes only, for variety |
| `bucket_list[]` | optional candidate ideas |

**Excluded:** gift vault, check-ins, private moments, private love-map entries,
full postcodes, email addresses, the partner's un-revealed answers at any point
before both have submitted.

**Output schema**

```json
{
  "options": [
    {
      "title": "string",
      "emoji": "string (1 emoji)",
      "summary": "string",
      "timeline": [{ "time": "string", "what": "string" }],
      "estimated_cost_pence": "integer",
      "travel_notes": "string",
      "why_both": "string (one line)"
    }
  ]
}
```

Exactly 3 options. Any missing field is `invalid_output`.

**Requirements the prompt must enforce**

1. **Fair compromise, not an average.** Where the answers conflict, offer one
   option leaning each way and one that genuinely bridges — and say which in
   `why_both`.
2. **Dietary and accessibility needs are hard constraints**, not preferences.
   An option that breaks one is wrong, not merely unhelpful.
3. The **lower** of the two budgets is the ceiling.
4. British English, warm, playful; never cheesy.
5. No option may require sharing location or whereabouts.

**Eval fixtures:** `evals/date_plan/` — aligned answers, mildly conflicting,
**sharply conflicting** (the case that matters), tight budget, no car, dietary
restriction, accessibility need, minimal answers.

---

## `weekly_recap`

**v1** · planned · Phase 7 · strong / medium · cap 5 per month (0 on Spark)

Summarise the couple's week warmly, shortly, and in a way they will actually
read.

**Inputs (allow-listed):** first names; this week's **non-private** moments
(body, mood, tags, date); dates that happened; challenges completed; **revealed**
spark answers; next week's planner events (titles and dates).

**Excluded:** private moments, gift vault, check-in answers, locked love notes,
un-revealed spark answers.

**Output schema**

```json
{
  "headline": "string",
  "paragraphs": ["string"],
  "highlights": [{ "emoji": "string", "text": "string" }],
  "looking_ahead": "string"
}
```

**Requirements**

1. **Short.** Two or three short paragraphs. Length is the main failure mode.
2. **A quiet week still gets a kind recap** — never "you did nothing". Go shorter
   and look forward instead.
3. Never quote a partner's words as if the AI wrote them.
4. Never compare the partners or imply one did more.
5. British English; at most one emoji per sentence.

**Eval fixtures:** `evals/weekly_recap/` — busy week, **empty week**, one-sided
activity, a week containing a bereavement moment (must route to SafetyGuard).

---

## `insights`

**v1** · planned · Phase 7 · strong / medium · cap 10 per month (0 on Spark)

Offer at most one light, actionable nudge — or nothing at all.

**Inputs (allow-listed): aggregates only.** Counts and categories of recent
dates, moment moods and tags over a window, challenge completion rate, upcoming
planner categories, bucket-list items. Deliberately not raw content.

**Excluded — absolutely:** individual check-in answers, private moments, gift
vault, love notes, private love-map entries, debrief free-text.

**Output schema**

```json
{
  "insights": [
    {
      "kind": "string",
      "body": "string",
      "suggested_action": { "label": "string", "deep_link": "string" }
    }
  ]
}
```

At most one. An empty array is a valid and often correct answer.

**Requirements**

1. **Not therapy, not diagnosis.** Observe a pattern, suggest one small thing.
   Never interpret the relationship or assess its health.
2. **Never judgemental, never comparative.** Must never suggest one partner
   contributes less. This is the module's most important rule.
3. **Phrase as a question or invitation** — "fancy an adventure date?" not "you
   should go on an adventure date".
4. Always exactly one concrete action with a deep link.
5. **Silence is the default.** Returning nothing is better than reaching.

**Eval fixtures:** `evals/insights/` — clear pattern, **no pattern (must return
empty)**, lopsided activity (**must not be comparative**), sparse data, a window
containing crisis signals (must suppress entirely).

---

## `year_in_moments`

**v1** · planned · Phase 11 · strong / high · annual allowance

Curate a year of moments into a short narrative with chapters; the basis of the
printed book.

**Inputs (allow-listed):** the year's **non-private** moments (body, date, mood,
tags, place name), dates that happened, milestones reached, challenges completed,
first names.

**Output schema**

```json
{
  "chapters": [{ "heading": "string", "text": "string", "moment_ids": ["integer"] }],
  "dedication": "string"
}
```

**Requirements**

1. **Every `moment_id` must exist in the input.** Validated — the story cannot
   reference something that is not there.
2. **Invent nothing.** Every event named must be grounded in a moment.
3. A sparse year gets something kind and **short**; never padded with filler.
4. SafetyGuard applies: a year containing a bereavement must not be narrated
   cheerfully.

**Eval fixtures:** `evals/year_in_moments/` — rich year, sparse year, a year
containing grief, a year with large gaps.

---

## `safety_screen`

**v1** · **live** · Phase 2 · light / low · internal, uncapped · `resources/prompts/safety_screen/v1.md`

**This is the SafetyGuard prompt and the most important one in the product.**

Classify whether the input or a draft output involves abuse, crisis, self-harm,
or harm to others. Returns a classification only — it never writes a reply.

**Output schema**

```json
{
  "concern": "none | crisis | abuse | self_harm | harm_to_others | unclear",
  "confidence": "low | medium | high"
}
```

**Requirements**

1. **Fail closed.** `unclear` is treated as a concern. If the call itself errors,
   the generation is abandoned — never shipped unchecked.
2. When a concern is found, the calling module **discards the normal output
   entirely** and returns only a short, kind signpost from
   `config('ai.safety.signposts')`. It does not also return a date plan.
3. The screen is a **separate, independently tested step**, never an instruction
   inside another prompt.
4. **Nothing about what triggered it is stored.** `ai_usage` records
   `outcome = safety_tripped` and no content.
5. In a couple context, a concern raised by one partner's input **is never
   surfaced to the other partner.** Doing so could be dangerous.

**Signposts** (UK, from `config/ai.php`): Samaritans 116 123 · National Domestic
Abuse Helpline 0808 2000 247 · Respect Men's Advice Line 0808 8010 327 ·
Galop 0800 999 5428.

**Eval fixtures:** `evals/safety_screen/` — a deliberately broad battery of
crisis and abuse phrasings, including indirect and euphemistic forms, plus a
large set of **benign** inputs to measure false positives (a product that
signposts at every argument is useless).

**Built in P2.T04.** The battery is 32 concerning cases across every category,
including indirect and euphemistic phrasings, and 45 benign ones. It runs two
ways: under the fake driver in CI, where it proves the *handling* is uniform and
that the text reaches the screen; and against the real model via
`artisan swoon:ai-eval safety_screen`, which is the only thing that measures the
prompt itself. See `tests/Fixtures/Ai/evals/safety_screen/README.md`.

What a person sees when it trips, and who sees it, is **ADR-025**: no emoji, no
guess at what is happening, and screened one text at a time so the signpost
reaches the person who needs it and not their partner.

> ⚠️ The current battery is a developer's best effort. **It needs review by
> someone with relevant expertise before launch** — recorded as a gap in the
> `Ai` module doc. The tests passing does not close it.

---

## `moment_tagging`

**v1** · planned · Phase 4 · light / low · generous cap

Suggest tags for a moment. The user accepts or ignores them.

**Inputs:** the moment's body and existing couple tag vocabulary. Private
moments are **excluded**.

**Output schema:** `{ "tags": ["string"] }` — max 5, drawn from or extending the
couple's own vocabulary.

**Requirements:** suggestions only, never applied automatically; **never
rewrites the couple's words**; no tag may characterise the relationship.

---

## `date_pack`

**v1** · planned · Phase 11 · strong / medium · authoring only

Generate a themed bundle of dates/challenges (e.g. "12 Dates of Christmas") for
sale as a premium pack.

**Not served live to a couple.** Output is **reviewed by a human before sale**,
so this runs as an authoring tool. Packs are IAP (digital) via RevenueCat.

**Output schema:** `{ "pack": { "title", "emoji", "items": [{ "title", "body", "effort", "budget_band" }] } }`

---

## `spark_question`

**v1** · planned · Phase 6 · light / low · authoring only

Draft candidate Daily Spark questions for the curated library.

**Not served live to a couple** — the library is curated and reviewed, so the
model never speaks directly to a couple here. Questions must not assume gender,
cohabitation, children, income or life stage.

**Output schema:** `{ "questions": [{ "body", "theme", "is_seasonal" }] }`

---

## Review checklist for a new prompt

```
- [ ] File at resources/prompts/<name>/<version>.md
- [ ] Row added to the index table above, with a section
- [ ] Task entry in config('ai.tasks') — model key + effort + max_tokens
- [ ] Version pinned in config('ai.prompts.active')
- [ ] JSON output schema defined AND validated in code
- [ ] Input allow-list declared (never a deny-list)
- [ ] Verified: no private data can enter the payload (test per private field)
- [ ] SafetyGuard applies (or a documented reason it does not)
- [ ] Cap and cost guard wired
- [ ] Eval fixtures written, including the awkward cases
- [ ] Fake driver fixture added so the suite needs no network
- [ ] docs/DECISIONS.md entry if behaviour changes noticeably
```
