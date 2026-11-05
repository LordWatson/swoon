# AI fixtures

One file per task in `config('ai.tasks')`, named `<task>.json`. `FakeDriver`
answers from the matching file whenever a test has not queued something else,
so a test about the chain gets a realistic response without caring about
wording.

```json
{
    "structured": { "...": "the model's structured output" },
    "text": "any free text alongside it, usually empty",
    "usage": { "input_tokens": 420, "output_tokens": 18 }
}
```

* `structured` is **required**, even if empty. A fixture missing the key is an
  error rather than an empty response, so a typo cannot quietly turn a test
  into one that asserts nothing. Use `"structured": {}` deliberately when you
  want output that fails validation.
* `usage` drives the cost arithmetic in `ai_usage`, so keep the numbers
  plausible for the task — a cap test that uses a hundred tokens for a date
  plan proves nothing.
* The model id is **not** in the fixture. `FakeDriver` fills it from the task's
  entry in `config/ai.php`, which is the only place a model id is allowed to
  appear.

A fixture is a believable response, not a *correct* one. Fixtures do not tell
you whether a prompt works — `tests/Fixtures/Ai/evals/` and
`artisan swoon:ai-eval` do that.
