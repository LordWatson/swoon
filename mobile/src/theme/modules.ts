/**
 * Per-module signature: emoji + colour token. See docs/BRAND.md § 7.
 *
 * Emoji are CONTENT, icons are UI — a module's emoji belongs in its heading and
 * its copy, never as a button icon.
 *
 * Keys match the mobile feature folder names in `src/features/`.
 */
import type { ColourTokens } from './colours';

export type ModuleSignature = {
  emoji: string;
  /** A colour token name, resolved through the active theme. */
  colour: keyof ColourTokens;
  label: string;
};

export const moduleSignatures = {
  accounts: { emoji: '💞', colour: 'primary', label: 'Us' },
  'date-planner': { emoji: '🍷', colour: 'primary', label: 'Dates' },
  reminders: { emoji: '⏰', colour: 'secondary', label: 'Reminders' },
  moments: { emoji: '📸', colour: 'secondary', label: 'Moments' },
  'on-this-day': { emoji: '🕰️', colour: 'highlight', label: 'On this day' },
  planner: { emoji: '🗓️', colour: 'secondary', label: 'Planner' },
  milestones: { emoji: '⏳', colour: 'highlight', label: 'Milestones' },
  'daily-spark': { emoji: '🔥', colour: 'highlight', label: 'Daily spark' },
  challenges: { emoji: '💌', colour: 'primary', label: 'Challenges' },
  'love-notes': { emoji: '💕', colour: 'primary', label: 'Love notes' },
  'date-roulette': { emoji: '🎡', colour: 'primary', label: 'Date roulette' },
  recap: { emoji: '✨', colour: 'ai', label: 'Recap' },
  insights: { emoji: '💡', colour: 'ai', label: 'Insights' },
  billing: { emoji: '🧾', colour: 'success', label: 'Subscription' },
  'love-map': { emoji: '🗺️', colour: 'secondary', label: 'Love map' },
  wishlist: { emoji: '🎁', colour: 'secondary', label: 'Wishlist' },
  'bucket-list': { emoji: '🪣', colour: 'success', label: 'Bucket list' },
  'date-debrief': { emoji: '💬', colour: 'success', label: 'Date debrief' },
  'check-in': { emoji: '🌡️', colour: 'success', label: 'Check-in' },
  'year-in-moments': { emoji: '📖', colour: 'ai', label: 'Year in moments' },
} as const satisfies Record<string, ModuleSignature>;

export type ModuleKey = keyof typeof moduleSignatures;
