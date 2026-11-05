/**
 * The shape of an invite code, client-side.
 *
 * Mirrors `App\Modules\Accounts\Support\InviteCode` — the same alphabet, with
 * 0/O and 1/I/L left out because partners read these aloud (rule 7). Duplicated
 * rather than fetched: the app needs it to reject a typo before making a
 * request, and a round trip to find out a code is the wrong length is a bad
 * experience for someone squinting at a text message.
 *
 * It is a *shape* check only. Whether a code exists, is live, or is yours is
 * the server's business and is never decided here.
 */
const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
const LENGTH = 8;

export const InviteCodeShape = {
  /** What a person typed, tidied: spaces and punctuation out, upper case in. */
  normalise(input: string): string {
    return input.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
  },

  looksValid(code: string): boolean {
    return new RegExp(`^[${ALPHABET}]{${LENGTH}}$`).test(code);
  },

  length: LENGTH,
} as const;
