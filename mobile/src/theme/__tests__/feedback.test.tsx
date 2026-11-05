/**
 * How a press feels, asserted at the hook rather than through a component.
 *
 * `usePressFeedback` is the single place the whole app decides what a press
 * does, so this is where it is worth testing: that a haptic actually fires,
 * that "reduce motion" swaps the spring for a short timing curve rather than
 * removing the response altogether, and that a phone which cannot buzz never
 * breaks a button.
 */
import { render } from '@testing-library/react-native';
import * as Haptics from 'expo-haptics';
import { Animated, Pressable } from 'react-native';

import { lightTheme, ThemeProvider, usePressFeedback } from '../index';
import { durations, pressScale } from '../motion';

jest.mock('expo-haptics', () => ({
  impactAsync: jest.fn(() => Promise.resolve()),
  notificationAsync: jest.fn(() => Promise.resolve()),
  ImpactFeedbackStyle: { Light: 'light', Medium: 'medium' },
  NotificationFeedbackType: { Success: 'success' },
}));

type Captured = ReturnType<typeof usePressFeedback>;

/** Renders the hook and hands back what it returned. */
async function captureHook(reduceMotion: boolean): Promise<Captured> {
  let captured: Captured | undefined;

  function Probe() {
    captured = usePressFeedback('button', 'light');
    return <Pressable testID="probe" />;
  }

  // Only the flag matters here, not the colour scheme.
  const theme = { ...lightTheme, reduceMotion };

  await render(
    <ThemeProvider value={theme}>
      <Probe />
    </ThemeProvider>,
  );

  if (!captured) throw new Error('the hook did not run');

  return captured;
}

afterEach(() => {
  jest.clearAllMocks();
});

it('fires a light haptic the moment a press starts', async () => {
  const feedback = await captureHook(false);

  feedback.onPressIn();

  expect(Haptics.impactAsync).toHaveBeenCalledWith(Haptics.ImpactFeedbackStyle.Light);
});

it('springs on press and springs back on release', async () => {
  const spring = jest.spyOn(Animated, 'spring');
  const feedback = await captureHook(false);

  feedback.onPressIn();
  expect(spring.mock.calls[0]?.[1]).toMatchObject({ toValue: pressScale.button });

  feedback.onPressOut();
  expect(spring.mock.calls[1]?.[1]).toMatchObject({ toValue: 1 });

  spring.mockRestore();
});

it('still responds under reduce motion, with a short curve instead of a spring', async () => {
  // The control must not stop responding — it just stops bouncing. Removing
  // the feedback entirely would be a worse outcome than the animation.
  const spring = jest.spyOn(Animated, 'spring');
  const timing = jest.spyOn(Animated, 'timing');

  const feedback = await captureHook(true);
  feedback.onPressIn();

  expect(spring).not.toHaveBeenCalled();
  expect(timing).toHaveBeenCalledWith(
    expect.anything(),
    expect.objectContaining({ toValue: pressScale.button, duration: durations.instant }),
  );

  spring.mockRestore();
  timing.mockRestore();
});

it('does not break a button on a device that cannot buzz', async () => {
  // Haptics reject rather than no-op on a simulator, on some Android hardware
  // and under Low Power Mode. A press must not throw because of it.
  jest.mocked(Haptics.impactAsync).mockRejectedValueOnce(new Error('no haptics here'));

  const feedback = await captureHook(false);

  expect(() => feedback.onPressIn()).not.toThrow();
});
