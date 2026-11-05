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

/**
 * Every route a state may legitimately occupy.
 *
 * `ROUTE_FOR` is where the gate *sends* you; this is where it leaves you
 * alone. The two are different: a solo user is sent to `/invite`, but
 * `/onboarding` is equally theirs and bouncing them out of it made the
 * onboarding flow unreachable — it was built and then never shown.
 *
 * Data rather than a chain of `startsWith` calls inside the gate, so a new
 * screen is one line here and the tests read the same list.
 */
export const ALLOWED_ROUTES = {
  unauthenticated: ['/welcome', '/sign-in', '/sign-up'],
  solo: ['/invite', '/onboarding'],
  coupled: ['/(tabs)', '/'],
} as const satisfies Record<Exclude<AuthState, 'loading'>, readonly string[]>;

/**
 * Whether the current path belongs to this state.
 *
 * Note the special case for `/`: a plain `startsWith('/')` matches **every**
 * route, which would make any path look like it belonged to a coupled user.
 */
export function isWithinState(current: string, state: Exclude<AuthState, 'loading'>): boolean {
  return ALLOWED_ROUTES[state].some((prefix) =>
    prefix === '/' ? current === '/' : current.startsWith(prefix),
  );
}
