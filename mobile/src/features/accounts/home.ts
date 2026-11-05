/**
 * What the Home screen needs to say, as pure functions.
 *
 * Here rather than inline in the screen because every one of these was
 * **hardcoded** until now — "Alex & Sam", 214 days together — and a real
 * person signing in saw somebody else's name and invented numbers. Pure and
 * tested is how that does not happen twice.
 */
import type { CoupleSummary } from './api';

/** First name only, for a greeting. */
export function firstName(name: string | null | undefined): string {
  const first = (name ?? '').trim().split(/\s+/)[0];

  return first === '' ? 'you' : first;
}

/**
 * "Good morning" / "Good afternoon" / "Good evening".
 *
 * Takes the date so it can be tested, rather than reading the clock itself.
 */
export function greeting(now: Date = new Date()): string {
  const hour = now.getHours();

  if (hour < 12) return 'Good morning,';
  if (hour < 18) return 'Good afternoon,';

  return 'Good evening,';
}

/**
 * How long the two of you have been together, in whole days.
 *
 * From the first date if they have told us, else the anniversary, else the day
 * they linked — which is the only one we always have. Null when there is no
 * couple at all.
 *
 * Both sides are normalised to UTC midnight before subtracting. Comparing a
 * `YYYY-MM-DD` date (parsed as UTC midnight) against a local `now` is how you
 * get an off-by-one that only shows up for users west of Greenwich, and there
 * is a test for exactly that.
 */
export function daysTogether(couple: CoupleSummary | null, now: Date = new Date()): number | null {
  if (couple === null) return null;

  const since = couple.first_date_on ?? couple.anniversary_on ?? couple.linked_at;
  const start = utcMidnight(new Date(since));

  if (Number.isNaN(start)) return null;

  const days = Math.floor((utcMidnight(now) - start) / 86_400_000);

  // A future first date is someone mistyping, not a negative relationship.
  return Math.max(days, 0);
}

/** Formatted for the Stat component: "214", "1,204". */
export function formatCount(value: number): string {
  return value.toLocaleString('en-GB');
}

function utcMidnight(date: Date): number {
  return Date.UTC(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate());
}
