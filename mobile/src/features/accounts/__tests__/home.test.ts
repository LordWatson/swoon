/**
 * What Home says about you.
 *
 * The regression: Home was hardcoded to "Alex & Sam" with 214 days together,
 * as a showcase for the design language. It was harmless while nobody could
 * sign in; the moment sign-in worked, a real person was shown somebody else's
 * name and invented numbers as if they were theirs.
 *
 * These are the functions that replaced it, and the off-by-one case below is
 * the one a screen test would never have caught.
 */
import type { CoupleSummary } from '../api';
import { daysTogether, firstName, formatCount, greeting } from '../home';
import { partnerColourToken } from '../../../theme';

function couple(overrides: Partial<CoupleSummary> = {}): CoupleSummary {
  return {
    id: '01HQ000000000000000000',
    linked_at: '2026-01-01T00:00:00+00:00',
    lock_in_mode: 'both_heart',
    anniversary_on: null,
    first_date_on: null,
    partner: null,
    ...overrides,
  };
}

describe('names', () => {
  it('uses the first name only', () => {
    expect(firstName('Alex Watson')).toBe('Alex');
    expect(firstName('Rimma')).toBe('Rimma');
  });

  it('copes with the untidy things people type', () => {
    expect(firstName('  Alex   Watson  ')).toBe('Alex');
  });

  it('never renders an empty greeting', () => {
    // A social account can arrive with a placeholder name, and the screen
    // renders before `/api/me` answers.
    expect(firstName(null)).toBe('you');
    expect(firstName(undefined)).toBe('you');
    expect(firstName('   ')).toBe('you');
  });
});

describe('the greeting', () => {
  it('follows the clock', () => {
    expect(greeting(new Date('2026-11-05T08:00:00'))).toBe('Good morning,');
    expect(greeting(new Date('2026-11-05T13:00:00'))).toBe('Good afternoon,');
    expect(greeting(new Date('2026-11-05T20:00:00'))).toBe('Good evening,');
  });

  it('has no gaps at the boundaries', () => {
    expect(greeting(new Date('2026-11-05T00:00:00'))).toBe('Good morning,');
    expect(greeting(new Date('2026-11-05T11:59:00'))).toBe('Good morning,');
    expect(greeting(new Date('2026-11-05T12:00:00'))).toBe('Good afternoon,');
    expect(greeting(new Date('2026-11-05T17:59:00'))).toBe('Good afternoon,');
    expect(greeting(new Date('2026-11-05T18:00:00'))).toBe('Good evening,');
    expect(greeting(new Date('2026-11-05T23:59:00'))).toBe('Good evening,');
  });
});

describe('days together', () => {
  it('counts from the first date when there is one', () => {
    const value = daysTogether(
      couple({ first_date_on: '2026-01-01', anniversary_on: '2026-06-01' }),
      new Date('2026-01-11T12:00:00Z'),
    );

    expect(value).toBe(10);
  });

  it('falls back to the anniversary, then to the day they linked', () => {
    expect(
      daysTogether(couple({ anniversary_on: '2026-01-01' }), new Date('2026-01-03T12:00:00Z')),
    ).toBe(2);

    expect(
      daysTogether(
        couple({ linked_at: '2026-01-01T09:00:00+00:00' }),
        new Date('2026-01-06T12:00:00Z'),
      ),
    ).toBe(5);
  });

  it('does not drift by a day for someone west of Greenwich', () => {
    // The bug this guards: a `YYYY-MM-DD` date parses as UTC midnight, and
    // comparing it against a LOCAL clock shifts the answer by one for anyone
    // behind UTC. Both sides are normalised to UTC midnight, so late evening
    // in New York still reads the same as the morning did.
    const start = couple({ first_date_on: '2026-01-01' });

    expect(daysTogether(start, new Date('2026-01-11T00:30:00Z'))).toBe(10);
    expect(daysTogether(start, new Date('2026-01-11T23:30:00Z'))).toBe(10);
  });

  it('is zero on the first day rather than blank', () => {
    expect(
      daysTogether(couple({ first_date_on: '2026-01-01' }), new Date('2026-01-01T15:00:00Z')),
    ).toBe(0);
  });

  it('never goes negative when somebody mistypes a future date', () => {
    expect(
      daysTogether(couple({ first_date_on: '2027-01-01' }), new Date('2026-01-01T12:00:00Z')),
    ).toBe(0);
  });

  it('is null with no couple, so the stat is hidden rather than zeroed', () => {
    expect(daysTogether(null)).toBeNull();
  });

  it('groups a big number the way a British reader expects', () => {
    expect(formatCount(1204)).toBe('1,204');
    expect(formatCount(7)).toBe('7');
  });
});

describe('partner colours', () => {
  it('maps the API names to theme tokens', () => {
    // The server says `rose`; the theme knows it as `primary`. One mapping,
    // so two screens cannot disagree about which colour somebody is.
    expect(partnerColourToken('rose')).toBe('primary');
    expect(partnerColourToken('lavender')).toBe('ai');
    expect(partnerColourToken('apricot')).toBe('secondary');
    expect(partnerColourToken('sage')).toBe('success');
  });

  it('falls back rather than blanking an avatar', () => {
    // Null is a real state — nobody has chosen yet — and an unknown value
    // means the API moved ahead of the app. Neither should erase a face.
    expect(partnerColourToken(null)).toBe('primary');
    expect(partnerColourToken(undefined, 'ai')).toBe('ai');
    expect(partnerColourToken('chartreuse', 'ai')).toBe('ai');
  });
});
