# UX rules — Swoon

**Last updated:** 2026-11-05
**Scope:** every screen, every module, no exceptions without a `docs/DECISIONS.md` entry.

These fifteen rules are the review checklist. A screen is not done until it passes
all of them. If a rule genuinely cannot be met, record why in DECISIONS.md — don't
quietly skip it.

---

## 1. One primary action per screen

Exactly one. It is a **pill** in `primary`, sitting at the **bottom**, inside the
thumb arc, above the safe-area inset.

Everything else on the screen is secondary: a text button, a chip, or a row
affordance. If a screen seems to need two primary actions, it is two screens.

*Check:* can you name the one thing this screen is for? Is that the only pill?

## 2. Three taps to value

From Home, each of these takes **≤3 taps**:

| Action | Path |
|---|---|
| Record a moment | `+` → Moment → save |
| Answer the Daily Spark | Home card → answer → send |
| Start a date plan | `+` → Plan a date → first question |

Measured from a cold Home screen, counting the save/send tap. If a flow drifts over
three, the fix is removing a step, not renaming one.

## 3. Questions one at a time

Multi-step flows — the date questionnaire, onboarding, the check-in — show **one
question per screen** with:

* a progress bar (`secondary`) showing position, not a percentage;
* **big tappable chips** and **sliders**, not text inputs, wherever the answer is
  bounded;
* a back affordance that never loses an answer;
* no "skip all" — but every non-essential question has "no preference".

Never a long scrolling form. Never two questions on one screen because they "go
together".

## 4. Always show "us"

Shared content shows **both partners' avatars**. Waiting states are friendly and
name the person:

> Waiting for Sam to answer 👀

Every waiting state offers a gentle **nudge** — an email via Resend,
**rate-limited to once per 24 hours per couple per item**. The nudge copy is warm
and never implies fault. After a nudge is sent, the button becomes "Nudged ✅" and
stays disabled until the window resets.

## 5. Reveal mechanics

Anything both partners answer stays **hidden until both have answered**:

* Daily Spark answers
* Date questionnaire answers
* Check-in responses (combined trend only — see rule 9)
* Date Debrief ratings

Before the reveal, the other partner's answer is not fetched by the client at all —
hiding it in the UI is not sufficient. The API must not return it. After both have
answered, it reveals with a short celebratory animation (`highlight` + confetti).

Partial progress is shown as "1 of 2 answered", never as a preview.

## 6. AI is always labelled

Every piece of AI-generated content wears the **Lavender ✨ `AITag`**. It can be:

* **regenerated** — an explicit affordance, with the cost counted against the cap;
* **rated** — thumbs up / down, feeding `ai_feedback`.

AI output is **never** presented as the partner's words and never styled like a
partner's message. If a card mixes AI text with partner text, the two are visually
separated and separately attributed.

## 7. Warm empty states

Every empty state has exactly three parts:

1. an emoji,
2. one friendly sentence,
3. one button that fixes it.

> 📸
> No moments yet. Go on, bottle one.
> **[ Add a moment ]**

No illustrations-as-filler, no "No data", no empty table.

## 8. No guilt

* Streaks **pause**, they never break. Copy: "Your streak's having a lie-in 😴"
* **No red badges** for missed things. Counts are `secondary`, never `danger`.
* No "you haven't…", no "don't forget", no day-counters of inactivity.
* Challenges that go uncompleted simply roll away. They are never listed as failed.
* If one partner is more active, the app never says so.

`danger` is reserved for destructive actions and genuine errors. Inactivity is
neither.

## 9. Privacy is visible

Private items show a **🔒** and the line **"Only you can see this"** — in
`caption`, next to the lock, on the item itself, not buried in settings.

**Default to private** for anything ambiguous. Specifically always private:

* Gift Vault entries
* Individual check-in answers (only a combined trend is shared, and only if **both**
  partners opt in)
* Unsent Love Notes
* Date questionnaire answers, until both have answered
* Private moment notes

Private data is excluded from AI prompts that produce shared output. See
`docs/ARCHITECTURE.md` § AI pipeline.

## 10. Forgiving actions

* Destructive actions confirm **once**, in plain words. The confirm sheet says what
  will happen, not "are you sure".
  > This deletes the moment for both of you. **[ Delete ]** **[ Keep it ]**
* **Deleting a moment is undoable for 10 seconds** via a snackbar. The row
  disappears immediately; the delete commits after the window.
* Unlink and account deletion confirm once and then happen **immediately** — no
  cooling-off period, no "contact support". (UK GDPR; see rule in `CLAUDE.md`.)

## 11. Offline-tolerant

Moments and planner items can be created **offline** and sync later.

* Optimistic insert into the local cache, with a subtle **"Saving…" → "Saved"**
  state on the item.
* A failed sync shows a quiet retry affordance on the item, never a modal.
* Photos and voice notes queue their upload separately from the record, so the text
  lands even if the media is still uploading.
* Nothing silently disappears. If a sync fails permanently, the item stays local
  and says so.

## 12. Accessible by default

* **44 pt minimum** touch targets — including icon buttons and chips.
* **A label on every icon button.** No icon-only control without one.
* **Dynamic type** respected everywhere; layouts survive the largest OS size.
* **Screen-reader order** checked per screen; reveal animations announce their
  result.
* **AA contrast** — guaranteed by the theme tokens and the contrast test
  (`docs/BRAND.md` § 3.4). Never hand-pick a colour to "look better".
* **Reduce motion** respected: animations become ≤100 ms cross-fades.
* Colour never carries meaning alone (partner identity always pairs with an avatar).

## 13. Copy rules

* **Sentence case.** Buttons, headings, everything.
* **British spelling.**
* **Max ~12 words per line** of UI copy.
* **Emoji at the end** of a sentence, **one per sentence** maximum.
* Second person, "you two" for shared things.
* Numbers under ten are words in prose ("three ideas"), digits in data ("3 dates").

## 14. Navigation

**Bottom tabs** (five, fixed):

| Tab | Contents |
|---|---|
| **Home** | On This Day, Daily Spark, next up, this week's challenge |
| **Dates** | Planner questionnaire, plan options, scheduled and past dates |
| **Moments** | The feed, search, add |
| **Planner** | Upcoming shared events, countdowns, milestones |
| **Us** | Profiles, Love Map, couple settings, subscription, privacy |

A centred **"+"** opens a **quick-add sheet**: Moment · Plan · Note · Date idea.
The sheet is the only place a create action is duplicated; it does not grow past
four options.

No hamburger menu. No nested tab bars. Deep links resolve to a tab, never to a
modal with no way back.

## 15. Consistency over novelty

Reuse the shared component library in `mobile/src/components/`. Build a new
component **only** when no existing one fits — and when you do:

1. put it in `mobile/src/components/`,
2. give it the same prop conventions as its neighbours,
3. document it in the component library README,
4. cover it with a test.

A one-off styled `View` inside a feature folder is a bug, not a shortcut.

---

## Review checklist

Copy this into the PR / task notes for any screen:

```
- [ ]  1. One primary action, pill, bottom, thumb-reachable
- [ ]  2. ≤3 taps from Home (if a core action)
- [ ]  3. One question per screen (if multi-step)
- [ ]  4. Both avatars shown; waiting state names the partner; nudge rate-limited
- [ ]  5. Reveal: API withholds the other answer until both have answered
- [ ]  6. AI content tagged, regenerable, ratable
- [ ]  7. Empty state: emoji + one sentence + one button
- [ ]  8. No guilt copy, no red counts, streaks pause
- [ ]  9. Private items show 🔒 + "Only you can see this"
- [ ] 10. Destructive = one plain confirm; moment delete undoable 10 s
- [ ] 11. Works offline; Saving…/Saved; media queued separately
- [ ] 12. 44 pt targets, icon labels, dynamic type, SR order, reduce motion
- [ ] 13. Sentence case, British spelling, ≤12 words/line, ≤1 emoji/sentence
- [ ] 14. Reachable from the right tab; deep link has a back path
- [ ] 15. Reused existing components, or documented + tested the new one
```
