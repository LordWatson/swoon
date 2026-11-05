# AI prompts — Swoon

**Last updated:** 2026-11-05
**Status:** skeleton. No prompt files exist yet — they are written in Phase 2
(the platform) and Phase 3 onward (the features).

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
  sees anything.
* Every user-facing generation passes **SafetyGuard** (fail-closed).
* Inputs are built by an **allow-list per task** — never a deny-list.
* Model and effort come from `config('ai.tasks.<task>')`, never a literal.

### Changing a prompt

1. Copy the current version to a new one (`v1.md` → `v2.md`). **Never edit a
   released version in place** — `ai_generations` rows reference it, and history
   must stay true.
2. Update the eval fixtures in `api/tests/Fixtures/Ai/evals/<name>/`.
3. Run the evals. Compare against the previous version before promoting.
4. Promote by changing `config('ai.prompts.active')`.
5. **Bump the row in this file** with the date and what changed.
6. Add a `docs/DECISIONS.md` entry if the change alters behaviour the couple
   would notice.

---

## Index

| Prompt | Version | Task key | Model key | Effort | Used by | Status |
|---|---|---|---|---|---|---|
| [`date_plan`](#date_plan) | v1 | `date_plan` | strong | high | DatePlanner | planned |
| [`weekly_recap`](#weekly_recap) | v1 | `weekly_recap` | strong | medium | Recap | planned |
| [`insights`](#insights) | v1 | `insights` | strong | medium | Insights | planned |
| [`year_in_moments`](#year_in_moments) | v1 | `year_in_moments` | strong | high | YearInMoments | planned |
| [`safety_screen`](#safety_screen) | v1 | `safety_screen` | light | low | Ai (SafetyGuard) | planned |
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

**v1** · planned · Phase 2 · light / low · internal, uncapped

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

> ⚠️ The current battery is a developer's best effort. **It needs review by
> someone with relevant expertise before launch** — recorded as a gap in the
> `Ai` module doc.

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
