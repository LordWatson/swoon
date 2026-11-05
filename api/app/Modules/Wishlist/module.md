# Wishlist & Gift Vault 🎁

**Status:** planned
**Last updated:** 2026-11-05 - created during project setup; scaffolding only, no implementation yet.
**Owner phase:** Phase 9 (see docs/PLAN.md)

> This is the **canonical** doc for this module and covers the full stack.
> Mobile-only detail lives in `mobile/src/features/wishlist/module.md`.
> Signature: 🎁 Apricot

## Purpose
Two deliberately different things in one module: a **shared wishlist** both partners can see, and a **private gift vault** - 'she mentioned this candle' - that the partner must never see. Plus reminders before birthdays and anniversaries, so the vault is actually used.

## User stories
- As a partner, I can add something I would love to the shared wishlist.
- As a partner, I can jot a gift idea that my partner can never see.
- As a partner, I get a reminder before a key date, with my vault to hand.
- As a partner, I can mark a gift bought without my partner knowing.

## Data model
**wishlist_items** - id, couple_id, owner_user_id (whose wish), title, notes, url (nullable), price_pence (nullable), priority, claimed_by_user_id (nullable), claimed_at, fulfilled_at, deleted_at.

**gift_vault_items** - id, couple_id, author_user_id, for_user_id, title, notes, url, price_pence, occasion, target_date (nullable), purchased_at (nullable), deleted_at.

**`gift_vault_items` is private to `author_user_id`. Always. There is no sharing flag, because a shared gift vault is not a gift vault.** A `claimed_by_user_id` on a wishlist item is also hidden from the item's owner, so a surprise stays a surprise.

## API endpoints
| Method | Path | Auth | Purpose | Request | Response resource |
|---|---|---|---|---|---|
| GET | /api/wishlist | sanctum | Shared wishlist (claims hidden from owner) | - | WishlistItemResource collection |
| POST | /api/wishlist | sanctum | Add a wish | StoreWishlistItemRequest | WishlistItemResource |
| PATCH | /api/wishlist/{id} | sanctum | Update own wish | UpdateWishlistItemRequest | WishlistItemResource |
| DELETE | /api/wishlist/{id} | sanctum | Remove own wish | - | 204 |
| POST | /api/wishlist/{id}/claim | sanctum | Claim a wish (hidden from owner) | - | WishlistItemResource |
| GET | /api/gift-vault | sanctum | Own vault only | - | GiftVaultItemResource collection |
| POST | /api/gift-vault | sanctum | Add a private gift idea | StoreGiftVaultItemRequest | GiftVaultItemResource |
| PATCH | /api/gift-vault/{id} | sanctum | Update own vault item | UpdateGiftVaultItemRequest | GiftVaultItemResource |
| DELETE | /api/gift-vault/{id} | sanctum | Remove own vault item | - | 204 |

## Business rules
1. **The gift vault is private to its author, unconditionally.** No sharing option, no 'reveal' flow, no admin view. It is the clearest privacy promise in the app and must be the hardest to break.
2. **A wishlist claim is hidden from the item's owner.** The owner sees their wish; they do not see that their partner has claimed it. The partner sees the claim.
3. A wishlist item is owned by the partner who wishes for it; only they may edit or delete it.
4. Either partner may **claim** the other's wish. Claiming is reversible.
5. **Vault reminders fire only to the author**, ahead of `target_date` or the relevant milestone, with a lead time long enough to actually buy something (default 14 days).
6. Marking a gift purchased is private to the author.
7. **Vault contents are never sent to any AI prompt that produces shared output.** They may feed the author's own private gift suggestions only, and that is a separate, clearly-labelled path.
8. A URL in either list is stored as text and is never fetched server-side (no SSRF, no link preview leaking what someone is buying).
9. Deleting a vault item is immediate and complete - there is no shared trash.

## AI usage (if any)
Optional, author-private gift suggestions only, drawing on the **partner-visible** love map and the author's own vault. Output goes to the author alone.

**Vault contents are excluded from `date_plan`, `weekly_recap`, `insights` and `year_in_moments` absolutely**, by the AI module's allow-list.

## Jobs, events & schedules
**Jobs:** `SendGiftVaultReminderEmail` (to the author only).

**Events:** `WishlistItemClaimed`, `GiftVaultItemAdded`; listens for `MilestoneCreated` to schedule vault reminders.

**Scheduled:** `gift-vault:remind` daily.

**Emails (Resend):** gift reminder, to the author only, and never mentioning the gift in a way a shared inbox would betray.

## Mobile
**Screens:** Wishlist (ours), Add wish, Gift Vault (mine, lock-badged throughout), Add gift idea, Gift reminders.

**Components:** `Card`, `Chip`, `Sheet`, `Button`, `EmptyState`, lock badge with 'Only you can see this'.

**Navigation:** Us tab -> Wishlist and Gift Vault, kept visually distinct so the two are never confused. The vault is never shown on a shared Home card.

**Offline:** both lists are readable offline; edits queue. The vault is stored in the normal cache - it is already scoped to one user's token.

**Local notifications:** gift reminders, scheduled to the author's device only, with a contentless body ('A gift reminder 🎁').

## Permissions & privacy
`WishlistItemPolicy` - view: both partners; write: owner; claim: the other partner; **the `claimed_by` field is stripped from the owner's serialisation**.

`GiftVaultItemPolicy` - **author only, for every action**. No partner access path exists.

Vault items are excluded from every shared AI prompt and from the partner's data export.

## Tests
`api/tests/Feature/Wishlist/` - **the partner receives 403/404 on every gift vault route** (one test per route); **vault items absent from the partner's index, search, export and AI payload**; **the claim field is absent from the owner's view of their own wish** but present for the claimer; owner-only wish edits; reminders go only to the author; URLs are never fetched server-side.

These are the module's most important tests - a leak here is a spoiled surprise at best and a betrayal at worst.

Run: `composer test -- --filter=Wishlist`

## Known gaps / TODO
- No price tracking or retailer integration, and probably should not be.
- Whether a fulfilled wish should become a Moment is undecided.

## Changelog
- 2026-11-05 - Module documented and scaffolded (folders + routes.php). No code yet.
