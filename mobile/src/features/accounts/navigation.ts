/**
 * Which of the app's three navigation states we are in.
 *
 * Kept as a pure function so the rule is testable without a navigator, and so
 * there is exactly one place that decides. The rule itself is a privacy rule
 * as much as a routing one: **a solo user must never see the couple tabs**,
 * because every tab behind them is about two people and half of them would
 * have nothing to render.
 */
import type { Me } from './api';

export type AuthState = 'loading' | 'unauthenticated' | 'solo' | 'coupled';

/**
 * The route each state belongs in.
 *
 * `as const` rather than a `Record<..., string>`: Expo Router's typed routes
 * only accept literal hrefs, and widening to `string` loses that check — which
 * is the one thing that catches a renamed screen at compile time.
 *
 * `coupled` names the group explicitly — Expo Router generates
 * `/(tabs)/index` for `src/app/(tabs)/index.tsx`, not `/`.
 */
export const ROUTE_FOR = {
  unauthenticated: '/welcome',
  solo: '/invite',
  coupled: '/(tabs)/index',
} as const satisfies Record<Exclude<AuthState, 'loading'>, string>;

export function authStateFor(input: {
  isLoading: boolean;
  hasToken: boolean;
  me: Me | null | undefined;
}): AuthState {
  if (input.isLoading) return 'loading';

  // No token, or a token the API refused: either way, start at the beginning.
  if (!input.hasToken || !input.me) return 'unauthenticated';

  // `couple === null` is the server's answer, not something the app infers —
  // a reveal gate must be absent from the JSON, not merely unrendered.
  return input.me.couple === null ? 'solo' : 'coupled';
}

export function routeFor(state: AuthState): (typeof ROUTE_FOR)[keyof typeof ROUTE_FOR] | null {
  return state === 'loading' ? null : ROUTE_FOR[state];
}
