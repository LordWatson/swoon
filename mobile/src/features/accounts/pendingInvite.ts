/**
 * The invite code someone tapped before they had an account.
 *
 * This is the whole of "survive the install detour". The sequence it exists
 * for: a person taps their partner's link, has no app, goes to the store,
 * installs, opens — and the link is **gone**, because a cold start after an
 * install carries no URL. So the code is stashed the moment it is seen and
 * resolved once there is an account to attach it to.
 *
 * Kept in `expo-secure-store` because the code is a capability: anyone holding
 * it can join a couple's space. It is cleared as soon as it is used or found
 * to be dead, so a stale code cannot resurface weeks later.
 */
import * as SecureStore from 'expo-secure-store';

import { InviteCodeShape } from './inviteCode';

const KEY = 'swoon.invite.pending';

export async function rememberPendingInvite(code: string): Promise<void> {
  if (!InviteCodeShape.looksValid(code)) return;

  try {
    await SecureStore.setItemAsync(KEY, code);
  } catch {
    // No secure storage. The worst case is the person types the code in
    // themselves, which the invite screen already supports.
  }
}

export async function readPendingInvite(): Promise<string | null> {
  try {
    const code = await SecureStore.getItemAsync(KEY);

    // Validated on the way out as well as in: storage could hold anything
    // written by an older build.
    return code !== null && InviteCodeShape.looksValid(code) ? code : null;
  } catch {
    return null;
  }
}

export async function clearPendingInvite(): Promise<void> {
  try {
    await SecureStore.deleteItemAsync(KEY);
  } catch {
    // Nothing to do. A code that cannot be cleared is re-validated on use.
  }
}
