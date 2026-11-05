/**
 * Signing in has to move you.
 *
 * The regression this file exists for: registering created the account and
 * left the person on the sign-up screen. The account was fine, the token was
 * saved, the API was happy — but the gate still thought there was no token,
 * because token presence was read once into `useState` on mount and never
 * again. Nothing recomputed, so nothing redirected.
 *
 * So the assertion that matters is the one about a token that appears *after*
 * mount: that is the shape of every sign-in, and it is the shape the old code
 * got wrong.
 */
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { act, render, waitFor } from '@testing-library/react-native';
import { Text } from 'react-native';

import { ApiError } from '../../../api/client';
import * as api from '../api';
import { saveToken, clearToken, resetTokenCache } from '../token';
import type { AuthState } from '../navigation';
import { useAuthState, useSignOut } from '../useAuth';

jest.mock('expo-secure-store', () => {
  const store = new Map<string, string>();

  return {
    getItemAsync: jest.fn((key: string) => Promise.resolve(store.get(key) ?? null)),
    setItemAsync: jest.fn((key: string, value: string) => {
      store.set(key, value);

      return Promise.resolve();
    }),
    deleteItemAsync: jest.fn((key: string) => {
      store.delete(key);

      return Promise.resolve();
    }),
  };
});

jest.mock('../api');

const fetchMe = api.fetchMe as jest.MockedFunction<typeof api.fetchMe>;

/** A `/api/me` payload, coupled or solo. */
function mePayload(coupled: boolean): api.Me {
  return {
    user: {
      id: 1,
      name: 'Alex',
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
            name: 'Sam',
            colour: 'lavender',
            avatar_path: null,
            timezone: 'Europe/London',
          },
        }
      : null,
    entitlement: { tier: 'spark', trial_ends_at: null, is_paid: false },
  };
}

/** Renders `useAuthState` and reports what it says, plus the query client. */
async function mountGate() {
  const client = new QueryClient({
    defaultOptions: { queries: { retry: false, gcTime: 0 } },
  });

  const states: AuthState[] = [];

  let signOut: ReturnType<typeof useSignOut> | undefined;

  function Probe() {
    const state = useAuthState();
    signOut = useSignOut();
    states.push(state);

    return <Text testID="state">{state}</Text>;
  }

  const view = await render(
    <QueryClientProvider client={client}>
      <Probe />
    </QueryClientProvider>,
  );

  return { view, client, states, signOut: () => signOut };
}

beforeEach(async () => {
  await clearToken();
  resetTokenCache();
  jest.clearAllMocks();
});

it('starts in loading rather than guessing', async () => {
  // Guessing would flash Welcome at someone who is already signed in, on every
  // single launch.
  const { states } = await mountGate();

  expect(states[0]).toBe('loading');
});

it('settles on unauthenticated when there is no token', async () => {
  const { view } = await mountGate();

  await waitFor(() => expect(view.getByTestId('state').props.children).toBe('unauthenticated'));
  expect(fetchMe).not.toHaveBeenCalled();
});

it('moves to the invite stack when a token appears after mount', async () => {
  // THE REGRESSION. This is exactly what registering does: the screen is
  // already mounted, then a token is saved. If the state does not change, the
  // person is left on the sign-up screen with an account they cannot see.
  const { view, client } = await mountGate();

  await waitFor(() => expect(view.getByTestId('state').props.children).toBe('unauthenticated'));

  fetchMe.mockResolvedValue(mePayload(false));

  await act(async () => {
    await saveToken('tok_fresh');
    await client.invalidateQueries();
  });

  await waitFor(() => expect(view.getByTestId('state').props.children).toBe('solo'));
});

it('moves to the tabs when the new session is already coupled', async () => {
  // Accepting an invite on a second device: the token arrives and the couple
  // is already there.
  const { view, client } = await mountGate();

  await waitFor(() => expect(view.getByTestId('state').props.children).toBe('unauthenticated'));

  fetchMe.mockResolvedValue(mePayload(true));

  await act(async () => {
    await saveToken('tok_fresh');
    await client.invalidateQueries();
  });

  await waitFor(() => expect(view.getByTestId('state').props.children).toBe('coupled'));
});

it('goes back to unauthenticated when you sign out', async () => {
  // Signing out has to move you too, and the first fix did not do it:
  // `queryClient.clear()` empties the cache without re-rendering mounted
  // observers, so the gate kept showing the tabs. Driven through the real
  // hook, because a hand-rolled approximation of sign-out was not what was
  // broken.
  await saveToken('tok_existing');
  fetchMe.mockResolvedValue(mePayload(true));
  (api.signOut as jest.MockedFunction<typeof api.signOut>).mockResolvedValue(undefined);

  const { view, signOut } = await mountGate();

  await waitFor(() => expect(view.getByTestId('state').props.children).toBe('coupled'));

  await act(async () => {
    await signOut()?.mutateAsync();
  });

  await waitFor(() => expect(view.getByTestId('state').props.children).toBe('unauthenticated'));
});

it('signs out of this device even when the API call fails', async () => {
  // A person tapping "sign out" on a flaky train must end up signed out here
  // regardless of whether the server heard about it.
  await saveToken('tok_existing');
  fetchMe.mockResolvedValue(mePayload(true));
  (api.signOut as jest.MockedFunction<typeof api.signOut>).mockRejectedValue(
    new ApiError(0, 'Network request failed'),
  );

  const { view, signOut } = await mountGate();

  await waitFor(() => expect(view.getByTestId('state').props.children).toBe('coupled'));

  await act(async () => {
    await signOut()
      ?.mutateAsync()
      .catch(() => undefined);
  });

  await waitFor(() => expect(view.getByTestId('state').props.children).toBe('unauthenticated'));
});

it('treats a token the API refuses as signed out', async () => {
  // A token can be revoked from another device, or the account deleted. The
  // app must not sit on a dead one showing a spinner for ever.
  await saveToken('tok_revoked');
  // A real 401, not a plain Error: `useMe` retries anything that is not an
  // ApiError 401, so a vague rejection would just look like a slow request.
  fetchMe.mockRejectedValue(new ApiError(401, 'Unauthenticated.'));

  const { view } = await mountGate();

  await waitFor(() => expect(view.getByTestId('state').props.children).toBe('unauthenticated'));
});
