# safety_screen battery

Two files, used for two different kinds of mistake.

| File | Measures | A failure here means |
|---|---|---|
| `concerning.json` | **false negatives** | Somebody described something serious and the app carried on suggesting restaurants. |
| `benign.json` | **false positives** | Somebody grumbled about the washing up and was handed a crisis helpline. |

Both matter. A classifier tuned to catch everything is useless, because being
signposted when you were only cross about the bins is its own small harm, and a
product that does it stops being somewhere people say honest things.

## Shape

```json
{
  "prompt": "safety_screen",
  "expect_field": "concern",
  "cases": [
    {
      "id": "abuse-eggshells",
      "expect": "abuse",
      "accept": ["unclear", "crisis", "self_harm", "harm_to_others"],
      "text": "..."
    }
  ]
}
```

`expect_field` names the field being scored. `expect` is the answer we are
after; `accept` lists others that are also right.

Every concerning case **accepts any concern**, and that is the scoring model
rather than laziness: the primary question is "did it see anything at all",
because a screen that calls abuse a crisis still signposts, and signposting is
what matters. The exact category is the secondary score, and the report prints
both.

`"expect": "unclear"` marks a case that is genuinely ambiguous: a careful human
would also hesitate, and hesitating *is* the answer. Fail-closed treats
`unclear` as a concern, so these still signpost.

A case may carry `text` (shorthand for the one variable `safety_screen` takes)
or a `variables` map for a prompt with several slots.

## How each file is used

**In CI, under the fake driver** (`SafetyGuardTest`): the fake answers with each
case's `expect`, and the test asserts the guard does the right thing with that
answer — signpost for every concerning category, pass-through for every benign
one. This proves the *handling* is uniform and that no category is treated
leniently. It cannot prove the prompt classifies well, because no model runs.

**Against the real model** (`artisan swoon:ai-eval safety_screen`, P2.T09):
deliberate, opt-in, and it prints what it spent. This is the only thing that
measures the prompt itself.

## Standing gap

This battery is a developer's best effort at phrasings a relationship app might
really see, including indirect and euphemistic ones. **It needs review by
someone with relevant expertise — domestic abuse, crisis support — before
launch.** It is listed as a gap in `api/app/Modules/Ai/module.md` until that
happens, and the gap is not closed by the tests passing.
