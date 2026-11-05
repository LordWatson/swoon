# Love Map / Profiles 🗺️

**Status:** planned
**Last updated:** 2026-11-05 - created during project setup; scaffolding only, no implementation yet.
**Owner phase:** Phase 9 (see docs/PLAN.md)

> This is the **canonical** doc for this module and covers the full stack.
> Mobile-only detail lives in `mobile/src/features/love-map/module.md`.
> Signature: 🗺️ Apricot

## Purpose
What each partner actually likes. Favourites, love languages, dislikes, sizes, dietary needs, dream trips. Feeds the date planner and gift ideas, and is the quiet reason the AI suggestions get good. A partner can view it; only its owner can edit it.

## User stories
- As a partner, I can fill in what I like and need.
- As a partner, I can see my partner's love map and stop guessing.
- As a partner, I cannot edit my partner's answers.
- As a couple, the date planner respects our dietary needs automatically.

## Data model
**love_map_fields** - id, key, label, group (`favourites`|`love_languages`|`dislikes`|`sizes`|`dietary`|`dreams`), input_type, options (json, nullable), is_sensitive (bool), position. A definition table, seeded.

**love_map_entries** - id, user_id, love_map_field_id, value (json), visibility (`partner`|`private`), updated_at. Unique on (user, field).

**Private to one partner:** any entry with `visibility = private`. Dietary and accessibility entries are forced to `partner` visibility because the planner must respect them - and the user is told that clearly before they fill them in.

## API endpoints
| Method | Path | Auth | Purpose | Request | Response resource |
|---|---|---|---|---|---|
| GET | /api/love-map/fields | sanctum | Field definitions | - | LoveMapFieldResource collection |
| GET | /api/love-map/me | sanctum | Own entries | - | LoveMapEntryResource collection |
| PUT | /api/love-map/me | sanctum | Update own entries | UpdateLoveMapRequest | LoveMapEntryResource collection |
| GET | /api/love-map/partner | sanctum | Partner's partner-visible entries | - | LoveMapEntryResource collection |

## Business rules
1. **Only the owner may edit their own love map.** A partner has read access and nothing more - and the UI does not show an edit affordance on the partner's map at all.
2. **Per-entry visibility.** An entry is either partner-visible or private. Ambiguous entries default to **private** (UX rule 9).
3. **Dietary and accessibility needs are always partner-visible** and always fed to the planner, because a date that ignores an allergy is worse than a date that reveals a preference. The user is told this before answering.
4. The love map is **never mandatory**. It fills in over time, and an empty map must not degrade the app - the planner simply has less to work with.
5. Filled in one question per screen (UX rule 3), and progress is celebrated, never demanded.
6. **No field may ask for anything that enables control** - no location, no whereabouts, no passwords, no financial access.
7. Partner-visible entries feed the date planner and gift ideas; **private entries feed neither**.
8. A field marked `is_sensitive` (e.g. sizes) shows a clear note about who can see it before it is filled in.

## AI usage (if any)
No prompt of its own. Partner-visible entries are an **input** to `date_plan`, `insights` and gift suggestions, via the AI module's allow-list. Private entries are excluded there, and the exclusion is tested field by field.

## Jobs, events & schedules
**Jobs:** none. **Events:** `LoveMapUpdated` (lets the planner know it has better data). **Scheduled:** none.

## Mobile
**Screens:** Love Map (own, editable, grouped), Partner's Love Map (read-only), Edit field sheet, a gentle completeness prompt on the Us tab.

**Components:** `Card`, `Chip`, `Sheet`, `Button`, `ProgressBar`, lock badge, `EmptyState`.

**Navigation:** Us tab -> Love Map / Partner's Love Map.

**Offline:** fully readable offline; edits queue.

**Local notifications:** none.

## Permissions & privacy
`LoveMapEntryPolicy` - read: owner always; partner only when `visibility = partner`. write: **owner only**, enforced server-side.

Private entries never reach the partner and never reach a prompt. The dietary/accessibility exception is deliberate, documented, and surfaced to the user at the point of entry.

## Tests
`api/tests/Feature/LoveMap/` - **partner cannot write** any entry (every field); **private entries absent from the partner endpoint**; ambiguous/unset visibility defaults to private; dietary entries are forced partner-visible and reach the planner payload; **private entries absent from the AI payload**; an empty map does not break the planner.

Run: `composer test -- --filter=LoveMap`

## Known gaps / TODO
- The field library needs writing, with care: it must not assume gender, body type, income or living arrangement.
- Sizes are sensitive and may deserve stronger treatment than a visibility flag.

## Changelog
- 2026-11-05 - Module documented and scaffolded (folders + routes.php). No code yet.
