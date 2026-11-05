# Prompts

One directory per prompt, one file per version:

```
resources/prompts/<name>/<version>.md
```

The version in use is **pinned** in `config('ai.prompts.active')`. Dropping a
`v2.md` next to `v1.md` changes nothing — promoting it is a deliberate config
edit, and `PromptRegistry` refuses a prompt that is not pinned at all.

**Never edit a released version in place.** `ai_generations` rows point at the
version that produced them, so editing `v1.md` rewrites history. Copy it to
`v2.md`, change that, run the evals, then promote.

## Slots

`{{ slot_name }}`, filled by `PromptRegistry::resolve($name, $variables)`.

* A slot with no value **throws**, and so does a value with no slot. There is
  no such thing as a quietly empty slot.
* Strings go in as written; numbers as numbers; booleans as `yes`/`no`;
  arrays as pretty JSON. `null` is rejected — decide what absence should say
  and pass that.
* Values are filled in one pass, so `{{ ... }}` inside a value is text, not
  another slot.
* There are no loops, filters or conditionals. A prompt is a document. If a
  prompt needs logic, the logic belongs in the Action that builds the values,
  where it can be tested.

## What goes in a prompt file

Everything the model is told, so one file is one reviewable unit and an eval
can replay it whole. The output schema is **not** here — it is declared in
code next to the task, because `SchemaValidator` has to enforce it.

Each prompt's inputs, output schema and the rules it must enforce are written
up in `docs/AI_PROMPTS.md`, which is the index for review.
