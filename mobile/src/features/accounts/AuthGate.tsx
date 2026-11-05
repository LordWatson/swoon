import { useRouter, useSegments } from 'expo-router';
import { type ReactNode, useEffect } from 'react';

import { authStateFor, ROUTE_FOR, type AuthState } from './navigation';
import { useHasToken, useMe } from './useAuth';

/**
 * Puts the app in the right place for who is signed in.
 *
 * Three states, one rule each (module.md § Navigation):
 *
 * - **unauthenticated** → the Welcome stack.
 * - **solo** → the Invite stack. A solo user must never see the couple tabs:
 *   every one of them is about two people, and half would have nothing to
 *   render. This is a privacy rule as much as a routing one.
 * - **coupled** → the five tabs.
 *
 * It redirects rather than rendering a different tree, so a deep link that
 * arrives before sign-in is not lost — the router keeps the URL and P1.T05
 * resumes it afterwards.
 */
export function AuthGate({ children }: { children: ReactNode }) {
  const router = useRouter();
  const segments = useSegments();
  const hasToken = useHasToken();
  const me = useMe();

  const state: AuthState = authStateFor({
    isLoading: hasToken === undefined || (hasToken && me.isPending),
    hasToken: hasToken === true,
    me: me.data,
  });

  useEffect(() => {
    if (state === 'loading') return;

    const target = ROUTE_FOR[state];
    const current = '/' + segments.join('/');

    // An invite link is allowed to sit outside the expected stack: it is
    // handled by its own screen, which sends the person on afterwards.
    if (segments[0] === 'invite' && segments.length > 1) return;

    if (!isWithin(current, state)) {
      router.replace(target);
    }
  }, [router, segments, state]);

  return children;
}

/**
 * Whether the current route already belongs to this state.
 *
 * Checked by group rather than by exact path so pushing a screen inside the
 * right stack — sign-in from Welcome, say — does not bounce the user back out
 * of it.
 */
function isWithin(current: string, state: Exclude<AuthState, 'loading'>): boolean {
  // The tabs group is the root route, so every tab path lives under it.
  if (state === 'coupled') return current === '/' || current.startsWith('/(tabs)');

  if (state === 'solo') return current.startsWith('/invite');

  return (
    current.startsWith('/welcome') ||
    current.startsWith('/sign-in') ||
    current.startsWith('/sign-up')
  );
}
