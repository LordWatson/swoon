/**
 * What is real and what is not, as a gate rather than a promise.
 *
 * This file exists because of a fair question: "how am I supposed to know
 * what's hardcoded and what isn't?" The answer was previously "ask me", which
 * is not an answer — the Home screen shipped with somebody else's name on it
 * and invented statistics, and nothing in the repository would have told you.
 *
 * So every screen is listed below with a status, and the first test fails if a
 * screen exists that is not listed. A new screen cannot be added without
 * declaring which it is, the same way a new route cannot be added without
 * declaring who may reach it (see the authorisation sweep on the API side).
 *
 * `docs/PLACEHOLDERS.md` is the human-readable version of this list. The list
 * here is the one that bites.
 *
 *   live        — everything it shows comes from the API or the device.
 *   placeholder — the feature does not exist yet. It must say so, must show
 *                 no invented data, and must offer no button that does
 *                 nothing.
 *   shell       — layout and navigation only; renders no content of its own.
 */
import fs from 'node:fs';
import path from 'node:path';

type Status = 'live' | 'placeholder' | 'shell';

const SCREENS: Record<string, { status: Status; note: string }> = {
  '_layout.tsx': { status: 'shell', note: 'fonts, providers, auth gate' },
  'welcome.tsx': { status: 'live', note: 'static copy and two real navigations' },
  'sign-up.tsx': { status: 'live', note: 'POST /api/auth/register' },
  'sign-in.tsx': { status: 'live', note: 'POST /api/auth/login' },
  'onboarding.tsx': { status: 'live', note: 'PATCHes each answer to /api/me' },
  'invite.tsx': { status: 'live', note: 'creates, shares and nudges a real invite' },
  'invite/[code].tsx': { status: 'live', note: 'previews and accepts a real code' },
  '(tabs)/_layout.tsx': { status: 'shell', note: 'five tabs, icons, quick-add sheet' },
  '(tabs)/index.tsx': { status: 'live', note: 'everything from GET /api/me' },
  '(tabs)/dates.tsx': { status: 'placeholder', note: 'Phase 3' },
  '(tabs)/moments.tsx': { status: 'placeholder', note: 'Phase 4' },
  '(tabs)/planner.tsx': { status: 'placeholder', note: 'Phase 5' },
  '(tabs)/us.tsx': {
    status: 'live',
    note: 'couple summary and sign-out from /api/me; settings are Phase 5',
  },
};

/**
 * A screen's source with its comments removed.
 *
 * The literal checks below are about what a screen *renders*, and a comment
 * explaining why a name was removed is not a hardcoded name. Line comments are
 * only stripped when the `//` follows whitespace or starts the line, so a URL
 * (`https://...`) does not swallow the rest of its line.
 *
 * It is a backstop, not a proof: a determined hardcoded string can still get
 * through. It catches the mistake that actually happened.
 */
function codeOf(file: string): string {
  return fs
    .readFileSync(path.join(__dirname, '..', file), 'utf8')
    .replace(/\/\*[\s\S]*?\*\//g, ' ')
    .replace(/(^|\s)\/\/.*$/gm, '$1');
}

/** Every screen file under src/app, as a path relative to it. */
function screenFiles(dir = path.join(__dirname, '..'), prefix = ''): string[] {
  return fs
    .readdirSync(dir, { withFileTypes: true })
    .flatMap((entry) => {
      const rel = prefix === '' ? entry.name : `${prefix}/${entry.name}`;

      if (entry.isDirectory()) {
        // The tests themselves are not screens.
        return entry.name === '__tests__' ? [] : screenFiles(path.join(dir, entry.name), rel);
      }

      return entry.name.endsWith('.tsx') ? [rel] : [];
    })
    .sort();
}

describe('the screen inventory', () => {
  it('has a status recorded for every screen', () => {
    // The gate. A new screen with no entry fails here, which is the whole
    // point: nobody should have to read the source to find out whether what
    // they are looking at is real.
    const undeclared = screenFiles().filter((file) => !(file in SCREENS));

    expect(undeclared).toEqual([]);
  });

  it('does not list a screen that has been deleted', () => {
    // The other direction: a stale entry would claim something exists.
    const files = screenFiles();
    const stale = Object.keys(SCREENS).filter((declared) => !files.includes(declared));

    expect(stale).toEqual([]);
  });

  it('keeps every placeholder honest', () => {
    // A placeholder must render `PlaceholderScreen`, which by construction has
    // no action button and states the phase. That is what stops a placeholder
    // quietly growing realistic-looking content — which is exactly what
    // happened to Home.
    for (const [file, entry] of Object.entries(SCREENS)) {
      if (entry.status !== 'placeholder') continue;

      const source = codeOf(file);

      expect(source).toContain('PlaceholderScreen');
      expect(entry.note).toMatch(/Phase \d/);
    }
  });

  it('allows no screen to wire a button to nothing', () => {
    // `onPress={() => undefined}` is how four tabs ended up with buttons that
    // did nothing when tapped. If a control cannot act yet, it should not be
    // on the screen.
    for (const file of screenFiles()) {
      const source = codeOf(file);

      expect(source).not.toContain('onPress={() => undefined}');
      expect(source).not.toContain('onAction={() => undefined}');
    }
  });

  it('names the people on screen from data, never from a literal', () => {
    // The Home regression in one assertion. These are the seeded and sample
    // names that were hardcoded; none of them belongs in a screen.
    const forbidden = ['Alex Watson', 'Sam Hart', '& Sam', 'Oklava', 'Rimma'];

    for (const file of screenFiles()) {
      const source = codeOf(file);

      for (const literal of forbidden) {
        expect(source).not.toContain(literal);
      }
    }
  });
});
