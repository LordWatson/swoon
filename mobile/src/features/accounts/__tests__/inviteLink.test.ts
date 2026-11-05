/**
 * Invite links, and the code that survives an app install.
 *
 * This is the one part of the app that genuinely cannot be checked by tapping
 * around: a cold start from a link, and a link followed *before* the app
 * existed, are both unreproducible by hand. So the parsing and the stash are
 * pure and tested here, and a mis-parsed link means someone who was invited
 * lands on a sign-up screen with no idea why.
 */
import * as SecureStore from 'expo-secure-store';

import { InviteCodeShape } from '../inviteCode';
import { parseInviteLink } from '../inviteLink';
import { clearPendingInvite, readPendingInvite, rememberPendingInvite } from '../pendingInvite';

jest.mock('expo-secure-store', () => {
  const store = new Map<string, string>();

  return {
    __store: store,
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

const VALID = 'ABCD2345';

beforeEach(async () => {
  await clearPendingInvite();
  jest.clearAllMocks();
});

describe('parsing a link', () => {
  it('reads the custom scheme', () => {
    expect(parseInviteLink(`swoon://invite/${VALID}`)).toBe(VALID);
  });

  it('reads the universal link', () => {
    expect(parseInviteLink(`https://link.swoon.test/invite/${VALID}`)).toBe(VALID);
  });

  it('copes with what messaging apps do to a URL', () => {
    // Trailing slashes, tracking parameters and fragments all arrive in the
    // wild, and none of them changes the code.
    expect(parseInviteLink(`https://link.swoon.test/invite/${VALID}/`)).toBe(VALID);
    expect(parseInviteLink(`https://link.swoon.test/invite/${VALID}?utm_source=whatsapp`)).toBe(
      VALID,
    );
    expect(parseInviteLink(`https://link.swoon.test/invite/${VALID}#x`)).toBe(VALID);
    expect(parseInviteLink(`  swoon://invite/${VALID}  `)).toBe(VALID);
  });

  it('accepts a code typed in lower case', () => {
    // Autocorrect and keyboards both do this.
    expect(parseInviteLink(`swoon://invite/${VALID.toLowerCase()}`)).toBe(VALID);
  });

  it('handles the scheme with a host, which some platforms add', () => {
    expect(parseInviteLink(`swoon://app/invite/${VALID}`)).toBe(VALID);
  });

  it('refuses a link that is not an invite', () => {
    expect(parseInviteLink('swoon://moments/5')).toBeNull();
    expect(parseInviteLink('https://link.swoon.test/privacy')).toBeNull();
  });

  it('refuses a malformed code rather than passing it to the API', () => {
    // Saves a pointless request, and lets the screen say something specific.
    expect(parseInviteLink('swoon://invite/SHORT')).toBeNull();
    expect(parseInviteLink('swoon://invite/')).toBeNull();
    // 0, O, 1, I and L are not in the alphabet, because partners read these
    // aloud (rule 7).
    expect(parseInviteLink('swoon://invite/ABCD2340')).toBeNull();
    expect(parseInviteLink('swoon://invite/ABCDI345')).toBeNull();
  });

  it('refuses nothing at all', () => {
    expect(parseInviteLink(null)).toBeNull();
    expect(parseInviteLink(undefined)).toBeNull();
    expect(parseInviteLink('')).toBeNull();
  });
});

describe('the code shape', () => {
  it('matches the server alphabet, with the misreadable characters out', () => {
    expect(InviteCodeShape.length).toBe(8);
    expect(InviteCodeShape.looksValid(VALID)).toBe(true);

    for (const bad of ['ABCD2340', 'ABCDO345', 'ABCD1345', 'ABCDI345', 'ABCDL345']) {
      expect(InviteCodeShape.looksValid(bad)).toBe(false);
    }
  });

  it('tidies what a person typed', () => {
    expect(InviteCodeShape.normalise('abcd 2345')).toBe(VALID);
    expect(InviteCodeShape.normalise('ABCD-2345')).toBe(VALID);
  });
});

describe('surviving the install detour', () => {
  it('remembers a code tapped before there was an account', async () => {
    // The sequence this exists for: tap the link, no app, visit the store,
    // install, open — and the cold start carries NO URL. Without the stash the
    // code is simply gone.
    await rememberPendingInvite(VALID);

    expect(await readPendingInvite()).toBe(VALID);
  });

  it('forgets it once it has been used', async () => {
    await rememberPendingInvite(VALID);
    await clearPendingInvite();

    expect(await readPendingInvite()).toBeNull();
  });

  it('never stores a malformed code', async () => {
    await rememberPendingInvite('nonsense');

    expect(await readPendingInvite()).toBeNull();
    expect(SecureStore.setItemAsync).not.toHaveBeenCalled();
  });

  it('refuses a stored code that no longer looks valid', async () => {
    // Storage could hold anything an older build wrote, so it is validated on
    // the way out as well as in.
    await (SecureStore.setItemAsync as jest.Mock)('swoon.invite.pending', 'LEFTOVER-RUBBISH');

    expect(await readPendingInvite()).toBeNull();
  });

  it('treats a keychain that refuses as simply having no code', async () => {
    // A device with no secure hardware must not crash on launch. The worst
    // case is the person types the code in, which the invite screen supports.
    (SecureStore.getItemAsync as jest.Mock).mockRejectedValueOnce(new Error('no keychain'));

    expect(await readPendingInvite()).toBeNull();
  });

  it('does not throw when it cannot save', async () => {
    (SecureStore.setItemAsync as jest.Mock).mockRejectedValueOnce(new Error('no keychain'));

    await expect(rememberPendingInvite(VALID)).resolves.toBeUndefined();
  });
});
