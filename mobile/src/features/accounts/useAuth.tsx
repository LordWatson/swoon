/**
 * `useMe()` — the single source of auth state (module.md § Local state).
 *
 * One query, cached by TanStack Query, so every screen reads the same answer
 * and a sign-in or an unlink invalidates it once rather than each screen
 * re-deriving it.
 */
import { useMutation, useQuery, useQueryClient, type QueryClient } from '@tanstack/react-query';

import { ApiError, setTokenReader } from '../../api/client';
import * as api from './api';
import { authStateFor, type AuthState } from './navigation';
import { clearToken, readToken, saveToken } from './token';

const ME_KEY = ['me'] as const;

/**
 * Whether a token exists, as a query rather than component state.
 *
 * This is deliberate and it was a bug before. Reading the token once into
 * `useState` on mount means that saving one later changes nothing anybody is
 * watching: registering created the account, saved the token, and left the
 * person on the sign-up screen because the gate still believed there was no
 * token. A query is shared, cached and **invalidatable**, so signing in and
 * signing out both move the app.
 */
const TOKEN_KEY = ['auth-token'] as const;

/**
 * Everything that changes when the session changes.
 *
 * One helper, called from every mutation that signs in: forgetting the token
 * key is the exact bug above, and a single function is harder to forget than
 * two invalidations at four call sites.
 */
async function refreshSession(queryClient: QueryClient): Promise<void> {
  await queryClient.invalidateQueries({ queryKey: TOKEN_KEY });
  await queryClient.invalidateQueries({ queryKey: ME_KEY });
}

/**
 * Point the API client at secure storage. Called once, from the root layout,
 * before anything renders.
 */
export function installTokenReader(): void {
  setTokenReader(readToken);
}

export function useMe() {
  const hasToken = useHasToken();

  return useQuery({
    queryKey: ME_KEY,
    queryFn: ({ signal }) => api.fetchMe(signal),
    // No token, no point: asking would be a guaranteed 401 on every cold
    // start, which shows up in logs as a failed request on every launch.
    enabled: hasToken === true,
    // A 401 means the token is dead; retrying cannot fix it, and the app needs
    // to get on with showing the Welcome screen.
    retry: (count, error) => !(error instanceof ApiError && error.isUnauthenticated) && count < 1,
  });
}

/**
 * Whether a token exists at all — what tells "never signed in" apart from
 * "signed in and still loading".
 *
 * `undefined` while it is still being read from secure storage.
 */
export function useHasToken(): boolean | undefined {
  const { data, isPending } = useQuery({
    queryKey: TOKEN_KEY,
    queryFn: async () => (await readToken()) !== null,
    // The answer only changes when we change it, and we invalidate when we do.
    staleTime: Infinity,
    retry: false,
  });

  return isPending ? undefined : data;
}

export function useAuthState(): AuthState {
  const hasToken = useHasToken();
  const me = useMe();

  return authStateFor({
    // `me` is disabled without a token, and a disabled query reports
    // `isPending` for ever — so the token has to be the thing that decides
    // whether we are still waiting.
    isLoading: hasToken === undefined || (hasToken === true && me.isPending),
    hasToken: hasToken === true,
    me: me.data,
  });
}

export function useSignIn() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: api.signIn,
    onSuccess: async (result) => {
      await saveToken(result.token);
      await refreshSession(queryClient);
    },
  });
}

export function useRegister() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: api.register,
    onSuccess: async (result) => {
      await saveToken(result.token);
      await refreshSession(queryClient);
    },
  });
}

export function useSignOut() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: api.signOut,
    // Clears locally even if the call fails: a person tapping "sign out" on a
    // flaky train must end up signed out of this device regardless.
    onSettled: async () => {
      await clearToken();

      // Invalidate, not just clear. `queryClient.clear()` empties the cache
      // but does NOT re-render mounted observers, so the gate kept showing
      // the tabs to someone who had signed out — the sign-in bug in mirror
      // image. Invalidating is what makes the app move.
      await refreshSession(queryClient);

      // Then drop the previous person's data rather than leaving it sitting
      // in the cache: `useMe` is disabled without a token, so nothing would
      // overwrite it.
      queryClient.removeQueries({ queryKey: ME_KEY });
    },
  });
}

export function useAcceptInvite() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: api.acceptInvite,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ME_KEY }),
  });
}

export function useCreateInvite() {
  return useMutation({ mutationFn: api.createInvite });
}

export function useUpdateProfile() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: api.updateProfile,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ME_KEY }),
  });
}

export function useRemindInvite() {
  return useMutation({ mutationFn: api.remindInvite });
}

export function useUnlink() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: api.unlink,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ME_KEY }),
  });
}
