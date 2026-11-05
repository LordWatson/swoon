/**
 * The in-progress onboarding answers, kept across a backgrounded app.
 *
 * Stored in `expo-secure-store` rather than plain storage, and that is a
 * deliberate choice rather than convenience: the draft holds **dietary and
 * accessibility needs**, which can reveal religion or disability. Leaving that
 * in plain-text storage while the flow is half-finished would be worse than
 * leaving it in memory.
 *
 * It is a draft, not state: the real answers are PATCHed to `/api/me` as each
 * screen is completed, so a person who gives up halfway keeps what they got
 * through. The draft exists only so re-opening the app does not blank the
 * screen they were on.
 */
import { useCallback, useEffect, useState } from 'react';
import * as SecureStore from 'expo-secure-store';

import type { OnboardingDraft } from './onboarding';

const KEY = 'swoon.onboarding.draft';

export function useOnboardingDraft() {
  const [draft, setDraft] = useState<OnboardingDraft | null>(null);

  useEffect(() => {
    let active = true;

    void SecureStore.getItemAsync(KEY)
      .then((raw) => {
        if (!active) return;

        setDraft(raw === null ? {} : (safeParse(raw) ?? {}));
      })
      .catch(() => {
        // No secure hardware, or a refusing keychain. An empty draft is a fine
        // outcome — the person answers the questions again, nothing is lost
        // that was already PATCHed.
        if (active) setDraft({});
      });

    return () => {
      active = false;
    };
  }, []);

  const update = useCallback((patch: OnboardingDraft) => {
    setDraft((current) => {
      const merged = { ...(current ?? {}), ...patch };

      void SecureStore.setItemAsync(KEY, JSON.stringify(merged)).catch(() => undefined);

      return merged;
    });
  }, []);

  const clear = useCallback(() => {
    setDraft({});
    void SecureStore.deleteItemAsync(KEY).catch(() => undefined);
  }, []);

  return { draft, update, clear, isReady: draft !== null };
}

function safeParse(raw: string): OnboardingDraft | null {
  try {
    const parsed: unknown = JSON.parse(raw);

    return typeof parsed === 'object' && parsed !== null ? (parsed as OnboardingDraft) : null;
  } catch {
    return null;
  }
}
