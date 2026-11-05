# Pricing — Swoon

**Last updated:** 2026-11-05
**Status:** starting hypotheses, not validated. Every number here is a bet to test.
**Implementation:** prices live in `api/config/pricing.php`, entitlements in the
`couple_entitlements` table, caps in `api/config/ai.php`. Nothing is hard-coded.

---

## 1. The model

**One subscription per couple.** Either partner pays; both get access. This is the
single most important pricing decision in the product:

* It halves the perceived price versus per-seat ("£9.99 for us" not "£19.98 for us").
* It removes the awkward conversation about who pays.
* It makes the couple — not the user — the billing entity, which matches how every
  other part of the data model works.

The cost is that we cannot upsell the second partner, and that unlinking has to
handle an in-flight subscription (see § 6).

## 2. Tiers

| Tier | Price (GBP) | What's included |
|---|---|---|
| **Spark** (free) | £0 | Couple linking · Daily Spark · Planner · up to **30 Moments** · On This Day · **1 AI date plan / month** · Date Roulette |
| **Together** (core) | **£9.99 / month** or **£79.99 / year** (~33% off) per couple | Unlimited Moments + photos/voice · AI Date Planner (fair-use **20 plans/month**) · Weekly Recap · Insights · Challenges · Love Notes · Gift Vault · Bucket List · Date Debrief · Year in Moments (in-app) |
| **Forever** (lifetime) | **£199 one-off** | Everything in Together, for life. Fair-use AI caps still apply. |

**Trial:** 7 days of Together, **started when the second partner joins** — not at
first sign-up. Both partners experience the paid product together, which is the
only way the core value (reveals, shared planning) is visible at all. A solo user
cannot burn the trial waiting for their partner.

**Annual framing:** £79.99/year is £6.67/month — presented as "save £40" rather
than "33% off", because the absolute number is more persuasive at this price point.

**Forever at £199** is twenty months of monthly. It exists mainly as a *gift* —
anniversary and wedding purchases — and as a hedge for users who distrust
subscriptions. It is a real liability: a Forever couple generating AI content for a
decade can exceed £199 in inference cost, which is why the fair-use caps apply to
it too. Watch the cohort.

## 3. Why these numbers

| Decision | Reasoning |
|---|---|
| £9.99/month | Mid-ticket: above the £2.99–4.99 impulse tier (which cannot fund per-couple AI inference), below the £14.99+ therapy/coaching tier (where the expectation becomes clinical, which we explicitly are not). |
| Per couple, not per person | See § 1. |
| Free tier includes Daily Spark | Daily Spark is the retention driver. Gating it would kill the habit before the paywall ever gets a chance. |
| Free tier caps Moments at 30 | Moments accrue emotional switching cost. 30 is enough to feel the value and start caring about the archive; it is also the point where losing it would hurt. |
| 1 free AI plan / month | One plan demonstrates the headline feature completely. A second would remove the reason to pay. |
| Date Roulette is free | It is the zero-AI-cost fallback. Making it free keeps "we can't decide" users engaged without inference cost. |

## 4. High-ticket add-ons

| Add-on | Price | Rail | Notes |
|---|---|---|---|
| **Year in Moments — printed hardback** | £59 standard / £89 premium (lay-flat) | **Stripe** (physical good) | AI-curated layout from the year's moments; a print partner fulfils. |
| **Concierge Occasion** — fully planned & booked anniversary, birthday, proposal or weekend away | £149 / £299 / £499 by complexity | **Stripe** (real-world service) | AI drafts the plan, a **human concierge** confirms bookings. Launch as a **small waitlist**, not at MVP. |
| **Gift Concierge** — curated, delivered gift around a key date | £49 service fee + gift cost | **Stripe** (physical good) | Uses Gift Vault + Love Map, **with explicit consent**. |
| **Premium date packs** — themed AI-generated challenge/date bundles (e.g. "12 Dates of Christmas") | £4.99–£9.99 | **IAP via RevenueCat** (digital) | Seasonal upsell. |

The add-ons are where the real revenue per couple lives. A £59 book from 5% of an
annual cohort is worth more than a month of subscription from all of them. They are
also the only part of the business with a physical cost of goods — margins must be
modelled per add-on, not assumed.

## 5. Payment rails — the rule that cannot be broken

| What | Rail | Why |
|---|---|---|
| Subscriptions (Together, Forever) | **Apple / Google IAP via RevenueCat** | Mandatory. Digital content consumed in-app. |
| Premium date packs | **IAP via RevenueCat** | Digital content. |
| Printed book | **Stripe** | Physical good — store rules permit, and IAP would take 15–30% of a low-margin physical item. |
| Concierge Occasion | **Stripe** | Real-world service delivered by a human. |
| Gift Concierge | **Stripe** | Physical good + human service. |

**Hard rules for implementation:**

1. **Never** offer a digital feature or subscription through Stripe in the app.
2. **Never** link out to a web checkout for a digital purchase from inside the app
   (this is what gets apps rejected, and the rules here have shifted repeatedly).
3. Stripe flows for physical goods are permitted, but keep the purchase surface
   clearly about a physical/real-world deliverable.
4. **Re-check Apple and Google store rules before launch and before each add-on
   ships.** This area changes often. Flagged in `docs/DECISIONS.md` (ADR-011).

## 6. Entitlements — server-side, always

```
RevenueCat webhook → api/app/Modules/Billing → couple_entitlements
```

* **The app never decides access.** It renders what the API says the couple is
  entitled to. A jailbroken client gets nothing extra.
* `couple_entitlements` is keyed by `couple_id` and holds: tier, source
  (`revenuecat` | `manual` | `trial`), `expires_at`, and the raw entitlement
  identifiers.
* Every gated endpoint checks entitlement in a **policy or middleware**, not in a
  controller body.
* **Unlinking with an active subscription:** the entitlement follows the **payer**.
  The payer keeps the tier and carries it into any future couple; the other partner
  drops to Spark at the end of the current period, and is told so plainly before
  confirming the unlink. Their data is never deleted for billing reasons.
* **Trial** is granted server-side when the second partner accepts, recorded with
  `source: trial`, and is one per couple **and** one per user — a user cannot farm
  trials by re-linking.

## 7. AI cost control

Every AI feature has a **per-couple monthly cost budget** and a **fair-use cap**,
both enforced server-side before the job is dispatched.

* `ai_usage` logs **every** call: couple, module, prompt name + version, model,
  input tokens, output tokens, computed cost, outcome — failures included,
  because a reply in the wrong shape was billed exactly like a good one.
* The cost column is **`cost_millipence`** (ADR-026). A safety screen costs
  about 0.0055p and SafetyGuard runs twice on every generation, so in whole
  pence the most numerous calls the product makes would every one log zero.
  `Support\Cost` is the only thing that computes it, from
  `config('ai.pricing_usd_cents_per_mtok')` and `config('ai.usd_to_pence')`;
  `Cost::toPence()` is the only thing that converts, and it rounds **up**,
  because a guard that rounds a spend down lets the last call through free.
* Prices live in config rather than being fetched, so a row written today stays
  comparable with one from last year after the provider changes its pricing.
  A test asserts every model the app can choose has a price.
* A **safety trip is logged with no couple and no user** (ADR-027), so a
  tripped generation costs the business its pennies and costs the couple
  nothing — neither allowance nor a record.
* Caps are **per tier**, from `config/ai.php` — never a literal in a job.
* When a cap is hit, the response is kind and specific (UX rule 8 — no guilt):
  > That's your date plans for this month 💝 They reset on 1 December.
* A **hard cost ceiling per couple per month** exists above the feature caps, as a
  runaway guard. Hitting it alerts us; it should never be reached in normal use.
* The strong model is used for date planning and recaps; the cheap model for light
  classification and safety pre-checks. Model choice per task is in `config/ai.php`.

**The number that matters:** AI cost per couple per month must stay well under
Together's net revenue (~£7 after store fees). Model this before launch and watch
it per cohort, because a power couple on Forever is the worst case.

## 8. Metrics to watch

These decide whether the pricing above survives contact with reality.

| Metric | Why it matters | Rough target |
|---|---|---|
| **Trial → paid conversion** | The headline number. Trial starts on partner join, so this also measures whether the reveal mechanic lands. | >25% |
| **Second-partner join rate** | If invitees don't accept, nothing else matters — no couple, no trial, no revenue. | >60% of invites |
| **AI cost per couple / month** | The margin killer. Track p50 **and** p95 — the tail is what hurts. | p95 < £2.00 |
| **ARPU vs AI cost** | Net revenue per couple minus inference. | >3× |
| **Monthly → annual mix** | Annual improves cash and retention materially at this price. | >35% annual |
| **Add-on take-up (book)** | The real revenue lever. Measured per annual cohort, since it needs a year of moments. | >5% |
| **Free → Spark cap friction** | Which cap actually triggers upgrades: Moments (30) or AI plans (1)? Instrument both. | — |
| **Forever cohort AI cost over time** | The lifetime liability. | cumulative < £199 |
| **Churn by tier, month 2 and 6** | Month 2 tests novelty; month 6 tests habit. | <8% / <4% monthly |
| **Refund / chargeback rate on add-ons** | Physical fulfilment risk. | <2% |

## 9. Known open questions

1. **Is £9.99 right for a couples app in the UK?** Untested. The nearest comparables
   sit at £4.99 (too low to fund AI) and £14.99+ (positioned as therapy). We may
   find £7.99 converts materially better.
2. **Does Forever cannibalise annual?** If a large share of annual buyers take
   Forever instead, lifetime revenue drops. Consider making Forever gift-only.
3. **Is the 30-Moment cap the right lever?** It may be too generous to ever bite, or
   so tight it reads as mean. Instrument before tuning.
4. **Concierge margin is unmodelled.** £149 may not cover a human's time at the
   complexity users expect. The waitlist exists to find out before we promise it.
5. **Do couples share a payment method?** Affects whether "either partner pays" is
   actually frictionless or quietly awkward.
