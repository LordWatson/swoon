/**
 * Where the auth token lives.
 *
 * `expo-secure-store`, never AsyncStorage: the token is the whole account, and
 * AsyncStorage is plain text on disk that any backup or file-level compromise
 * reads (CLAUDE.md, and `module.md` § Local state).
 *
 * The API client knows nothing about this — it is handed a reader at startup
 * (`setTokenReader`), which keeps `src/api/client.ts` free of any storage
 * dependency and trivially testable.
 */
import * as SecureStore from 'expo-secure-store';

const KEY = 'swoon.auth.token';

/** Cached so every request does not hit the keychain. */
let cached: string | null | undefined;

export async function readToken(): Promise<string | null> {
  if (cached !== undefined) return cached;

  try {
    cached = await SecureStore.getItemAsync(KEY);
  } catch {
    // A device with no secure hardware, or a keychain that refuses. Treat it
    // as signed out rather than crashing on launch — the worst case is the
    // person signs in again.
    cached = null;
  }

  return cached;
}

export async function saveToken(token: string): Promise<void> {
  cached = token;
  await SecureStore.setItemAsync(KEY, token);
}

export async function clearToken(): Promise<void> {
  cached = null;
  await SecureStore.deleteItemAsync(KEY);
}

/** Only for tests: drops the in-memory copy so the next read hits storage. */
export function resetTokenCache(): void {
  cached = undefined;
}
