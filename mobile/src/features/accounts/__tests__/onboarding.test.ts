/**
 * The onboarding flow's rules, as properties of the step model.
 *
 * Tested here rather than by driving the screen, because the rules are what
 * matter and they are stated once: **one question per screen**, **back never
 * loses an answer**, and **every non-essential question has a way to say
 * nothing** (UX rule 3). A screen test would assert the rendering; these
 * assert the rule.
 */
import {
  ACCESSIBILITY_CHOICES,
  DIETARY_CHOICES,
  isSkippable,
  nextStep,
  ONBOARDING_STEPS,
  previousStep,
  SKIPPABLE,
  stepNumber,
  toggle,
  type OnboardingStep,
} from '../onboarding';

describe('the step order', () => {
  it('runs forwards from the first to the last and then stops', () => {
    let step: OnboardingStep | null = ONBOARDING_STEPS[0];
    const walked: OnboardingStep[] = [];

    while (step !== null) {
      walked.push(step);
      step = nextStep(step);
    }

    expect(walked).toEqual([...ONBOARDING_STEPS]);
  });

  it('runs backwards to the first step and then stops', () => {
    // "Back" from the first screen must not be a step: there is nowhere to go,
    // and the button is hidden rather than dead.
    expect(previousStep(ONBOARDING_STEPS[0])).toBeNull();
    expect(previousStep('timezone')).toBe('name');
    expect(previousStep('accessibility')).toBe('dietary');
  });

  it('numbers the steps from one, for the progress bar', () => {
    // UX rule 3: position, not a percentage — "question 2 of 4" is what a
    // person wants to know.
    expect(stepNumber(ONBOARDING_STEPS[0])).toBe(1);
    expect(stepNumber(ONBOARDING_STEPS[ONBOARDING_STEPS.length - 1])).toBe(ONBOARDING_STEPS.length);
  });

  it('asks four things, each on its own screen', () => {
    // If this number changes, the change was deliberate: the list IS the
    // order, and nothing else derives it.
    expect(ONBOARDING_STEPS).toHaveLength(4);
    expect(new Set(ONBOARDING_STEPS).size).toBe(ONBOARDING_STEPS.length);
  });
});

describe('what may be skipped', () => {
  it('lets the optional questions be passed over', () => {
    expect(isSkippable('dietary')).toBe(true);
    expect(isSkippable('accessibility')).toBe(true);
  });

  it('does not let the identifying ones be skipped', () => {
    // A nameless account and a wrong timezone both make the rest of the app
    // worse, so these two are confirmations rather than optional.
    expect(isSkippable('name')).toBe(false);
    expect(isSkippable('timezone')).toBe(false);
  });

  it('only marks steps that actually exist as skippable', () => {
    for (const step of SKIPPABLE) {
      expect(ONBOARDING_STEPS).toContain(step);
    }
  });
});

describe('answering', () => {
  it('adds and removes a chip without touching the others', () => {
    // This is what makes "back never loses an answer" true: the draft is only
    // ever replaced immutably, never mutated in place.
    const first = toggle(undefined, 'Vegan');
    const second = toggle(first, 'Gluten-free');
    const third = toggle(second, 'Vegan');

    expect(first).toEqual(['Vegan']);
    expect(second).toEqual(['Vegan', 'Gluten-free']);
    expect(third).toEqual(['Gluten-free']);
    // The earlier arrays are untouched — which is what lets a draft be
    // compared and restored rather than second-guessed.
    expect(first).toEqual(['Vegan']);
  });

  it('treats an empty list as a real answer, not a missing one', () => {
    // "No preference" is an answer. Storing it as `[]` rather than leaving the
    // field unset is what stops the app asking again next launch.
    expect(toggle(['Vegan'], 'Vegan')).toEqual([]);
  });

  it('offers choices for both of the optional questions', () => {
    expect(DIETARY_CHOICES.length).toBeGreaterThan(4);
    expect(ACCESSIBILITY_CHOICES.length).toBeGreaterThan(4);

    // No duplicates: a chip list with the same label twice is a bug you only
    // see on a device.
    expect(new Set(DIETARY_CHOICES).size).toBe(DIETARY_CHOICES.length);
    expect(new Set(ACCESSIBILITY_CHOICES).size).toBe(ACCESSIBILITY_CHOICES.length);
  });
});
