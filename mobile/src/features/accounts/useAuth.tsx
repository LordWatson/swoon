/**
 * `useMe()` — the single source of auth state (module.md § Local state).
 *
 * One query, cached by TanStack Query, so every screen reads the same answer
 * and a sign-in or an unlink invalidates it once rather than each screen
 * re-deriving it.
 */
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useEffect, useState } from 'react';

import { ApiError, setTokenReader } from '../../api/client';
import * as api from './api';
import { authStateFor, type AuthState } from './navigation';
import { clearToken, readToken, saveToken } from './token';

const ME_KEY = ['me'] as const;

/**
 * Point the API client at secure storage. Called once, from the root layout,
 * before anything renders.
 */
export function installTokenReader(): void {
  setTokenReader(readToken);
}

export function useMe() {
  return useQuery({
    queryKey: ME_KEY,
    queryFn: ({ signal }) => api.fetchMe(signal),
    // A 401 means the token is dead; retrying cannot fix it, and the app needs
    // to get on with showing the Welcome screen.
    retry: (count, error) => !(error instanceof ApiError && error.isUnauthenticated) && count < 1,
  });
}

/**
 * Whether a token exists at all, which is what tells "never signed in" apart
 * from "signed in and still loading".
 */
export function useHasToken(): boolean | undefined {
  const [hasToken, setHasToken] = useState<boolean | undefined>(undefined);

  useEffect(() => {
    let active = true;

    void readToken().then((token) => {
      if (active) setHasToken(token !== null);
    });

    return () => {
      active = false;
    };
  }, []);

  return hasToken;
}

export function useAuthState(): AuthState {
  const hasToken = useHasToken();
  const me = useMe();

  return authStateFor({
    isLoading: hasToken === undefined || (hasToken && me.isPending),
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
      await queryClient.invalidateQueries({ queryKey: ME_KEY });
    },
  });
}

export function useRegister() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: api.register,
    onSuccess: async (result) => {
      await saveToken(result.token);
      await queryClient.invalidateQueries({ queryKey: ME_KEY });
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
      queryClient.clear();
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

export function useUnlink() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: api.unlink,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ME_KEY }),
  });
}
