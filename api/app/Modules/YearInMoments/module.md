# Year in Moments 📖

**Status:** planned
**Last updated:** 2026-11-05 - created during project setup; scaffolding only, no implementation yet.
**Owner phase:** Phase 11 (see docs/PLAN.md)

> This is the **canonical** doc for this module and covers the full stack.
> Mobile-only detail lives in `mobile/src/features/year-in-moments/module.md`.
> Signature: 📖 Lavender

## Purpose
An annual recap story in the app, and the basis of the printed hardback add-on. Claude curates the year's moments into a narrative; the book is then a physical object the couple keeps.

## User stories
- As a couple, we get a lovely story of our year in the app.
- As a couple, we can order it as a printed hardback.
- As a couple, we can choose what goes in and what stays out.
- As a couple, the in-app story is free with Together.

## Data model
**year_recaps** - id, couple_id, year, ai_generation_id, body (json: chapters, each with a heading, text and moment ids), curated_moment_ids (json), published_at, read_at.

**book_orders** - see `orders` in the Billing module; this module adds book_layout (json), proof_url, print_partner_ref, status.

Private moments are excluded from curation. Shipping details live in Billing, encrypted.

## API endpoints
| Method | Path | Auth | Purpose | Request | Response resource |
|---|---|---|---|---|---|
| GET | /api/year-in-moments/{year} | sanctum | The year's story | - | YearRecapResource |
| POST | /api/year-in-moments/{year}/generate | sanctum | Generate or regenerate | - | 202 |
| PATCH | /api/year-in-moments/{year}/curation | sanctum | Include/exclude moments | UpdateCurationRequest | YearRecapResource |
| POST | /api/year-in-moments/{year}/book | sanctum | Order the printed book (Stripe) | OrderBookRequest | OrderResource |
| GET | /api/year-in-moments/{year}/book/proof | sanctum | Preview the proof | - | BookProofResource |

## Business rules
1. **The couple controls the curation.** The AI proposes a selection and a narrative; either partner may include or exclude any moment before anything is printed. An uneditable auto-generated book would be a disaster.
2. **Private moments are never included**, and are not offered for inclusion.
3. The in-app story is included with Together; the **printed book is a Stripe purchase** (physical good - never IAP).
4. A **proof must be previewed and explicitly approved** before the order goes to the print partner. Printing is irreversible.
5. Original-resolution photos are used for print; display sizes for the app.
6. Generation is queued, AI-labelled, regenerable, and keeps superseded versions.
7. **SafetyGuard runs on the generation.** A year containing a bereavement or a crisis must not be narrated cheerfully.
8. A year with few moments still produces something kind, and says plainly that it is short - it never pads with filler.
9. A chapter must not invent events. The narrative is drawn from the moments, and anything not grounded in a moment is invalid output.
10. Orders, shipping and refunds are Billing's concern; this module owns the content and the layout.

## AI usage (if any)
Prompt **`year_in_moments` v1**. Model key `config('ai.tasks.year_in_moments')` -> `strong`, effort `high`.

**Input (allow-listed):** the year's non-private moments (body, date, mood, tags, place name), dates that happened, milestones reached, challenges completed, first names. No private content, no vault, no check-ins.

**Output schema:** `{ chapters: [{ heading, text, moment_ids: [id] }], dedication }` - every `moment_id` must exist in the input, which is validated, so the story cannot reference something that is not there.

**Cost guard:** its own annual allowance. **Safety:** SafetyGuard, fail-closed.

## Jobs, events & schedules
**Jobs:** `GenerateYearRecap`, `BuildBookLayout`, `RenderBookProof`, `SubmitBookToPrintPartner`.

**Events:** `YearRecapGenerated`, `BookProofApproved`, `BookOrdered`.

**Scheduled:** `year-recaps:generate` each December for eligible couples.

**Emails (Resend):** 'your year is ready', proof ready, order confirmation, shipped.

## Mobile
**Screens:** Year in Moments story (a scrollable chapter view), Curation (include/exclude), Book options, Proof preview, Order, Order status.

**Components:** `Card`, `AITag`, `Button`, `Sheet`, `EmptyState`, image gallery.

**Navigation:** Us tab -> Year in Moments, promoted on Home in December.

**Offline:** the story is cached and readable offline; ordering needs the network.

**Local notifications:** one 'your year is ready 📖'.

## Permissions & privacy
`YearRecapPolicy` - both partners may view and curate. Private moments are excluded from the input, the curation list and the book.

The **print partner receives only what the couple approved** - the approved layout and its images, no account data beyond the shipping details needed to deliver, which come from Billing encrypted at rest.

## Tests
`api/tests/Feature/YearInMoments/` - **private moments absent from the input, the curation list and the layout**; curation changes are respected in the output; **every `moment_id` in the output exists in the input** (no invented events); proof approval is required before submission to the print partner; a sparse year produces a kind short story; SafetyGuard path; the book cannot be bought through the IAP rail.

Run: `composer test -- --filter=YearInMoments`

## Known gaps / TODO
- No print partner chosen; their API, trim sizes, colour profile and lead times will shape the layout code.
- Book layout engine is undesigned - likely server-side PDF generation.
- Pricing assumes a print cost that is not yet quoted.

## Changelog
- 2026-11-05 - Module documented and scaffolded (folders + routes.php). No code yet.
