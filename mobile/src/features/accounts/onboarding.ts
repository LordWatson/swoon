/**
 * The onboarding flow: its steps, and the draft that survives a backgrounded
 * app.
 *
 * Kept as data and pure functions, so "one question per screen" and "back
 * never loses an answer" are properties that can be asserted without a
 * navigator.
 */

export type OnboardingStep = 'name' | 'timezone' | 'dietary' | 'accessibility';

export type OnboardingDraft = {
  name?: string;
  timezone?: string;
  dietary_needs?: string[];
  accessibility_needs?: string[];
};

/**
 * In order. **One question per screen** (UX rule 3): the list is the order and
 * nothing re-derives it.
 *
 * `name` and `timezone` are confirmations rather than questions — both are
 * already known from sign-up and the device — but they get their own screen
 * each, because a screen that asks "is this right?" about two things at once
 * is two questions.
 */
export const ONBOARDING_STEPS: readonly OnboardingStep[] = [
  'name',
  'timezone',
  'dietary',
  'accessibility',
] as const;

/** Which steps a person may pass over. */
export const SKIPPABLE: readonly OnboardingStep[] = ['dietary', 'accessibility'] as const;

export function isSkippable(step: OnboardingStep): boolean {
  return SKIPPABLE.includes(step);
}

export function stepNumber(step: OnboardingStep): number {
  return ONBOARDING_STEPS.indexOf(step) + 1;
}

export function nextStep(step: OnboardingStep): OnboardingStep | null {
  return ONBOARDING_STEPS[ONBOARDING_STEPS.indexOf(step) + 1] ?? null;
}

export function previousStep(step: OnboardingStep): OnboardingStep | null {
  const index = ONBOARDING_STEPS.indexOf(step);

  return index <= 0 ? null : (ONBOARDING_STEPS[index - 1] ?? null);
}

/**
 * The chip choices. Each list ends with a way to say "nothing", because every
 * non-essential question needs one (UX rule 3) — and "no preference" is a real
 * answer, not a skipped question.
 */
export const DIETARY_CHOICES = [
  'Vegetarian',
  'Vegan',
  'Pescatarian',
  'Gluten-free',
  'Dairy-free',
  'Nut allergy',
  'Halal',
  'Kosher',
  'No shellfish',
] as const;

export const ACCESSIBILITY_CHOICES = [
  'Step-free access',
  'Quiet spaces',
  'Good lighting',
  'Seating nearby',
  'Space for a wheelchair',
  'Subtitles or captions',
] as const;

/** Toggle a chip, immutably. */
export function toggle(list: string[] | undefined, value: string): string[] {
  const current = list ?? [];

  return current.includes(value) ? current.filter((item) => item !== value) : [...current, value];
}
