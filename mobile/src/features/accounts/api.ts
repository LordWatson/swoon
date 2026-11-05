/**
 * The Accounts endpoints, typed.
 *
 * Everything goes through `apiRequest`, which is the only thing in the app
 * that calls `fetch`. Shapes mirror `api/app/Modules/Accounts/module.md`;
 * responses are flat, with no `data` wrapper.
 */
import { apiRequest } from '../../api/client';

export type Entitlement = {
  tier: 'spark' | 'together' | 'forever';
  trial_ends_at: string | null;
  is_paid: boolean;
};

export type Partner = {
  id: number;
  name: string;
  colour: string | null;
  avatar_path: string | null;
  timezone: string;
};

export type CoupleSummary = {
  id: string;
  linked_at: string;
  lock_in_mode: 'both_heart' | 'either_locks';
  anniversary_on: string | null;
  first_date_on: string | null;
  partner: Partner | null;
};

export type Me = {
  user: {
    id: number;
    name: string;
    email: string | null;
    colour: string | null;
    avatar_path: string | null;
    timezone: string;
    locale: string;
    date_of_birth: string | null;
    dietary_needs: string[];
    accessibility_needs: string[];
  };
  /** Null means signed in but solo — which is what the app routes on. */
  couple: CoupleSummary | null;
  entitlement: Entitlement;
};

export type AuthResult = { user: Me['user']; token: string };

export type Invite = {
  code: string;
  expires_at: string;
  email: string | null;
  universal_link: string;
  deep_link: string;
};

export type InvitePreview = {
  inviter_first_name: string;
  expires_at: string;
  is_usable: boolean;
};

export function fetchMe(signal?: AbortSignal): Promise<Me> {
  return apiRequest<Me>('/api/me', { signal });
}

export function register(body: {
  name: string;
  email: string;
  password: string;
  timezone?: string;
}): Promise<AuthResult> {
  return apiRequest<AuthResult>('/api/auth/register', { method: 'POST', body, anonymous: true });
}

export function signIn(body: { email: string; password: string }): Promise<AuthResult> {
  return apiRequest<AuthResult>('/api/auth/login', { method: 'POST', body, anonymous: true });
}

export function signOut(): Promise<void> {
  return apiRequest<void>('/api/auth/logout', { method: 'POST' });
}

export function createInvite(email?: string): Promise<Invite> {
  return apiRequest<Invite>('/api/invites', { method: 'POST', body: email ? { email } : {} });
}

/** Unauthenticated on purpose: the link is tapped before sign-up. */
export function previewInvite(code: string, signal?: AbortSignal): Promise<InvitePreview> {
  return apiRequest<InvitePreview>(`/api/invites/${encodeURIComponent(code)}`, {
    anonymous: true,
    signal,
  });
}

export function acceptInvite(code: string): Promise<CoupleSummary> {
  return apiRequest<CoupleSummary>(`/api/invites/${encodeURIComponent(code)}/accept`, {
    method: 'POST',
  });
}

export function unlink(): Promise<void> {
  return apiRequest<void>('/api/couple', { method: 'DELETE' });
}

export function requestExport(): Promise<{ message: string }> {
  return apiRequest<{ message: string }>('/api/me/export', { method: 'POST' });
}

export function deleteAccount(): Promise<void> {
  return apiRequest<void>('/api/me', { method: 'DELETE', body: { confirmation: 'DELETE' } });
}
