/**
 * The app shell: which stack a person sees, and the quick-add sheet.
 *
 * The navigation rule is tested as a **pure function** rather than by driving
 * a navigator. The rule is the thing worth protecting — "a solo user never
 * sees the couple tabs" is a privacy rule as much as a routing one — and
 * asserting it on `authStateFor` is both faster and far harder to write a
 * passing-but-meaningless test for than mounting a router.
 */
import { cleanup, fireEvent, render } from '@testing-library/react-native';
import type { ReactElement } from 'react';
import { SafeAreaProvider } from 'react-native-safe-area-context';

import { ThemeProvider, lightTheme } from '../../../theme';
import type { Me } from '../api';
import { ALLOWED_ROUTES, authStateFor, isWithinState, ROUTE_FOR, routeFor } from '../navigation';
import { QUICK_ADD_OPTIONS, QuickAddSheet } from '../QuickAddSheet';

/**
 * `Sheet` reads safe-area insets, so the provider has to be in the tree — with
 * a fixed frame, since there is no real device to measure.
 */
function renderThemed(ui: ReactElement) {
  return render(
    <SafeAreaProvider
      initialMetrics={{
        frame: { x: 0, y: 0, width: 390, height: 844 },
        insets: { top: 47, left: 0, right: 0, bottom: 34 },
      }}
    >
      <ThemeProvider value={lightTheme}>{ui}</ThemeProvider>
    </SafeAreaProvider>,
  );
}

afterEach(() => {
  cleanup();
});

/** A `/api/me` payload, with the couple present or absent. */
function me(coupled: boolean): Me {
  return {
    user: {
      id: 1,
      name: 'Alex Watson',
      email: 'alex@example.com',
      colour: 'rose',
      avatar_path: null,
      timezone: 'Europe/London',
      locale: 'en-GB',
      date_of_birth: null,
      dietary_needs: [],
      accessibility_needs: [],
    },
    couple: coupled
      ? {
          id: '01HQ000000000000000000',
          linked_at: '2026-01-01T00:00:00+00:00',
          lock_in_mode: 'both_heart',
          anniversary_on: null,
          first_date_on: null,
          partner: {
            id: 2,
            name: 'Sam Hart',
            colour: 'lavender',
            avatar_path: null,
            timezone: 'Europe/London',
          },
        }
      : null,
    entitlement: { tier: 'spark', trial_ends_at: null, is_paid: false },
  };
}

describe('which stack a person sees', () => {
  it('waits rather than guessing while the token is still being read', () => {
    // Guessing here would flash the Welcome screen at someone who IS signed
    // in, every single launch.
    expect(authStateFor({ isLoading: true, hasToken: false, me: undefined })).toBe('loading');
    expect(routeFor('loading')).toBeNull();
  });

  it('sends someone with no token to Welcome', () => {
    expect(authStateFor({ isLoading: false, hasToken: false, me: undefined })).toBe(
      'unauthenticated',
    );
  });

  it('sends someone whose token the API refused to Welcome', () => {
    // A token can be revoked from another device, or the account deleted. The
    // app must not sit on a dead token showing a loading state for ever.
    expect(authStateFor({ isLoading: false, hasToken: true, me: null })).toBe('unauthenticated');
  });

  it('sends a signed-in user with no couple to the invite stack, never the tabs', () => {
    // The important one. Every tab is about two people; half would have
    // nothing to render, and some would imply a partner who does not exist.
    const state = authStateFor({ isLoading: false, hasToken: true, me: me(false) });

    expect(state).toBe('solo');
    expect(routeFor(state)).toBe('/invite');
    expect(routeFor(state)).not.toBe(ROUTE_FOR.coupled);
  });

  it('sends a coupled user to the tabs', () => {
    const state = authStateFor({ isLoading: false, hasToken: true, me: me(true) });

    expect(state).toBe('coupled');
    expect(routeFor(state)).toBe(ROUTE_FOR.coupled);
  });

  it('decides from the server, not from anything the app inferred', () => {
    // `couple: null` is the server's answer. A reveal gate has to be absent
    // from the JSON rather than merely unrendered, and this is the same
    // principle one level up.
    const payload = me(true);
    const withoutCouple = { ...payload, couple: null };

    expect(authStateFor({ isLoading: false, hasToken: true, me: withoutCouple })).toBe('solo');
  });

  it('has a route for every state except loading', () => {
    expect(Object.keys(ROUTE_FOR).sort()).toEqual(['coupled', 'solo', 'unauthenticated']);
  });
});

describe('which routes a state may occupy', () => {
  it('leaves a solo user alone in onboarding', () => {
    // The regression: the gate bounced /onboarding to /invite, so the
    // onboarding flow was built and then never reachable.
    expect(isWithinState('/onboarding', 'solo')).toBe(true);
    expect(isWithinState('/invite', 'solo')).toBe(true);
    expect(isWithinState('/invite/ABCD2345', 'solo')).toBe(true);
  });

  it('does not let one state sit in another stack', () => {
    expect(isWithinState('/welcome', 'solo')).toBe(false);
    expect(isWithinState('/onboarding', 'coupled')).toBe(false);
    expect(isWithinState('/(tabs)/moments', 'unauthenticated')).toBe(false);
  });

  it('treats the root route as exactly the root', () => {
    // `startsWith('/')` matches EVERY path, which would make any route look
    // like it belonged to a coupled user and disable the gate entirely.
    expect(isWithinState('/', 'coupled')).toBe(true);
    expect(isWithinState('/welcome', 'coupled')).toBe(false);
    expect(isWithinState('/invite', 'coupled')).toBe(false);
  });

  it('sends each state somewhere it is allowed to be', () => {
    // Stops the redirect target and the permitted list drifting apart, which
    // would be an endless redirect.
    for (const state of ['unauthenticated', 'solo', 'coupled'] as const) {
      expect(isWithinState(ROUTE_FOR[state], state)).toBe(true);
      expect(ALLOWED_ROUTES[state].length).toBeGreaterThan(0);
    }
  });
});

describe('the quick-add sheet', () => {
  it('offers exactly four things', () => {
    // Four is the design, not an accident: it is the number a person can read
    // without deciding (UX rule 14). A fifth is a product decision.
    expect(QUICK_ADD_OPTIONS).toHaveLength(4);
    expect(QUICK_ADD_OPTIONS.map((option) => option.key)).toEqual([
      'moment',
      'plan',
      'note',
      'date-idea',
    ]);
  });

  it('renders all four when open, each with a label', async () => {
    const view = await renderThemed(
      <QuickAddSheet visible onClose={() => undefined} onChoose={() => undefined} />,
    );

    for (const option of QUICK_ADD_OPTIONS) {
      expect(view.getByText(option.label)).toBeTruthy();
    }
  });

  it('says who can see it', async () => {
    const view = await renderThemed(
      <QuickAddSheet visible onClose={() => undefined} onChoose={() => undefined} />,
    );

    expect(view.getByText('Only the two of you will ever see it 🔒')).toBeTruthy();
  });

  it('reports which option was chosen', async () => {
    const onChoose = jest.fn();
    const view = await renderThemed(
      <QuickAddSheet visible onClose={() => undefined} onChoose={onChoose} />,
    );

    fireEvent.press(view.getByTestId('quick-add-moment'));

    expect(onChoose).toHaveBeenCalledWith('moment');
  });

  it('renders nothing while closed', async () => {
    const view = await renderThemed(
      <QuickAddSheet visible={false} onClose={() => undefined} onChoose={() => undefined} />,
    );

    expect(view.queryByText('A moment')).toBeNull();
  });
});
