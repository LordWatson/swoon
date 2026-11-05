/**
 * Parsing a partner invite link.
 *
 * Two shapes reach the app and both must work:
 *
 *   swoon://invite/ABCD2345                    the custom scheme
 *   https://link.swoon.test/invite/ABCD2345    the universal link
 *
 * A pure function, because link handling is the one thing in the app that is
 * genuinely hard to test on a device — a cold start from a link cannot be
 * reproduced by tapping around — and because a link that silently fails to
 * parse means a person who was invited lands on a sign-up screen with no idea
 * why.
 */
import { DEEP_LINK_SCHEME } from '../../config/app';
import { InviteCodeShape } from './inviteCode';

/**
 * The code in a link, or null.
 *
 * Lenient about the things that genuinely vary — trailing slashes, query
 * strings a messaging app appended, case — and strict about the code itself,
 * so a malformed link is rejected here rather than becoming a pointless API
 * call.
 */
export function parseInviteLink(url: string | null | undefined): string | null {
  if (!url) return null;

  const trimmed = url.trim();

  // Both forms end `/invite/<code>`, so one expression covers them. The scheme
  // is checked rather than assumed: a link to someone else's app that happens
  // to contain /invite/ is not ours.
  const pattern = new RegExp(
    `^(?:${DEEP_LINK_SCHEME}://(?:[^/]*/)?|https?://[^/]+/)invite/([^/?#]+)`,
    'i',
  );

  const match = pattern.exec(trimmed);

  if (match === null) return null;

  const code = InviteCodeShape.normalise(decodeURIComponent(match[1]));

  return InviteCodeShape.looksValid(code) ? code : null;
}
